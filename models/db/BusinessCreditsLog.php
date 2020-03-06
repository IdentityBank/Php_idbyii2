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

namespace idbyii2\models\db;

################################################################################
# Use(s)                                                                       #
################################################################################

use DateInterval;
use DateTime;
use idbyii2\enums\CostActionType;
use idbyii2\helpers\Localization;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;

################################################################################
# Class(es)                                                                    #
################################################################################

/**
 * Class BusinessCreditsLog
 *
 * @property int    $id
 * @property string $oid
 * @property string $timestamp
 * @property string $action_name
 * @property string $action_type
 * @property int    $cost
 * @property int    $credits_before
 * @property int    $additional_credits_before
 */
class BusinessCreditsLog extends ActiveRecord
{

    /**
     * @return string
     */
    public static function tableName()
    {
        return 'p57b_log.idb_credits_log';
    }

    /**
     * @param $oid
     *
     * @return array
     *
     * @throws \ReflectionException
     */
    public static function getLastMonthForChart($oid)
    {
        $after = (new DateTime())->sub(new DateInterval('P1M'));

        $logs = self::find()->where(['>=', 'timestamp', Localization::getDatabaseDateTime($after)])->andWhere(
            ['oid' => $oid]
        )->all();
        $creditsBurned = self::getArrayForLastMonth();
        /** @var BusinessCreditsLog $log */
        foreach ($logs as $log) {
            $timestamp = new DateTime($log->timestamp);
            $key = $timestamp->format('d.m');
            $creditsBurned[$log->action_type][$key] = ArrayHelper::getValue(
                    $creditsBurned[$log->action_type],
                    $key,
                    0
                ) + $log->cost;
        }

        return $creditsBurned;
    }

    /**
     * @return array
     * @throws \ReflectionException
     */
    public static function getArrayForLastMonth()
    {
        $now = new DateTime();
        $after = (new DateTime())->sub(new DateInterval('P1M'));

        $array = [];
        while ($after->getTimestamp() <= $now->getTimestamp()) {
            $key = $after->format('d.m');
            foreach (CostActionType::getAllValues() as $costKey) {
                $array[$costKey][$key] = 0;
            }
            $after->add(new DateInterval('P1D'));
        }

        return $array;
    }
}

################################################################################
#                                End of file                                   #
################################################################################
