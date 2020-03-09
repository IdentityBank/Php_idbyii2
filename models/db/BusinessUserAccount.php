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

use idbyii2\models\identity\IdbBusinessUser;

################################################################################
# Class(es)                                                                    #
################################################################################

/**
 * Class BusinessUserAccount
 *
 * @package idbyii2\models\db
 */
class BusinessUserAccount extends UserAccount
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
        $searchModel = SearchBusinessUserAccount::instantiate
        (
            [
                'uid' => $this->uid,
                'login' => $this->login,
            ]
        );
        $searchModel->save();
    }

    /**
     * @return void
     */
    public function deleteSearch()
    {
        $searchModel = SearchBusinessUserAccount::instantiate
        (
            [
                'uid' => $this->uid,
            ]
        );
        $searchModel = SearchBusinessUserAccount::findOne(['uid_hash' => $searchModel->uid_hash]);
        if ($searchModel) {
            $searchModel->delete();
        }
    }

    /**
     * @param $uid
     *
     * @return void
     */
    public static function deleteByUid($uid)
    {
        $searchModel = SearchBusinessUserAccount::instantiate
        (
            [
                'uid' => $uid,
            ]
        );
        $searchModel = SearchBusinessUserAccount::findOne(['uid_hash' => $searchModel->uid_hash]);
        if ($searchModel) {
            $model = self::findOne(['login' => $searchModel->login_hash]);
            if ($model) {
                $model->delete();
            }
        }
    }

    /**
     * @return bool
     */
    public function getUserData()
    {
        if ($this->_userData === false) {
            $this->_userData = $this->hasMany(BusinessUserData::className(), ['uid' => 'uid']);
        }

        return $this->_userData;
    }

    /**
     * @return bool
     */
    public function getUserDataProvider()
    {
        if ($this->_userDataProvider === false) {
            $this->_userDataProvider = $this->hasMany(BusinessUserData::className(), ['uid' => 'uid'])->where(
                ['key_hash' => IdbBusinessUser::getUserDataKeysProvider($this->uid)]
            );
        }

        return $this->_userDataProvider;
    }

    /**
     * @param $key
     *
     * @return mixed|string
     */
    public function getUserDataProviderValue($key)
    {
        if ($this->_userDataProviderKeyValuePair === false) {
            $this->_userDataProviderKeyValuePair = [];
            if (is_array($this->userDataProvider)) {
                $userDataProvider = $this->userDataProvider;
                foreach ($userDataProvider as $data) {
                    $this->_userDataProviderKeyValuePair[$data->getKey()] = $data->getValue();
                }
            }
        }

        return ((!empty($this->_userDataProviderKeyValuePair[$key])) ? $this->_userDataProviderKeyValuePair[$key] : '');
    }
}

################################################################################
#                                End of file                                   #
################################################################################
