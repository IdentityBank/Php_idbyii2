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
use idbyii2\helpers\Localization;
use idbyii2\helpers\Translate;

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
abstract class Signup extends IdbModel
{

    protected $namespace = '';

    /** @var IdbSecurity $idbSecurity */
    protected $idbSecurity;
    protected $blowfishCost = 1;
    protected $dataPassword = "password";
    protected $authKeyPassword = "password";

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['timestamp'], 'safe'],
            [['data', 'auth_key_hash', 'auth_key'], 'required'],
            [['data', 'auth_key_hash', 'auth_key'], 'string'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Translate::_('idbyii2', 'ID'),
            'timestamp' => Translate::_('idbyii2', 'Timestamp'),
            'data' => Translate::_('idbyii2', 'Data'),
            'auth_key_hash' => Translate::_('idbyii2', 'Authentication Key Hash Value'),
            'auth_key' => Translate::_('idbyii2', 'Authentication Key'),
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
            if (!empty($values['dataPassword'])) {
                $this->dataPassword = $values['dataPassword'];
            }
            if (!empty($values['authKeyPassword'])) {
                $this->authKeyPassword = $values['authKeyPassword'];
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
            if (empty($this->auth_key_hash)) {
                $this->auth_key_hash = 'auth_key_hash';
            }

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

            $this->data = base64_encode($this->idbSecurity->encryptByPasswordSpeed($this->data, $this->dataPassword));
            $this->auth_key_hash = $this->idbSecurity->secureHash(
                $this->auth_key,
                $this->authKeyPassword,
                $this->blowfishCost
            );

            $this->auth_key = base64_encode(
                $this->idbSecurity->encryptByPasswordSpeed($this->auth_key, $this->authKeyPassword)
            );

            return true;
        }

        return false;
    }

    /**
     * @param $insert
     * @param $changedAttributes
     *
     * @return void
     */
    public function afterSave($insert, $changedAttributes)
    {
        $this->data = $this->idbSecurity->decryptByPasswordSpeed(base64_decode($this->data), $this->dataPassword);
        $this->auth_key = $this->idbSecurity->decryptByPasswordSpeed(
            base64_decode($this->auth_key),
            $this->authKeyPassword
        );
        parent::afterSave($insert, $changedAttributes);
    }

    /**
     * @param $key
     *
     * @return mixed
     */
    public static function findByAuthKey($key)
    {
        $instance = self::instantiate();
        $keyHash = $instance->idbSecurity->secureHash($key, $instance->authKeyPassword, $instance->blowfishCost);

        return self::findOne(['auth_key_hash' => $keyHash]);
    }

    /**
     * @return void
     */
    public function afterFind()
    {
        $this->data = $this->idbSecurity->decryptByPasswordSpeed(base64_decode($this->data), $this->dataPassword);
        $this->auth_key = $this->idbSecurity->decryptByPasswordSpeed(
            base64_decode($this->auth_key),
            $this->authKeyPassword
        );
        parent::afterFind();
    }

    /**
     * @return string
     */
    public static function generateVeryficationCodeStatic()
    {
        return rand(100, 999) . '.' . rand(100, 999) . '.' . rand(100, 999) . '.' . rand(100, 999);
    }

    /**
     * @param        $key
     * @param        $value
     * @param bool   $trim
     * @param bool   $withNamespace
     * @param string $namespace
     *
     * @return void
     */
    public function setDataChunk(
        $key,
        $value,
        $trim = true,
        $withNamespace = true,
        $namespace = null
    ) {
        if ($namespace == null) {
            $namespace = $this->namespace;
        }

        $data = json_decode($this->data, true);
        $value = $trim ? trim($value) : $value;

        if ($withNamespace) {
            $data[$namespace][$key] = $value;
        } else {
            $data[$key] = $value;
        }

        $this->data = json_encode($data);
    }

    /**
     * @param        $key
     * @param string $namespace
     *
     * @return mixed
     */
    public function getDataChunk($key, $namespace = null)
    {
        if ($namespace == null) {
            $namespace = $this->namespace;
        }

        $data = json_decode($this->data, true);

        return $data[$namespace][$key] ?? null;
    }

    /**
     * @return mixed
     */
    public function getData()
    {
        $data = json_decode($this->data, true);

        return $data;
    }

    /**
     * @param        $tokenKeys
     * @param string $namespace
     *
     * @return bool|false|string
     */
    public function JSONByNamespace($tokenKeys, $namespace = null)
    {
        $data = json_decode($this->data, true);

        if (!empty($data[$namespace])) {
            $tokenData = [];
            foreach ($tokenKeys as $tokenKey) {
                if (!empty($data[$namespace][$tokenKey])) {
                    $tokenData[$tokenKey] = $data[$namespace][$tokenKey];
                }
            }

            return self::generateTokenJSON($tokenData);
        } else {
            return false;
        }
    }

    /**
     * @param $data
     * @return false|string
     */
    public static function generateTokenJSON($data)
    {
        $tokenData = [
            'version' => 1,
            'timestamp' => Localization::getDateTimeFileFormat()
        ];

        return json_encode(array_merge($tokenData, $data));
    }
}

################################################################################
#                                End of file                                   #
################################################################################
