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

use idbyii2\enums\DataObjectType;
use idbyii2\models\db\DataSet;
use Yii;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\NotFoundHttpException;

################################################################################
# Class(es)                                                                    #
################################################################################

/**
 * Class DataHTML
 *
 * @package idbyii2\helpers
 */
class DataHTML
{

    /**
     * @param DataSet $set
     *
     * @return string
     */
    static function renderSet(DataSet $set)
    {
        $objects = $set->objects;
        usort(
            $objects,
            function ($a, $b) {
                return $a->order < $b->order ? -1 : 1;
            }
        );

        $html = '';
        foreach ($objects as $object) {
            $html .= '<div>';

            if ($object->object_type == DataObjectType::SET) {
                $html .= '<div>';
                $html .= '<div><b>' . $object->display_name . '</b></div>';

                $html .= DataHTML::renderSet($object->object);
                $html .= '</div>';
            } elseif ($object->object_type == DataObjectType::TYPE) {
                $html .= '<div>' . $object->display_name . '</div>';
            }
            $html .= '</div>';
        }

        return $html;
    }

    /**
     * @param $array
     *
     * @return string
     */
    static function renderArray($array)
    {
        $order = !empty($array['order']) ? $array['order'] : '';
        $html = '<div class="data-creator-set">';
        $html .= '<div class="creator-remove">x</div>';
        $html .= '<span class="creator-order creator-order--set">' . $order
            . '</span><input type="text" name="set" value="' . $array['display_name'] . '"/>';
        foreach ($array['data'] as $data) {
            if ($data['object_type'] == DataObjectType::TYPE) {
                $html .= '<div>';
                $html .= '<span class="creator-order">' . $data['order']
                    . '</span><input type="text" name="type" value="' . $data['display_name'] . '" />';
                $html .= '</div>';
            } elseif ($data['object_type'] == DataObjectType::SET) {
                $html .= self::renderArray($data);
            }
        }
        $html .= '<div class="data-creator-buton-container">
                    <button class="btn btn-app-blue button--add-type" data-uuid="' . $array['uuid'] . '">Add Type</button>
                    <button class="btn btn-adn button--add-set" data-uuid="' . $array['uuid'] . '">Add Set</button>
                    </div>';
        $html .= '</div>';

        return $html;
    }

    /**
     * @param $metadata
     *
     * @return array
     * @throws NotFoundHttpException
     */
    public static function generateColumnsForPeopleAccess($metadata)
    {
        $columns [] = [
            'class' => 'yii\grid\CheckboxColumn',
            'checkboxOptions' => function ($model, $key) {
                return ['value' => $model[0]];
            },
        ];

        if (
            empty($metadata['PeopleAccessMap'])
            || empty($metadata['data'])
        ) {
            throw new NotFoundHttpException();
        }

        $counter = 1;
        foreach ($metadata['PeopleAccessMap'] as $column_id => $column_uuid) {
            $number = self::getKeyForColumn($column_uuid, $metadata);
            if (is_numeric($number)) {
                $number = $number + 1;
                $search = null;
                if (Yii::$app->session->has('people-access-search')) {
                    $search = json_decode(Yii::$app->session->get('people-access-search'), true);
                }
                $value = '';
                if (!is_null($search)) {
                    foreach ($search as $se) {
                        if ($se['uuid'] === $column_uuid) {
                            $value = $se['value'];
                        }
                    }
                }

                $columns [] = [
                    'header' => '<span id="col-name-' . $counter . '">'
                        . self::getDisplayName($column_uuid, $metadata)
                        . '</span><br/> <input class="search" name="' . $column_uuid . '" value="' . $value
                        . '" type="text"/>',
                    'value' => function ($model) use ($number) {
                        if (!empty($model[$number])) {
                            return $model[$number];
                        }

                        return '';
                    }
                ];
                $counter++;
            } else {
                $columns = [];
            }
        }

        if (empty($columns)) {
            Yii::$app->controller->redirect(['/applications/contacts/check-mapping']);
        } else {
            $columns [] = [
                'class' => 'yii\grid\ActionColumn',
                'visible' => (Yii::$app->user->can('idb_developer')),
                'template' => '{create}',
                'urlCreator' => function ($action, $model, $key, $index) {
                    return Url::toRoute(["idb-data/$action", 'uuid' => $model[0]]);
                },
                'visibleButtons' => [
                    'create' => true,
                ],
                'buttons' => [
                    'create' => function ($url, $model) {

                        return Html::a(
                            '<b class="fa fa-address-book-o"></b>',
                            ['people', 'dbUserId' => $model[0]],
                            [
                                'title' => Translate::_(
                                    'idbyii2',
                                    'Create people portal access'
                                ),
                                'id' => 'modal-btn-view'
                            ]
                        );
                    },
                ]
            ];
        }

        return $columns;
    }

