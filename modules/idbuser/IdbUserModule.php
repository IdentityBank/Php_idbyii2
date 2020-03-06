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

namespace idbyii2\modules\idbuser;

################################################################################
# Use(s)                                                                       #
################################################################################

use yii\base\Module;

################################################################################
# Class(es)                                                                    #
################################################################################

/**
 * Class IdbUserModule
 *
 * @package idbyii2\modules\idbuser
 */
class IdbUserModule extends Module
{

    public $configUserAccountDefault = [
        'blowfishCost' => 1,
        'loginPassword' => 'password',
        'uidPassword' => 'password',
    ];
    public $configUserAccount = [];
    public $configUserDataDefault = [
        'blowfishCost' => 1,
        'keyPassword' => 'password',
        'valuePassword' => 'password',
    ];
    public $configUserData = [];

    /**
     * @return void
     */
    public function init()
    {
        if (is_array($this->configUserAccount)) {
            $this->configUserAccount = array_merge($this->configUserAccountDefault, $this->configUserAccount);
        } else {
            $this->configUserAccount = $this->configUserAccountDefault;
        }
        if (is_array($this->configUserData)) {
            $this->configUserData = array_merge($this->configUserDataDefault, $this->configUserData);
        } else {
            $this->configUserData = $this->configUserDataDefault;
        }
        parent::init();
    }
}

################################################################################
#                                End of file                                   #
################################################################################
