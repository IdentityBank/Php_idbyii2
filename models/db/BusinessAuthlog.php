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

use app\helpers\BusinessConfig;
use DateInterval;
use DateTime;
use Exception;
use idbyii2\enums\EmailActionType;
use idbyii2\helpers\EmailTemplate;
use idbyii2\helpers\Localization;
use idbyii2\helpers\Translate;
use idbyii2\models\identity\IdbBusinessUser;
use Yii;
use yii\helpers\Url;

################################################################################
# Class(es)                                                                    #
################################################################################

/**
 * Class BusinessAuthlog
 *
 * @package idbyii2\models\db
 */
class BusinessAuthlog extends BusinessLogModel
{

    /**
     * @return string
     */
    public static function tableName()
    {
        return 'p57b_log.business_authlog';
    }

    /**
     * Find latest error login attempt and if is more than setup in settings send mail with password recovery.
     *
     * @param $model
     *
     * @throws \Exception
     */
    public static function findLatestErrors($model)
    {
        $login = IdbBusinessUser::createLogin(
            $model->userId,
            $model->accountNumber
        );
        $userAccount = IdbBusinessUser::findUserAccountByLogin($login);

        BusinessAuthlog::error(
            $userAccount->uid,
            ['p' => strlen($model->accountPassword) . "_" . time()]
        );

        if (empty($userAccount)) {
            return;
        }

        try {
            $self = self::instantiate();

            $failedTime = new DateTime();
            $failedTime->sub(new DateInterval('PT1H'));

            $errors = self::find()->where(
                [
                    'uid' => $self->generateUidHash($userAccount->uid),
                ]
            )->andWhere(
                [
                    '>',
                    'timestamp',
                    Localization::getDatabaseDateTime($failedTime)
                ]
            )->orderBy('timestamp DESC')->limit(
                BusinessConfig::get()->getYii2BusinessErrorsToSendRecovery()
            )->all();

            if (
            parent::sendPasswordRecoveryCheck(
                $errors,
                BusinessConfig::get()->getYii2BusinessErrorsToSendRecovery()
            )
            ) {
                $email = BusinessUserData::getUserDataByKeys($userAccount->uid, ['email']);
                $firstName = BusinessUserData::getUserDataByKeys($userAccount->uid, ['firstname']);
                $lastName = BusinessUserData::getUserDataByKeys($userAccount->uid, ['lastname']);
                $businessName = BusinessUserData::getUserDataByKeys($userAccount->uid, ['name']);

                EmailTemplate::sendEmailByAction(
                    EmailActionType::BUSINESS_PASSWORD_RECOVERY,
                    [
                        'url' => Url::toRoute('/passwordrecovery', true),
                        'person' => $firstName[0]->value . ' ' . $lastName[0]->value,
                        'firstName' => $firstName[0]->value,
                        'lastName' => $firstName[0]->value,
                        'businessName' => $businessName[0]->value
                    ],
                    Translate::_('idbyii2', 'IDB Account bad login attempt.'),
                    $email[0]->getValue(),
                    Yii::$app->language
                );
            }
        } catch (Exception $exception) {
            Yii::error('Send password recovery');
            Yii::error($exception->getMessage());
        }
    }
}

################################################################################
#                                End of file                                   #
################################################################################