    /**
     * @param $uuid
     * @param $metadata
     *
     * @return bool|string
     */
    static function getKeyForColumn($uuid, $metadata)
    {
        foreach ($metadata['data'] as $key => $data) {
            if ($data['object_type'] === 'type' && $data['uuid'] == $uuid) {
                return $key;
            } elseif ($data['object_type'] === 'set') {
                if (self::getDisplayName($uuid, $data) != false) {
                    return self::getDisplayName($uuid, $data);
                }
            }
        }

        return false;
    }

    static function getDisplayName($uuid, $metadata)
    {
        foreach ($metadata['data'] as $data) {
            if ($data['object_type'] === 'type' && $data['uuid'] == $uuid) {
                $delimiter = empty($metadata['display_name']) ? '' : '-';
                return $metadata['display_name'] . $delimiter . $data['display_name'];
            } elseif ($data['object_type'] === 'set') {
                if (self::getDisplayName($uuid, $data) != false) {
                    return self::getDisplayName($uuid, $data);
                }
            }
        }

        return false;
    }

    /**
     * @param $metadata
     *
     * @return array
     * @throws NotFoundHttpException
     */
    static function generateColumns($metadata)
    {
        $columns [] = [
            'class' => 'yii\grid\CheckboxColumn',
            'headerOptions' => ['id' => 'idb_selector', 'class' => 'no-sort-checkbox'],
            'checkboxOptions' => function ($model) {
                return ['value' => $model[0], 'class' => 'used-checkbox'];
            }
        ];

        $counter = 1;
        if (!empty($metadata['settings']) && !empty($metadata['settings'][Yii::$app->user->identity->id])) {
            foreach ($metadata['settings'][Yii::$app->user->identity->id] as $key => $column) {
                $columns [] = [
                    'header' => '<span class="col_name" id="col-name-' . $counter . '">' . strip_tags(self::getDisplayName(
                            $key,
                            $metadata
                        ))
                        . '</span> <br/>
                            <div class="input-group input-group-sm hidden-xs">
                                <input class="search form-control pull-right" name="' . $key . '" type="text"/>
                                <div class="input-group-btn">
                                    <button class="btn btn-default btn-search"><i class="fa fa-search"></i></button>
                                </div>
                            </div>',
                    'value' => function ($model) use ($counter) {

                        if (!empty($model[$counter])) {
                            return $model[$counter];
                        }

                        return '';
                    }
                ];
                $counter++;
            }
        } elseif (!empty($metadata['database'])) {
            foreach ($metadata['database'] as $key => $column) {
                $columns [] = [
                    'header' => '<span class="col_name" id="col-name-' . $counter . '">' . strip_tags(self::getDisplayName(
                            $column['uuid'],
                            $metadata
                        )) . '</span><br/>
                            <div class="input-group input-group-sm hidden-xs">
                                <input class="search form-control pull-right" name="' . $column['uuid'] . '" type="text"/>
                                <div class="input-group-btn">
                                    <button class="btn btn-default btn-search"><i class="fa fa-search"></i></button>
                                </div>
                            </div>',
                    'value' => function ($model) use ($key) {
                        if (!empty($model[$key + 1])) {
                            return $model[$key + 1];
                        }

                        return '';
                    }
                ];
                $counter++;
            }
        } else {
            throw new NotFoundHttpException();
        }

        $columns [] = [
            'class' => 'yii\grid\ActionColumn',
            'template' => '{usedForButton} {files} {update} {delete}',
            'header' => '',
            'headerOptions' => ['id' => 'idb_action', 'class' => 'no-sort'],
            'contentOptions' => function ($model, $key, $index, $column) {
                return ['style' => 'white-space:nowrap;'];
            },
            'urlCreator' => function ($action, $model, $key, $index) {
                return Url::toRoute(["idb-data/$action", 'uuid' => $model[0]]);
            },
            'visibleButtons' => [
                'update' => true,
                'delete' => true,
                'usedForButton' => true
            ],
            'buttons' => [
                'files' => function ($url, $model, $key) {
                    return Html::a(
                        '<span class="fa fa-file"></span></a>',
                        ["/idb-storage/index", 'uuid' => $model[0]],
                        [
                            'style' => 'cursor:pointer;',
                            'title' => Translate::_('idbyii2', "Files")
                        ]
                    );
                },
                'usedForButton' => function ($url, $model, $key) {
                    return Html::a(
                        '<span class="glyphicon glyphicon-hand-left"></span></a>',
                        null,
                        [
                            'style' => 'cursor:pointer;color: #FF0000;',
                            'data-target' => "#used-for-modal",
                            'class' => 'used-for',
                            'data-toggle' => "modal",
                            'data-uuid' => $model[0],
                            'title' => Translate::_('idbyii2', "Update Audit Log")
                        ]
                    );
                },
                'delete' => function ($url, $model, $key) {
                    return Yii::$app->controller->renderPartial(
                        '@app/themes/adminlte2/views/site/_modalWindow',
                        [
                            'modal' => [
                                'name' => 'cancelFormActionModal_' . preg_replace(
                                        "/[^A-Za-z0-9 ]/",
                                        '_',
                                        base64_encode($model[0])
                                    ),
                                'header' => Translate::_('idbyii2', 'Delete people data'),
                                'body' => Translate::_(
                                    'idbyii2',
                                    'That action will permanently remove your data. Are you sure you want continue that action?'
                                ),
                                'question' => Translate::_(
                                    'idbyii2',
                                    'If this is not your intention, please click on "{cancel_delete}".',
                                    ['cancel_delete' => 'Cancel delete action']
                                ),
                                'button' => [
                                    'label' => '<span class="glyphicon glyphicon-trash"></span>',
                                    'class' => 'unstyled-button'
                                ],
                                'leftButton' => [
                                    'label' => Translate::_('idbyii2', 'Permanently delete selected row'),
                                    'action' => Url::toRoute(['/idbdata/idb-data/delete', 'uuid' => $model[0]]),
                                    'style' => 'btn btn-back'
                                ],
                                'rightButton' => [
                                    'label' => Translate::_('idbyii2', 'Cancel delete action'),
                                    'style' => 'btn btn-success',
                                    'action' => 'data-dismiss',
                                    'style' => 'btn btn-primary'
                                ],
                            ]
                        ]
                    );
                }
            ]
        ];


        return $columns;
    }

