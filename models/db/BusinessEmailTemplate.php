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
use yii\db\ActiveRecord;

################################################################################
# Class(es)                                                                    #
################################################################################

/**
 * This is the model class for table "p57b_business.email_templates".
 *
 * @property int    $id
 * @property string $oid
 * @property string $action_type
 * @property string $created_at
 * @property string $path
 * @property string $title
 * @property bool   $active
 * @property string $language
 */
class BusinessEmailTemplate extends ActiveRecord
{

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'p57b_business.email_templates';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['oid', 'action_type', 'path', 'language'], 'required'],
            [['action_type', 'language'], 'string'],
            [['created_at'], 'safe'],
            [['active'], 'boolean'],
            [['oid', 'title'], 'string', 'max' => 255],
            [['path'], 'string', 'max' => 1024],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Translate::_('idbyii2', 'ID'),
            'oid' => Translate::_('idbyii2', 'Oid'),
            'action_type' => Translate::_('idbyii2', 'Action Type'),
            'created_at' => Translate::_('idbyii2', 'Created at'),
            'path' => Translate::_('idbyii2', 'Path'),
            'title' => Translate::_('idbyii2', 'Title'),
            'active' => Translate::_('idbyii2', 'Active'),
            'language' => Translate::_('idbyii2', 'Language')
        ];
    }
}

################################################################################
#                                End of file                                   #
################################################################################
