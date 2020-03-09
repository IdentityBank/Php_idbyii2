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

use Exception;
use idbyii2\models\idb\IdbStorageClient;
use idbyii2\models\idb\IdbStorageItem;
use yii\data\BaseDataProvider;

################################################################################
# Class(es)                                                                    #
################################################################################

/**
 * Class IdbStorageItemDataProvider
 *
 * @package idbyii2\models\data
 */
class IdbStorageItemDataProvider extends BaseDataProvider
{

    public $query;
    public $model;
    public $metadata;
    public $sort;
    private $countTmp;
    private $filterExpr;
    private $exprAttrNames;
    private $exprAttrVal;


    public function __construct()
    {
        $this->filterExpr = null;
        $this->exprAttrNames = null;
        $this->exprAttrVal = null;
        $this->countTmp = -1;
        $this->model = IdbStorageClient::model();
    }

    /**
     * @param $filterExpr
     * @param $exprAttrNames
     * @param $exprAttrVal
     */
    public function setSearch($filterExpr, $exprAttrNames, $exprAttrVal)
    {
        $this->filterExpr = $filterExpr;
        $this->exprAttrNames = $exprAttrNames;
        $this->exprAttrVal = $exprAttrVal;
    }

    /**
     * @param $array
     */
    public function prepareSearch($array, $operator = 'AND', $owner = null)
    {
        $result = [];
        $tmp = &$result;
        $names = [];
        $values = [];
        for ($i = 0; $i < count($array); $i++) {
            if ($i + 1 == count($array)) {
                $tmp = [
                    "o" => $array[$i]['operator'] ?? '=',
                    'l' => '#col' . $i,
                    'r' => ':col' . $i
                ];
            } else {
                $tmp = [
                    "o" => $operator,
                    'l' => [
                        'o' => $array[$i]['operator'] ?? '=',
                        'l' => '#col' . $i,
                        'r' => ':col' . $i
                    ],
                    'r' => []
                ];
            }


            $tmp = &$tmp['r'];
        }

        $counter = 0;
        foreach ($array as $value) {
            $names['#col' . $counter] = $value['column'];
            $values[':col' . $counter] = str_replace('*', '%', $value['value']);
            $counter++;
        }


        if($owner !== null) {
            $result['b'] = '()';
            $result = [
                'o' => 'AND',
                'l' => $result,
                'r' => [
                    'o' => '=',
                    'l' => '#owner',
                    'r' => ':owner'
                ]
            ];

            $names['#owner'] = 'owner';
            $values[':owner'] = $owner;
        }


        $this->exprAttrNames = $names;
        $this->exprAttrVal = $values;
        $this->filterExpr = $result;
    }

    /**
     * @return int
     * @throws Exception
     */
    public function getCountWithoutFilters()
    {
        return 20;
    }

    /**
     * @return array
     */
    protected function prepareModels()
    {
        $data = null;

        $data = $this->model->setPagination($this->pagination->getPage(), $this->pagination->getPageSize())
            ->findStorageItems(
                $this->filterExpr,
                $this->exprAttrNames,
                $this->exprAttrVal
            );
        if (!empty($data['CountAll'][0][0])) {
            $this->countTmp = $data['CountAll'][0][0];
        } else {
            $this->countTmp = -1;
        }

        $models = IdbStorageItem::initMultiple($data['QueryData']);

        return $models;
    }

    /**
     * @param array $models
     *
     * @return array
     */
    protected function prepareKeys($models)
    {
        if ($models) {
            return array_keys($models);
        } else {
            return [];
        }
    }

    /**
     * @return int|mixed
     * @throws Exception
     */
    protected function prepareTotalCount()
    {
        if ($this->countTmp !== -1) {
            $total = $this->countTmp;
        } else {
            $total = $this->model->setPagination($this->pagination->getPage(), $this->pagination->getPageSize())
                ->findStorageItems(
                    $this->filterExpr,
                    $this->exprAttrNames,
                    $this->exprAttrVal
                );
            if (!empty($total['CountAll'][0])) {
                $total = $total['CountAll'][0][0];
            } else {
                $total = -1;
            }
        }

        $this->pagination->totalCount = $total;

        return $total;
    }
}

################################################################################
#                                End of file                                   #
################################################################################