    /**
     * @param $internal
     * @param $metadata
     * @return bool
     */
    static function checkIfInternalExists($internal, $metadata)
    {
        foreach ($metadata['data'] as $data) {
            if ($data['object_type'] === 'type' && $data['internal_name'] == $internal) {
                return true;
            } elseif ($data['object_type'] === 'set') {
                if (self::checkIfInternalExists($internal, $data)) {
                    return self::checkIfInternalExists($internal, $data);
                }
            }
        }

        return false;
    }

    static function getUuid($displayName, $metadata)
    {
        foreach ($metadata['data'] as $data) {
            if (
                !empty($data['object_type'])
                && !empty($data['display_name'])
                && !empty($data['display_name'])
                && !empty($displayName[0])
                && $data['object_type'] === 'type'
                && $data['display_name'] === !empty($displayName[1])? $displayName[1] : $displayName[0]
            ) {
                if(!empty($displayName[1])) {
                    if($metadata['display_name'] === $displayName[0]) {
                        return $data['uuid'];
                    }
                } else {
                    return $data['uuid'];
                }
            } elseif ($data['object_type'] === 'set') {
                if (self::getUuid($displayName, $data) !== false) {
                    return self::getUuid($displayName, $data);
                }
            }
        }

        return false;
    }
}

################################################################################
#                                End of file                                   #
################################################################################
