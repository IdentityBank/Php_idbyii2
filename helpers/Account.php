<?php
# * ********************************************************************* *
# *                                                                       *
# *   Yii2 Models and Modules                                             *
# *   This file is part of idbyii2. This project may be found at:         *
# *   https://github.com/IdentityBank/Php_idbyii2.                        *
# *                                                                       *
# *   Copyright (C) 2020 by Identity Bank. All Rights Reserved.           *
# *   https://www.identitybank.eu - You belong to you                     *
# *                                                                       *
# *   This program is free software: you can redistribute it and/or       *
# *   modify it under the terms of the GNU Affero General Public          *
# *   License as published by the Free Software Foundation, either        *
# *   version 3 of the License, or (at your option) any later version.    *
# *                                                                       *
# *   This program is distributed in the hope that it will be useful,     *
# *   but WITHOUT ANY WARRANTY; without even the implied warranty of      *
# *   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the        *
# *   GNU Affero General Public License for more details.                 *
# *                                                                       *
# *   You should have received a copy of the GNU Affero General Public    *
# *   License along with this program. If not, see                        *
# *   https://www.gnu.org/licenses/.                                      *
# *                                                                       *
# * ********************************************************************* *

################################################################################
# Namespace                                                                    #
################################################################################

namespace idbyii2\helpers;

################################################################################
# Use(s)                                                                       #
################################################################################

use DateTime;
use Exception;
use idbyii2\components\PortalApi;
use idbyii2\models\db\AuthAssignment;
use idbyii2\models\db\BusinessAccount;
use idbyii2\models\db\BusinessAccountUser;
use idbyii2\models\db\BusinessDatabase;
use idbyii2\models\db\BusinessDatabaseUser;
use idbyii2\models\db\BusinessDeleteAccount as DeleteAccountModel;
use idbyii2\models\db\BusinessImport;
use idbyii2\models\db\BusinessImportWorksheet;
use idbyii2\models\db\BusinessOrganization;
use idbyii2\models\db\BusinessUserAccount;
use idbyii2\models\db\BusinessUserData;
use idbyii2\models\db\PeopleDeleteRequest;
use idbyii2\models\db\PeopleDisconnectRequest;
use idbyii2\models\db\PeopleNotification;
use idbyii2\models\db\PeopleUserAccount;
use idbyii2\models\db\PeopleUserData;
use idbyii2\models\db\UserAccount;
use idbyii2\models\db\UserData;
use idbyii2\models\idb\IdbBankClientBusiness;
use idbyii2\models\idb\IdbBankClientPeople;
use ReflectionClass;
use Throwable;
use xmz\simplelog\SNLog as Log;
use Yii;
use yii\db\StaleObjectException;
use yii\helpers\ArrayHelper;
use function xmz\simplelog\registerLogger;

################################################################################
# Class(es)                                                                    #
################################################################################

/**
 * Class Account
 *
 * @package idbyii2\helpers
 */
class Account
{

    const TEST_DELAY = 1;
    const TEST_MULTIPLIER = 5;
    const TIME_TO_DELETE_BUSINESS_ACCOUNT = 20;
    const PAGE_SIZE = 20;
    const TRY_LIMIT = 5;

    /**
     * @throws Exception
     * @throws Throwable
     */
    public static function disconnectOutdated()
    {
        $portalBusinessApi = PortalApi::getBusinessApi();

        /** @var PeopleDisconnectRequest $outdated */
        foreach(PeopleDisconnectRequest::findOutdated() as $outdated)
        {
            $data = json_decode($outdated->data, true);
            $portalBusinessApi->requestDeleteBusinessDataForUser($data);
            $idbClient = IdbBankClientPeople::model($data['forDelete']['accountId']);

            $idbClient->deleteRelationBusiness2People($data['forDelete']['businessId'], $data['forDelete']['peopleDbUserId']);

            $outdated->delete();
        }
    }

    /**
     * @throws Throwable
     * @throws StaleObjectException
     */
    public static function deleteOutdatedPeople()
    {
        $outdated = PeopleDeleteRequest::findOutdated();

        /** @var PeopleDeleteRequest $deleteRequest */
        foreach ($outdated as $deleteRequest) {
            self::deletePeopleAccount($deleteRequest->peopleId, $deleteRequest->timestamp);
            $deleteRequest->delete();
        }
    }

