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

use idbyii2\models\db\SearchBusinessUserAccount;
use yii\data\ActiveDataProvider;
use yii\data\BaseDataProvider;

################################################################################
# Class(es)                                                                    #
################################################################################

/**
 * Class IdbUserDataProvider
 *
 * @package idbyii2\models\data
 */
class IdbBusinessUserDataSearchUserAccountProvider extends BaseDataProvider
{

    private $uids;
    private $attributes;
    private $query;

    /**
     * @param null $uids
     * @param null $attributes
     *
     * @throws \yii\base\Exception
     */
    public function init($uids = null, $attributes = null)
    {
        $this->uids = $uids;
        $this->attributes = $attributes;
    }

    /**
     * @return array
     * @throws \yii\base\Exception
     */
    protected function prepareModels()
    {

        if (empty($this->uids)) {
            $this->query = SearchBusinessUserAccount::find();
        } else {
            $uidHashes = [];
            $uidHashesLut = [];
            foreach ($this->uids as $uid) {
                $searchModel = SearchBusinessUserAccount::instantiate(['uid' => $uid]);
                $uidHashes[] = $searchModel->uid_hash;
                $uidHashesLut[$searchModel->uid_hash] = $uid;
            }
            $this->query = SearchBusinessUserAccount::find()->where(['uid_hash' => $uidHashes]);
        }

        $provider = new ActiveDataProvider
        (
            [
                'query' => $this->query
            ]
        );

        $pagination = $this->getPagination();
        $pagination->setPageSize(5);
        $provider->setPagination($pagination);

        $modelsUserData = $models = $provider->getModels();
        if (!empty($this->uids)) {
            $modelsUserData = [];
            $models = $provider->getModels();
            foreach ($models as $model) {
                $uid = $uidHashesLut[$model->uid_hash];
                $modelsUserData[] = IdbBusinessUserData::find($uid, $this->attributes);
            }
        }

        return $modelsUserData;
    }

    /**
     * @param array $models
     *
     * @return array
     */
    protected function prepareKeys($models)
    {
        return array_keys($models);
    }

    /**
     * @return int
     */
    protected function prepareTotalCount()
    {
        return $this->query->count();
    }
}

################################################################################
#                                End of file                                   #
################################################################################
