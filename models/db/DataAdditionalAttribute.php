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
use yii\db\ActiveRecord;

################################################################################
# Class(es)                                                                    #
################################################################################

/**
 * This is the model class for table "data_additional_attributes".
 *
 * @property int           $daid
 * @property int           $oid
 * @property string        $object_type
 * @property string        $value
 *
 * @property DataAttribute $dataAttribute
 */
class DataAdditionalAttribute extends ActiveRecord
{

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'data_additional_attributes';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['daid', 'oid', 'object_type', 'value'], 'required'],
            [['daid', 'oid'], 'default', 'value' => null],
            [['daid', 'oid'], 'integer'],
            [['object_type'], 'string'],
            [['value'], 'string', 'max' => 255],
            [['daid', 'oid', 'object_type'], 'unique', 'targetAttribute' => ['daid', 'oid', 'object_type']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'daid' => 'Daid',
            'oid' => 'Oid',
            'object_type' => Translate::_('idbyii2', 'Object Type'),
            'value' => Translate::_('idbyii2', 'Value'),
        ];
    }

    public function getDataAttribute()
    {
        return $this->hasOne(DataAttribute::class, ['id' => 'daid']);
    }

}

################################################################################
#                                End of file                                   #
################################################################################
