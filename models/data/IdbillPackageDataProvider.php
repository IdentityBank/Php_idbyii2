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

use idbyii2\models\idb\BillingIdbBillingClient;
use yii\data\BaseDataProvider;

################################################################################
# Class(es)                                                                    #
################################################################################

/**
 * Class IdbillPackageDataProvider
 *
 * @package idbyii2\models\data
 */
class IdbillPackageDataProvider extends BaseDataProvider
{

    public $model;
    public $sort;
    private $filterExpr;

    /**
     * IdbillPackageDataProvider constructor.
     *
     * @param $accountName
     */
    public function __construct()
    {
        $this->sort = null;
        $this->filterExpr = null;
        $this->model = BillingIdbBillingClient::model();
    }

    /**
     * @param null $conditions
     *
     * @return void
     */
    public function init($conditions = null)
    {
        parent::init();
    }

    /**
     * @param array $models
     *
     * @return array
     */
    protected function prepareKeys($models)
    {
        $keyMap = [
            0 => 'id',
            1 => 'order',
            2 => 'credits',
            3 => 'duration',
            4 => 'name',
            5 => 'price',
            6 => 'currency',
            7 => 'image',
            8 => 'included',
            9 => 'excluded',
            10 => 'tag',
            11 => 'active',
        ];
        if ($models) {
            return $keyMap;
        } else {
            return [];
        }
    }

    /**
     * @return array
     */
    protected function prepareModels()
    {
        $data = $this->model->setPagination($this->pagination->getPage(), $this->pagination->getPageSize())->getCountAllPackages();
        $keys = $this->prepareKeys($data['QueryData']);

        for ($i = 0; $i < count($data['QueryData']); $i++) {
            $data['QueryData'][$i] = array_combine($keys, $data['QueryData'][$i]);
        }

        return $data['QueryData'];
    }

    /**
     * @return int
     */
    protected function prepareTotalCount()
    {
        $data = $this->model->setPagination($this->pagination->getPage(), $this->pagination->getPageSize())->getCountAllPackages();

        return $data['CountAll'][0][0];
    }
}

################################################################################
#                                End of file                                   #
################################################################################
