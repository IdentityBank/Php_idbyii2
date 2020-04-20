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

namespace idbyii2\helpers;

################################################################################
# Include(s)                                                                   #
################################################################################

include_once 'simplelog.inc';
include_once 'jsonsimpleconfig.inc';

################################################################################
# Use(s)                                                                       #
################################################################################

use xmz\jsonsimpleconfig\Jsc;
use yii\helpers\Html;

################################################################################
# Local Config                                                                 #
################################################################################

const serverConfigFile = '/etc/p57b/server.jsc';
const yii2ConfigFile = '/etc/p57b/idbyii2.jsc';

################################################################################
# Class(es)                                                                    #
################################################################################

/**
 * Class IdbYii2Config
 *
 * @package idbyii2\helpers
 */
class IdbYii2Config
{

    private static $instance;
    private $jscData;

    /**
     * IdbYii2Config constructor.
     */
    protected function __construct()
    {
        $this->jscData = Jsc::get(yii2ConfigFile);
        if (empty($this->jscData)) {
            $this->jscData = Jsc::gets('');
        }
        $this->mergeJscFile(serverConfigFile);
    }

    /**
     * @param $loginConfigFile
     *
     * @return void
     */
    public function mergeJscFile($loginConfigFile)
    {
        $this->mergeJsc(Jsc::get($loginConfigFile));
    }

    /**
     * @param $jsc
     *
     * @return void
     */
    public function mergeJsc($jsc)
    {
        $this->jscData->merge($jsc);
    }

    /**
     * Render script tag with options from array in format ["variable_name" => "value"] (Supported: string, bool, int,
     * double, float).
     *
     * @param array $options
     *
     * @return string
     */
    public static function jsOptions(Array $options)
    {
        $variables = '';
        foreach ($options as $key => $option) {
            if (
                gettype($option) === 'double'
                && gettype($option) === 'integer'
            ) {
                $variables .= 'const ' . $key . ' = ' . $option . ';';
            } elseif (gettype($option) === 'boolean') {
                $variables .= 'const ' . $key . ' = ' . ($option ? 'true' : 'false') . ';';
            } else {
                $variables .= 'const ' . $key . ' = "' . addslashes($option) . '";';
            }
        }

        return Html::script($variables);
    }

