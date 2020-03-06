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

use Exception;
use idbyii2\helpers\Translate;
use ReflectionClass;

################################################################################
# Class(es)                                                                    #
################################################################################

/**
 * Class IdbBusinessMetadataModel
 *
 * @package idbyii2\models\idb
 */
class IdbBusinessMetadataModel
{

    const BRANCH_UUID = 'uuid';
    const BRANCH_INTERNAL_NAME = 'internal_name';
    const BRANCH_DISPLAY_NAME = 'display_name';
    const BRANCH_COLUMNS = 'columns';
    const BRANCH_OBJECT_TYPE = 'object_type';
    const BRANCH_DATA = 'data';
    const BRANCH_DATABASE = 'database';
    const BRANCH_USED_FOR = 'used_for';
    const BRANCH_HEADER_MAPPING = 'headerMapping';
    const BRANCH_SETTINGS = 'settings';
    const BRANCH_PEOPLE_MAPPING = 'PeopleAccessMap';

    private $businessDbId = null;
    private $businessClient = null;

    /**
     * Create metadata model
     *
     * @param string                                   $businessDbId   String value ID for referenced business database
     * @param \idbyii2\models\idb\IdbBankModelBusiness $businessClient IDB Business object for all actions for related
     *                                                                 business metadata object data
     *
     * @return \idbyii2\models\idb\IdbBusinessMetadataModel
     */
    public static function model(string $businessDbId, IdbBankModelBusiness $businessClient)
    {
        $businessMetadataModel = new static();
        $businessMetadataModel->businessDbId = $businessDbId;
        $businessMetadataModel->businessClient = $businessClient;

        return $businessMetadataModel;
    }

    /**
     * @param string $branch
     *
     * @return bool
     * @throws \ReflectionException
     */
    private static function checkBranch(string $branch)
    {
        $consts = self::getConstants();
        foreach ($consts as $constName => $constValue) {
            if (
                ($constValue === $branch)
                && (substr($constName, 0, 7) === "BRANCH_")
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array
     * @throws \ReflectionException
     */
    private static function getConstants()
    {
        $reflectionClass = new ReflectionClass(static::class);

        return $reflectionClass->getConstants();
    }

    /**
     * @return string UUID value for business database
     * @throws \Exception
     */
    public function getUuid(): string
    {
        return $this->get(self::BRANCH_UUID);
    }

    /**
     * @param string $branch Branch name for metadata object which represent data object for that branch
     *
     * @return |null
     * @throws \Exception
     */
    public function get(string $branch)
    {
        $metadataBranch = null;
        if (!self::checkBranch($branch)) {
            throw new Exception(
                Translate::_(
                    'idbyii2',
                    'The requested branch does not exist. Use IdbBusinessMetadataModel::BRANCH_<NAME>'
                )
            );
        }
        $metadata = $this->getMetadata();
        if (!empty($metadata['Metadata'])) {
            $metadata = json_decode($metadata['Metadata']);
            if (
                (json_last_error() === JSON_ERROR_NONE)
                && (!empty($metadata->$branch))
            ) {
                $metadataBranch = $metadata->$branch;
            }
        }

        return $metadataBranch;
    }

    /**
     * @return array|mixed|null Returns all branches stored with metadata
     * @throws \Exception
     */
    public function getCurrentBranches()
    {
        $metadataBranches = null;
        $metadata = $this->getMetadata();
        if (!empty($metadata['Metadata'])) {
            $metadata = json_decode($metadata['Metadata'], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $metadataBranches = array_keys($metadata);
            }
        }

        return $metadataBranches;
    }

    /**
     * @param \idbyii2\models\idb\IdbBankModelBusiness|null $businessClient
     *
     * @return null
     * @throws \Exception
     */
    private function getMetadata(IdbBankModelBusiness $businessClient = null)
    {
        if (empty($businessClient)) {
            $businessClient = $this->businessClient;
        }
        $metadata = $businessClient->getAccountMetadata();

        return $metadata;
    }

    /**
     * @return string Returns internal name assigned for Business database
     * @throws \Exception
     */
    public function getInternalName(): string
    {
        return $this->get(self::BRANCH_INTERNAL_NAME);
    }

    /**
     * @return string Returns user readable name assigned for Business database
     * @throws \Exception
     */
    public function getDisplayName(): string
    {
        return $this->get(self::BRANCH_DISPLAY_NAME);
    }

    /**
     * @return array - returns array of the columns models which maps business database at the relational database
     * @throws \Exception
     */
    public function getColumns(): array
    {
        $columns = [];
        $columnsData = $this->get(self::BRANCH_COLUMNS);
        if (!empty($columnsData) && is_array($columnsData)) {
            foreach ($columnsData as $columnData) {
                $columns[] = IdbBusinessMetadataColumnModel::fromData($columnData);
            }
        }

        return $columns;
    }

    /**
     * @return string Returns type of object
     * @throws \Exception
     */
    public function getObjectType(): string
    {
        return $this->get(self::BRANCH_OBJECT_TYPE);
    }

    /**
     * @return array Returns data sets and types for business database
     * @throws \Exception
     */
    public function getData(): array
    {
        $dataSets = [];
        $branchDataSets = $this->get(self::BRANCH_DATA);
        if (!empty($branchDataSets) && is_array($branchDataSets)) {
            foreach ($branchDataSets as $branchDataSet) {
                $dataSets[] = IdbBusinessMetadataDataSetModel::fromData($branchDataSet);
            }
        }

        return $dataSets;
    }

    /**
     * @return array Returns columns ids and types
     * @throws \Exception
     * @deprecated Use @see getColumns instead
     */
    public function getDatabase(): array
    {
        $columns = [];
        $columnsData = $this->get(self::BRANCH_DATABASE);
        if (!empty($columnsData) && is_array($columnsData)) {
            foreach ($columnsData as $columnData) {
                $columns[] = IdbBusinessMetadataColumnModel::fromData($columnData);
            }
        }

        return $columns;
    }

    /**
     * @return string Returns main use purpose for business database
     * @throws \Exception
     */
    public function getUsedFor(): string
    {
        return $this->get(self::BRANCH_USED_FOR);
    }

    /**
     * @return array - returns array of the header mapping models which maps business database columns to selected
     *               types columns (e.g. email, mobile)
     * @throws \Exception
     */
    public function getHeaderMapping(): array
    {
        $columns = [];
        $columnsData = $this->get(self::BRANCH_HEADER_MAPPING);
        if (!empty($columnsData) && is_array($columnsData)) {
            foreach ($columnsData as $columnData) {
                $columns[] = IdbBusinessMetadataHeaderMappingModel::fromData($columnData);
            }
        }

        return $columns;
    }

    /**
     * @return array Settings data
     * @throws \Exception
     */
    public function getSettings(): array
    {
        return (array)$this->get(self::BRANCH_SETTINGS);
    }

    /**
     * @return \idbyii2\models\idb\IdbBusinessMetadataPeopleMappingModel returns object of the header mapping for
     *                                                                   people data
     * @throws \Exception
     */
    public function getPeopleMappingModel(): IdbBusinessMetadataPeopleMappingModel
    {
        return IdbBusinessMetadataPeopleMappingModel::fromData($this->get(self::BRANCH_PEOPLE_MAPPING));
    }
}

################################################################################
#                                End of file                                   #
################################################################################
