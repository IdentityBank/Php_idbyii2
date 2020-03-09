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
use idbyii2\enums\DataObjectType;

################################################################################
# Class(es)                                                                    #
################################################################################

/**
 * This is the model class for table "data_sets_objects".
 *
 * @property int              $id
 * @property int              $dsid
 * @property int              $oid
 * @property string           $object_type
 * @property string           $display_name
 * @property int              $order
 * @property int              $required
 * @property string           $used_for
 *
 * @property DataSet          $dataSet
 * @property DataSet|DataType $object
 */
class DataSetObject extends IdbModel
{

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'data_sets_objects';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['dsid', 'oid'], 'required'],
            [['dsid', 'oid', 'order', 'required'], 'default', 'value' => null],
            [['dsid', 'oid', 'order', 'required'], 'integer'],
            [['object_type', 'display_name', 'used_for'], 'string'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'dsid' => 'Dsid',
            'oid' => 'Oid',
            'object_type' => Translate::_('idbyii2', 'Object Type'),
            'display_name' => Translate::_('idbyii2', 'Display Name'),
            'order' => Translate::_('idbyii2', 'Order'),
            'required' => Translate::_('idbyii2', 'Required'),
            'used_for' => Translate::_('idbyii2', 'Audit Log'),
        ];
    }

    public function getDataSet()
    {
        return $this->hasOne(DataSet::class, ['id' => 'dsid']);
    }

    public function getObject()
    {
        switch ($this->object_type) {
            case DataObjectType::TYPE:
                return $this->hasOne(DataType::class, ['id' => 'oid']);
            case DataObjectType::SET:
                return $this->hasOne(DataSet::class, ['id' => 'oid']);
            default:
                return null;
        }
    }
}

################################################################################
#                                End of file                                   #
################################################################################