    /**
     * @param      $peopleId
     * @param null $startDate
     */
    public static function deletePeopleAccount($peopleId, $startDate = null)
    {
        try {
            $parsedId = IdbAccountId::parse($peopleId);
            self::deleteBusinessRelationForPeople($peopleId);
            PeopleNotification::deleteAll(['uid' => $parsedId['pid']]);
            PeopleUserAccount::deleteByUid($parsedId['pid']);
            PeopleUserData::deleteAll(['uid' => $parsedId['pid']]);
            $process = (empty($startDate) ? 'WITHOUT PROCESS' : "PROCESS START ON: $startDate");
            self::logDeleteAccountLog("DELETED PEOPLE ACCOUNT FOR: $peopleId $process");
        } catch (Exception $e) {
            self::logDeleteAccountErrors("DELETE PEOPLE ACCOUNT ERROR FOR: $peopleId");
            Yii::error('DELETE PEOPLE ACCOUNT ERROR');
            Yii::error($e->getMessage());
        } catch (Throwable $e) {
            self::logDeleteAccountErrors("DELETE PEOPLE ACCOUNT ERROR FOR: $peopleId");
            Yii::error('DELETE PEOPLE ACCOUNT ERROR');
            Yii::error($e->getMessage());
        }
    }

    /**
     * @param $peopleId
     *
     * @return bool
     */
    public static function deleteBusinessRelationForPeople($peopleId)
    {
        $parsedId = IdbAccountId::parse($peopleId);
        $model = IdbBankClientPeople::model($parsedId['idbid']);
        $relations = $model->getRelatedBusinesses($peopleId);

        for ($i = 0; $i < self::TEST_MULTIPLIER; $i++) {
            if (!empty($relations)) {
                foreach (ArrayHelper::getValue($relations, 'QueryData.0', []) as $businessId) {
                    $model->deleteRelationBusiness2People($businessId, $peopleId);
                }
            }
            $relations = $model->getRelatedBusinesses($peopleId);

            if (
                !empty($relations)
                && empty(ArrayHelper::getValue($relations, 'QueryData', []))
            ) {
                return true;
            }

            sleep(self::TEST_DELAY);
        }

        self::logDeleteAccountErrors("DELETE RELATIONS ERROR FOR: $peopleId");

        return false;
    }

    /**
     * @param $message
     */
    private static function logDeleteAccountErrors($message, $portal = 'people')
    {
        $logName = "p57b.$portal.idb_delete_account_errors";
        $logPath = "/var/log/p57b/$logName.log";
        registerLogger($logName, $logPath);
        $pid = getmypid();
        Log::debug(
            $logName,
            "$pid - " .
            $message
        );
    }

    /**
     * @param $message
     * @param string $portal
     */
    private static function logDeleteAccountLog($message, $portal = 'people')
    {
        $logName = "p57b.$portal.idb_delete_account_log";
        $logPath = "/var/log/p57b/$logName.log";
        registerLogger($logName, $logPath);
        $pid = getmypid();
        Log::debug(
            $logName,
            "$pid - " .
            $message
        );
    }

    /**
     * @param $uid
     * @return bool|string
     */
    public static function checkIfDeleteOrganization($uid)
    {
        /** @var BusinessAccountUser $accountUser */
        $accountUser = BusinessAccountUser::find()->where(compact('uid'))->one();
        /** @var BusinessAccount $account */
        $account = BusinessAccount::find()->where(['aid' => $accountUser->aid])->one();

        if(AuthAssignment::find()->where(['user_id' => $uid, 'item_name' => 'organization_admin'])) {
            return $account->oid;
        }

        $accounts = BusinessAccount::find()->where(['uid' => $account->uid])->all();

        /** @var BusinessAccount $account */
        foreach($accounts as $account) {
            if(BusinessAccountUser::find()->where(['aid' => $account->aid])->andWhere(['<>', 'uid' => $uid])) {
                return false;
            }
        }

        return $account->oid;
    }

    /**
     * @throws Exception
     */
    public static function deleteBusinessAccounts()
    {
        try {
            $date = (new DateTime())->modify("-" . self::TIME_TO_DELETE_BUSINESS_ACCOUNT . " days");

            for ($i = 0; $i > -1; $i++) {
                $models = DeleteAccountModel::find()->offset($i * self::PAGE_SIZE)->limit(self::PAGE_SIZE)->where(['<=', 'created_at', Localization::getDatabaseDateTime($date)])->all();

                /** @var DeleteAccountModel $model */
                foreach ($models as $model) {
                    $oid = self::checkIfDeleteOrganization($model->uid);

                    if(!$oid) {
                        self::deleteBusinessAccount($model->uid, $model->created_at);
                    } else {
                        self::deleteOrganizationAccount($oid, $model->created_at);
                    }

                    self::deleteAndCheck(DeleteAccountModel::class, ['id' => $model->id]);
                }

                if (empty($models) || count($models) < self::PAGE_SIZE) {
                    break;
                }
            }
        } catch (Exception $e) {
            self::logDeleteAccountErrors("DELETE OUTDATED ORGANIZATIONS ERROR", 'business');
            self::logDeleteAccountErrors($e->getMessage(), 'business');
        }
    }

