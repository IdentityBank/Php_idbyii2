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

################################################################################
# Class(es)                                                                    #
################################################################################

/**
 * Class BillingUserAccount
 *
 * @package idbyii2\models\db
 */
class BillingUserAccount extends UserAccount
{

    /**
     * @param null $row
     *
     * @return static
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
     * @return void
     */
    public function addSearch()
    {
        $searchModel = SearchBillingUserAccount::instantiate
        (
            [
                'uid' => $this->uid,
                'login' => $this->login,
            ]
        );
        $searchModel->save();
    }

    /**
     * @return mixed|void
     * @throws \Throwable
     * @throws \yii\db\StaleObjectException
     */
    public function deleteSearch()
    {
        $searchModel = SearchBillingUserAccount::instantiate
        (
            [
                'uid' => $this->uid,
            ]
        );
        $searchModel = SearchBillingUserAccount::findOne(['uid_hash' => $searchModel->uid_hash]);
        if ($searchModel) {
            $searchModel->delete();
        }
    }

    /**
     * @param $uid
     *
     * @return void
     * @throws \Throwable
     * @throws \yii\db\StaleObjectException
     */
    public static function deleteByUid($uid)
    {
        $searchModel = SearchBillingUserAccount::instantiate
        (
            [
                'uid' => $uid,
            ]
        );
        $searchModel = SearchBillingUserAccount::findOne(['uid_hash' => $searchModel->uid_hash]);
        if ($searchModel) {
            $model = self::findOne(['login' => $searchModel->login_hash]);
            if ($model) {
                $model->delete();
            }
        }
    }
}

################################################################################
#                                End of file                                   #
################################################################################
