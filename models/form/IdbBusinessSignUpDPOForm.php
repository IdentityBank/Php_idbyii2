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
use codeonyii\yii2validators\AtLeastValidator;
use idbyii2\helpers\Translate;
use idbyii2\validators\UrlValidator;
use Yii;

################################################################################
# Class(es)                                                                    #
################################################################################

/**
 * Class IdbBusinessSignUpForm
 *
 * @package idbyii2\models\form
 */
class IdbBusinessSignUpDPOForm extends IdbModel
{

    public $dpoTermsAndCondition;
    public $dpoDataProcessingAgreements;
    public $dpoPrivacyNotice;
    public $dpoCookiePolicy;
    public $dpoEmail;
    public $dpoMobile;
    public $dpoAddress;
    public $dpoOther;
    // Authenticator
    public $captchaEnabled = true;
    public $verificationCode;

    public static function dpoDetailsAttributes()
    {
        return [
            'dpoTermsAndCondition',
            'dpoDataProcessingAgreements',
            'dpoPrivacyNotice',
            'dpoCookiePolicy',
            'dpoEmail',
            'dpoMobile',
            'dpoAddress',
            'dpoOther',
        ];
    }

    public function rules()
    {
        return [
            [
                ['dpoEmail'],
                AtLeastValidator::class,
                'in' => ['dpoEmail', 'dpoMobile', 'dpoAddress', 'dpoOther'],
                'message' => Translate::_(
                    'idbyii2',
                    'You must fill at least one of the DPO contact.'
                )
            ],
            [
                ['dpoTermsAndCondition', 'dpoDataProcessingAgreements', 'dpoPrivacyNotice', 'dpoCookiePolicy'],
                'required'
            ],
            [
                ['dpoTermsAndCondition', 'dpoDataProcessingAgreements', 'dpoPrivacyNotice', 'dpoCookiePolicy'],
                UrlValidator::class
            ],
            [['dpoOther', 'dpoAddress'], 'string'],
            ['dpoMobile', 'string', 'length' => [3, 20]],
            ['dpoEmail', 'email'],
            [
                'dpoMobile',
                'match',
                'pattern' => '/^\+[1-9]{1}\d{3,14}$/s',
                'message' => Translate::_(
                    'idbyii2',
                    'The mobile number must be provided with the country code and starts with + character.'
                )
            ],
            ['verificationCode', 'validateCaptcha'],
        ];
    }

    /**
     * @return array
     */
    public function attributeLabels()
    {
        return [
            'dpoEmail' => Translate::_('idbyii2', 'Email'),
            'dpoMobile' => Translate::_('idbyii2', 'Mobile phone number'),
            'dpoAddress' => Translate::_('idbyii2', 'Address'),
            'dpoOther' => Translate::_('idbyii2', 'Details how to contact DPO'),

            'dpoTermsAndCondition' => Translate::_('idbyii2', 'Terms and conditions'),
            'dpoDataProcessingAgreements' => Translate::_('idbyii2', 'Data Processing Agreements'),
            'dpoPrivacyNotice' => Translate::_('idbyii2', 'Privacy Notice'),
            'dpoCookiePolicy' => Translate::_('idbyii2', 'Cookie Policy'),
        ];
    }

    /**
     * @return array
     */
    public function attributeHints()
    {
        return [
            'dpoMobile' => Translate::_(
                'idbyii2',
                'Enter a mobile phone number starting with a \'+\', then country code and phone number without leading zero.'
            ),
        ];
    }

    /**
     * @return bool
     */
    public function beforeValidate()
    {
        if (parent::beforeValidate()) {

            foreach ($this->attributes as $attributeName => $attributeValue) {
                if ($attributeName === 'dpoMobile') {
                    $attributeValue = preg_replace('/[^0-9\+]/is', '', $attributeValue);
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
