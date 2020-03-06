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
use idbyii2\components\IdbRabbitMq;
use idbyii2\models\db\BusinessCreditsLog;
use idbyii2\models\idb\BusinessIdbBillingClient;
use idbyii2\services\Payment;
use Yii;
use yii\helpers\ArrayHelper;

################################################################################
# Class(es)                                                                    #
################################################################################

/**
 * Class Credits
 *
 * @package idbyii2\helpers
 */
class Credits
{

    const PACKAGE_ID_INDEX = 0;
    const COST_INDEX = '1';
    const COST_TYPE_INDEX = '2';
    const COST_ID_INDEX = '0.1';
    const COST_ACTION_NAME_INDEX = '3';
    const BUSINESS_PACKAGE_ID_INDEX = '0.0';
    const BUSINESS_PACKAGE_CREDITS_INDEX = '0.4';
    const BUSINESS_PACKAGE_BASE_CREDITS_INDEX = '0.5';
    const BUSINESS_PACKAGE_ADDITIONAL_CREDITS_INDEX = '0.6';
    const BUSINESS_PACKAGE_ACCOUNT_TYPE_INDEX = '0.12';
    const channelName = "creditsIDB";

    /**
     * @param $data
     *
     * @throws \Exception
     */
    public static function executeTakeCredits($data)
    {
        self::addTaskToQueue(compact('data'));
    }

    /**
     * @param array $data
     *
     * @throws \Exception
     */
    private static function addTaskToQueue($data)
    {
        if (!empty($data)) {
            $data = json_encode($data);
            $idbRabbitMq = IdbRabbitMq::get();
            $idbRabbitMq->produce(self::channelName, $data);
        } else {
            $msg = 'Credits IDB - queue data cannot be empty!';
            Yii::error($msg);
            echo $msg . PHP_EOL;
            throw new Exception($msg);
        }
    }

    /**
     * @param $data
     */
    public static function executeTaskFromTakeCreditsQueue($data)
    {
        try {
            $data = json_decode($data, true)['data'];
            $data = json_decode(base64_decode($data), true);

            $oid = null;
            if (!empty($data['oid'])) {
                $oid = $data['oid'];
            } elseif (!empty($data['businessDbId'])) {
                $parsedId = IdbAccountId::parse($data['businessDbId']);
                $oid = $parsedId['oid'];
            } elseif (!empty($data['account'])) {
                $parsedId = IdbAccountId::parse(ArrayHelper::getValue($data, 'account', ''));
                $oid = ArrayHelper::getValue($parsedId, 'oid', null);
            } elseif (ArrayHelper::getValue($data, 'data.oid', false)) {
                $oid = ArrayHelper::getValue($data, 'data.oid', false);
            }

            $cost = self::getCostByAction($data['query']);

            if (
                $oid === null
                || empty($cost)
            ) {
                return true;
            }

            self::takeCredits($oid, $data['query'], $cost);

        } catch (Exception $e) {
            Yii::error('IDB TAKE COST');
            Yii::error($e->getMessage());
            var_dump($e->getMessage());
        }
    }

    /**
     * @param $query
     *
     * @return int|mixed
     * @throws \yii\web\NotFoundHttpException
     */
    private static function getCostByAction($query)
    {
        $cost = 0;
        $model = BusinessIdbBillingClient::model();
        if (file_exists(TempFile::getTempFileName('costs', true))) {
            $costs = unserialize(TempFile::getTempFileContent('costs', true));
            $cost = ArrayHelper::getValue($costs, $query, 0);
        } else {
            $cost = $model->findCostByAction($query);
            $cost = ArrayHelper::getValue($cost, self::COST_ID_INDEX, 0);
            self::cacheCosts();
        }

        return $cost;
    }

    /**
     * @throws \yii\web\NotFoundHttpException
     */
    public static function cacheCosts()
    {
        $model = BusinessIdbBillingClient::model();

        $costs = $model->findAllCosts();
        $cache = [];
        foreach ($costs as $cost) {
            $cache[ArrayHelper::getValue($cost, self::COST_ACTION_NAME_INDEX, 0)] = [
                'cost' => ArrayHelper::getValue($cost, self::COST_INDEX, 0),
                'type' => ArrayHelper::getValue($cost, self::COST_TYPE_INDEX, 'other')
            ];
        }
        $costs = serialize($cache);

        TempFile::writeTempFile($costs, 'costs', true);
    }

    /**
     * @param      $oid
     * @param      $actionName
     * @param null $cost
     */
    public static function takeCredits($oid, $actionName, $cost = null)
    {
        try {
            if (empty($cost)) {
                $cost = self::getCostByAction($actionName);
            }

            $model = BusinessIdbBillingClient::model();

            $package = $model->getBusinessPackage($oid);
            if (
                ArrayHelper::getValue($package, self::BUSINESS_PACKAGE_ACCOUNT_TYPE_INDEX, 'amateur') === 'business'
                && $cost['cost'] > 0
            ) {
                $credits = ArrayHelper::getValue($package, self::BUSINESS_PACKAGE_CREDITS_INDEX, 0);
                $additionalCredits = ArrayHelper::getValue(
                    $package,
                    self::BUSINESS_PACKAGE_ADDITIONAL_CREDITS_INDEX,
                    0
                );
                $log = new BusinessCreditsLog();
                $log->oid = $oid;
                $log->action_name = $actionName;
                $log->action_type = $cost['type'];
                $log->cost = $cost['cost'];
                $log->credits_before = $credits;
                $log->additional_credits_before = $additionalCredits;
                $log->timestamp = Localization::getDatabaseDateTime(new DateTime());

                if ($credits >= $cost['cost']) {
                    $credits -= $cost['cost'];
                } elseif ($credits + $additionalCredits >= $cost['cost']) {
                    $cost['cost'] -= $credits;
                    $credits = 0;
                    $additionalCredits -= $cost['cost'];
                } else {
                    if (Payment::rechargeOrNotifyOrganization($oid, $package[self::PACKAGE_ID_INDEX])) {
                        $additionalCredits = ArrayHelper::getValue(
                                $package,
                                self::BUSINESS_PACKAGE_BASE_CREDITS_INDEX,
                                0
                            )
                            - $cost['cost'];
                    } else {
                        return;
                    }
                }

                $log->save();

                $model->updateBusinessPackage(
                    ArrayHelper::getValue($package, self::BUSINESS_PACKAGE_ID_INDEX),
                    [
                        'credits' => $credits,
                        'additional_credits' => $additionalCredits
                    ]
                );
            } elseif(ArrayHelper::getValue($package, self::BUSINESS_PACKAGE_ACCOUNT_TYPE_INDEX, 'amateur') === 'amateur') {
                $log = new BusinessCreditsLog();
                $log->oid = $oid;
                $log->action_name = $actionName;
                $log->action_type = $cost['type'];
                $log->cost = $cost['cost'];
                $log->credits_before = 0;
                $log->additional_credits_before = 0;
                $log->timestamp = Localization::getDatabaseDateTime(new DateTime());
                $log->save();
            }
        } catch (Exception $e) {
            Yii::error('IDB TAKE CREDIT');
            Yii::error($e->getMessage());
        }
    }
}

################################################################################
#                                End of file                                   #
################################################################################
