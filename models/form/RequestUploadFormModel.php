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
use Yii;

################################################################################
# Class(es)                                                                    #
################################################################################
class RequestUploadFormModel extends IdbModel
{

    public $people_user;
    public $name;
    public $message;
    public $upload_limit;

    public function rules()
    {
        $config = Yii::$app->controller->module->configIdbStorage;

        return [
            [['people_user'], 'required'],
            [['name'], 'required'],
            [['message'], 'required'],
            [['upload_limit'], 'required'],
            [['upload_limit'],'number','min'=>$config['minRequestUploadsLimit']],
            [['upload_limit'],'number','max'=>$config['maxRequestUploadsLimit']],
            [['upload_limit'], 'default', 'value' => $config['defaultRequestUploadsLimit']]
        ];
    }

    public function __construct($config = [])
    {
        parent::__construct($config);
        $this->upload_limit = Yii::$app->controller->module->configIdbStorage['defaultRequestUploadsLimit'];
    }

    public function attributeLabels()
    {
        return [
            'people_user' => Translate::_('idbyii2', 'People to request'),
            'name' => Translate::_('idbyii2', 'Filename'),
            'message' => Translate::_('idbyii2', 'Message'),
            'upload_limit' => Translate::_('idbyii2', 'Upload limit'),
        ];
    }
}

################################################################################
#                                End of file                                   #
################################################################################
