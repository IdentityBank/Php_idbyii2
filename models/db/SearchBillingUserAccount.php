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
use Yii;

################################################################################
# Class(es)                                                                    #
################################################################################

/**
 * Class SearchPeopleUserAccount
 *
 * @package idbyii2\models\db
 */
class SearchBillingUserAccount extends BillingSearchModel
{

    /**
     * @return string
     */
    public static function tableName()
    {
        return 'p57b_search.billing_user_account';
    }

    /**
     * @return array
     */
    public function rules()
    {
        return [
            [['uid_hash', 'login_hash'], 'required'],
            [['uid_hash', 'login_hash'], 'string', 'max' => 255],
            [['uid_hash', 'login_hash'], 'unique', 'targetAttribute' => ['uid_hash', 'login_hash']],
        ];
    }

    /**
     * @return array
     */
    public function attributeLabels()
    {
        return [
            'uid_hash' => Translate::_('idbyii2', 'User ID'),
            'login_hash' => Translate::_('idbyii2', 'Login'),
        ];
    }

    /**
     * @param null $row
     *
     * @return static
     */
    public static function instantiate($row = null)
    {
        $model = new static();
        if (empty($row['uid_hash']) && !empty($row['uid'])) {
            $row['uid_hash'] = $model->generateUidHash($row['uid']);
        }
        if (empty($row['login_hash']) && !empty($row['login'])) {
            $row['login_hash'] = $row['login'];
        }
        if (is_array($row) && !empty($row)) {
            $model->setAttributes($row, false);
        }

        return $model;
    }

    /**
     * @return void
     */
    public function afterFind()
    {
        $this->login_hash = $this->idbSecurity->decryptByPasswordSpeed(
            base64_decode($this->login_hash),
            $this->uid_hash . $this->searchPassword
        );
        parent::afterFind();
    }

    /**
     * @return bool
     */
    public function beforeValidate()
    {
        if (parent::beforeValidate()) {
            $this->login_hash = base64_encode(
                $this->idbSecurity->encryptByPasswordSpeed($this->login_hash, $this->uid_hash . $this->searchPassword)
            );

            return true;
        }

        return false;
    }

    /**
     * @param $uid
     *
     * @return mixed
     */
    protected function generateUidHash($uid)
    {
        return $this->idbSecurity->secureHash($uid, $this->searchPassword, ($this->blowfishCost));
    }

    /**
     * @param $model
     *
     * @return void
     */
    private static function refreshModel($model)
    {
        $searchModel = self::instantiate
        (
            [
                'uid' => $model->uid,
                'login' => $model->login,
            ]
        );
        $searchModel->save();
    }

    /**
     * @return void
     */
    public static function refreshAll()
    {
        Yii::$app->db->createCommand()->truncateTable(self::tableName())->execute();

        $query = BillingUserAccount::find();
        $totalCount = $query->count();

        for ($offset = 0; $offset < $totalCount; $offset++) {
            $model = $query->offset($offset)->one();
            self::refreshModel($model);
        }
    }
}

################################################################################
#                                End of file                                   #
################################################################################
