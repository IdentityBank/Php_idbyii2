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

namespace idbyii2\enums;

################################################################################
# Use(s)                                                                       #
################################################################################

use idbyii2\helpers\Translate;

################################################################################
# Class(es)                                                                    #
################################################################################

/**
 * Class EmailActionType
 *
 * @package idbyii2\enums
 */
class EmailActionType extends Enum
{

    const BUSINESS_EMAIL_VERIFICATION = 'BUSINESS_EMAIL_VERIFICATION';
    const BUSINESS_LOGIN_DATA = 'BUSINESS_LOGIN_DATA';
    const BUSINESS_PAYMENT_REQUEST = 'BUSINESS_PAYMENT_REQUEST';
    const BUSINESS_PASSWORD_RECOVERY = 'BUSINESS_PASSWORD_RECOVERY';
    const BUSINESS_START_REGISTER = 'BUSINESS_START_REGISTER';
    const PEOPLE_EMAIL_VERIFICATION = 'PEOPLE_EMAIL_VERIFICATION';
    const PEOPLE_START_REGISTER = 'PEOPLE_START_REGISTER';
    const PEOPLE_LOGIN_DATA = 'PEOPLE_LOGIN_DATA';
    const PEOPLE_USED_FOR = 'PEOPLE_USED_FOR';
    const PEOPLE_MFA_RECOVERY = 'PEOPLE_MFA_RECOVERY';
    const PEOPLE_PASSWORD_RECOVERY = 'PEOPLE_PASSWORD_RECOVERY';
    const BILLING_PAYMENT_REMINDER = 'BILLING_PAYMENT_REMINDER';
    const BUSINESS_MFA_RECOVERY = 'BUSINESS_MFA_RECOVERY';

    /**
     * @return array
     * @throws \ReflectionException
     */
    public static function getPeopleActions()
    {
        return array_filter(
            self::getAllKeyValuePairs(),
            function ($name) {
                $name = explode('_', $name);

                return $name[0] === "PEOPLE";
            },
            ARRAY_FILTER_USE_KEY
        );
    }

    /**
     * @param $enum
     *
     * @return mixed|null
     */
    public static function translate($enum)
    {
        switch ($enum) {
            case self::BUSINESS_EMAIL_VERIFICATION:
                return Translate::_('idbyii2', 'Business email verification');
            case self::BUSINESS_LOGIN_DATA:
                return Translate::_('idbyii2', 'Business login data');
            case self::BUSINESS_PAYMENT_REQUEST:
                return Translate::_('idbyii2', 'Business payment request');
            case self::BUSINESS_MFA_RECOVERY:
                return Translate::_('idbyii2', 'Business mfa recovery');
            case self::BUSINESS_PASSWORD_RECOVERY:
                return Translate::_('idbyii2', 'Business password recovery');
            case self::BUSINESS_START_REGISTER:
                return Translate::_('idbyii2', 'Business register start');
            case self::PEOPLE_EMAIL_VERIFICATION:
                return Translate::_('idbyii2', 'People email verification');
            case self::PEOPLE_START_REGISTER:
                return Translate::_('idbyii2', 'People start register');
            case self::PEOPLE_LOGIN_DATA:
                return Translate::_('idbyii2', 'People login data');
            case self::PEOPLE_MFA_RECOVERY:
                return Translate::_('idbyii2', 'People mfa recovery');
            case self::PEOPLE_USED_FOR:
                return Translate::_('idbyii2', 'People used for');
            case self::PEOPLE_PASSWORD_RECOVERY:
                return Translate::_('idbyii2', 'People password recovery');
            case self::BILLING_PAYMENT_REMINDER:
                return Translate::_('idbyii2', 'Billing payment reminder');
        }

        return null;
    }
}

################################################################################
#                                End of file                                   #
################################################################################
