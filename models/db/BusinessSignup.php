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

use DateTime;
use Exception;
use idbyii2\enums\SignUpNamespace;
use idbyii2\helpers\Account;
use idbyii2\helpers\IdbSecurity;
use idbyii2\helpers\Localization;
use idbyii2\helpers\Uuid;
use idbyii2\models\form\IdbBusinessSignUpForm;
use Yii;

################################################################################
# Class(es)                                                                    #
################################################################################

/**
 * This is the model class for table "signup".
 *
 * @property int $id
 * @property string $timestamp
 * @property string $data
 * @property string $auth_key_hash
 * @property string $auth_key
 */
class BusinessSignup extends Signup
{

    protected $namespace = SignUpNamespace::BUSINESS;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'signup';
    }

    /**
     * @return void
     */
    public function init()
    {
        if (!empty(Yii::$app->getModule('signup')->configSignUp)) {
            $this->setAttributes(Yii::$app->getModule('signup')->configSignUp);
        }
        $this->idbSecurity = new IdbSecurity(Yii::$app->security);
        parent::init();
    }

    /**
     * @throws Exception
     */
    public function generateAuthKey()
    {
        $data = json_decode($this->data, true);
        if (!isset($data[SignUpNamespace::BUSINESS])) {
            throw new Exception('IdbBusinessSignUpForm doesn\'t exists in $data');
        }

        $salt = substr(uniqid('', true), -8);
        $generateString = $salt . $data[SignUpNamespace::BUSINESS]['name']
            . $data[SignUpNamespace::BUSINESS]['city']
            . $data[SignUpNamespace::BUSINESS]['addressLine1'];

        $this->auth_key =
            Uuid::uuid5($generateString) . '-' . (new DateTime())->format(Localization::getDateTimeNumberFormat());
    }

    /**
     * @throws Exception
     */
    public function generateUserAuthKey()
    {
        $data = json_decode($this->data, true);
        if (!isset($data[SignUpNamespace::BUSINESS])) {
            throw new Exception('IdbBusinessUserSignUpForm doesn\'t exists in $data');
        }

        $salt = substr(uniqid('', true), -8);
        $generateString = $salt . $data[SignUpNamespace::BUSINESS]['userId']
            . $data[SignUpNamespace::BUSINESS]['email'];

        $this->auth_key =
            Uuid::uuid5($generateString) . '-' . (new DateTime())->format(Localization::getDateTimeNumberFormat());
    }

    /**
     * @param $type
     *
     * @throws \Exception
     */
    public function generateVeryficationCode($type)
    {
        $data = self::generateVeryficationCodeStatic();
        $dataDecoded = json_decode($this->data, true);
        $dataDecoded[SignUpNamespace::BUSINESS][$type] = $data;
        $this->data = json_encode($dataDecoded);
        $this->save();
    }

    /**
     * @param \idbyii2\models\form\IdbBusinessSignUpForm $form
     *
     * @throws \Exception
     */
    public function setDataFromForm(IdbBusinessSignUpForm $form)
    {
        $date = new DateTime();

        $data = [
            SignUpNamespace::BUSINESS => [
                'firstname' => trim($form->firstname),
                'lastname' => trim($form->lastname),
                'initials' => trim($form->initials),
                'mobile' => trim($form->mobile),
                'email' => trim($form->email),
                'name' => trim($form->name),
                'vat' => trim($form->VAT),
                'registrationNumber' => trim($form->registrationNumber),
                'addressLine1' => trim($form->addressLine1),
                'addressLine2' => trim($form->addressLine2),
                'city' => trim($form->city),
                'region' => trim($form->region),
                'postcode' => trim($form->postcode),
                'country' => trim($form->country),
                'password' => $form->password,
                'billingFirstName' => trim($form->firstname),
                'billingLastName' => trim($form->lastname),
                'billingName' => trim($form->name),
                'billingAddressLine1' => trim($form->addressLine1),
                'billingAddressLine2' => trim($form->addressLine2),
                'billingCity' => trim($form->city),
                'billingRegion' => trim($form->region),
                'billingPostcode' => trim($form->postcode),
                'billingCountry' => trim($form->country),
                'billingVat' => trim($form->VAT),
                'billingRegistrationNumber' => trim($form->registrationNumber),
                'tryCount' => 3,
                'paymentComplete' => 'false',
                'currentState' => 'email-verification',
                'dpoTermsAndCondition' => trim($form->dpo['dpoTermsAndCondition']),
                'dpoDataProcessingAgreements' => trim($form->dpo['dpoDataProcessingAgreements']) ,
                'dpoPrivacyNotice' => trim($form->dpo['dpoPrivacyNotice']),
                'dpoCookiePolicy' => trim($form->dpo['dpoCookiePolicy']),
                'dpoEmail' => trim($form->dpo['dpoEmail']),
                'dpoMobile' => trim($form->dpo['dpoMobile']),
                'dpoAddress' => trim($form->dpo['dpoAddress']),
                'dpoOther' => trim($form->dpo['dpoOther']),
            ],
            'form' => $_SERVER
        ];

        $this->data = json_encode($data);
    }

    /**
     * @param array $form
     *
     * @throws Exception
     */
    public function setDataFromPost(array $form)
    {
        $date = new DateTime();

        $data = [
            SignUpNamespace::BUSINESS => [
                'firstname' => trim($form['firstname']),
                'lastname' => trim($form['lastname']),
                'userId' => trim($form['firstname']) . trim($form['lastname']),
                'mobile' => trim($form['mobile']),
                'email' => trim($form['email']),
                'password' => $form['password'] ?? '',
                'dbid' => $form['dbid'],
                'oid' => $form['oid'],
                'aid' => $form['aid'],
                'uid' => $form['uid'] ?? '',
                'tryCount' => 3,
                'currentState' => 'send-registration-url',
            ],
            'form' => $_SERVER
        ];

        $this->data = json_encode($data);
    }

    public static function deleteOutdated()
    {
        try {
            Account::deleteAndCheck(
                self::class,
                [
                    '<',
                    'timestamp',
                    Localization::getDatabaseDateTime((new \DateTime())->sub(new \DateInterval('P7D')))
                ]
            );
        } catch(\Exception $e) {
            Yii::error('OUTDATED signups delete ERROR');
            Yii::error($e->getMessage());
        }
    }

    /**
     * @param string $namespace
     *
     * @return bool|false|string
     */
    public function getDataJSONByNamespace($namespace = SignUpNamespace::BUSINESS)
    {
        return parent::JSONByNamespace(
            [
                'firstname',
                'lastname',
                'initials',
                'mobile',
                'email',
                'name',
                'uid'
            ],
            $namespace
        );
    }
}

################################################################################
#                                End of file                                   #
################################################################################
