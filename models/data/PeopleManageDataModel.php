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

namespace idbyii2\models\data;

################################################################################
# Use(s)                                                                       #
################################################################################

use idbyii2\helpers\Translate;
use idbyii2\models\form\IdbModel;

################################################################################
# Class(es)                                                                    #
################################################################################

/**
 * Class PeopleManageDataModel
 *
 * @package idbyii2\models\data
 */
class PeopleManageDataModel extends IdbModel
{

    // Contact person
    public $dataTypes;
    public $dataSets;

    public $value;
    public $displayed;
    public $attribute;

    public $oldValue;
    public $oldDisplayed;
    public $oldAttribute;

    /**
     * @return array
     */
    public function attributeLabels()
    {
        return
            [
                'dataTypes' => Translate::_('idbyii2', 'Data Types'),
                'repeatPassword' => Translate::_('idbyii2', 'Data Sets'),
            ];
    }

    /**
     * @return array
     */
    public function attributeHints()
    {
        return
            [
                'dataTypes' => Translate::_(
                    'idbyii2',
                    'Choose data types, you want to add to your profile'
                ),
                'dataSets' => Translate::_('idbyii2', 'Choose data sets, you want to add to your profile'),
            ];
    }
}

################################################################################
#                                End of file                                   #
################################################################################
