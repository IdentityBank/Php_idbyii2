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

use app\helpers\Translate;
use idbyii2\helpers\IdbSecurity;
use Yii;

################################################################################
# Class(es)                                                                    #
################################################################################

/**
 * This is the model class for table "user_account".
 *
 * @property string $login
 * @property string $access_token
 * @property string $uid
 */
abstract class UserAccount extends IdbModel
{

    protected $_userData = false;
    protected $_userDataProvider = false;
    protected $_userDataProviderKeyValuePair = false;
    protected $idbSecurity;
    protected $blowfishCost = 1;
    protected $loginPassword = "password";
    protected $uidPassword = "password";

    /**
     * @return string
     */
    public static function tableName()
    {
        return 'user_account';
    }

    /**
     * @return array
     */
    public function rules()
    {
        return
            [
                [['login', 'uid'], 'required'],
                [['login', 'access_token', 'uid'], 'string', 'max' => 255],
                [['login', 'access_token'], 'unique'],
            ];
    }

    /**
     * @return array
     */
    public function attributeLabels()
    {
        return
            [
                'login' => Translate::_('idbyii2', 'Login'),
                'access_token' => Translate::_('idbyii2', 'Access Token'),
                'uid' => Translate::_('idbyii2', 'User ID'),
            ];
    }

    /**
     * @param      $values
     * @param bool $safeOnly
     *
     * @return void
     */
    public function setAttributes($values, $safeOnly = true)
    {
        if (is_array($values) && !empty($values)) {
            if (!empty($values['blowfishCost'])) {
                $this->blowfishCost = $values['blowfishCost'];
            }
            if (!empty($values['loginPassword'])) {
                $this->loginPassword = $values['loginPassword'];
            }
            if (!empty($values['uidPassword'])) {
                $this->uidPassword = $values['uidPassword'];
            }
        }
        $attributesKeys = array_keys($this->getAttributes());
        $attributes = [];
        foreach ($values as $key => $val) {
            if (in_array($key, $attributesKeys)) {
                $attributes[$key] = $val;
            }
        }
        parent::setAttributes($attributes, $safeOnly);
    }

    /**
     * @return void
     */
    public function init()
    {
        if (!empty(Yii::$app->getModule('idbuser')->configUserAccount)) {
            $this->setAttributes(Yii::$app->getModule('idbuser')->configUserAccount);
        }
        $this->idbSecurity = new IdbSecurity(Yii::$app->security);
        parent::init();
    }

    /**
     * @return void
     */
    public function afterFind()
    {
        $this->uid = $this->idbSecurity->decryptByPassword(base64_decode($this->uid), $this->uidPassword);
        parent::afterFind();
    }

    /**
     * @param $login
     *
     * @return mixed
     */
    public function generateSecureLogin($login)
    {
        return $this->idbSecurity->secureHash($login, $this->loginPassword, $this->blowfishCost);
    }

    /**
     * @param $token
     *
     * @return mixed
     */
    public function generateSecureToken($token)
    {
        return $this->idbSecurity->secureHash($token, $this->loginPassword . $this->uidPassword, $this->blowfishCost);
    }

    /**
     * @param $insert
     *
     * @return bool
     */
    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            $this->login = $this->generateSecureLogin($this->login);
            $this->uid = base64_encode($this->idbSecurity->encryptByPassword($this->uid, $this->uidPassword));

            return true;
        }

        return false;
    }

    /**
     * @return mixed
     */
    public abstract function addSearch();

    /**
     * @return mixed
     */
    public abstract function deleteSearch();

    /**
     * @param $insert
     * @param $changedAttributes
     *
     * @return void
     */
    public function afterSave($insert, $changedAttributes)
    {
        $this->uid = $this->idbSecurity->decryptByPassword(base64_decode($this->uid), $this->uidPassword);
        $this->addSearch();
        parent::afterSave($insert, $changedAttributes);
    }

    /**
     * @return bool
     */
    public function beforeDelete()
    {
        if (!parent::beforeDelete()) {
            return false;
        }
        $this->deleteSearch();
        Yii::$app->authManager->revokeAll($this->uid);

        return true;
    }

    /**
     * @param null $login
     *
     * @return bool
     */
    public function isLoginUsed($login = null)
    {
        if (!empty($login)) {
            return !is_null(self::findOne(['login' => $this->generateSecureLogin($login)]));
        }

        return false;
    }
}

################################################################################
#                                End of file                                   #
################################################################################
