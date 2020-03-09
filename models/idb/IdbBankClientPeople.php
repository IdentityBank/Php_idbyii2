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

use Yii;

################################################################################
# Class(es)                                                                    #
################################################################################

/**
 * Class IdbBankClientPeople
 *
 * @package idbyii2\models\idb
 */
class IdbBankClientPeople extends IdbBankClient
{

    const OBJECT_SERVICE = 'people';
    public $service = IdbBankClientPeople::OBJECT_SERVICE;

    /**
     * @param $accountName
     *
     * @return \idb\idbank\PeopleIdBankClient|null
     * @throws \Exception
     */
    protected function getIdbModel($accountName)
    {
        $model = IdbBankModelPeople::model(IdbBankClientPeople::OBJECT_SERVICE, $accountName);
        if ($model) {
            $model->setConnection($this->host, $this->port, $this->configuration);
            $model->validateServiceIdbIdFunction = $this;
        }

        return $model;
    }

    /**
     * @param $accountName
     *
     * @return \idb\idbank\PeopleIdBankClient
     */
    public static function model($accountName)
    {
        if (!empty(Yii::$app->idbankclientpeople)) {
            return Yii::$app->idbankclientpeople->getIdbModel($accountName);
        }
    }
}

################################################################################
#                                End of file                                   #
################################################################################
