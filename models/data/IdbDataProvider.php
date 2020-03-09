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

use idbyii2\helpers\Metadata;
use idbyii2\models\idb\IdbBankClientBusiness;
use Yii;
use yii\data\BaseDataProvider;
use yii\web\NotFoundHttpException;

################################################################################
# Class(es)                                                                    #
################################################################################

/**
 * Class IdbDataProvider
 *
 * @package idbyii2\models\data
 */
class IdbDataProvider extends BaseDataProvider
{

    public $query;
    public $model;
    public $metadata;
    public $sort;
    private $countTmp;
    private $filterExpr;
    private $exprAttrNames;
    private $exprAttrVal;
    private $isPeopleAccessMap = false;
    private $keyId = null;
    private $caseSensitive = true;


    /**
     * IdbDataProvider constructor.
     *
     * @param      $accountName
     * @param null $keyId
     *
     * @throws \Exception
     */
    public function __construct($accountName, $keyId = null)
    {
        $this->sort = null;
        $this->filterExpr = null;
        $this->exprAttrNames = null;
        $this->exprAttrVal = null;
        $this->countTmp = -1;
        $this->model = IdbBankClientBusiness::model($accountName);
        $this->metadata = json_decode($this->model->getAccountMetadata()['Metadata'], true);
        $this->keyId = $keyId;
        if (
            isset($this->metadata['options']['case_sensitive'])
            && $this->metadata['options']['case_sensitive'] === 'off'
        ) {
            $this->caseSensitive = false;
        }
    }

    /**
     * @param null $conditions
     */
    public function init($conditions = null)
    {
        parent::init();

        if (is_array($conditions) && array_key_exists('isPeopleAccessMap', $conditions)) {
            $this->isPeopleAccessMap = $conditions['isPeopleAccessMap'];

            if (Metadata::hasPeopleAccessMap($this->metadata)) {
                $this->metadata['settings'] = [];
                foreach ($this->metadata['PeopleAccessMap'] as $column => $column_uuid) {
                    $this->metadata['settings'][$column_uuid] = 'on';
                }
            }
        }
    }

    /**
     * @return array
     * @throws NotFoundHttpException
     */
    protected function prepareModels()
    {
        $data = null;
        $order = [];
        $order['database'] [] = 'id';
        if (
            !empty(Yii::$app->user)
            && is_null($this->keyId)
        ) {
            $keyId = Yii::$app->user->identity->id;
        } else {
            $keyId = $this->keyId;
        }
        if (!empty($this->metadata['settings']) && !empty($this->metadata['settings'][$keyId])) {
            foreach ($this->metadata['settings'][$keyId] as $key => $value) {
                $order['database'] [] = $key;
            }
        } elseif (!empty($this->metadata['database'])) {
            foreach ($this->metadata['database'] as $column) {
                $order['database'][] = $column['uuid'];
            }
        } else {
            throw new NotFoundHttpException(404);
        }
        $data = $this->model->setPagination($this->pagination->getPage(), $this->pagination->getPageSize())
                            ->findCountAll(
                                $this->filterExpr,
                                $this->exprAttrNames,
                                $this->exprAttrVal,
                                $order,
                                $this->sort
                            );
        if (!empty($data['CountAll'][0][0])) {
            $this->countTmp = $data['CountAll'][0][0];
        } else {
            $this->countTmp = -1;
        }
        $models = $data['QueryData'];

        if (is_null($models)) {
            $models = [];
        }

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
     * @param $array
     */
    public function prepareSearch($array)
    {
        $result = [];
        $tmp = &$result;
        $names = [];
        $values = [];
        for ($i = 0; $i < count($array); $i++) {
            if ($i + 1 == count($array)) {
                $tmp = [
                    "o" => $this->caseSensitive ? 'LIKE' : 'ILIKE',
                    'l' => '#col' . $i,
                    'r' => ':col' . $i
                ];
            } else {
                $tmp = [
                    "o" => 'AND',
                    'l' => [
                        'o' => $this->caseSensitive ? 'LIKE' : 'ILIKE',
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

            $names['#col' . $counter] = $value['uuid'];
            $values[':col' . $counter] = str_replace('*', '%', $value['value']);
            $counter++;
        }

        $this->exprAttrNames = $names;
        $this->exprAttrVal = $values;
        $this->filterExpr = $result;
    }

    /**
     * @return int
     * @throws \Exception
     */
    public function getCountWithoutFilters()
    {
        $total = $this->model->countAll();
        if (!empty($total['QueryData'][0])) {
            return intval($total['QueryData'][0][0]);
        } else {
            return -1;
        }
    }

    /**
     * @return int|mixed
     * @throws \Exception
     */
    protected function prepareTotalCount()
    {
        $total = -1;
        if ($this->countTmp !== -1) {
            $total = $this->countTmp;
        } else {
            $total = $this->model->countAll($this->filterExpr, $this->exprAttrNames, $this->exprAttrVal);
            if (!empty($total['QueryData'][0])) {
                $total = $total['QueryData'][0][0];
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
