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

namespace idbyii2\models\db;

################################################################################
# Use(s)                                                                       #
################################################################################

use idbyii2\helpers\Translate;

################################################################################
# Class(es)                                                                    #
################################################################################

/**
 * This is the model class for table "import".
 *
 * @property int    $id
 * @property string $uid
 * @property string $oid
 * @property string $aid
 * @property string $dbid
 * @property string $created_at
 * @property string $file_name
 * @property string $file_path
 * @property string $status
 * @property string $steps
 * @property string $import_attributes
 *
 */
class BusinessImport extends IdbModel
{

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'import';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['created_at'], 'safe'],
            [['file_name', 'file_path', 'uid'], 'required'],
            [['status', 'import_attributes'], 'string'],
            [['file_name', 'file_path'], 'string', 'max' => 255],
            [['file_name'], 'unique'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Translate::_('idbyii2', 'ID'),
            'created_at' => Translate::_('idbyii2', 'Uploaded on'),
            'file_name' => Translate::_('idbyii2', 'File Name'),
            'file_path' => Translate::_('idbyii2', 'File Name'),
            'status' => Translate::_('idbyii2', 'Status'),
            'import_attributes' => Translate::_('idbyii2', 'Attributes'),
        ];
    }

    /**
     * @return mixed|null
     */
    public function getBeforeStep()
    {
        $steps = json_decode($this->steps, true);

        if (!is_array($steps)) {
            return null;
        }

        return end($steps);
    }

    /**
     * @return array|null
     */
    public function getBackUrl()
    {
        $step = $this->getBeforeStep();

        switch ($step) {
            case 'select-db':
                return ['/tools/wizard/select-db'];
            case 'index':
                return ['/tools/wizard/index'];
            case 'worksheets':
                return ['/tools/wizard/worksheets', 'file' => $this->id];
        }
    }
}

################################################################################
#                                End of file                                   #
################################################################################
