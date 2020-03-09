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

use idbyii2\helpers\IdbAccountId;
use idbyii2\helpers\IdbSecurity;
use Yii;
use yii\db\ActiveRecord;


################################################################################
# Class(es)                                                                    #
################################################################################

/**
 * This is the model class for table "p57b_log.idb_audit_log".
 *
 * @property int    $id
 * @property string $timestamp
 * @property string $tag
 * @property string $idb_data
 * @property string $portal_uuid
 * @property string $business_db_id
 * @property string $message
 */
class IdbAuditLog extends ActiveRecord
{

    private $idbSecurity;
    private $blowfishCost = 1;
    private $idbDataPassword = "password";

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'p57b_log.idb_audit_log';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['timestamp'], 'safe'],
            [['tag', 'idb_data', 'portal_uuid', 'business_db_id', 'message'], 'string'],
            [['idb_data', 'portal_uuid', 'business_db_id', 'message'], 'required'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'timestamp' => 'Timestamp',
            'tag' => 'Tag',
            'idb_data' => 'Idb Data',
            'portal_uuid' => 'Portal Uuid',
            'business_db_id' => 'Business Db ID',
            'message' => 'Message',
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
            if (!empty($values['idbDataPassword'])) {
                $this->idbDataPassword = $values['idbDataPassword'];
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
        if (!empty(Yii::$app->getModule('idbdata')->configAuditLog)) {
            $this->setAttributes(Yii::$app->getModule('idbdata')->configAuditLog);
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

    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {

            $this->idb_data = base64_encode(
                $this->idbSecurity->encryptByPasswordSpeed($this->idb_data, $this->idbDataPassword)
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
        $this->idb_data = $this->idbSecurity->decryptByPasswordSpeed(
            base64_decode($this->idb_data),
            $this->idbDataPassword
        );

        parent::afterSave($insert, $changedAttributes);
    }

    /**
     * @return void
     */
    public function afterFind()
    {
        $this->idb_data = $this->idbSecurity->decryptByPasswordSpeed(
            base64_decode($this->idb_data),
            $this->idbDataPassword
        );

        parent::afterFind();
    }

    public static function saveByArray($data)
    {
        //'idb_data', 'business_db_id',
        foreach ($data['ids'] as $id) {
            $tmp = new self();
            $tmp->message = $data['legal'] . ' - ' . $data['message'];
            $tmp->portal_uuid = Yii::$app->user->identity->id;
            $tmp->idb_data = json_encode(Yii::$app->request);
            $tmp->business_db_id = IdbAccountId::generateBusinessDbUserId(
                Yii::$app->user->identity->oid,
                Yii::$app->user->identity->aid,
                Yii::$app->user->identity->dbid,
                $id
            );
            $tmp->save();
        }
    }
}

################################################################################
#                                End of file                                   #
################################################################################
