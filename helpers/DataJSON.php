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

use DateTime;
use idbyii2\enums\DataObjectType;
use idbyii2\models\db\DataSet;
use idbyii2\models\db\DataType;

################################################################################
# Class(es)                                                                    #
################################################################################

/**
 * Class DataJSON
 *
 * @package idbyii2\helpers
 */
class DataJSON
{

    const NEW_COLUMN = 'new';
    const SKIP_COLUMN = 'skip';

    /**
     * @param $newColumns
     *
     * @return array
     */
    public static function getFormattedDataToAddColumns($newColumns)
    {
        $newData = [];
        $newData['database'] = [
            'add' => []
        ];

        foreach ($newColumns as $newColumn) {
            $newData['database']['add'][] = [
                'uuid' => $newColumn['uuid'],
                'type' => 'string'
            ];

        }

        return $newData;
    }

    /**
     * @param array $objects
     *
     * @return array
     */
    public static function getFormattedArrayToMapping(array $objects)
    {
        $json = [];

        foreach ($objects as $header) {
            $json['headerMapping'][] = [
                'header' => $header['header'] ?? '',
                'uuid' => $header["uuid"] ?? '',
                'index' => $header['inedex'] ?? ''
            ];
        }

        return $json;
    }

    /**
     * @param array $objects
     *
     * @param null $metadata
     *
     * @return array
     * @throws \Exception
     */
    public static function getFormattedArray(array $objects, $metadata = null)
    {
        $json = [
            'uuid' => self::generateUuid('General'),
            'internal_name' => 'General',
            'display_name' => '',
            'object_type' => DataObjectType::SET,
            'columns' => []
        ];
        $i = 0;

        foreach ($objects as $header) {
            if ($header['type'] === self::NEW_COLUMN) {
                $internalName = (new DateTime())->getTimestamp() . $header['header'];
                if ($metadata !== null) {
                    while (DataHTML::checkIfInternalExists($internalName, $metadata)) {
                        $internalName = (new DateTime())->getTimestamp() . $header['header'];
                    }
                }

                $uuid = self::generateUuid('type' . $header['header'] . $header['type']);
                $json['data'][] = [
                    'uuid' => $uuid,
                    'internal_name' => $internalName,
                    'display_name' => $header['header'],
                    'data_type' => 'string',
                    'order' => $i,
                    'searchable' => '1',
                    'sortable' => '1',
                    'sensitive' => '1',
                    'tag' => '',
                    'used_for' => 'General',
                    'required' => '1',
                    'object_type' => DataObjectType::TYPE
                ];
                $json['database'][] = [
                    'uuid' => $uuid,
                    'type' => 'string'
                ];
                $json['used_for'] = 'General';
                $json['headerMapping'][] = [
                    'header' => $header['header'],
                    'uuid' => $uuid,
                    'index' => $header['index']
                ];

                $data = [
                    'uuid' => $uuid,
                    'title' => $header['header'],
                    'order' => $i,
                    'on' => 'true',
                    'type' => 'string'
                ];

                array_push($json['columns'], $data);

            } elseif ($header['type'] !== self::SKIP_COLUMN) {
                $json['headerMapping'][] = [
                    'header' => $header['header'],
                    'uuid' => $header['type'],
                    'index' => $header['index']
                ];
            }
            $i++;
        }

        return $json;
    }

    static function generateUuid($namespace)
    {
        $date = new DateTime();

        return Uuid::uuid5($date->getTimestamp() . rand(0, 99999) . $namespace)->string;
    }

    static function setsToArray(array $sets)
    {
        $arraySets = [];
        foreach ($sets as $set) {
            $arraySets [] = self::setToArray($set);
        }

        return $arraySets;
    }

    static function setToArray(DataSet $set)
    {
        $objects = $set->objects;
        usort(
            $objects,
            function ($a, $b) {
                return $a->order < $b->order ? -1 : 1;
            }
        );

        $json = [
            'uuid' => self::generateUuid($set->internal_name . $set->display_name),
            'internal_name' => $set->internal_name,
            'display_name' => $set->display_name,
            'object_type' => DataObjectType::SET
        ];
        foreach ($objects as $object) {
            if ($object->object_type == DataObjectType::TYPE) {
                $displayName = $object->display_name !== '' ?
                    $object->display_name : $object->object->display_name;

                $json['data'][] = [
                    'uuid' => self::generateUuid($object->object_type . $object->object->display_name),
                    'internal_name' => $object->object->internal_name,
                    'display_name' => $displayName,
                    'data_type' => $object->object->data_type,
                    'order' => $object->order,
                    'searchable' => $object->object->searchable,
                    'sortable' => $object->object->sortable,
                    'sensitive' => $object->object->sensitive,
                    'tag' => $object->object->tag,
                    'used_for' => $object->used_for,
                    'required' => $object->required,
                    'object_type' => DataObjectType::TYPE
                ];

                if (!empty($object->object->additionalAttribute)) {
                    $attributes = [];
                    foreach ($object->object->additionalAttributes as $additionalAttribute) {
                        $attributes [] = [
                            $additionalAttribute->attribute->name => $additionalAttribute->value
                        ];
                    }

                    $json['data'][count($json['data']) - 1]['attributes'] = $attributes;
                }
            } elseif ($object->object_type == DataObjectType::SET) {
                $tmp = [
                    'uuid' => self::generateUuid($object->object->display_name . $object->object_type),
                    'used_for' => $object->used_for,
                    'required' => $object->required,
                    'order' => $object->order,
                    'display_name' => $object->display_name
                ];

                $json['data'][] = array_merge(
                    self::setToArray($object->object),
                    $tmp
                );

            }
        }

        return $json;
    }

    static function typesToArray(array $types)
    {
        $arrayTypes = [];
        foreach ($types as $type) {
            $arrayTypes [] = self::typeToArray($type);
        }

        return $arrayTypes;
    }

    static function typeToArray(DataType $type)
    {
        return [
            'uuid' => self::generateUuid('type' . $type->display_name),
            'internal_name' => $type->internal_name,
            'display_name' => $type->display_name,
            'data_type' => $type->data_type,
            'order' => '',
            'searchable' => $type->searchable,
            'sortable' => $type->sortable,
            'sensitive' => $type->sensitive,
            'tag' => $type->tag,
            'used_for' => '',
            'required' => $type->required,
            'object_type' => DataObjectType::TYPE
        ];
    }

    static function setToJSON(DataSet $set)
    {
        return json_encode(self::setToArray($set));
    }

}

################################################################################
#                                End of file                                   #
################################################################################
