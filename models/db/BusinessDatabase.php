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
use idbyii2\validators\IdbNameValidator;
use yii\helpers\ArrayHelper;

################################################################################
# Class(es)                                                                    #
################################################################################

/**
 * This is the model class for table "idb_database".
 *
 * @property string $dbid
 * @property string $aid
 * @property string $name
 * @property string $description
 * @property string $created_at
 * @property string $updated_at
 */
class BusinessDatabase extends BusinessModel
{

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'idb_database';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return ArrayHelper::merge(
            [
                [['dbid', 'aid', 'name'], 'required'],
                [['name', 'description'], 'string'],
                [['created_at', 'updated_at'], 'safe'],
                [['dbid', 'aid'], 'string', 'max' => 255],
                [['dbid'], 'unique'],
                [['dbid', 'aid'], 'unique', 'targetAttribute' => ['dbid', 'aid']],
            ],
            IdbNameValidator::customRules('name')
        );
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'dbid' => Translate::_('idbyii2', 'Vault ID'),
            'aid' => Translate::_('idbyii2', 'Account ID'),
            'name' => Translate::_('idbyii2', 'Name'),
            'description' => Translate::_('idbyii2', 'Description'),
            'created_at' => Translate::_('idbyii2', 'Created at'),
            'updated_at' => Translate::_('idbyii2', 'Updated at')
        ];
    }

    /**
     * @param bool $insert
     *
     * @return bool
     */
    public function beforeValidate()
    {
        if (parent::beforeValidate()) {
            $this->name = IdbNameValidator::adjustName($this->name);
            if (empty($this->name)) {
                $this->name = Translate::_('idbyii2', 'Vault');
            }
            $this->description = htmlentities($this->description);

            return true;
        }

        return false;
    }

    /**
     * @param null $row
     *
     * @return BusinessDatabase
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
     * @param \idbyii2\models\db\BusinessAccount $account
     *
     * @return \idbyii2\models\db\BusinessDatabase
     * @throws \Exception
     */
    public static function createDatabaseByAccount(BusinessAccount $account)
    {
        $database = new self();
        $database->name = $account->name;
        $database->aid = $account->aid;
        $database->dbid = $database->newUid($account->name . $account->aid . $account->oid, 'dbid');
        $database->save();

        return $database;
    }

    /**
     * @param string $oid
     *
     * @return array
     */
    public static function findByOrganization(string $oid)
    {
        $databases = BusinessDatabase::find()->all();
        $accounts = BusinessAccount::findAll(['oid' => $oid]);
        $result = [];

        /** @var \idbyii2\models\db\BusinessDatabase $database */
        foreach ($databases as $database) {
            /** @var \idbyii2\models\db\BusinessAccount $account */
            foreach ($accounts as $account) {
                if ($account->aid == $database->aid) {
                    array_push($result, $database);
                }
            }
        }

        return $result;
    }
}

################################################################################
#                                End of file                                   #
################################################################################
