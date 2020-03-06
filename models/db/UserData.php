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

use idbyii2\helpers\IdbSecurity;
use Yii;

################################################################################
# Class(es)                                                                    #
################################################################################

/**
 * This is the model class for table "user_data".
 *
 * @property string $uid
 * @property string $key_hash
 * @property string $key
 * @property string $value
 */
abstract class UserData extends IdbModel
{

    private $idbSecurity;
    private $blowfishCost = 1;
    private $keyPassword = "password";
    private $valuePassword = "password";

    /**
     * @return string
     */
    public static function tableName()
    {
        return 'user_data';
    }

    /**
     * @return array
     */
    public function rules()
    {
        return [
            [['uid', 'key', 'key_hash'], 'required'],
            [['key', 'value'], 'string'],
            [['uid', 'key_hash'], 'string', 'max' => 255],
            [['uid', 'key_hash'], 'unique', 'targetAttribute' => ['uid', 'key_hash']],
        ];
    }

    /**
     * @return array
     */
    public function attributeLabels()
    {
        return [
            'uid' => 'UID',
            'key_hash' => 'Key Hash',
            'key' => 'Key',
            'value' => 'Value',
        ];
    }

    /**
     * @param $uid
     * @param $key
     *
     * @return mixed
     */
    public function getKeyHash($uid, $key)
    {
        return $this->idbSecurity->secureHash($uid . $key, $this->valuePassword, $this->blowfishCost);
    }

    /**
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * @return string
     */
    public function getValue()
    {
        return $this->value;
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
            if (!empty($values['keyPassword'])) {
                $this->keyPassword = $values['keyPassword'];
            }
            if (!empty($values['valuePassword'])) {
                $this->valuePassword = $values['valuePassword'];
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
        if (!empty(Yii::$app->getModule('idbuser')->configUserData)) {
            $this->setAttributes(Yii::$app->getModule('idbuser')->configUserData);
        }
        $this->idbSecurity = new IdbSecurity(Yii::$app->security);
        parent::init();
    }

    /**
     * @param null $row
     *
     * @return static
     */
    public static function instantiate($row = null)
    {
        $model = new static();
        if (is_array($row) && !empty($row)) {
            $model->setAttributes($row, false);
        }

        return $model;
    }

    /**
     * @return bool
     */
    public function beforeValidate()
    {
        if (parent::beforeValidate()) {
            $this->key = trim($this->key);
            $this->key_hash = $this->getKeyHash($this->uid, $this->key);

            return true;
        }

        return false;
    }

    /**
     * @param $insert
     *
     * @return bool
     */
    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            $this->key = base64_encode(
                $this->idbSecurity->encryptByPasswordSpeed($this->key, $this->uid . $this->keyPassword)
            );
            $this->value = base64_encode(
                $this->idbSecurity->encryptByPasswordSpeed($this->value, $this->key_hash . $this->valuePassword)
            );

            return true;
        }

        return false;
    }

    /**
     * @param $key
     * @param $value
     *
     * @return mixed
     */
    public abstract function addSearch($key, $value);

    /**
     * @return mixed
     */
    public abstract function clearUserKey();

    /**
     * @param $insert
     * @param $changedAttributes
     *
     * @return void
     */
    public function afterSave($insert, $changedAttributes)
    {
        $key = $this->idbSecurity->decryptByPasswordSpeed(base64_decode($this->key), $this->uid . $this->keyPassword);
        $value = $this->idbSecurity->decryptByPasswordSpeed(
            base64_decode($this->value),
            $this->key_hash . $this->valuePassword
        );
        $this->addSearch($key, $value);
        parent::afterSave($insert, $changedAttributes);
    }

    /**
     * @return void
     */
    public function afterFind()
    {
        $this->key = $this->idbSecurity->decryptByPasswordSpeed(
            base64_decode($this->key),
            $this->uid . $this->keyPassword
        );
        $this->value = $this->idbSecurity->decryptByPasswordSpeed(
            base64_decode($this->value),
            $this->key_hash . $this->valuePassword
        );
        parent::afterFind();
    }

    /**
     * @return bool
     */
    public function beforeDelete()
    {
        if (!parent::beforeDelete()) {
            return false;
        }
        $this->clearUserKey();

        return true;
    }

    /**
     * @param $password
     * @param $salt
     *
     * @return mixed
     */
    public function generateSecurePassword($password, $salt)
    {
        return $this->idbSecurity->secureHash(
            $password,
            $this->keyPassword . $this->valuePassword,
            $this->blowfishCost,
            $salt
        );
    }

    /**
     * @param        $uid
     * @param string $column
     *
     * @return bool
     */
    public function isUidUsed($uid, $column = 'uid')
    {
        if (!empty($uid)) {
            return !is_null(self::findOne(['uid' => $uid]));
        }

        return false;
    }

    /**
     * @var array
     */
    public static $forbiddenKeys = [
        'password',
        'authKey'
    ];

    /**
     * @param $uid
     * @param $key
     *
     * @return bool|false|int
     * @throws \Throwable
     * @throws \yii\db\StaleObjectException
     */
    public static function deleteOne($uid, $key)
    {
        if (in_array($key, self::$forbiddenKeys)) {
            return false;
        }
        $model = self::instantiate();
        $model = self::findOne(['uid' => $uid, 'key_hash' => $model->getKeyHash($uid, $key)]);
        if ($model) {
            return $model->delete();
        }

        return false;
    }

    /**
     * @param $newPassword
     * @param $salt
     *
     * @return bool
     * @throws \Throwable
     * @throws \yii\db\StaleObjectException
     */
    public function updatePassword($newPassword, $salt)
    {
        $this->value = $this->generateSecurePassword($newPassword, $salt);

        return ($this->validate() && $this->update());
    }
}

################################################################################
#                                End of file                                   #
################################################################################
