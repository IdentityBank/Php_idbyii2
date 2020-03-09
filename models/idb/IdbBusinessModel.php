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

namespace idbyii2\models\idb;

################################################################################
# Use(s)                                                                       #
################################################################################

use yii\db\QueryInterface;

################################################################################
# Class(es)                                                                    #
################################################################################

/**
 * Class IdbBusinessModel
 *
 * @package idbyii2\models\idb
 */
class IdbBusinessModel implements QueryInterface
{

    private $businessDbId = null;
    private $businessClient = null;
    private $businessMetadata = null;

    public static function find(string $businessDbId)
    {
        return self::model($businessDbId);
    }

    /**
     * Create instance of business model.
     *
     * @param            $businessDbId - business database id for query request
     *                                 it should be created via @return \idbyii2\models\idb\IdbBusinessModel
     *
     * @see IdbAccountId::formatBusinessDbId
     *
     * @see IdbAccountId::generateBusinessDbId
     *                      or as string with format
     */
    public static function model(string $businessDbId)
    {
        $businessModel = new static();
        $businessModel->businessDbId = $businessDbId;
        $businessModel->businessClient = IdbBankClientBusiness::model($businessDbId);
        $businessModel->businessMetadata = IdbBusinessMetadataModel::model(
            $businessDbId,
            $businessModel->businessClient
        );

        return $businessModel;
    }

    /**
     * @return \idbyii2\models\idb\IdbBusinessMetadataModel
     */
    public function metadata(): IdbBusinessMetadataModel
    {
        return $this->businessMetadata;
    }

    /**
     * @inheritdoc
     */
    public function all($businessClient = null)
    {
        // TODO: Implement all() method.
    }

    /**
     * @inheritdoc
     */
    public function one($businessClient = null)
    {
        // TODO: Implement one() method.
    }

    /**
     * Returns the number of records.
     *
     * @param null                 $filterExpression
     * @param null                 $expressionAttributeNames
     * @param null                 $expressionAttributeValues
     * @param IdbBankModelBusiness $businessClient the database connection used to execute the query.
     *                                             If this parameter is not given, the `db` application component will
     *                                             be used.
     *
     * @return int number of records.
     * @throws \Exception
     */
    public function count(
        $filterExpression = null,
        $expressionAttributeNames = null,
        $expressionAttributeValues = null,
        IdbBankModelBusiness $businessClient = null
    ) {
        $value = 0;
        if (empty($businessClient)) {
            $businessClient = $this->businessClient;
        }
        $queryResult = $businessClient->count($filterExpression, $expressionAttributeNames, $expressionAttributeValues);
        if (
            !empty($queryResult['QueryData'][0][0])
            && is_int($queryResult['QueryData'][0][0])
        ) {
            $value = intval($queryResult['QueryData'][0][0]);
        }

        return $value;
    }

    /**
     * Returns the number of all records.
     *
     * @param null                                           $filterExpression
     * @param null                                           $expressionAttributeNames
     * @param null                                           $expressionAttributeValues
     * @param \idbyii2\models\idb\IdbBankClientBusiness|null $businessClient - the IDB client connection used to
     *                                                                       execute the query. If this parameter is
     *                                                                       not given, the `businessClient`
     *                                                                       application component will be used.
     *
     * @return int the number of all records
     * @throws \Exception
     */
    public function countAll(
        $filterExpression = null,
        $expressionAttributeNames = null,
        $expressionAttributeValues = null,
        IdbBankModelBusiness $businessClient = null
    ): int {
        $value = 0;
        if (empty($businessClient)) {
            $businessClient = $this->businessClient;
        }
        $queryResult = $businessClient->countAll(
            $filterExpression,
            $expressionAttributeNames,
            $expressionAttributeValues
        );
        if (
            !empty($queryResult['QueryData'][0][0])
            && is_int($queryResult['QueryData'][0][0])
        ) {
            $value = intval($queryResult['QueryData'][0][0]);
        }

        return $value;
    }

    /**
     * @inheritdoc
     */
    public function exists($businessClient = null)
    {
        // TODO: Implement exists() method.
    }

    /**
     * @inheritdoc
     */
    public function indexBy($column)
    {
        // TODO: Implement indexBy() method.
    }

    /**
     * @inheritdoc
     */
    public function where($condition)
    {
        // TODO: Implement where() method.
    }

    /**
     * @inheritdoc
     */
    public function andWhere($condition)
    {
        // TODO: Implement andWhere() method.
    }

    /**
     * @inheritdoc
     */
    public function orWhere($condition)
    {
        // TODO: Implement orWhere() method.
    }

    /**
     * @inheritdoc
     */
    public function filterWhere(array $condition)
    {
        // TODO: Implement filterWhere() method.
    }

    /**
     * @inheritdoc
     */
    public function andFilterWhere(array $condition)
    {
        // TODO: Implement andFilterWhere() method.
    }

    /**
     * @inheritdoc
     */
    public function orFilterWhere(array $condition)
    {
        // TODO: Implement orFilterWhere() method.
    }

    /**
     * @inheritdoc
     */
    public function orderBy($columns)
    {
        // TODO: Implement orderBy() method.
    }

    /**
     * @inheritdoc
     */
    public function addOrderBy($columns)
    {
        // TODO: Implement addOrderBy() method.
    }

    /**
     * @inheritdoc
     */
    public function limit($limit)
    {
        // TODO: Implement limit() method.
    }

    /**
     * @inheritdoc
     */
    public function offset($offset)
    {
        // TODO: Implement offset() method.
    }

    /**
     * @inheritdoc
     */
    public function emulateExecution($value = true)
    {
        // TODO: Implement emulateExecution() method.
    }
}

################################################################################
#                                End of file                                   #
################################################################################
