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

################################################################################
# Class(es)                                                                    #
################################################################################

/**
 * This is the model class for table "idb_organization".
 *
 * @property string $oid
 * @property string $name
 * @property string $payment_token
 * @property string $created_at
 * @property string $updated_at
 */
class BusinessOrganization extends BusinessModel
{

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'idb_organization';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['oid', 'name'], 'required'],
            [['name'], 'string'],
            [['created_at', 'updated_at'], 'safe'],
            [['oid'], 'string', 'max' => 255],
            [['oid'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'oid' => Translate::_('idbyii2', 'Organization ID'),
            'name' => Translate::_('idbyii2', 'Name'),
            'payment_token' => Translate::_('idbyii2', 'Payment Token'),
            'created_at' => Translate::_('idbyii2', 'Created at'),
            'updated_at' => Translate::_('idbyii2', 'Updated at')
        ];
    }

    /**
     * @param null $row
     *
     * @return \idbyii2\models\db\BusinessOrganization
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
     * @param $name
     *
     * @return \idbyii2\models\db\BusinessOrganization
     * @throws \Exception
     */
    public static function createOrganizationByName($name)
    {
        $organization = new self();
        $organization->name = $name;
        $organization->oid = $organization->newUid($name . 'oid', 'oid');
        $organization->save();

        return $organization;
    }
}

################################################################################
#                                End of file                                   #
################################################################################
