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
use idbyii2\helpers\IdbSecurity;
use idbyii2\helpers\Localization;
use idbyii2\helpers\Translate;
use idbyii2\helpers\Uuid;
use idbyii2\models\form\IdbPeopleSignUpForm;
use Yii;

################################################################################
# Class(es)                                                                    #
################################################################################

/**
 * This is the model class for table "signup".
 *
 * @property int    $id
 * @property string $timestamp
 * @property string $data
 * @property string $auth_key_hash
 * @property string $auth_key
 */
class SignupPeople extends Signup
{

    protected $namespace = SignUpNamespace::PEOPLE;

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
        if (!empty(Yii::$app->getModule('registration')->configSignUp)) {
            $this->setAttributes(Yii::$app->getModule('registration')->configSignUp);
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
        if (!isset($data[SignUpNamespace::PEOPLE])) {
            throw new Exception(Translate::_('idbyii2', 'IdbPeopleSignUpForm doesn\'t exists in $data'));
        }

        $salt = substr(uniqid('', true), -8);
        $key = $data[SignUpNamespace::PEOPLE]['email'] ?? $data[SignUpNamespace::PEOPLE]['mobile'];
        $generateString = $salt . $data[SignUpNamespace::PEOPLE]['name']
            . $data[SignUpNamespace::PEOPLE]['surname']
            . $key;

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
        $dataDecoded[SignUpNamespace::PEOPLE][$type] = $data;
        $this->data = json_encode($dataDecoded);
        $this->save();
    }

    /**
     * @param \idbyii2\models\form\IdbPeopleSignUpForm $form
     *
     * @return void
     */
    public function setDataFromForm(IdbPeopleSignUpForm $form)
    {
        $data = [
            SignUpNamespace::PEOPLE => [
                'name' => trim($form->name),
                'surname' => trim($form->surname),
                'mobile' => trim($form->mobile),
                'email' => trim($form->email),
                'userId' => trim($form->userId),
                'password' => trim($form->password),
                'businessUserId' => $this->getDataChunk('businessUserId'),
                'businessAccountid' => $this->getDataChunk('businessAccountid'),
                'businessDatabaseId' => $this->getDataChunk('businessDatabaseId'),
                'businessDatabaseUserId' => $this->getDataChunk('businessDatabaseUserId'),
                'businessAccountName' => $this->getDataChunk('businessAccountName'),
                'businessOrgazniationId' => $this->getDataChunk('businessOrgazniationId'),
                'tryCount' => 3,
            ],
            'form' => $_SERVER
        ];

        $this->data = json_encode($data);
    }

    /**
     * @param array $post
     *
     * @return void
     */
    public function setDataFromPost(array $post)
    {
        if (empty($post['userId'])) {
            $post['userId'] = '';
        }
        $data = [
            SignUpNamespace::PEOPLE => [
                'name' => trim($post['name']),
                'surname' => trim($post['surname']),
                'mobile' => trim($post['mobile']),
                'email' => trim($post['email']),
                'userId' => trim($post['userId']),
                'businessUserId' => trim($post['businessUserId']),
                'businessAccountid' => trim($post['businessAccountid']),
                'businessDatabaseId' => trim($post['businessDatabaseId']),
                'businessDatabaseUserId' => trim($post['businessDatabaseUserId']),
                'businessOrgazniationId' => trim($post['businessOrgazniationId']),
                'businessAccountName' => trim($post['businessAccountName']),
                'withoutEmail' => $post['withoutEmail'] ?? null,
                'withoutMobile' => $post['withoutMobile'] ?? null
            ],
            'form' => $_SERVER
        ];

        $this->data = json_encode($data);
    }

    /**
     * @param string $namespace
     *
     * @return bool|false|string
     */
    public function getDataJSONByNamespace($namespace = SignUpNamespace::PEOPLE)
    {
        return parent::JSONByNamespace(
            [
                'name',
                'surname',
                'mobile',
                'email',
                'userId',
                'businessAccountName',
                'uid'
            ],
            $namespace
        );
    }

    /**
     * @param        $key
     * @param string $namespace
     *
     * @return mixed
     */
    public function getDataChunk($key, $namespace = SignUpNamespace::PEOPLE)
    {
        $data = json_decode($this->data, true);
        if (isset($data[$namespace][$key])) {
            return $data[$namespace][$key];
        } else {
            return null;
        }
    }


}

################################################################################
#                                End of file                                   #
################################################################################
