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

namespace idbyii2\models\form;

################################################################################
# Use(s)                                                                       #
################################################################################

use idbyii2\helpers\Translate;
use idbyii2\models\db\BusinessPasswordPolicy;
use idbyii2\models\db\PeoplePasswordPolicy;
use idbyii2\validators\IdbNameValidator;
use idbyii2\validators\PasswordValidator;
use yii\helpers\ArrayHelper;

################################################################################
# Class(es)                                                                    #
################################################################################

/**
 * Class IdbPeopleSignUpForm
 *
 * @package idbyii2\models\form
 */
class IdbPeopleSignUpForm extends IdbModel
{

    // Login data
    public $userId;

    // Contact person
    public $name;
    public $surname;
    public $mobile;
    public $email;
    public $withoutEmail;
    public $withoutMobile;

    // Password
    public $password;
    public $repeatPassword;

    // Related Business data
    public $businessUserId;
    public $businessOrgazniationId;
    public $businessAccountid;
    public $businessDatabaseId;
    public $businessDatabaseUserId;
    public $businessAccountName;

    public $passwordPolicy;

    public function __construct()
    {
        $this->passwordPolicy = BusinessPasswordPolicy::getPasswordPolicyJSONByName();
    }

    /**
     * @return array
     */
    public function rules()
    {
        return ArrayHelper::merge(
            [
                [
                    [
                        'name',
                        'surname',
                        'email',
                        'mobile',
                        'userId',
                        'password',
                        'repeatPassword',
                        'businessUserId',
                        'businessOrgazniationId',
                        'businessAccountid',
                        'businessDatabaseId',
                        'businessDatabaseUserId'
                    ],
                    'required'
                ],
                [
                    [
                        'name',
                        'surname',
                        'email',
                        'mobile',
                        'userId',
                        'password',
                        'repeatPassword',
                    ],
                    'string',
                    'max' => 255
                ],
                [
                    [
                        'name',
                        'surname',
                        'email',
                        'mobile',
                        'userId',
                        'password',
                        'repeatPassword',
                    ],
                    'trim'
                ],
                ['email', 'email'],
                ['mobile', 'string', 'length' => [3, 20]],
                [
                    'mobile',
                    'match',
                    'pattern' => '/^\+[1-9]{1}\d{3,14}$/s',
                    'message' => Translate::_(
                        'idbyii2',
                        'The mobile number must be provided with the country code and starts with + character.'
                    )
                ],
                [
                    'repeatPassword',
                    'compare',
                    'compareAttribute' => 'password',
                    'message' => Translate::_('idbyii2', "The passwords do not match.")
                ],
                ['password', PasswordValidator::className()],
            ],
            IdbNameValidator::customRules('userId', $this->getAttributeLabel('userId'))
        );
    }

    /**
     * @return array
     */
    public function attributeLabels()
    {
        return [
            'password' => Translate::_('idbyii2', 'Password'),
            'repeatPassword' => Translate::_('idbyii2', 'Repeat Password'),
            'name' => Translate::_('idbyii2', 'First name'),
            'surname' => Translate::_('idbyii2', 'Surname'),
            'email' => Translate::_('idbyii2', 'Email'),
            'mobile' => Translate::_('idbyii2', 'Mobile phone number'),
            'userId' => Translate::_('idbyii2', 'Login name'),
        ];
    }

    /**
     * @return array
     */
    public function attributeHints()
    {
        return [
            'password' => '<div>' . $this->attributeHintTemplate(PeoplePasswordPolicy::getHelpByName()) . '</div>',
        ];
    }

    /**
     * @return bool
     */
    public function beforeValidate()
    {
        if (parent::beforeValidate()) {
            foreach ($this->attributes as $attributeName => $attributeValue) {
                if (($attributeName === 'password') || ($attributeName === 'repeatPassword')) {
                    continue;
                }
                $attributeValue = trim($attributeValue);
                $attributeValue = trim($attributeValue, '"\'');
                $attributeValue = strip_tags($attributeValue);
                $attributeValue = htmlspecialchars($attributeValue);
                $this->$attributeName = $attributeValue;
            }

            return true;
        }

        return false;
    }
}

################################################################################
#                                End of file                                   #
################################################################################
