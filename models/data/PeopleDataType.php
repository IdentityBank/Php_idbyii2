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

################################################################################
# Class(es)                                                                    #
################################################################################

/**
 * This is the model class for table "data_type".
 *
 * @property int    $id
 * @property string $key
 */
class PeopleDataType extends Model
{

    public $keyPk;
    public $key;
    public $value;
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
            [['key', 'value'], 'safe'],
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
        ];
    }

    /**
     * @param $accountMetadata
     *
     * @return array|null
     */
    public static function modelsFromMetadata($accountMetadata)
    {
        $dataTypesModels = null;
        if (
            !empty($accountMetadata[PeopleIdBankClient::DATA_TYPES])
            && is_array($accountMetadata[PeopleIdBankClient::DATA_TYPES])
        ) {
            $dataTypes = $accountMetadata[PeopleIdBankClient::DATA_TYPES];
            $dataTypesModels = [];
            foreach ($dataTypes as $dataType) {
                if (
                    !empty($dataType['key'])
                    && !empty($dataType['value'])
                ) {
                    $dataTypesModel = new self($accountMetadata);
                    $dataTypesModel->keyPk = $dataTypesModel->key = $dataType['key'];
                    $dataTypesModel->value = $dataType['value'];
                    $dataTypesModels[] = $dataTypesModel;
                }
            }
        }

        return $dataTypesModels;
    }

    /**
     * @param      $key
     * @param      $accountMetadata
     * @param null $idbankClient
     *
     * @return \idbyii2\models\data\PeopleDataType|null
     * @throws \yii\web\NotFoundHttpException
     */
    public static function findModel($key, $accountMetadata, $idbankClient = null)
    {
        $model = null;
        if (
            !empty($accountMetadata[PeopleIdBankClient::DATA_TYPES])
            && is_array($accountMetadata[PeopleIdBankClient::DATA_TYPES])
        ) {
            $dataTypes = $accountMetadata[PeopleIdBankClient::DATA_TYPES];
            foreach ($dataTypes as $dataType) {
                if (
                    !empty($dataType['key'])
                    && ($dataType['key'] === $key)
                ) {
                    $dataTypesModel = new self($accountMetadata, $idbankClient);
                    $dataTypesModel->keyPk = $dataTypesModel->key = $dataType['key'];
                    $dataTypesModel->value = $dataType['value'];
                    $model = $dataTypesModel;
                    break;
                }
            }
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
            !empty($accountMetadata[PeopleIdBankClient::DATA_TYPES])
            && is_array($accountMetadata[PeopleIdBankClient::DATA_TYPES])
        ) {
            $dataTypes = $accountMetadata[PeopleIdBankClient::DATA_TYPES];
            foreach ($dataTypes as $index => $dataType) {
                if (
                    !empty($dataType['key'])
                    && ($dataType['key'] === $key)
                ) {
                    unset($accountMetadata[PeopleIdBankClient::DATA_TYPES][$index]);
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
            empty($this->accountMetadata[PeopleIdBankClient::DATA_TYPES])
            || !is_array($this->accountMetadata[PeopleIdBankClient::DATA_TYPES])
        ) {
            $this->accountMetadata[PeopleIdBankClient::DATA_TYPES] = [];
        }
        if (!empty($this->keyPk)) {
            $this->accountMetadata = self::removeModel($this->keyPk, $this->accountMetadata);
        }
        $this->accountMetadata[PeopleIdBankClient::DATA_TYPES][] = ['key' => $this->key, 'value' => $this->value];

        if (!empty($this->idbankClient)) {
            $this->idbankClient->setAccountMetadata(json_encode($this->accountMetadata));
        }

        return $rows;
    }
}

################################################################################
#                                End of file                                   #
################################################################################
