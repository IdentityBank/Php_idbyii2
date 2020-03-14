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

namespace idbyii2\models\form;

################################################################################
# Use(s)                                                                       #
################################################################################

use app\helpers\Translate;
use idbyii2\models\db\BusinessNotification;

################################################################################
# Class(es)                                                                    #
################################################################################

class NotificationsForm extends IdbModel
{

    public $id;
    public $uid;
    public $expires_at;
    public $title;
    public $body;
    public $type;
    public $status;
    public $topic;
    public $url;
    public $action_name;

    /**
     * @return array
     */
    public function rules()
    {
        return
            [
                [['title', 'uid', 'body', 'type', 'status', 'url', 'action_name'], 'required'],
                [['expires_at', 'topic'], 'safe']
            ];
    }

    /**
     * @return array
     */
    public function attributeLabels()
    {
        return
            [
                'uid' => Translate::_('idbyii2', 'UID'),
                'expires_at' => Translate::_('idbyii2', 'Expires At'),
                'title' => Translate::_('idbyii2', 'Title'),
                'body' => Translate::_('idbyii2', 'Body'),
                'type' => Translate::_('idbyii2', 'Type'),
                'status' => Translate::_('idbyii2', 'Status'),
                'issued_at' => Translate::_('idbyii2', 'Issued at'),
                'url' => Translate::_('idbyii2', 'URL'),
                'action_name' => Translate::_('idbyii2', 'Action')
            ];
    }

    public static function fromBusinessNotification($modelBusinessNotification)
    {
        $model = new NotificationsForm();

        $model->id = $modelBusinessNotification->id;
        $model->uid = $modelBusinessNotification->uid;
        $model->type = $modelBusinessNotification->type;
        $model->status = $modelBusinessNotification->status;
        $model->topic = $modelBusinessNotification->topic;
        $model->expires_at = $modelBusinessNotification->expires_at;
        $data = $modelBusinessNotification->data;

        $data = json_decode($data, true);

        if (!empty($data['title'])) {
            $model->title = $data['title'];
        }
        if (!empty($data['body'])) {
            $model->body = $data['body'];
        }
        if (!empty($data['url'])) {
            $model->url = $data['url'];
        }
        if (!empty($data['action_name'])) {
            $model->action_name = $data['action_name'];
        }

        return $model;
    }

    public function save()
    {
        $model = BusinessNotification::instantiate(
            [
                'type' => $this->type,
                'status' => $this->status,
                'topic' => $this->topic?? '',
                'expires_at' => $this->expires_at,
                'uid' => $this->uid,
                'data' => json_encode(
                    [
                        'title' => $this->title,
                        'body' => $this->body,
                        'url' => $this->url,
                        'action_name' => $this->action_name
                    ]
                )
            ]
        );
        $status = $model->validate() && $model->save();
        if ($status) {
            $this->id = $model->id;
        }

        return $status;
    }

    public function update()
    {
        if (($model = BusinessNotification::findOne($this->id)) !== null) {
            $model->load(
                [
                    'BusinessNotification' =>
                        [
                            'type' => $this->type,
                            'status' => $this->status,
                            'expires_at' => $this->expires_at,
                            'uid' => $this->uid,
                            'data' => json_encode(
                                [
                                    'title' => $this->title,
                                    'body' => $this->body,
                                    'url' => $this->url,
                                    'action_name' => $this->action_name,
                                ]
                            )
                        ]
                ]
            );
            $status = $model->validate() && $model->update();
            if ($status) {
                $this->id = $model->id;
            }

            return $status;
        }

        return false;
    }
}

################################################################################
#                                End of file                                   #
################################################################################
