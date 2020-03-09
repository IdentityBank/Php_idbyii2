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
use yii\db\ActiveRecord;

################################################################################
# Class(es)                                                                    #
################################################################################

/**
 * This is the model class for table "data_sets".
 *
 * @property int                     $id
 * @property string                  $internal_name
 * @property string                  $display_name
 * @property string                  $tag
 * @property string                  $created_at
 *
 * @property DataAdditionalAttribute $additionalAttribute
 * @property DataSetObject[]         $objects
 */
class DataSet extends ActiveRecord
{

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'data_sets';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['internal_name', 'display_name'], 'required'],
            [['display_name', 'tag'], 'string'],
            [['created_at'], 'safe'],
            [['internal_name'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'internal_name' => Translate::_('idbyii2', 'Internal Name'),
            'display_name' => Translate::_('idbyii2', 'Display Name'),
            'tag' => 'Tag',
            'created_at' => Translate::_('idbyii2', 'Created at'),
        ];
    }

    public function init()
    {

        parent::init();
    }

    public function getAdditionalAttribute()
    {
        return $this->hasMany(DataAdditionalAttribute::class, ['id', 'oid'])
                    ->andOnCondition(['object_type' => DataObjectType::SET]);
    }

    public function getObjects()
    {
        return $this->hasMany(DataSetObject::class, ['dsid' => 'id']);
    }
}

################################################################################
#                                End of file                                   #
################################################################################
