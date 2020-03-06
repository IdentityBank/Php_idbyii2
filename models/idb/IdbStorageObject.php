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

use yii\helpers\ArrayHelper;

################################################################################
# Class(es)                                                                    #
################################################################################

class IdbStorageObject
{

    /** @var int $id */
    public $id;
    /** @var integer $version */
    public $version;
    /** @var string $oid */
    public $oid;
    /** @var string $createtime */
    public $createtime;
    /** @var string $updatetime */
    public $updatetime;
    /** @var string $tag */
    public $tag;
    /** @var array $storage */
    public $storage;
    /** @var array $metadata */
    public $metadata;
    /** @var array $attributes */
    public $attributes;
    /** @var array $permission */
    public $permission;
    /** @var array $security */
    public $security;

    public function __construct($dataArray)
    {
        $this->id = ArrayHelper::getValue($dataArray, '0', null);
        $this->version = ArrayHelper::getValue($dataArray, '1', null);
        $this->oid = ArrayHelper::getValue($dataArray, '2', null);
        $this->createtime = ArrayHelper::getValue($dataArray, '3', null);
        $this->updatetime = ArrayHelper::getValue($dataArray, '4', null);
        $this->tag = ArrayHelper::getValue($dataArray, '5', null);
        $this->storage = json_decode(ArrayHelper::getValue($dataArray, '6', null), true);
        $this->metadata = json_decode(ArrayHelper::getValue($dataArray, '7', null), true);
        $this->attributes = json_decode(ArrayHelper::getValue($dataArray, '8', null), true);
        $this->permission = ArrayHelper::getValue($dataArray, '9', null);
        $this->security = ArrayHelper::getValue($dataArray, '10', null);
    }
}

################################################################################
#                                End of file                                   #
################################################################################
