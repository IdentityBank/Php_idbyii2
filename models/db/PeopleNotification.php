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

use idbyii2\enums\NotificationType;
use idbyii2\helpers\IdbSecurity;
use idbyii2\helpers\Translate;
use Yii;

################################################################################
# Class(es)                                                                    #
################################################################################

/**
 * This is the model class for table "notifications".
 *
 * @property int    $id
 * @property string $uid
 * @property string $issued_at
 * @property string $expires_at
 * @property string $data
 * @property string $type
 * @property int    $status
 */
class PeopleNotification extends IdbModel
{

    private $idbSecurity;
    private $blowfishCost = 1;
    private $dataPassword = 'password';

    /**
     * @return string
     */
    public static function tableName()
    {
        return 'notifications';
    }

    /**
     * @return array
     */
    public function rules()
    {
        return [
            [['uid', 'data', 'type'], 'required'],
            [['issued_at', 'expires_at'], 'safe'],
            [['data', 'type'], 'string'],
            [['type'], 'default', 'value' => NotificationType::GREEN],
            [['status'], 'default', 'value' => null],
            [['status'], 'integer'],
            [['status'], 'default', 'value' => 1],
            [['uid'], 'string', 'max' => 255],
        ];
    }

    /**
     * @return array
     */
    public function attributeLabels()
    {
        return [
            'id' => Translate::_('idbyii2', 'ID'),
            'uid' => Translate::_('idbyii2', 'UID'),
            'issued_at' => Translate::_('idbyii2', 'Issued at'),
            'expires_at' => Translate::_('idbyii2', 'Expires At'),
            'data' => Translate::_('idbyii2', 'Data'),
            'type' => Translate::_('idbyii2', 'Type'),
            'status' => Translate::_('idbyii2', 'Status'),
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
        if (!empty(Yii::$app->getModule('notifications')->configNotifications)) {
            $this->setAttributes(Yii::$app->getModule('notifications')->configNotifications);
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
     * @param $uid
     *
     * @return array|\yii\db\ActiveRecord[]
     */
    public static function getNotificationsForUser($uid)
    {
        $instance = self::instantiate();
        $time = "now()";
        $notifications = $instance::find()->where(['uid' => $uid, 'status' => 1])->andWhere(
            ['or', ['expires_at' => null], ['>=', 'expires_at', $time]]
        )->all();

        return $notifications;
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

        parent::afterSave($insert, $changedAttributes);
    }

    /**
     * @return void
     */
    public function afterFind()
    {
        $this->data = $this->idbSecurity->decryptByPasswordSpeed(base64_decode($this->data), $this->dataPassword);

        parent::afterFind();
    }
}

################################################################################
#                                End of file                                   #
################################################################################

