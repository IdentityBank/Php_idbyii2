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

use app\helpers\PeopleConfig;
use idbyii2\helpers\IdbSecurity;
use Yii;
use yii\db\ActiveRecord;

################################################################################
# Class(es)                                                                    #
################################################################################

/**
 * Class PeopleSearchModel
 *
 * @package idbyii2\models\db
 */
class PeopleSearchModel extends ActiveRecord
{

    protected $idbSecurity;
    protected $searchPassword = 'password';
    protected $blowfishCost = 1;

    /**
     * @return mixed
     */
    public static function getDb()
    {
        return Yii::$app->get('p57b_people_search');
    }

    /**
     * @return void
     */
    public function init()
    {
        $this->searchPassword = PeopleConfig::get()->getYii2PeopleSearchPassword();
        $this->blowfishCost = PeopleConfig::get()->getYii2PeopleSearchBlowfishCost();
        $this->idbSecurity = new IdbSecurity(Yii::$app->security);
        parent::init();
    }

    /**
     * @param null $row
     *
     * @return static
     */
    public static function instantiate($row = null)
    {
        $model = new static();
        if (is_array($row) && !empty($row)) {
            $model->setAttributes($row, false);
        }

        return $model;
    }
}

################################################################################
#                                End of file                                   #
################################################################################
