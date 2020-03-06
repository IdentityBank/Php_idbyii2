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

class IdbStorageItem
{
    /** @var int $id  */
    public $id;
    /** @var string $uid */
    public $uid;
    /** @var boolean $owner */
    public $owner;
    /** @var string $oid */
    public $oid;
    /** @var string $type */
    public $type;
    /** @var string $pid */
    public $pid;
    /** @var string $createtime */
    public $createtime;
    /** @var string $updatetime */
    public $updatetime;
    /** @var string $name */
    public $name;
    /** @var array $metadata */
    public $metadata;
    /** @var array $permission */
    public $permission;

    public function __construct($dataArray)
    {
        $this->id = ArrayHelper::getValue($dataArray, '0', null);
        $this->uid = ArrayHelper::getValue($dataArray, '1', null);
        $this->owner = ArrayHelper::getValue($dataArray, '2', null);
        $this->oid = ArrayHelper::getValue($dataArray, '3', null);
        $this->type = ArrayHelper::getValue($dataArray, '4', null);
        $this->pid = ArrayHelper::getValue($dataArray, '5', null);
        $this->createtime = ArrayHelper::getValue($dataArray, '6', null);
        $this->updatetime = ArrayHelper::getValue($dataArray, '6', null);
        $this->name = ArrayHelper::getValue($dataArray, '8', null);
        $this->metadata = json_decode(ArrayHelper::getValue($dataArray, '9', null), true);
        $this->permission = ArrayHelper::getValue($dataArray, '10', null);
    }

    public static function initMultiple($dataArray)
    {
        $models = [];
        foreach ($dataArray as $data) {
            $models [] = new self($data);
        }

        return $models;
    }
}

################################################################################
#                                End of file                                   #
################################################################################
