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
 * This is the model class for table "idb_account_user".
 *
 * @property string $aid
 * @property string $uid
 */
class BusinessAccountUser extends IdbCrossTable
{

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'idb_account_user';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['aid', 'uid'], 'required'],
            [['aid', 'uid'], 'string', 'max' => 255],
            [['aid', 'uid'], 'unique', 'targetAttribute' => ['aid', 'uid']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'aid' => Translate::_('idbyii2', 'Account ID'),
            'uid' => Translate::_('idbyii2', 'User ID'),
        ];
    }
}

################################################################################
#                                End of file                                   #
################################################################################
