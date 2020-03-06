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

use idbyii2\helpers\Translate;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

################################################################################
# Class(es)                                                                    #
################################################################################

/**
 * This is the model class for table "auth_item".
 *
 * @property string           $name
 * @property int              $type
 * @property string           $description
 * @property string           $rule_name
 * @property resource         $data
 * @property int              $created_at
 * @property int              $updated_at
 *
 * @property AuthAssignment[] $authAssignments
 * @property AuthRule         $ruleName
 * @property AuthItemChild[]  $authItemChildren
 * @property AuthItemChild[]  $authItemChildren0
 * @property RolesModel[]     $children
 * @property RolesModel[]     $parents
 */
class RolesModel extends ActiveRecord
{

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'auth_item';
    }

    public function behaviors()
    {
        return [
            TimestampBehavior::className(),
            'timestamp' => [
                'class' => 'yii\behaviors\TimestampBehavior',
                'attributes' => [
                    ActiveRecord::EVENT_BEFORE_INSERT => ['created_at'],
                    ActiveRecord::EVENT_BEFORE_UPDATE => ['updated_at'],
                ],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['name', 'type'], 'required'],
            [['type', 'created_at', 'updated_at'], 'default', 'value' => null],
            [['type', 'created_at', 'updated_at'], 'integer'],
            [['description', 'data'], 'string'],
            [['name', 'rule_name'], 'string', 'max' => 64],
            [['name'], 'unique'],
            [
                ['rule_name'],
                'exist',
                'skipOnError' => true,
                'targetClass' => AuthRule::className(),
                'targetAttribute' => ['rule_name' => 'name']
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'name' => Translate::_('idbyii2', 'Name'),
            'type' => Translate::_('idbyii2', 'Type'),
            'description' => Translate::_('idbyii2', 'Description'),
            'rule_name' => Translate::_('idbyii2', 'Rule Name'),
            'data' => Translate::_('idbyii2', 'Data'),
            'created_at' => Translate::_('idbyii2', 'Created at'),
            'updated_at' => Translate::_('idbyii2', 'Updated at'),
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAuthAssignments()
    {
        return $this->hasMany(AuthAssignment::className(), ['item_name' => 'name']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRuleName()
    {
        return $this->hasOne(AuthRule::className(), ['name' => 'rule_name']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAuthItemChildren()
    {
        return $this->hasMany(AuthItemChild::className(), ['parent' => 'name']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAuthItemChildren0()
    {
        return $this->hasMany(AuthItemChild::className(), ['child' => 'name']);
    }

    /**
     * @return \yii\db\ActiveQuery
     * @throws \yii\base\InvalidConfigException
     */
    public function getChildren()
    {
        return $this->hasMany(RolesModel::className(), ['name' => 'child'])->viaTable(
            'auth_item_child',
            ['parent' => 'name']
        );
    }

    /**
     * @return \yii\db\ActiveQuery
     * @throws \yii\base\InvalidConfigException
     */
    public function getParents()
    {
        return $this->hasMany(RolesModel::className(), ['name' => 'parent'])->viaTable(
            'auth_item_child',
            ['child' => 'name']
        );
    }

    /**
     * @return array
     */
    public static function getTypes()
    {
        return [
            1 => Translate::_('idbyii2', 'Role'),
            2 => Translate::_('idbyii2', 'Task')
        ];
    }

    /**
     * @param $type
     *
     * @return mixed
     */
    public static function getTypeNameFromValue($type)
    {
        if ($type == 1) {
            return Translate::_('idbyii2', 'Role');
        } elseif ($type == 2) {
            return Translate::_('idbyii2', 'Task');
        } else {
            return Translate::_('idbyii2', 'Undefined');
        }
    }

    /**
     * @return mixed
     */
    public function getTypeName()
    {
        return self::getTypeNameFromValue($this->type);
    }

    /**
     * @param $name
     *
     * @return string
     */
    public static function getDiaplayName($name)
    {
        $name = str_replace('_', ' ', ucwords($name, '_'));
        $name = str_replace('Organization', '', $name);
        $name = trim($name);

        return $name;
    }
}

################################################################################
#                                End of file                                   #
################################################################################
