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
 * Class IdbBusinessMetadataColumnModel
 *
 * @package idbyii2\models\idb
 */
class IdbBusinessMetadataColumnModel extends Model
{

    public $uuid;
    public $title;
    public $order;
    public $on;
    public $type;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['uuid', 'type'], 'safe'],
            [['uuid', 'type'], 'required'],
            [['title'], 'string'],
            [['title'], 'string', 'max' => 255],
            [['order'], 'integer'],
            [['on'], 'boolean'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'uuid' => Translate::_('idbyii2', 'UUID'),
            'title' => Translate::_('idbyii2', 'Title'),
            'order' => Translate::_('idbyii2', 'Order'),
            'on' => Translate::_('idbyii2', 'Enabled'),
            'type' => Translate::_('idbyii2', 'Type'),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function validateAttribute($model, $attribute)
    {
        switch ($attribute) {
            case 'uuid':
            case 'title':
            case 'order':
            case 'on':
            case 'type':
                {
                }
                break;
            default:
                $this->addError($model, $attribute, Translate::_('idbyii2', '{attribute} is invalid.'));
        }
    }

    /**
     * Allow to create Column model from metadata object
     *
     * @param \stdClass $data stdClass data extracted from metadata 'columns' branch
     *
     * @return \idbyii2\models\idb\IdbBusinessMetadataColumnModel|null if data model is valid then returns created
     *                                                                 model, null otherwise
     */
    public static function fromData(stdClass $data)
    {
        $model = new static();
        if (!empty($data)) {
            $model->uuid = (($data->uuid) ?? null);
            $model->title = (($data->title) ?? null);
            $model->order = ((!empty($data->order)) ? intval($data->order) : null);
            $model->on = ((!empty($data->on)) ? boolval($data->on) : null);
            $model->type = (($data->type) ?? null);
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
        return "$this->uuid - $this->title";
    }
}

################################################################################
#                                End of file                                   #
################################################################################
