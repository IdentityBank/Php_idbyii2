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
use idbyii2\enums\DataTypeType;

################################################################################
# Class(es)                                                                    #
################################################################################

/**
 * This is the model class for table "data_types".
 *
 * @property int                       $id
 * @property string                    $internal_name
 * @property string                    $display_name
 * @property string                    $data_type
 * @property int                       $searchable
 * @property int                       $sortable
 * @property int                       $sensitive
 * @property int                       $required
 * @property string                    $tag
 * @property string                    $created_at
 * @property DataAdditionalAttribute[] $additionalAttributes
 */
class DataType extends IdbModel
{

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'data_types';
    }

    public function init()
    {
        $this->searchable = 1;
        $this->sortable = 1;
        $this->sensitive = 1;
        $this->required = 1;
        $this->data_type = DataTypeType::STRING;
        parent::init();
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['internal_name', 'display_name'], 'required'],
            [['searchable', 'sortable', 'sensitive', 'required'], 'integer'],
            [['created_at'], 'safe'],
            [['internal_name', 'data_type'], 'string', 'max' => 255],
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
            'data_type' => Translate::_('idbyii2', 'Data Type'),
            'searchable' => Translate::_('idbyii2', 'Searchable'),
            'sortable' => Translate::_('idbyii2', 'Sortable'),
            'sensitive' => Translate::_('idbyii2', 'Sensitive'),
            'required' => Translate::_('idbyii2', 'Required'),
            'tag' => 'Tag',
            'created_at' => Translate::_('idbyii2', 'Created at'),
        ];
    }

    public function getAdditionalAttributes()
    {
        return $this->hasMany(DataAdditionalAttribute::class, ['id', 'oid'])
                    ->andOnCondition(['object_type' => DataObjectType::TYPE]);
    }
}

################################################################################
#                                End of file                                   #
################################################################################
