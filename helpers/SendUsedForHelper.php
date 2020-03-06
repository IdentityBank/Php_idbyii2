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
use idbyii2\components\IdbRabbitMq;
use idbyii2\components\Messenger;
use idbyii2\enums\EmailActionType;
use idbyii2\enums\NotificationTopic;
use idbyii2\models\db\BusinessAccount;
use idbyii2\models\db\BusinessAccountUser;
use idbyii2\models\db\BusinessNotification;
use idbyii2\models\db\BusinessOrganization;
use idbyii2\models\db\BusinessUserData;
use Yii;

################################################################################
# Class(es)                                                                    #
################################################################################

/**
 * Class SendUsedForHelper
 *
 * @package idbyii2\helpers
 */
class SendUsedForHelper
{

    const channelName = "sendUsedForIDB";

    /**
     * @param $data
     *
     * @throws \Exception
     */
    public static function executeSendUsedFor($data)
    {
        self::addTaskToQueue(["data" => $data]);
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
            $msg = 'Send Used For IDB - queue data cannot be empty!';
            echo $msg . PHP_EOL;
            throw new Exception($msg);
        }
    }

    /**
     * @param $data
     *
     * @throws \Exception
     */
    public static function executeTaskFromSendUsedForQueue($data)
    {
        $data = json_decode($data, true);
        echo("OK we have new Send Used For IDB task with business id: [" . $data['data']['businessId'] . "] ..."
            . PHP_EOL);

        if (json_last_error() === JSON_ERROR_NONE) {
            if (empty($data['data']['businessId'])) {
                $msg = 'Send Used For IDB - missing business id!';
                echo $msg . PHP_EOL;
                throw new Exception($msg);
            } else {
                echo "Execute Send Used For IDB task for business id: [" . $data['data']['businessId'] . "]" . PHP_EOL;
                self::send(
                    $data['data']['ids'],
                    $data['data']['mailOrSms'],
                    $data['data']['legal'],
                    $data['data']['message'],
                    $data['data']['oid'],
                    $data['data']['iso']
                );
                try {
                    $msg = 'Send Used For IDB - complete send information';
                    echo $msg . PHP_EOL;
                } catch (Exception $e) {
                    var_dump('FILE: ' . $e->getFile() . 'LINE: ' . $e->getLine() . 'MESSAGE: ' . $e->getMessage())
                    . PHP_EOL;
                }
            }
        } else {
            $msg = 'Send Used For IDB - task data are empty or not complete!';
            echo $msg . PHP_EOL;
            throw new Exception($msg);
        }
    }

    /**
     * @param $ids
     * @param $mailOrSms
     * @param $legal
     * @param $message
     * @param $oid
     * @param $iso
     */
    public static function send($ids, $mailOrSms, $legal, $message, $oid, $iso)
    {
        for ($i = 0; $i < $ids["Query"]; $i++) {
            if ($mailOrSms === 'mail') {
                self::sendMail($ids["QueryData"][$i][0], $legal, $message, $oid, $iso);
            } elseif ($mailOrSms === 'sms') {
                self::sendSms($ids["QueryData"][$i][1], $legal, $message);
            }
        }
    }

    /**
     * @param $mail
     * @param $legal
     * @param $message
     * Send e-mail
     * @param $oid
     * @param $iso
     */
    public static function sendMail($mail, $legal, $message, $oid, $iso)
    {
        $organization = BusinessOrganization::find()->where(['oid' => $oid])->one();
        $account = BusinessAccount::find()->asArray()->select('aid')->where(['oid' => $oid])->one();
        $accountUser = BusinessAccountUser::find()->asArray()->select('uid')
                                          ->where(['aid' => $account['aid']])
                                          ->one();

        $firstName = BusinessUserData::getUserDataByKeys(
            $accountUser['uid'],
            ['firstname']
        )[0];

        $lastName = BusinessUserData::getUserDataByKeys(
            $accountUser['uid'],
            ['lastname']
        )[0];

        EmailTemplate::sendEmailByAction(
            EmailActionType::PEOPLE_USED_FOR,
            [
                'legal' => $legal,
                'message' => $message,
                'firstName' => '',
                'businessName' => $organization->name,
                'firstName' => $firstName->value,
                'lastName' => $lastName->value
            ],
            Translate::_('idbyii2', 'Your data has been used'),
            $mail,
            $iso,
            $oid
        );
    }

    /**
     * @param $mobile
     * @param $legal
     * @param $message
     * Send SMS
     */
    public static function sendSms($mobile, $legal, $message)
    {
        $messenger = Messenger::get();

        $messenger->sms(
            $mobile,
            Translate::_(
                'idbyii2',
                "Legal: {legal}. {message}",
                [
                    'legal' => $legal,
                    'message' => $message
                ]
            )
        );
    }
}

################################################################################
#                                End of file                                   #
################################################################################
