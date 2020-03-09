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

use idb\idbank\BusinessIdBankClient;
use idb\idbank\IdBankClient;
use idb\idbank\PeopleIdBankClient;
use idbyii2\helpers\IdbAccountId;
use idbyii2\helpers\IdbYii2Config;
use Yii;
use yii\base\Component;

################################################################################
# Class(es)                                                                    #
################################################################################

/**
 * Class IdbBankClient
 *
 * @package idbyii2\models\idb
 */
class IdbBankClient extends Component
{

    public $host = null;
    public $port = null;
    public $configuration = null;
    public $service = null;

    /**
     * @param      $accountName
     *
     * @param null $service
     * @param null $action
     * @param null $validateAttributes
     *
     * @throws \Exception
     */
    public function validate(
        $accountName,
        $service = null,
        $action = null,
        $validateAttributes = null
    ) {
        if (empty($service)) {
            $service = $this->service;
        }
        if (IdbYii2Config::get()->idbIdValidationEnabled()) {
            IdbAccountId::validateServiceIdbId(
                $accountName,
                $service,
                $action,
                $validateAttributes
            );
        }
    }

    /**
     * @param $service
     * @param $accountName
     * @param $host
     * @param $port
     * @param $configuration
     *
     * @return mixed
     */
    public static function create($service, $accountName, $host, $port, $configuration)
    {
        $client = new IdbBankClient();
        $client->host = $host;
        $client->port = $port;
        $client->configuration = $configuration;
        $client->service = $service;

        return $client->getIdbModel($accountName);
    }

    /**
     * @param $accountName
     *
     * @return IdBankClient|BusinessIdBankClient|PeopleIdBankClient
     */
    public static function model($accountName)
    {
        if (!empty(Yii::$app->idbankclient)) {
            return Yii::$app->idbankclient->getIdbModel($accountName);
        }
    }
}

################################################################################
#                                End of file                                   #
################################################################################
