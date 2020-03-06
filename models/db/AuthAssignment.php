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
use idbyii2\helpers\Translate;
use Yii;
use yii\db\ActiveRecord;

################################################################################
# Class(es)                                                                    #
################################################################################

/**
 * This is the model class for table "auth_assignment".
 *
 * @property string   $item_name
 * @property string   $user_id
 * @property int      $created_at
 *
 * @property AuthItem $itemName
 */
class AuthAssignment extends ActiveRecord
{

    private $allowAdmin = false;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'auth_assignment';
    }

    /**
     * Allow admin actions
     */
    public function enableAdmin()
    {
        $this->allowAdmin = true;
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['item_name', 'user_id'], 'required'],
            [['created_at'], 'default', 'value' => null],
            [['created_at'], 'integer'],
            [['item_name'], 'string', 'max' => 64],
            [['user_id'], 'string', 'max' => 255],
            [['item_name', 'user_id'], 'unique', 'targetAttribute' => ['item_name', 'user_id']],
            [
                ['item_name'],
                'exist',
                'skipOnError' => true,
                'targetClass' => RolesModel::className(),
                'targetAttribute' => ['item_name' => 'name']
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'item_name' => Translate::_('idbyii2', 'Item Name'),
            'user_id' => Translate::_('idbyii2', 'User ID'),
            'created_at' => Translate::_('idbyii2', 'Created at'),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function beforeSave($insert)
    {
        if (
            (strpos($this->item_name, 'idb_') !== 0)
            || ($this->allowAdmin)
        ) {
            if (parent::beforeSave($insert)) {
                return true;
            }
        } else {
            throw new Exception(Translate::_('idbyii2', 'You do not have permission to perform this action.'));
        }

        return false;
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getItemName()
    {
        return $this->hasOne(AuthItem::className(), ['name' => 'item_name']);
    }

    /**
     * @param $permissionName
     * @param $oid
     * @param $uid
     *
     * @return \yii\rbac\Assignment
     * @throws \Exception
     */
    public function assignPermissionForOrganization($permissionName, $oid, $uid)
    {
        return Yii::$app->authManager->assign($permissionName, IdbAccountId::generateAssignOrganization($oid, $uid));
    }

    /**
     * @param $permissionName
     * @param $oid
     * @param $aid
     * @param $uid
     *
     * @return \yii\rbac\Assignment
     * @throws \Exception
     */
    public function assignPermissionForAccount($permissionName, $oid, $aid, $uid)
    {
        return Yii::$app->authManager->assign($permissionName, IdbAccountId::generateAssignAccount($oid, $aid, $uid));
    }

    /**
     * @param $permissionName
     * @param $oid
     * @param $aid
     * @param $dbid
     * @param $uid
     *
     * @return \yii\rbac\Assignment
     * @throws \Exception
     */
    public function assignPermissionForDatabase($permissionName, $oid, $aid, $dbid, $uid)
    {
        return Yii::$app->authManager->assign(
            $permissionName,
            IdbAccountId::generateAssignDatabase($oid, $aid, $dbid, $uid)
        );
    }

    /**
     * @param $permissionName
     * @param $oid
     * @param $uid
     *
     * @return bool
     * @throws \Exception
     */
    public function canPermissionForOrganization($permissionName, $oid, $uid)
    {
        return Yii::$app->authManager->checkAccess(
            IdbAccountId::generateAssignOrganization($oid, $uid),
            $permissionName
        );
    }

    /**
     * @param $permissionName
     * @param $oid
     * @param $aid
     * @param $uid
     *
     * @return bool
     * @throws \Exception
     */
    public function canPermissionForAccount($permissionName, $oid, $aid, $uid)
    {
        return Yii::$app->authManager->checkAccess(
            IdbAccountId::generateAssignAccount($oid, $aid, $uid),
            $permissionName
        );
    }

    /**
     * @param $permissionName
     * @param $oid
     * @param $aid
     * @param $dbid
     * @param $uid
     *
     * @return bool
     * @throws \Exception
     */
    public function canPermissionForDatabase($permissionName, $oid, $aid, $dbid, $uid)
    {
        return Yii::$app->authManager->checkAccess(
            IdbAccountId::generateAssignDatabase($oid, $aid, $dbid, $uid),
            $permissionName
        );
    }
}

################################################################################
#                                End of file                                   #
################################################################################
