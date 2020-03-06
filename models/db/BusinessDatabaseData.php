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

use Exception;
use idbyii2\helpers\IdbAccountId;
use idbyii2\helpers\Localization;
use idbyii2\helpers\Translate;
use idbyii2\helpers\Uuid;

################################################################################
# Class(es)                                                                    #
################################################################################

/**
 * This is the model class for table "idb_database_data".
 *
 * @property string $business_db_id
 * @property string $idb_data_id
 */
class BusinessDatabaseData extends BusinessModel
{

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'idb_database_data';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['business_db_id', 'idb_data_id'], 'required'],
            [['business_db_id'], 'string', 'max' => 1024],
            [['idb_data_id'], 'string', 'max' => 63],
            [['idb_data_id'], 'unique'],
            [['business_db_id', 'idb_data_id'], 'unique', 'targetAttribute' => ['business_db_id', 'idb_data_id']],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'business_db_id' => Translate::_('idbyii2', 'Business Vault ID'),
            'idb_data_id' => Translate::_('idbyii2', 'IDB Data ID'),
        ];
    }

    /**
     * @param $oid
     * @param $aid
     * @param $dbid
     *
     * @return \idbyii2\models\db\BusinessDatabaseData
     * @throws \Exception
     */
    public static function createBusinessDbIdItems($oid, $aid, $dbid)
    {
        $businessDbId = IdbAccountId::generateBusinessDbId($oid, $aid, $dbid);

        return self::create($businessDbId);
    }

    public static function create($businessDbId)
    {
        $database = BusinessDatabaseData::findOne($businessDbId);
        if ($database instanceof BusinessDatabaseData) {
            throw new Exception('IDB ID vault already created!');
        } else {

            $database = new BusinessDatabaseData();
            $database->business_db_id = $businessDbId;
            do {
                $timestamp = Localization::getDateTimeNumberString();
                $database->idb_data_id = Uuid::uuid5($timestamp . $database->business_db_id)->string;
            } while (!$database->save());

            return $database->idb_data_id;
        }
    }

    /**
     * @param string $businessId
     *
     * @return string|null
     */
    public static function getDatabaseNameByBusinessId(string $businessId)
    {
        if (!empty($businessId)) {
            $database = BusinessDatabaseData::findOne(['business_db_id' => $businessId]);

            if ($database instanceof BusinessDatabaseData) {
                return $database->idb_data_id;
            }
        }

        return null;
    }

}