    /**
     * @param string $oid
     * @param string $startDate
     */
    public static function deleteOrganizationAccount(string $oid, string $startDate = null)
    {
        try {
            self::deletePeopleRelations($oid);
            self::clearLocalStorageBusiness($oid);
            $process = (empty($startDate) ? 'WITHOUT PROCESS' : "PROCESS START ON: $startDate");
            self::logDeleteAccountLog("DELETED ORGANIZATION ACCOUNT FOR: $oid $process", 'business');
        } catch (Exception $e) {
            self::logDeleteAccountErrors("DELETE ORGANIZATION $oid ERROR", 'business');
            self::logDeleteAccountErrors($e->getMessage(), 'business');
        }
    }

    public static function deleteBusinessAccount(string $uid, $startDate = null)
    {
        try {
            self::clearLocalStorageBusinessAccount($uid);
            $process = (empty($startDate) ? 'WITHOUT PROCESS' : "PROCESS START ON: $startDate");
            self::logDeleteAccountLog("DELETED ORGANIZATION ACCOUNT FOR: $uid $process", 'business');
        } catch (Exception $e) {
            self::logDeleteAccountErrors("DELETE business account $uid ERROR", 'business');
            self::logDeleteAccountErrors($e->getMessage(), 'business');
        }
    }


    /**
     * @param string $oid
     * @throws Exception
     */
    public static function deletePeopleRelations(string $oid)
    {
        $clientModel = IdbBankClientBusiness::model('relation');

        $aid = BusinessAccount::find()->where(compact('oid'))->asArray()->select('aid')->one()['aid'];

        $databases = BusinessDatabase::find()->where(compact('aid'))->all();

        /** @var BusinessDatabase $database */
        foreach ($databases as $database) {
            $businessDbId = IdbAccountId::generateBusinessDbId(
                $oid,
                $aid,
                $database->dbid
            );

            $clientModel->deleteRelationsForBusiness($businessDbId);
        }
    }


    /**
     * @param string $oid
     * @throws Exception
     */
    public static function clearLocalStorageBusiness(string $oid)
    {
        for ($i = 0; $i > -1; $i++) {
            $accounts = BusinessAccount::find()->where(compact('oid'))->offset($i * self::PAGE_SIZE)->limit(self::PAGE_SIZE)->all();
            /** @var BusinessAccount $account */
            foreach ($accounts as $account) {
                $aid = $account->aid;
                /** @var BusinessAccountUser $accountUser */
                $accountUser = BusinessAccountUser::find()->where(['aid' => $aid])->one();
                $uid = $accountUser->uid;

                self::clearLocalStorageBusinessAccount($uid);
            }

            if (empty($accounts) || count($accounts) < self::PAGE_SIZE) {
                break;
            }
        }

        self::deleteAndCheck(BusinessOrganization::class, compact('oid'));
        self::deleteAndCheck(BusinessAccount::class, compact('oid'));
        self::deleteAndCheck(BusinessImport::class, compact('oid'));
        self::deleteAndCheck(BusinessImportWorksheet::class, compact('oid'));
    }

    /**
     * @param string $uid
     * @throws Exception
     */
    public static function clearLocalStorageBusinessAccount(string $uid)
    {
        $aid = BusinessAccountUser::find()->asArray()->select('aid')->where(compact($uid))->one();
        $aid = $aid['aid'];

        self::deleteAndCheck(BusinessUserAccount::class, compact('uid'));
        self::deleteAndCheck(BusinessUserData::class, compact('uid'));
        self::deleteAndCheck(AuthAssignment::class, compact('uid'));
        self::deleteAndCheck(BusinessDatabaseUser::class, compact('uid'));
        self::deleteAndCheck(BusinessAccountUser::class, compact('uid'));

        if(!BusinessAccountUser::find()->where(compact('aid'))->exists()) {
            self::deleteAndCheck(BusinessDatabase::class, compact('aid'));
        }
    }

    /**
     * @param string $model
     * @param array $where
     * @return bool
     * @throws Exception
     */
    public static function deleteAndCheck(string $model, array $where)
    {
        try {
            $instance = new ReflectionClass($model);
            $instance = $instance->newInstanceWithoutConstructor();
            for ($i = 0; $i > -1; $i++) {
                $instance->deleteAll($where);
                if (empty($instance->find()->where($where)->all())) {
                    return true;
                } else if ($i >= self::TRY_LIMIT) {
                    break;
                }

                sleep($i * self::TEST_DELAY);
            }


        } catch (Exception $e) {
            Yii::error('DELETE MODEL FROM DB ERROR');
            Yii::error($e->getMessage());
        }

        throw new Exception("Cant delete $model where " . json_encode($where));
    }
}

################################################################################
#                                End of file                                   #
################################################################################
