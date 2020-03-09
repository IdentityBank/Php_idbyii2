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

use app\helpers\PeopleConfig;
use DateInterval;
use DateTime;
use Exception;
use idbyii2\enums\EmailActionType;
use idbyii2\helpers\EmailTemplate;
use idbyii2\helpers\Localization;
use idbyii2\helpers\Translate;
use idbyii2\models\identity\IdbPeopleUser;
use Yii;
use yii\helpers\Url;

################################################################################
# Class(es)                                                                    #
################################################################################

/**
 * Class PeopleAuthlog
 *
 * @package idbyii2\models\db
 */
class PeopleAuthlog extends PeopleLogModel
{

    /**
     * @return string
     */
    public static function tableName()
    {
        return 'p57b_log.people_authlog';
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
        $login = IdbPeopleUser::createLogin(
            $model->userId,
            $model->accountNumber
        );
        $userAccount = IdbPeopleUser::findUserAccountByLogin($login);

        if (empty($userAccount)) {
            return;
        }

        try {
            PeopleAuthlog::error(
                $userAccount->uid,
                ['p' => strlen($model->accountPassword) . "_" . time()]
            );

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
                PeopleConfig::get()->getYii2PeopleErrorsToSendRecovery()
            )->all();

            if (parent::sendPasswordRecoveryCheck($errors, PeopleConfig::get()->getYii2PeopleErrorsToSendRecovery())) {
                $email = PeopleUserData::getUserDataByKeys($userAccount->uid, ['email']);
                $firstName = PeopleUserData::getUserDataByKeys($userAccount->uid, ['name']);
                $lastName = PeopleUserData::getUserDataByKeys($userAccount->uid, ['surname']);
                $businessName = PeopleUserData::getUserDataByKeys($userAccount->uid, ['businessAccountName']);

                EmailTemplate::sendEmailByAction(
                    EmailActionType::PEOPLE_PASSWORD_RECOVERY,
                    [
                        'url' => Url::toRoute('/passwordrecovery', true),
                        'person' => $firstName[0]->value . ' ' . $lastName[0]->value,
                        'firstName' => $firstName[0]->value,
                        'lastName' => $lastName[0]->value,
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
