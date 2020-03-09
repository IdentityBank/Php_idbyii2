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
use Yii;
use yii\data\BaseDataProvider;
use yii\helpers\ArrayHelper;

################################################################################
# Class(es)                                                                    #
################################################################################

/**
 * Class IdbillCreditDataProvider
 *
 * @package idbyii2\models\data
 */
class IdbillBusinessDataProvider extends BaseDataProvider
{

    public $model;
    public $sort;
    private $filterExpr;

    /**
     * IdbillCreditDataProvider constructor.
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
        if (empty($models)) {
            return [];
        }

        return array_keys($models);
    }

    /**
     * @param $model
     *
     * @return array
     */
    private function getBusinessPackageKeys()
    {
        return [
            0 => 'id',
            1 => 'bid',
            2 => 'package_id',
            3 => 'payment_log_id',
            4 => 'credits',
            5 => 'base_credits',
            6 => 'additional_credits',
            7 => 'duration',
            8 => 'start_date',
            9 => 'end_date',
            10 => 'last_payment',
            11 => 'next_payment',
            12 => 'account_type'
        ];
    }

    /**
     * @return array
     */
    protected function prepareModels()
    {
        $data = $this->model->setPagination($this->pagination->getPage(), $this->pagination->getPageSize())->findAllbusinessPackages()['QueryData'];
        $keys = $this->getBusinessPackageKeys();
        foreach($data as $key => $businessPackage) {
            $data[$key] = array_combine($keys, $businessPackage);
        }

        return $data;
    }

    /**
     * @return int
     */
    protected function prepareTotalCount()
    {
        $data = $this->model->setPagination($this->pagination->getPage(), $this->pagination->getPageSize())->findAllbusinessPackages();

        return $data['CountAll'][0][0];
    }
}

################################################################################
#                                End of file                                   #
################################################################################
