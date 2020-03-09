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

use idb\idbill\IdbBillingClient as IdbillClient;
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
class IdbBillingClient extends Component
{

    public $host = null;
    public $port = null;
    public $configuration = null;
    public $billingName = null;

    /**
     * @return mixed
     */
    protected function getIdbBillingModel()
    {
        $model = IdbillClient::model($this->billingName);
        if ($model) {
            $model->setConnection($this->host, $this->port, $this->configuration);
        }

        return $model;
    }

    /**
     * @param $billingName
     * @param $host
     * @param $port
     * @param $configuration
     *
     * @return mixed
     */
    public static function create($billingName, $host, $port, $configuration)
    {
        $client = new IdbBankClient();
        $client->host = $host;
        $client->port = $port;
        $client->configuration = $configuration;
        $client->billingName = $billingName;

        return $client->getIdbBillingModel();
    }

    /**
     * @return mixed
     */
    public static function model()
    {
        if (!empty(Yii::$app->idbillclient)) {
            return Yii::$app->idbillclient->getIdbBillingModel();
        }
    }
}

################################################################################
#                                End of file                                   #
################################################################################
