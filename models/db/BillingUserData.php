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
 * Class BillingUserData
 *
 * @package idbyii2\models\db
 */
class BillingUserData extends UserData
{

    /**
     * @param $key
     * @param $value
     *
     * @return void
     */
    public function addSearch($key, $value)
    {
        SearchBillingUserData::addUserKeyValue($this->uid, $key, $value);
    }

    /**
     * @return void
     */
    public function clearUserKey()
    {
        SearchBillingUserData::clearUserKey($this->uid, $this->key);
    }

    /**
     * @param null  $condition
     * @param array $params
     *
     * @return void
     */
    public static function deleteAll($condition = null, $params = [])
    {
        if (!empty($condition['uid']) && (count($condition) == 1)) {
            SearchBillingUserData::clearUser($condition['uid']);
        }
        parent::deleteAll($condition, $params);
    }
}

################################################################################
#                                End of file                                   #
################################################################################
