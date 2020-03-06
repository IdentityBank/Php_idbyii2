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
# Include(s)                                                                   #
################################################################################

include_once 'idbank.inc';

################################################################################
# Use(s)                                                                       #
################################################################################

use idb\idbank\BusinessIdBankClient;
use idbyii2\helpers\Translate;
use Yii;

################################################################################
# Class(es)                                                                    #
################################################################################

/**
 * Class IdbBankModel
 *
 * @package idbyii2\models\idb
 */
class IdbBankModelBusiness extends BusinessIdBankClient
{

    /**
     * Sleep time in seconds
     */
    const SLEEP_TIME = 2;
    const SLEEP_MULTIPLIER = 5;

    /**
     * Method get metadata for specific end user database through IDBankClient library
     * Overrides because of cache in business portal
     *
     * @return string|null
     * @throws \Exception
     */
    public function getAccountMetadata()
    {
        if (Yii::$app->cacheApc->exists($this->accountName . '.metadata')) {

            if (Yii::$app->cacheDB->exists($this->accountName . '.md5')) {
                if (
                    Yii::$app->cacheDB->get($this->accountName . '.md5') !== md5(
                        Yii::$app->cacheApc->get($this->accountName . '.metadata')['Metadata']
                    )
                    && Yii::$app->cacheDB->get($this->accountName . '.checksum') !== strlen(
                        Yii::$app->cacheApc->get($this->accountName . '.metadata')['Metadata']
                    )
                ) {
                    for ($i = 0; $i < self::SLEEP_MULTIPLIER; $i++) {
                        if (Yii::$app->cacheDB->get($this->accountName . '.md5')) {
                            return parent::getAccountMetadata();
                        }

                        sleep(self::SLEEP_TIME);
                    }
                }
            }
        }

        return Yii::$app->cacheApc->getOrSet(
            $this->accountName . '.metadata',
            function () {
                if (Yii::$app->cacheDB->exists($this->accountName . '.md5')) {
                    for ($i = 0; $i < self::SLEEP_MULTIPLIER; $i++) {
                        if (Yii::$app->cacheDB->get($this->accountName . '.md5')) {
                            return parent::getAccountMetadata();
                        }

                        sleep(self::SLEEP_TIME);
                    }
                } else {
                    $metadata = parent::getAccountMetadata();
                    if (!empty($metadata) && !empty($metadata['Metadata'])) {
                        Yii::$app->cacheDB->set($this->accountName . '.md5', md5($metadata['Metadata']));
                        Yii::$app->cacheDB->set($this->accountName . '.checksum', strlen($metadata['Metadata']));

                        return $metadata;
                    } else {
                        return null;
                    }
                }
            }
        );
    }

    /**
     * Method set metadata for specific end user database through IDBankClient library
     * Overrides because of cache in business portal
     *
     * @param string $metadata
     *
     * @return string|null
     * @throws \Exception
     */
    public function setAccountMetadata($metadata)
    {
        for ($i = 0; $i < self::SLEEP_MULTIPLIER; $i++) {
            if (Yii::$app->cacheDB->get($this->accountName . '.md5')) {
                Yii::$app->cacheDB->set($this->accountName . '.md5', md5($metadata));
                Yii::$app->cacheDB->set($this->accountName . '.checksum', strlen($metadata));

                return parent::setAccountMetadata($metadata);
            }

            sleep(self::SLEEP_TIME);
        }

        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function parseResponse($response)
    {
        if (empty($response)) {
            Yii::$app->session->setFlash(
                'dangerMessageMain',
                Translate::_(
                    'idbyii2',
                    'An error has occured. Please contact your system administrator.'
                )
            );

            return null;
        } else {
            return parent::parseResponse($response);
        }
    }

    /**
     * Method Return model to communicate through idBankClient
     * Overrides because of need reference to children object
     *
     * @param string $service
     * @param string $accountName
     *
     * @return BusinessIdBankClient|IdbBankModelBusiness|null
     */
    public static function model($service, $accountName)
    {
        if (!empty($service) && !empty($accountName)) {
            return new self($service, $accountName);
        }

        return null;
    }
}

################################################################################
#                                End of file                                   #
################################################################################
