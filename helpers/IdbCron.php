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

use Exception;
use idbyii2\components\Messenger;
use idbyii2\models\db\BusinessSignup;
use idbyii2\services\Invoice;
use idbyii2\services\Payment;

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
     * @throws \Throwable
     */
    public static function daily($args)
    {
        try {
            Credits::cacheCosts();
            Account::deleteOutdatedPeople();
            Invoice::logOutDatedInvoices();
            Account::deleteBusinessAccounts();
            BusinessSignup::deleteOutdated();
            Account::disconnectOutdated();
            Event::cacheDailyEvents();
        } catch (Exception $e) {
            self::reportError(" [Ex] ERROR executing daily cron. - " . $e->getMessage());
        } catch (Error $e) {
            self::reportError(" [E] ERROR executing daily cron. - " . $e->getMessage());
        }
    }

    /**
     * @param $args
     *
     * @throws Exception
     */
    public static function dailyMorning($args)
    {
        try {
            if (IdbYii2Config::get()->isCronPaymentEnabled()) {
                Payment::rechargeOrNotifyOutdated();
            } else {
                self::echoLog("Cron payment are disabled!");
            }
        } catch (Exception $e) {
            self::reportError(" [Ex] ERROR executing dailyMorning cron. - " . $e->getMessage());
        } catch (Error $e) {
            self::reportError(" [E] ERROR executing dailyMorning cron. - " . $e->getMessage());
        }
    }

    /**
     * @param $args
     *
     * @throws \yii\web\NotFoundHttpException
     */
    public static function hour($args)
    {
        try {
            Event::hourlyEvents();
        } catch (Exception $e) {
            self::reportError(" [Ex] ERROR executing hour cron. - " . $e->getMessage());
        } catch (Error $e) {
            self::reportError(" [E] ERROR executing hour cron. - " . $e->getMessage());
        }
    }

    /**
     * @param $args
     */
    public static function halfHour($args)
    {
        try {
        } catch (Exception $e) {
            self::reportError(" [Ex] ERROR executing halfHour cron. - " . $e->getMessage());
        } catch (Error $e) {
            self::reportError(" [E] ERROR executing halfHour cron. - " . $e->getMessage());
        }
    }

    /**
     * @param $args
     */
    public static function quarterHour($args)
    {
        try {
        } catch (Exception $e) {
            self::reportError(" [Ex] ERROR executing quarterHour cron. - " . $e->getMessage());
        } catch (Error $e) {
            self::reportError(" [E] ERROR executing quarterHour cron. - " . $e->getMessage());
        }
    }

    /**
     * @param $args
     */
    public static function minute5($args)
    {
        try {
        } catch (Exception $e) {
            self::reportError(" [Ex] ERROR executing minute5 cron. - " . $e->getMessage());
        } catch (Error $e) {
            self::reportError(" [E] ERROR executing minute5 cron. - " . $e->getMessage());
        }
    }

    private static function reportError($errorMessage)
    {
        self::echoLog($errorMessage);
        $messenger = Messenger::get();

        $messenger->slack(
            'server_cron',
            Translate::_(
                'idbyii2',
                "Error executing cron task for server {server}: {message}",
                [
                    'server' => IdbYii2Config::get()->serverName(),
                    'message' => $errorMessage
                ]
            )
        );
    }

    public static function echoLog($msg)
    {
        try {
            $timestamp = Localization::getDateTimeLogString();
            echo("[$timestamp] - $msg" . PHP_EOL);
        } catch (Exception $e) {
            Yii2::error($e->getMessage());
        }
    }
}

################################################################################
#                                End of file                                   #
################################################################################
