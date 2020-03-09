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

namespace idbyii2\models\data;

################################################################################
# Class(es)                                                                    #
################################################################################

/**
 * Class IdbUserData
 *
 * @package idbyii2\models\data
 */
abstract class IdbUserData
{

    private $uid;
    private $attributes;
    private $attributesHashes;
    private $data = [];
    private $query;

    /**
     * @param      $uid
     * @param null $attributes
     *
     * @return \idbyii2\models\data\IdbUserData
     */
    public static function find($uid, $attributes = null)
    {
        $model = new static();
        $model->findUserData($uid, $attributes);

        return $model;
    }

    /**
     * @param      $uid
     * @param null $attributes
     *
     * @return mixed
     */
    public function findUserData($uid, $attributes = null)
    {
        $this->uid = $uid;
        $this->query = static::findUserDataModel()->where(['uid' => $uid]);
        if (!empty($attributes) && is_array($attributes)) {
            $this->attributes = $attributes;
            $userData = static::instantiateUserDataModel();
            foreach ($attributes as $attribute) {
                $this->attributesHashes[] = $userData->getKeyHash($uid, $attribute);
            }
            $this->query->andWhere(['key_hash' => $this->attributesHashes]);
        }
        $dataItems = $this->query->all();
        foreach ($dataItems as $dataItem) {
            $this->data[$dataItem->key] = $dataItem->value;
        }

        return $this->query;
    }

    public function getUserId()
    {
        return $this->uid;
    }

    public function getValue($key)
    {
        return $this->data[$key] ?? null;
    }

    /**
     * @return mixed
     */
    protected abstract static function findUserDataModel();

    /**
     * @return mixed
     */
    protected abstract static function instantiateUserDataModel();
}

################################################################################
#                                End of file                                   #
################################################################################
