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

use app\helpers\Translate;
use idbyii2\models\db\BusinessPasswordPolicy;
use idbyii2\validators\IdbNameValidator;
use idbyii2\validators\PasswordValidator;
use Yii;
use yii\helpers\ArrayHelper;

################################################################################
# Class(es)                                                                    #
################################################################################

/**
 * Class IdbBusinessUserSignUpForm
 *
 * @package idbyii2\models\form
 */
class IdbBusinessUserSignUpForm extends IdbModel
{

    // Contact person
    public $userId;
    public $firstname;
    public $lastname;
    public $mobile;
    public $email;
    // Password
    public $password;
    public $repeatPassword;
    // Authenticator
    public $authenticatorEnabled = true;
    public $captchaEnabled = true;
    public $verificationCode;

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
                        'userId',
                        'firstname',
                        'lastname',
                        'email',
                        'mobile',
                        'password',
                        'repeatPassword',
                    ],
                    'required'
                ],
                [
                    [
                        'userId',
                        'firstname',
                        'lastname',
                        'email',
                        'mobile',
                    ],
                    'trim'
                ],
                [
                    [
                        'userId',
                        'firstname',
                        'lastname',
                        'email',
                        'mobile',
                        'password',
                        'repeatPassword',
                    ],
                    'string',
                    'max' => 255
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
                ['verificationCode', 'validateCaptcha'],
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
        return
            [
                'userId' => Translate::_('idbyii2', 'Login name'),
                'password' => Translate::_('idbyii2', 'Account password'),
                'repeatPassword' => Translate::_('idbyii2', 'Repeat Password'),
                'firstname' => Translate::_('idbyii2', 'First name'),
                'lastname' => Translate::_('idbyii2', 'Surname'),
                'email' => Translate::_('idbyii2', 'Email'),
                'mobile' => Translate::_('idbyii2', 'Mobile phone number'),
            ];
    }

    /**
     * @return array
     */
    public function attributeHints()
    {
        return
            [
                'password' => $this->attributeHintTemplate(BusinessPasswordPolicy::getHelpByName())
            ];
    }

    /**
     * @return bool
     */
    public function beforeValidate()
    {
        if (parent::beforeValidate()) {
            foreach ($this->attributes as $attributeName => $attributeValue) {
                if ($attributeName === 'mobile') {
                    $attributeValue = preg_replace('/[^0-9\+]/is', '', $attributeValue);
                }
                if (
                    ($attributeName === 'password')
                    || ($attributeName === 'repeatPassword')
                ) {
                    continue;
                }
                $attributeValue = trim($attributeValue);
                $attributeValue = trim($attributeValue, '"\'');
                $attributeValue = strip_tags($attributeValue);
                $this->$attributeName = $attributeValue;
            }

            return true;
        }

        return false;
    }

    /**
     * @param $attribute
     * @param $params
     *
     * @return void
     */
    public function validateCaptcha($attribute, $params)
    {
        if (
            !$this->hasErrors() && ($this->captchaEnabled)
            && (!Yii::$app->signUpCaptcha->validate(
                $this->verificationCode
            ))
        ) {
            $this->addError(
                $attribute,
                Translate::_(
                    'idbyii2',
                    '{attribute} is incorrect.',
                    ['attribute' => $this->getAttributeLabel($attribute)]
                ) . ' ' . Translate::_(
                    'idbyii2',
                    'The system detected that you have to be bot. I am sorry but you cannot continue.'
                )
            );
        }
    }
}

################################################################################
#                                End of file                                   #
################################################################################
