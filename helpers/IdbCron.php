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

use idbyii2\models\db\BusinessSignup;
use idbyii2\services\Invoice;
use Exception;
use idbyii2\services\Payment;
use Throwable;
use yii\db\StaleObjectException;

################################################################################
# Class(es)                                                                    #
################################################################################

/**
 * Class IdbCron
 *
 * @package idbyii2\helpers
 */
class IdbCron
{

    /**
     * @param $args
     *
     * @throws Throwable
     * @throws StaleObjectException
     */
    public static function daily($args)
    {
        Credits::cacheCosts();
        Account::deleteOutdatedPeople();
        Invoice::logOutDatedInvoices();
        Account::deleteBusinessAccounts();
        BusinessSignup::deleteOutdated();
        Account::disconnectOutdated();
        Event::cacheDailyEvents();
    }

    /**
     * @param $args
     *
     * @throws Exception
     */
    public static function dailyMorning($args)
    {
        Payment::rechargeOrNotifyOutdated();
    }

    /**
     * @param $args
     * @throws \yii\web\NotFoundHttpException
     */
    public static function hour($args)
    {
        Event::hourlyEvents();
    }

    /**
     * @param $args
     */
    public static function halfHour($args)
    {
    }

    /**
     * @param $args
     */
    public static function quarterHour($args)
    {
    }

    /**
     * @param $args
     */
    public static function minute5($args)
    {
    }
}

################################################################################
#                                End of file                                   #
################################################################################
