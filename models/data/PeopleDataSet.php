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

use idb\idbank\PeopleIdBankClient;
use idbyii2\helpers\Translate;
use yii\base\Model;
use yii\web\NotFoundHttpException;

################################################################################
# Class(es)                                                                    #
################################################################################

/**
 * This is the model class for table "data_set".
 *
 * @property int    $id
 * @property string $type
 */
class PeopleDataSet extends Model
{

    public $keyPk;
    public $key;
    public $value;
    public $dataSetTypes;
    private $accountMetadata;
    private $idbankClient;

    function __construct($accountMetadata, $idbankClient = null)
    {
        $this->accountMetadata = $accountMetadata;
        $this->idbankClient = $idbankClient;
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['key'], 'required'],
            [['key', 'value'], 'string'],
            [['key', 'value', 'dataSetTypes'], 'safe'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'key' => Translate::_('idbyii2', 'Key'),
            'value' => Translate::_('idbyii2', 'Display Name'),
            'dataSetTypes' => Translate::_('idbyii2', 'Types for that data set'),
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getDataSetTypes()
    {
        $dataSetTypes = [];
        if (!empty($this->dataSetTypes) && is_array($this->dataSetTypes)) {
            foreach ($this->dataSetTypes as $dataSetType) {
                $dataType = PeopleDataType::findModel($dataSetType, $this->accountMetadata);
                if ($dataType) {
                    $dataSetTypes[] = $dataType;
                }
            }
        }

        return $dataSetTypes;
    }

    /**
     * @param array $values
     *
     * @return string
     */
    public static function formatValues(array $values)
    {
        $result = '';

        foreach ($values as $key => $value) {
            $result .= $key . ': ' . '<b>' . $value . '</b><br>';
        }

        return $result;
    }

    /**
     * @param $accountMetadata
     *
     * @return array|null
     */
    public static function modelsFromMetadata($accountMetadata)
    {
        $dataSetsModels = null;
        if (
            !empty($accountMetadata[PeopleIdBankClient::DATA_SETS])
            && is_array($accountMetadata[PeopleIdBankClient::DATA_SETS])
        ) {
            $dataSets = $accountMetadata[PeopleIdBankClient::DATA_SETS];
            $dataSetsModels = [];
            foreach ($dataSets as $dataSet) {
                if (
                    !empty($dataSet['key'])
                    && !empty($dataSet['value'])
                ) {
                    $dataTypesModel = new self($accountMetadata);
                    $dataTypesModel->keyPk = $dataTypesModel->key = $dataSet['key'];
                    $dataTypesModel->value = $dataSet['value'];
                    if (!empty($dataSet[PeopleIdBankClient::DATA_SETS])) {
                        $dataTypesModel->dataSetTypes = $dataSet[PeopleIdBankClient::DATA_SETS];
                    }
                    $dataSetsModels[] = $dataTypesModel;
                }
            }
        }

        return $dataSetsModels;
    }

    /**
     * @param      $key
     * @param      $accountMetadata
     * @param null $idbankClient
     *
     * @return \idbyii2\models\data\PeopleDataSet|null
     * @throws \yii\web\NotFoundHttpException
     */
    public static function findModel($key, $accountMetadata, $idbankClient = null)
    {
        $model = null;
        if (
            !empty($accountMetadata[PeopleIdBankClient::DATA_SETS])
            && is_array($accountMetadata[PeopleIdBankClient::DATA_SETS])
        ) {
            $dataSets = $accountMetadata[PeopleIdBankClient::DATA_SETS];
            foreach ($dataSets as $dataSet) {
                if (
                    !empty($dataSet['key'])
                    && ($dataSet['key'] === $key)
                ) {
                    $dataTypesModel = new self($accountMetadata, $idbankClient);
                    $dataTypesModel->keyPk = $dataTypesModel->key = $dataSet['key'];
                    $dataTypesModel->value = $dataSet['value'];
                    if (!empty($dataSet[PeopleIdBankClient::DATA_SETS])) {
                        $dataTypesModel->dataSetTypes = $dataSet[PeopleIdBankClient::DATA_SETS];
                    }
                    $model = $dataTypesModel;
                    break;
                }
            }
        }
        if (empty($model)) {
            throw new NotFoundHttpException(Translate::_('idbyii2', 'The requested model does not exist.'));
        }

        return $model;
    }

    /**
     * @param      $key
     * @param      $accountMetadata
     * @param null $idbankClient
     *
     * @return mixed
     */
    public static function removeModel($key, $accountMetadata, $idbankClient = null)
    {
        $model = null;
        if (
            !empty($accountMetadata[PeopleIdBankClient::DATA_SETS])
            && is_array($accountMetadata[PeopleIdBankClient::DATA_SETS])
        ) {
            $dataSets = $accountMetadata[PeopleIdBankClient::DATA_SETS];
            foreach ($dataSets as $index => $dataSet) {
                if (
                    !empty($dataSet['key'])
                    && ($dataSet['key'] === $key)
                ) {
                    unset($accountMetadata[PeopleIdBankClient::DATA_SETS][$index]);
                    // break; // Remove all duplicates for now
                }
            }
        }
        if (!empty($idbankClient)) {
            $idbankClient->setAccountMetadata($accountMetadata);
        }

        return $accountMetadata;
    }

    /**
     * @param bool $runValidation
     * @param null $attributeNames
     *
     * @return bool
     */
    public function save($runValidation = true, $attributeNames = null)
    {
        return $this->update($runValidation, $attributeNames) !== false;
    }

    /**
     * @param bool $runValidation
     * @param null $attributeNames
     *
     * @return array|bool
     */
    public function update($runValidation = true, $attributeNames = null)
    {
        if ($runValidation && !$this->validate($attributeNames)) {
            return false;
        }

        return $this->updateInternal($attributeNames);
    }

    /**
     * @param null $attributes
     *
     * @return array
     */
    protected function updateInternal($attributes = null)
    {
        $rows = [];
        if (
            empty($this->accountMetadata[PeopleIdBankClient::DATA_SETS])
            || !is_array($this->accountMetadata[PeopleIdBankClient::DATA_SETS])
        ) {
            $this->accountMetadata[PeopleIdBankClient::DATA_SETS] = [];
        }
        if (!empty($this->keyPk)) {
            $this->accountMetadata = self::removeModel($this->keyPk, $this->accountMetadata);
        }
        $this->accountMetadata[PeopleIdBankClient::DATA_SETS][] = [
            'key' => $this->key,
            'value' => $this->value,
            PeopleIdBankClient::DATA_SETS => $this->dataSetTypes
        ];
        if (!empty($this->idbankClient)) {
            $this->idbankClient->setAccountMetadata($this->accountMetadata);
        }

        return $rows;
    }
}

################################################################################
#                                End of file                                   #
################################################################################
