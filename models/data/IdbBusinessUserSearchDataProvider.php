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
# Use(s)                                                                       #
################################################################################

use idbyii2\models\db\SearchBusinessUserData;
use yii\data\ActiveDataProvider;
use yii\data\BaseDataProvider;

################################################################################
# Class(es)                                                                    #
################################################################################

/**
 * Class IdbBusinessUserSearchDataProvider
 *
 * @package idbyii2\models\data
 */
class IdbBusinessUserSearchDataProvider extends BaseDataProvider
{

    public $query;

    /**
     * @param null $conditions
     *
     * @return void
     */
    public function init($conditions = null)
    {
        $this->initQuery($conditions);
        parent::init();
    }

    /**
     * @param $conditions
     *
     * @return void
     */
    protected function initQuery($conditions)
    {
        $this->query = SearchBusinessUserData::find();
        if ((is_array($conditions)) && !empty($conditions)) {
            foreach ($conditions as $key => $value) {
                $searchModel = SearchBusinessUserData::instantiate
                (
                    [
                        'key' => $key,
                        'value' => $value,
                    ]
                );
                $this->query->where(['key_hash' => $searchModel->key_hash, 'value_hash' => $searchModel->value_hash]);
            }
        } else {
            $this->query->where(['key_hash' => 'key_hash', 'value_hash' => 'value_hash']);
        }
    }

    /**
     * @return mixed
     */
    protected function prepareModels()
    {
        $provider = new ActiveDataProvider
        (
            [
                'query' => $this->query
            ]
        );
        $pagination = $this->getPagination();
        $pagination->setPageSize(20);
        $provider->setPagination($pagination);

        return $provider->getModels();
    }

    /**
     * @param $models
     *
     * @return array
     */
    protected function prepareKeys($models)
    {
        return array_keys($models);
    }

    /**
     * @return mixed
     */
    protected function prepareTotalCount()
    {
        return $this->query->count();
    }
}

################################################################################
#                                End of file                                   #
################################################################################
