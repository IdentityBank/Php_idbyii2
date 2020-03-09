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

use idbyii2\models\db\BusinessAccount;
use idbyii2\models\db\BusinessAccountUser;
use idbyii2\models\db\BusinessDatabase;
use idbyii2\models\db\BusinessDatabaseUser;
use idbyii2\models\db\BusinessOrganization;
use idbyii2\models\db\BusinessUserData;
use idbyii2\models\db\SearchBusinessUserData;
use Yii;
use yii\helpers\ArrayHelper;

################################################################################
# Include(s)                                                                   #
################################################################################


################################################################################
# Class(es)                                                                    #
################################################################################

/**
 * Class AccessManager
 *
 * @package idbyii2\helpers
 */
class AccessManager
{

    /**
     * @param      $uid
     * @param null $oid
     * @param null $aid
     * @param null $dbid
     *
     * @return array
     */
    public static function resetUserDatabase($uid, $oid = null, $aid = null, $dbid = null)
    {
        $userDb = self::getDb($uid, $oid, $aid, $dbid);
        self::changeDatabase(
            $userDb['uid'] ?? null,
            $userDb['oid'] ?? null,
            $userDb['aid'] ?? null,
            $userDb['dbid'] ?? null
        );

        return $userDb;
    }

    /**
     * @param      $uid
     * @param null $oid
     * @param null $aid
     * @param null $dbid
     *
     * @return array
     */
    private static function getDb($uid, $oid = null, $aid = null, $dbid = null, $exclude = [])
    {
        $userDb = ['uid' => $uid];
        $userAccount = BusinessAccountUser::find()->where(['uid' => $uid]);
        if (!empty($aid)) {
            $userAccount->andWhere(['aid' => $aid]);
        }
        $userAccount = $userAccount->one();
        if ($userAccount) {
            $account = BusinessAccount::find()->where(['aid' => $userAccount->aid])->one();
            if ($account) {
                $userDb['aid'] = $account->aid;
                if (!empty($dbid)) {
                    $database = BusinessDatabase::find()->where(['aid' => $userAccount->aid, 'dbid' => $dbid])->one();
                    if (empty($database)) {
                        $dbid = null;
                    }
                }
                $accountOrganization = BusinessOrganization::find()->where(['oid' => $account->oid])->one();
                if ($accountOrganization) {
                    $userDb['oid'] = $account->oid;
                }
                $accountDatabases = BusinessDatabaseUser::find()->where(['uid' => $uid]);
                if (!empty($dbid)) {
                    $accountDatabases->andWhere(['dbid' => $dbid]);
                }
                $accountDatabases = $accountDatabases->all();
                foreach ($accountDatabases as $accountDatabase) {
                    if ($accountDatabase) {
                        $database = BusinessDatabase::find()->where(
                            ['aid' => $userAccount->aid, 'dbid' => $accountDatabase->dbid]
                        )->one();
                        if ($database) {
                            $userDb['dbid'] = $database->dbid;
                            break;
                        }
                    }
                }
                if (empty($userDb['dbid'])) {
                    $exclude = ArrayHelper::merge($exclude, [$userDb['aid']]);
                    $accounts = BusinessAccount::find()->where(['not in', 'aid', $exclude])->andWhere(
                        ['oid' => $account->oid]
                    )->all();
                    if (
                        !empty($accounts)
                        && is_array($accounts)
                        && (count($accounts) > 0)
                    ) {
                        $userDb = self::getDb($uid, $oid, $accounts[0], null, $exclude);
                    }
                }
            }
        }

        return $userDb;
    }

    /**
     * @param $uid
     * @param $oid
     * @param $aid
     * @param $dbid
     */
    public static function changeDatabase($uid, $oid, $aid, $dbid)
    {
        $userDataModel = BusinessUserData::instantiate();

        $business = BusinessUserData::findOne(
            ['uid' => $uid, 'key_hash' => $userDataModel->getKeyHash($uid, 'oid')]
        );
        if (empty($business)) {
            $business = BusinessUserData::instantiate(['uid' => $uid, 'key' => 'oid', 'value' => $oid]);
        }
        $business->value = $oid;
        if (!$business->save()) {
            Yii::error('Cannot save organization id!');
            Yii::error(json_encode($business->getErrors()));
        }

        $business = BusinessUserData::findOne(
            ['uid' => $uid, 'key_hash' => $userDataModel->getKeyHash($uid, 'aid')]
        );
        if (empty($business)) {
            $business = BusinessUserData::instantiate(['uid' => $uid, 'key' => 'aid', 'value' => $aid]);
        }
        $business->value = $aid;
        if (!$business->save()) {
            Yii::error('Cannot save account id!');
            Yii::error(json_encode($business->getErrors()));
        }

        $business = BusinessUserData::findOne(
            ['uid' => $uid, 'key_hash' => $userDataModel->getKeyHash($uid, 'dbid')]
        );
        if (empty($business)) {
            $business = BusinessUserData::instantiate(['uid' => $uid, 'key' => 'dbid', 'value' => $dbid]);
        }
        $business->value = $dbid;
        if (!$business->save()) {
            Yii::error('Cannot save vault id!');
            Yii::error(json_encode($business->getErrors()));
        }
    }

    /**
     * @param $uid
     * @param $oid
     * @param $aid
     * @param $dbid
     *
     * @return bool
     */
    public static function isValidDatabase($uid, $oid, $aid, $dbid)
    {
        if (
            empty($uid)
            || empty($oid)
            || empty($aid)
            || empty($dbid)
        ) {
            return false;
        }
        $userDb = self::getDb($uid, $oid, $aid, $dbid);

        return ((($userDb['uid'] ?? null) === $uid)
            && (($userDb['oid'] ?? null) === $oid)
            && (($userDb['aid'] ?? null) === $aid)
            && (($userDb['dbid'] ?? null) === $dbid));
    }

    /**
     * @param $email
     * @param $phone
     *
     * @return bool
     */
    public static function checkIfUserExists($email, $phone)
    {

        $users = BusinessAccountUser::find()->select('uid')
                                    ->where(['aid' => Yii::$app->user->identity->aid])->asArray()->all();

        $businesses = SearchBusinessUserData::findAllByKeys(
            ['email', 'mobile'],
            ArrayHelper::getColumn($users, 'uid')
        );

        $emailExists = false;
        /** @var BusinessUserData $business */
        foreach ($businesses['email'] as $business) {
            if ($business->getValue() === $email) {
                $emailExists = true;
                break;
            }
        }

        foreach ($businesses['mobile'] as $business) {
            if ($business->getValue() === $phone) {
                if ($emailExists) {
                    return true;
                } else {
                    break;
                }
            }
        }

        return false;
    }
}

################################################################################
#                                End of file                                   #
################################################################################
