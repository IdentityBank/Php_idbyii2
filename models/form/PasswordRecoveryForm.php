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
use Yii;

################################################################################
# Class(es)                                                                    #
################################################################################

/**
 * Class IdbBusinessSignUpForm
 *
 * @package idbyii2\models\form
 */
class PasswordRecoveryForm extends IdbModel
{

    public $mobile;
    public $email;
    public $token;
    public $verificationCode;

    public $captchaEnabled = true;

    /**
     * @return array
     */
    public function rules()
    {
        return [
            [['email', 'mobile', 'token'], 'required'],
            [['email', 'mobile', 'token'], 'trim'],
            [['email', 'mobile'], 'string', 'max' => 255],
            [['token'], 'string', 'min' => 90],
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
            [['email', 'mobile', 'token'], 'stripWhitespace'],
        ];
    }

    /**
     * @return array
     */
    public function attributeLabels()
    {
        return [
            'email' => Translate::_('idbyii2', 'Email'),
            'mobile' => Translate::_('idbyii2', 'Mobile phone number'),
            'token' => Translate::_('idbyii2', 'Password token'),
        ];
    }

    /**
     * @return array
     */
    public function attributeHints()
    {
        return [];
    }

    /**
     * @return bool
     */
    public function beforeValidate()
    {
        if (parent::beforeValidate()) {
            foreach ($this->attributes as $attributeName => $attributeValue) {
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

    /**
     * @param $attribute
     * @param $params
     */
    public function stripWhitespace($attribute, $params)
    {
        $this->{$attribute} = preg_replace('/\s+/', '', $this->{$attribute});
    }
}

################################################################################
#                                End of file                                   #
################################################################################
