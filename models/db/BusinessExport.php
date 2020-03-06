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
use idbyii2\helpers\Translate;
use Yii;

################################################################################
# Class(es)                                                                    #
################################################################################

/**
 * This is the model class for table "import".
 *
 * @property int    $id
 * @property string $uid
 * @property string $oid
 * @property string $aid
 * @property string $dbid
 * @property string $created_at
 * @property string $downloaded_at
 * @property string $file_name
 * @property string $file_path
 * @property string $status
 * @property string $attributes
 * @property string $url
 *
 */
class BusinessExport extends IdbModel
{

    protected $idbSecurity;
    protected $blowfishCost = 1;
    protected $loginPassword = "password";
    protected $uidPassword = "password";

    /**
     * @return void
     */
    public function init()
    {
        $this->idbSecurity = new IdbSecurity(Yii::$app->security);
        parent::init();
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
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'export';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['created_at', 'downloaded_at'], 'safe'],
            [['file_name', 'status', 'uid'], 'required'],
            [['status'], 'string'],
            [['file_name', 'file_path', 'url'], 'string', 'max' => 255],
            [['attributes'], 'string', 'max' => 2044],
            [['file_name'], 'unique'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Translate::_('idbyii2', 'ID'),
            'created_at' => Translate::_('idbyii2', 'Created at'),
            'file_name' => Translate::_('idbyii2', 'File Name'),
            'file_path' => Translate::_('idbyii2', 'File Path'),
            'status' => Translate::_('idbyii2', 'Status'),
            'downloaded_at' => Translate::_('idbyii2', 'Downloaded at'),
            'url' => Translate::_('idbyii2', 'Download link'),
        ];
    }

    /**
     * @return void
     */
    public function afterFind()
    {
        $this->attributes = $this->idbSecurity->decryptByPassword(base64_decode($this->attributes), $this->uidPassword);
        $this->oid = $this->idbSecurity->decryptByPassword(base64_decode($this->oid), $this->uidPassword);
        $this->dbid = $this->idbSecurity->decryptByPassword(base64_decode($this->dbid), $this->uidPassword);
        $this->aid = $this->idbSecurity->decryptByPassword(base64_decode($this->aid), $this->uidPassword);
        parent::afterFind();
    }

    /**
     * @param $insert
     *
     * @return bool
     */
    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            $this->attributes = base64_encode(
                $this->idbSecurity->encryptByPassword($this->attributes, $this->uidPassword)
            );
            $this->oid = base64_encode($this->idbSecurity->encryptByPassword($this->oid, $this->uidPassword));
            $this->dbid = base64_encode($this->idbSecurity->encryptByPassword($this->dbid, $this->uidPassword));
            $this->aid = base64_encode($this->idbSecurity->encryptByPassword($this->aid, $this->uidPassword));

            return true;
        }

        return false;
    }

    /**
     * @return string
     */
    public function getFullPath()
    {
        return $this->file_path . $this->file_name . '.csv';
    }
}

################################################################################
#                                End of file                                   #
################################################################################
