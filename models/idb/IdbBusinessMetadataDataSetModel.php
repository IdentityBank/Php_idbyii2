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

namespace idbyii2\models\idb;

################################################################################
# Use(s)                                                                       #
################################################################################

use idbyii2\helpers\Translate;
use stdClass;
use Yii;
use yii\base\Model;

################################################################################
# Class(es)                                                                    #
################################################################################

/**
 * Class IdbBusinessMetadataDataModel
 *
 * @package idbyii2\models\idb
 */
class IdbBusinessMetadataDataSetModel extends Model
{

    public $uuid;
    public $internal_name;
    public $display_name;
    public $data_type;
    public $order;
    public $searchable;
    public $sortable;
    public $sensitive;
    public $tag;
    public $used_for;
    public $required;
    public $object_type;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['uuid', 'data_type', 'object_type'], 'safe'],
            [['uuid', 'internal_name', 'data_type', 'object_type'], 'required'],
            [['display_name'], 'string'],
            [['display_name'], 'string', 'max' => 255],
            [['order'], 'integer'],
            [['searchable', 'sortable', 'sensitive', 'required'], 'boolean'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'uuid' => Translate::_('idbyii2', 'UUID'),
            'internal_name' => Translate::_('idbyii2', 'Internal Name'),
            'display_name' => Translate::_('idbyii2', 'Display Name'),
            'data_type' => Translate::_('idbyii2', 'Data type'),
            'object_type' => Translate::_('idbyii2', 'Type'),
            'used_for' => Translate::_('idbyii2', 'Audit Log'),
            'tag' => Translate::_('idbyii2', 'Tag'),
            'order' => Translate::_('idbyii2', 'Order'),
            'searchable' => Translate::_('idbyii2', 'Searchable'),
            'sortable' => Translate::_('idbyii2', 'Sortable'),
            'sensitive' => Translate::_('idbyii2', 'Sensitive'),
            'required' => Translate::_('idbyii2', 'Required'),
        ];
    }

    /**
     * Allow to create 'Data Set' model from metadata object
     *
     * @param \stdClass $data stdClass data extracted from metadata 'data' branch
     *
     * @return \idbyii2\models\idb\IdbBusinessMetadataDataSetModel|null if data model is valid then returns created
     *                                                                  model, null otherwise
     */
    public static function fromData(stdClass $data)
    {
        $model = new static();
        if (!empty($data)) {
            $model->uuid = (($data->uuid) ?? null);
            $model->internal_name = (($data->internal_name) ?? null);
            $model->display_name = (($data->display_name) ?? null);
            $model->data_type = (($data->data_type) ?? null);
            $model->object_type = (($data->object_type) ?? null);
            $model->used_for = (($data->used_for) ?? null);
            $model->tag = (($data->tag) ?? null);
            $model->order = ((intval($data->order)) ?? null);
            $model->searchable = ((intval($data->searchable)) ?? null);
            $model->sortable = ((intval($data->sortable)) ?? null);
            $model->sensitive = ((intval($data->sensitive)) ?? null);
            $model->required = ((intval($data->required)) ?? null);
        }
        if (!$model->validate()) {
            Yii::debug(print_r($model->errors, true), __METHOD__);
            $model = null;
        }

        return $model;
    }

    /**
     * @return string String value of the object
     */
    public function toString()
    {
        return $this->__toString();
    }

    /**
     * @inheritdoc
     */
    public function __toString()
    {
        return "$this->uuid - $this->display_name [$this->internal_name] - $this->data_type - $this->object_type";
    }
}

################################################################################
#                                End of file                                   #
################################################################################
