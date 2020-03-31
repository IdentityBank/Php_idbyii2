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

namespace idbyii2\helpers;

################################################################################
# Use(s)                                                                       #
################################################################################


################################################################################
# Class(es)                                                                    #
################################################################################
use yii\helpers\ArrayHelper;

/**
 * Class Response
 *
 * @package idbyii2\helpers
 */
class Metadata
{
    /**
     * Update data node of metadata by diff between two metadata.
     *
     * @param $diff
     * @param $metadata
     */
    public static function updateDataByDiff($diff, &$metadata)
    {
        foreach ($diff as $key => $value) {
            if (!empty($value['type'])) {
                switch ($value['type']) {
                    case 'created':
                    case 'updated':
                        $metadata[$key] = $value['data'];

                        break;

                    case 'deleted':
                        unset($metadata[$key]);

                        break;
                }
            } else {
                foreach ($value as $k => $v) {

                    if (!empty($v['type'])) {
                        switch ($v['type']) {
                            case 'created':
                            case 'updated':

                                $metadata[$key][$k] = $v['data'];

                                break;

                            case 'deleted':
                                unset($metadata[$key][$k]);

                                break;
                        }
                    } elseif ($k === 'data' || $k === 'gdpr') {
                        self::updateDataByDiff($v, $metadata[$key][$k]);
                    }
                }
            }
        }
    }


    /**
     * Update metadata settings node by edit node.
     *
     * @param $edit
     * @param $settings
     */
    public static function updateSettingsByEdit($edit, &$settings)
    {
        if (!empty($edit['add'])) {
            foreach ($edit['add'] as $add) {
                foreach ($settings as $uid => $value) {
                    $settings[$uid][$add['uuid']] = 'on';
                }
            }
        }

        if (!empty($edit['drop'])) {
            foreach ($edit['drop'] as $drop) {
                foreach ($settings as $uid => $value) {
                    unset($settings[$uid][$drop['uuid']]);
                }
            }
        }
    }

    /**
     * @param $metadata
     *
     * @return bool
     */
    public static function hasPeopleAccessMap($metadata)
    {
        $peopleAccessMap = ArrayHelper::getValue($metadata, 'PeopleAccessMap', []);
        if(
            !ArrayHelper::keyExists('email_no', $peopleAccessMap)
            || !ArrayHelper::keyExists('mobile_no', $peopleAccessMap)
            || !ArrayHelper::keyExists('name_no', $peopleAccessMap)
            || !ArrayHelper::keyExists('surname_no', $peopleAccessMap)
        ) {
            return false;
        }

        foreach($peopleAccessMap as $type) {
            if (!self::hasType($type, $metadata)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param $uuid
     * @param $metadata
     *
     * @return bool
     */
    public static function hasType($uuid, $metadata)
    {
        $type = self::getType($uuid, $metadata);
        return empty($type)? false: true;
    }

    /**
     * @param $metadata
     * @param $dataToAdd
     */
    public static function addToAllDataTypes(&$metadata, $dataToAdd)
    {
        foreach($metadata['data'] as &$data) {
            if($data['object_type'] === 'type') {
                $data = array_merge($data, $dataToAdd);
            } elseif($data['object_type'] === 'set') {
                self::addToAllDataTypes($data, $dataToAdd);
            }
        }
    }

    /**
     * @param $metadata
     * @param $dataToAdd
     */
    public static function addToGdpr(&$metadata, $dataToAdd)
    {
        foreach($metadata['data'] as &$data) {
            if($data['object_type'] === 'type') {
                $data['gdpr'] = array_merge($data['gdpr']?? [], $dataToAdd);
            } elseif($data['object_type'] === 'set') {
                self::addToAllDataTypes($data, $dataToAdd);
            }
        }
    }

    /**
     * @param $uuid
     * @param $metadata
     *
     * @return bool|mixed
     */
    static function getType($uuid, $metadata)
    {
        foreach ($metadata['data'] as $data) {
            if ($data['object_type'] == 'type' && $data['uuid'] == $uuid) {
                return $data;
            } elseif ($data['object_type'] === 'set') {
                if (self::getType($uuid, $data) != false) {
                    return self::getType($uuid, $data);
                }
            }
        }

        return false;
    }

    /**
     * @param $metadata
     * @return array
     */
    public static function getTypes($metadata)
    {
        $types = [];
        foreach($metadata['data'] as $data) {
            if($data['object_type'] === 'type') {
                $types []= $data;
            } elseif ($data['object_type'] === 'set') {
                $types = array_merge($types, $data);
            }
        }

        return $types;
    }

    /**
     * @param $metadata
     * @return array
     */
    static function getGDPR($metadata)
    {
        $gdpr = [];
        foreach ($metadata['data'] as $data) {
            if ($data['object_type'] == 'type') {
                if(!empty($data['gdpr'])) {
                    $gdpr[$data['uuid']] = array_merge($data['gdpr'], ['display_name' => $data['display_name']]);
                }
            } elseif ($data['object_type'] === 'set') {
                if (!empty(self::getGDPR($data))) {
                    $gdpr = array_merge($gdpr, self::getGDPR($data));
                }
            }
        }

        return $gdpr;
    }

    /**
     * @param $metadata
     * @return array
     */
    static function getGDPRWithProcessors($metadata)
    {
        $gdpr = [];
        if(!empty(ArrayHelper::getValue($metadata, 'options.listDataProcessors', null))) {
            $gdpr['listDataProcessors'] = $metadata['options']['listDataProcessors'];
        }
        $gdpr['dataTypes'] = self::getGDPR($metadata);

        return $gdpr;
    }

    /**
     * @param $metadata
     * @return mixed|null
     */
    public static function getPurposeLimitation($metadata)
    {
        foreach ($metadata['data'] as $data) {
            if ($data['object_type'] == 'type') {
                if(!empty(ArrayHelper::getValue($data, 'gdpr.purposeLimitation', null))) {
                    return $data['gdpr']['purposeLimitation'];
                }
            } elseif ($data['object_type'] === 'set') {
                if (!empty(self::getPurposeLimitation($data))) {
                    return self::getPurposeLimitation($data);
                }
            }
        }

        return null;
    }

    /**
     * @param $result
     * @param $metadata
     * @return array
     */
    public static function mapUuid($result, $metadata)
    {
        $mapped = [];
        $orderedUiids = self::getOrderedUuids($metadata);
        unset($result[0]);
        foreach($result as $key => $row) {
            $mapped[$orderedUiids[$key]] = $row;
        }

        return $mapped;
    }

    /**
     * @param $metadata
     * @param int $key
     * @return array
     */
    private static function getOrderedUuids($metadata, &$key = 1)
    {
        $uuids = [];
        foreach ($metadata['data'] as $data) {
            if ($data['object_type'] == 'type') {
                $uuids[$key] = $data['uuid'];
                $key++;
            } elseif ($data['object_type'] === 'set') {
                if (!empty(self::getOrderedUuids($data))) {
                    array_merge($uuids, self::getOrderedUuids($data, $key));
                }
            }
        }

        return $uuids;
    }

    /**
     * @param array $data
     * @param $metadata
     * @return array
     */
    public static function getAllowedToPseudonymisation(array $data, $metadata)
    {
        foreach($data as $uuid => $value) {
            $type = self::getType($uuid, $metadata);
            if(empty($type['pseudonymisation']) || !boolval($type['pseudonymisation'])) {
                unset($data[$uuid]);
            }
        }

        return $data;
    }
}

#################################################################################
##                                End of file                                   #
#################################################################################