    /**
     * @return \idbyii2\helpers\IdbYii2Config
     */
    public static function get()
    {
        if (!isset(self::$instance) || !self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * @return null
     */
    function getAccountDestinationUrls()
    {
        return $this->getSection(
            '"Yii2"."idbDestinationUrls"',
            [
                'business' => 'business',
                'people' => 'people',
                'admin' => 'admin',
                'billing' => 'billing',
            ]
        );
    }

    /**
     * @param      $group
     * @param null $default
     *
     * @return null
     */
    function getSection($group, $default = null)
    {
        if (is_null($this->jscData)) {
            return null;
        }
        $data = $this->jscData->getSection($group);
        if (empty($data) && !empty($default)) {
            $data = $default;
        }

        return $data;
    }

    /**
     * @return null
     */
    function getAccountDestinationTokens()
    {
        return $this->getSection(
            '"Yii2"."idbDestinationTokens"',
            [
                'business' => 'business',
                'people' => 'people',
                'admin' => 'admin',
                'billing' => 'billing',
            ]
        );
    }

    /**
     * @return null
     */
    function getAccountDestinationPasswords()
    {
        return $this->getSection(
            '"Yii2"."idbDestinationPasswords"',
            [
                'business' => 'business',
                'people' => 'people',
                'admin' => 'admin',
                'billing' => 'billing',
            ]
        );
    }

    /**
     * @return null
     */
    function getMessengerConfiguration()
    {
        return $this->getSection('"IDBank"."Messenger"');
    }

    /**
     * @return null
     */
    function getIdBankConfiguration()
    {
        return $this->getSection('"IDBank"."IdBankClient"');
    }

    /**
     * @return null
     */
    function getIdBillConfiguration()
    {
        return $this->getSection('"IDBill"."IdbBillingClient"');
    }

    /**
     * @return null
     */
    function getBusinessPortalApiConfiguration()
    {
        return $this->getSection('"IDBank"."PortalApi"."Business"');
    }

    /**
     * @return null
     */
    function getPeoplePortalApiConfiguration()
    {
        return $this->getSection('"IDBank"."PortalApi"."People"');
    }

    /**
     * @return null
     */
    function getYii2BusinessDbHost()
    {
        return $this->getValue('"Yii2"."business"."db"', 'dbHost');
    }

    /**
     * @return null
     */
    function getYii2BusinessDbPort()
    {
        return $this->getValue('"Yii2"."business"."db"', 'dbPort');
    }

    /**
     * @return string
     */
    function getYii2BusinessDbSchema()
    {
        return 'p57b_business';
    }

    /**
     * @return null
     */
    function getYii2BusinessDbName()
    {
        return $this->getValue('"Yii2"."business"."db"', 'dbName');
    }

    /**
     * @return null
     */
    function getYii2BusinessDbUser()
    {
        return $this->getValue('"Yii2"."business"."db"', 'dbUser');
    }

    /**
     * @return null
     */
    function getYii2BusinessDbPassword()
    {
        return $this->getValue('"Yii2"."business"."db"', 'dbPassword');
    }

    /**
     * @return null
     */
    function getYii2PeopleDbHost()
    {
        return $this->getValue('"Yii2"."people"."db"', 'dbHost');
    }

    /**
     * @return null
     */
    function getYii2PeopleDbPort()
    {
        return $this->getValue('"Yii2"."people"."db"', 'dbPort');
    }

    /**
     * @return string
     */
    function getYii2PeopleDbSchema()
    {
        return 'p57b_people';
    }

    /**
     * @return null
     */
    function getYii2PeopleDbName()
    {
        return $this->getValue('"Yii2"."people"."db"', 'dbName');
    }

    /**
     * @return null
     */
    function getYii2PeopleDbUser()
    {
        return $this->getValue('"Yii2"."people"."db"', 'dbUser');
    }

    /**
     * @return null
     */
    function getYii2PeopleDbPassword()
    {
        return $this->getValue('"Yii2"."people"."db"', 'dbPassword');
    }

    /**
     * @return null
     */
    function getYii2BillingDbHost()
    {
        return $this->getValue('"Yii2"."billing"."db"', 'dbHost');
    }

    /**
     * @return null
     */
    function getYii2BillingDbPort()
    {
        return $this->getValue('"Yii2"."billing"."db"', 'dbPort');
    }

    /**
     * @return string
     */
    function getYii2BillingDbSchema()
    {
        return 'p57b_billing';
    }

    /**
     * @return null
     */
    function getYii2BillingDbName()
    {
        return $this->getValue('"Yii2"."billing"."db"', 'dbName');
    }

    /**
     * @return null
     */
    function getYii2BillingDbUser()
    {
        return $this->getValue('"Yii2"."billing"."db"', 'dbUser');
    }

    /**
     * @return null
     */
    function getYii2BillingDbPassword()
    {
        return $this->getValue('"Yii2"."billing"."db"', 'dbPassword');
    }

    /**
     * @return null
     */
    function getYii2SecurityGiiAllowedIP()
    {
        return $this->getValue('"Yii2"."security"', 'giiAllowedIP', null);
    }

    /**
     * @return null
     */
    function idbIdValidationEnabled()
    {
        return $this->getValue('"IDBank"."idbId"', 'validate', true);
    }

    /**
     * @return null
     */
    function idbApiMigrationEnabled()
    {
        return $this->getValue('"IDBank"."api"', 'allowMigrationIdbank');
    }

    /**
     * @return null
     */
    function getIdbBusinessMigrationPath()
    {
        return $this->getValue(
            '"IDBank"."api"',
            'businessMigrationPath',
            '/usr/local/share/p57b/php/idbconsole/migration/business'
        );
    }

    /**
     * @return null
     */
    function getIdbPeopleMigrationPath()
    {
        return $this->getValue(
            '"IDBank"."api"',
            'peopleMigrationPath',
            '/usr/local/share/p57b/php/idbconsole/migration/people'
        );
    }

    /**
     * @param null $portal
     *
     * @return string|null
     */
    function getYii2MfaIssuer($portal = null)
    {
        $purpose = $this->serverPurpose();
        $mfaIssuer = $this->getValue('"Yii2"."MFA"', 'issuer', 'Identity Bank');
        if ($this->isMfaServerTagEnabled()) {
            $mfaTag = $this->mfaServerTag();
            if (!empty($mfaTag)) {
                $mfaIssuer .= " [$mfaTag]";
            }
            if (!empty($portal)) {
                $portal = IdbAccountNumberDestination::fromId($portal);
                switch ($portal->toId()) {
                    case IdbAccountNumberDestination::business:
                        {
                            if (
                                empty($purpose)
                                || strtolower($purpose) === 'live'
                            ) {
                                $portal = Translate::_('idbyii2', 'MFABusiness');
                            } else {
                                $portal = $portal->toString();
                            }
                        }
                        break;
                    case IdbAccountNumberDestination::people:
                    case IdbAccountNumberDestination::admin:
                    case IdbAccountNumberDestination::billing:
                        {
                            if (
                                empty($purpose)
                                || strtolower($purpose) === 'live'
                            ) {
                                $portal = null;
                            } else {
                                $portal = $portal->toString();
                            }
                        }
                        break;
                    default:
                        {
                            $portal = null;
                        }
                        break;
                }
                if (!empty($portal)) {
                    $mfaIssuer .= " [$portal]";
                }
            }

            return $mfaIssuer;
        } else {
            return $mfaIssuer;
        }
    }

    /**
     * @return null
     */
    function serverPurpose()
    {
        return $this->getValue('Server', 'purpose');
    }

    /**
     * @return null
     */
    function isMfaServerTagEnabled()
    {
        return $this->getValue('"Yii2"."MFA"', 'serverTagEnabled', true);
    }

    /**
     * @return null
     */
    function mfaServerTag()
    {
        $purpose = $this->serverPurpose();
        $serverName = $this->serverName();
        if (
            empty($purpose)
            || strtolower($purpose) !== 'live'
        ) {
            return $serverName;
        }

        return null;
    }

    /**
     * @return null
     */
    function serverName()
    {
        return $this->getValue('Server', 'name');
    }

    /**
     * @param      $group
     * @param      $key
     * @param null $default
     *
     * @return null
     */
    function getValue($group, $key, $default = null)
    {
        if (is_null($this->jscData)) {
            return null;
        }

        return ($this->jscData->getValue($group, $key, $default));
    }

    /**
     * @return null
     */
    function isMfaSkipEnabled()
    {
        return $this->getValue('"Yii2"."MFA"', 'skippable', false);
    }

    /**
     * @return null
     */
    function getIdbRabbitMqHost()
    {
        return $this->getValue('"Yii2"."queue"."RabbitMQ"', 'host', 'localhost');
    }

    /**
     * @return null
     */
    function getIdbRabbitMqPort()
    {
        return $this->getValue('"Yii2"."queue"."RabbitMQ"', 'port', 5672);
    }

    /**
     * @return null
     */
    function getIdbRabbitMqUser()
    {
        return $this->getValue('"Yii2"."queue"."RabbitMQ"', 'user');
    }

    /**
     * @return null
     */
    function getIdbRabbitMqPassword()
    {
        return $this->getValue('"Yii2"."queue"."RabbitMQ"', 'password');
    }

    /**
     * @return void
     */
    private function __clone()
    {
    }

    /**
     * @return void
     */
    private function __wakeup()
    {
    }

    /**
     * @return null
     */
    function getIdbStorageName()
    {
        return $this->getValue('"IDBStorage"."IdbStorageClient"', 'name', 'p57b');
    }

    /**
     * @return null
     */
    function getIdbStorageHost()
    {
        return $this->getValue('"IDBStorage"."IdbStorageClient"', 'host', '127.0.0.1');
    }

    /**
     * @return null
     */
    function getIdbStoragePort()
    {
        return $this->getValue('"IDBStorage"."IdbStorageClient"', 'port', 97);
    }

    /**
     * @return null
     */
    function getIdbStorageConfiguration()
    {
        return $this->getSection('"IDBStorage"."IdbStorageClient"');
    }

    /**
     * @return null
     */
    function getIdbStorageModuleConfig()
    {
        return $this->getSection('"IDBStorage"."IdbStorageModule"', []);
    }

    /**
     * @return null
     */
    function getLoginFirewall()
    {
        return $this->getValue(null, 'loginFirewall', ['localhost']);
    }

    /**
     * @return null
     */
    function getLoginVerifyPassword()
    {
        return $this->getValue(null, 'loginVerifyPassword', 'IDB');
    }

    /**
     * @return null
     */
    function getLoginVerifyTemplate()
    {
        // Available attributes
        // * timestamp
        // * timestamp_md5
        // * timestamp_sha256
        // * userId
        // * accountNumber
        // * userId_accountNumber_sha256
        return $this->getValue(null, 'loginVerifyTemplate', '{timestamp_sha256}{userId}{accountNumber}{timestamp_md5}');
    }

    /**
     * @return bool
     */
    function isCronPaymentEnabled()
    {
        return ($this->getValue('Payment', 'cronPaymentEnabled') === 'IDB_ALLOW_CRON_PAYMENT');
    }
}

################################################################################
#                                End of file                                   #
################################################################################
