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

use app\helpers\BusinessConfig;
use app\helpers\Translate;
use idbyii2\helpers\Totp;
use idbyii2\models\db\BusinessPasswordPolicy;
use idbyii2\validators\IdbNameValidator;
use idbyii2\validators\PasswordValidator;
use Yii;
use yii\helpers\ArrayHelper;

################################################################################
# Class(es)                                                                    #
################################################################################

/**
 * Class IdbBusinessSignUpForm
 *
 * @package idbyii2\models\form
 */
class IdbBusinessSignUpForm extends IdbModel
{

    const SCENARIO_BUSINESS_DETAILS = 'BusinessDetails';
    const SCENARIO_PRIMARY_CONTACT = 'PrimaryContact';

    // Contact person
    public $firstname;
    public $lastname;
    public $initials;
    public $mobile;
    public $email;
    // Business details
    public $name;
    public $addressLine1;
    public $addressLine2;
    public $city;
    public $region;
    public $postcode;
    public $country;
    public $VAT;
    public $registrationNumber;
    // Password
    public $password;
    public $repeatPassword;
    // Authenticator
    public $authenticatorEnabled = true;
    public $authenticatorCode;
    public $captchaEnabled = true;
    public $verificationCode;

    public $passwordPolicy;

    public $dpo = [
        'dpoTermsAndCondition' => '',
        'dpoDataProcessingAgreements' => '',
        'dpoPrivacyNotice' => '',
        'dpoCookiePolicy' => '',
        'dpoEmail' => '',
        'dpoMobile' => '',
        'dpoAddress' => '',
        'dpoOther' => '',
    ];


    public function __construct()
    {
        $this->passwordPolicy = BusinessPasswordPolicy::getPasswordPolicyJSONByName();
    }

    public static function businessDetailsAttributes()
    {
        return [
            'name',
            'addressLine1',
            'addressLine2',
            'city',
            'region',
            'postcode',
            'country',
            'VAT',
            'registrationNumber',
        ];
    }

    public function scenarios()
    {
        $scenarios = parent::scenarios();
        $scenarios[self::SCENARIO_BUSINESS_DETAILS] = ArrayHelper::merge(
            self::businessDetailsAttributes(),
            [
                'captchaEnabled',
                'verificationCode',
                'passwordPolicy',
            ]
        );
        $scenarios[self::SCENARIO_PRIMARY_CONTACT] = ArrayHelper::merge(
            $scenarios[self::SCENARIO_BUSINESS_DETAILS],
            [
                'firstname',
                'lastname',
                'initials',
                'email',
                'mobile',
                'password',
                'repeatPassword',
                'authenticatorEnabled',
                'authenticatorCode',
            ]
        );

        return $scenarios;
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
                        'firstname',
                        'lastname',
                        'initials',
                        'email',
                        'mobile',
                        'password',
                        'repeatPassword',
                        'name',
                        'addressLine1',
                        'city',
                        'postcode',
                        'country',
                        'VAT',
                        'registrationNumber'
                    ],
                    'required'
                ],
                [
                    [
                        'firstname',
                        'lastname',
                        'initials',
                        'email',
                        'mobile',
                        'name',
                        'addressLine1',
                        'city',
                        'postcode',
                        'country',
                        'VAT',
                        'registrationNumber'
                    ],
                    'trim'
                ],
                [
                    [
                        'firstname',
                        'lastname'
                    ],
                    'string',
                    'length' => [2, 64]
                ],
                [
                    [
                        'password',
                        'repeatPassword',
                    ],
                    'string',
                    'max' => 255
                ],
                [
                    [
                        'email',
                        'addressLine1',
                        'addressLine2',
                    ],
                    'string',
                    'max' => 128
                ],
                [
                    [
                        'name',
                        'initials',
                        'city',
                        'region',
                        'postcode',
                        'country',
                    ],
                    'string',
                    'max' => 64
                ],
                [
                    [
                        'registrationNumber',
                        'VAT',
                    ],
                    'string',
                    'max' => 30
                ],
                ['mobile', 'string', 'length' => [3, 20]],
                ['email', 'email'],
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
                ['authenticatorCode', 'string', 'max' => 7],
                ['authenticatorCode', 'validateAuthenticatorCode', 'skipOnEmpty' => false],
                [
                    'repeatPassword',
                    'compare',
                    'compareAttribute' => 'password',
                    'message' => Translate::_('idbyii2', "The passwords do not match.")
                ],
                ['password', PasswordValidator::className()],
            ],
            IdbNameValidator::customRules('name', $this->getAttributeLabel('name'))
        );
    }

    /**
     * @return array
     */
    public function attributeLabels()
    {
        return
            [
                'password' => Translate::_('idbyii2', 'Account password'),
                'repeatPassword' => Translate::_('idbyii2', 'Repeat Password'),
                'firstname' => Translate::_('idbyii2', 'First name'),
                'lastname' => Translate::_('idbyii2', 'Surname'),
                'initials' => Translate::_('idbyii2', 'Initials'),
                'email' => Translate::_('idbyii2', 'Email'),
                'mobile' => Translate::_('idbyii2', 'Mobile phone number'),
                'name' => Translate::_('idbyii2', 'Enter your business name (this will also be your login name)'),
                'addressLine1' => Translate::_('idbyii2', 'Address Line 1'),
                'addressLine2' => Translate::_('idbyii2', 'Address Line 2'),
                'city' => Translate::_('idbyii2', 'City'),
                'region' => Translate::_('idbyii2', 'Province / Region'),
                'postcode' => Translate::_('idbyii2', 'Postcode'),
                'country' => Translate::_('idbyii2', 'Country'),
                'authenticatorCode' => Translate::_('idbyii2', 'Your signup authenticator code'),
                'VAT' => Translate::_('idbyii2', 'Your VAT number'),
                'registrationNumber' => Translate::_('idbyii2', 'Company Registration Number')
            ];
    }

    /**
     * @return array
     */
    public function attributeHints()
    {
        return
            [
                'password' => $this->attributeHintTemplate(BusinessPasswordPolicy::getHelpByName()),
                'email' => Translate::_(
                    'idbyii2',
                    'Email (we recommend that you create a new business email only to use with your Identity Bank account)'
                ),
                'mobile' => Translate::_(
                    'idbyii2',
                    'Enter a mobile phone number starting with a \'+\', then country code and phone number without leading zero. Identity Bank will use this phone number to send an activation SMS.'
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
                if ($attributeName === 'mobile') {
                    $attributeValue = preg_replace('/[^0-9\+]/is', '', $attributeValue);
                }
                if (
                    ($attributeName === 'password')
                    || ($attributeName === 'repeatPassword')
                    || ($attributeName === 'dpo')
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
    public function validateAuthenticatorCode($attribute, $params)
    {
        if (!$this->hasErrors() && ($this->authenticatorEnabled)) {
            $this->$attribute = preg_replace('/\s+/', "", $this->$attribute);
            $securityKey = Totp::securityKeyFromString(
                BusinessConfig::get()->getYii2BusinessSignUpFormAuthenticatorSecurityKey()
            );
            if (!Totp::verify($securityKey, $this->$attribute)) {
                $this->addError(
                    $attribute,
                    Translate::_(
                        'idbyii2',
                        '{attribute} is incorrect.',
                        ['attribute' => $this->getAttributeLabel($attribute)]
                    )
                );
            }
        }
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
