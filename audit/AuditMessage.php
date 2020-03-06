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

namespace idbyii2\audit;

################################################################################
# Use(s)                                                                       #
################################################################################

use DateTime;
use idbyii2\helpers\IdbSecurity;
use Yii;

################################################################################
# Class(es)                                                                    #
################################################################################

/**
 * Class AuditMessage
 *
 * @package idbyii2\audit
 */
class AuditMessage
{

    private $_version = 1;
    private $_separator = "|";
    private $_liveServerLog = false;

    private $_encrypted = false;
    private $_password = 'password';

    /**
     * AuditMessage constructor.
     *
     * @param null $config
     */
    public function __construct($config = null)
    {
        if (isset($config['liveServerLog'])) {
            $this->_liveServerLog = $config['liveServerLog'];
        }
        if (!empty($config['separator'])) {
            $this->setSeparator($config['separator']);
        }
        if (isset($config['encrypted'])) {
            $this->_encrypted = $config['encrypted'];
        }
        if (!empty($config['password'])) {
            $this->setPassword($config['password']);
        }
    }

    /**
     * @param $separator
     *
     * @return void
     */
    public function setSeparator($separator)
    {
        $this->_separator = $separator;
    }

    /**
     * @param $password
     *
     * @return void
     */
    public function setPassword($password)
    {
        $this->_password = $password;
    }

    /**
     * @param $event
     *
     * @return string
     */
    public function create($event)
    {
        $returnValue = "";
        if ($event) {
            //Time & Date
            $returnValue .= $this->_separator;
            $date = new DateTime();
            $returnValue .= $date->format('Y-m-d' . $this->_separator . 'H.i.s');
            $returnValue .= $this->_separator;

            //User
            $id = null;
            $userId = null;
            $accountNumber = null;
            $user = Yii::$app->user->identity;
            if ($user) {
                $id = $user->id;
                $userId = $user->userId;
                $accountNumber = $user->accountNumber;
            }
            $returnMessage = $id;
            $returnMessage .= $this->_separator;
            $returnMessage .= $userId;
            $returnMessage .= $this->_separator;
            $returnMessage .= $accountNumber;
            $returnMessage .= $this->_separator;

            $moduleName = null;
            $moduleName = $module = null;
            $controller = $event->controller;
            if ($controller) {
                $module = $controller->module;
            }
            if ((empty($module->controller)) || ($module->controller !== $controller)) {
                $moduleName = $module->id;
            }
            $returnMessage .= $moduleName;
            $returnMessage .= $this->_separator;

            $controllerName = null;
            if ($controller) {
                $controllerName = $controller->id;
            }
            $returnMessage .= $controllerName;
            $returnMessage .= $this->_separator;

            $actionName = null;
            if ($controller) {
                $action = $controller->action;
                if ($action) {
                    $actionName = $action->id;
                }
            }
            $returnMessage .= $actionName;
            $returnMessage .= $this->_separator;

            // _SERVER, _SESSION, _COOKIE, _REQUEST, _GET, _POST

            $requestUriName = null;
            if (isset($_SERVER) && isset($_SERVER['REQUEST_URI'])) {
                $requestUriName = $_SERVER['REQUEST_URI'];
            }
            $returnMessage .= $requestUriName;
            $returnMessage .= $this->_separator;

            $get = $getData = null;
            if (isset($_GET)) {
                $get = $_GET;
                $getData = json_encode($get);
            }

            $post = $postData = null;

            if (isset($_POST)) {
                $post = $_POST;
                $this->removeForbidden($post);
            }

            $request_data = [];
            $request_data['FORMAT_VERSION'] = $this->_version;
            if (!empty($get)) {
                $request_data['GET'] = $get;
            }
            if (!empty($post)) {
                $request_data['POST'] = $post;
            }
            if ($this->_liveServerLog) {
                $request_data['SERVER'] = [];
                if (!empty($_SERVER["REMOTE_ADDR"])) {
                    $request_data["SERVER"]["REMOTE_ADDR"] = $_SERVER["REMOTE_ADDR"];
                }
                if (!empty($_SERVER["REMOTE_HOST"])) {
                    $request_data["SERVER"]["REMOTE_HOST"] = $_SERVER["REMOTE_HOST"];
                }
                if (!empty($_SERVER["HTTP_HOST"])) {
                    $request_data["SERVER"]["HTTP_HOST"] = $_SERVER["HTTP_HOST"];
                }
                if (!empty($_SERVER["HTTP_USER_AGENT"])) {
                    $request_data["SERVER"]["HTTP_USER_AGENT"] = $_SERVER["HTTP_USER_AGENT"];
                }
                if (!empty($_SERVER["HTTP_COOKIE"])) {
                    $request_data["SERVER"]["HTTP_COOKIE"] = $_SERVER["HTTP_COOKIE"];
                }
            } else {
                if (isset($_REQUEST)) {
                    $request = $_REQUEST;
                    $this->removeForbidden($request);
                }
                $request_data['SERVER'] = $_SERVER;
                $request_data['REQUEST'] = $request;
                $request_data['HEADERS'] = apache_request_headers();
            }
            $request_data = json_encode($request_data);

            switch ($this->_version) {
                case 1:
                default:
                    {
                        if ($this->_liveServerLog) {
                            $getData = json_encode([]);
                        }
                        $returnMessage .= $getData;
                        $returnMessage .= $this->_separator;
                        $returnMessage .= $request_data;
                    }
                    break;
            }
            $returnMessage .= $this->_separator;

            if (($this->_encrypted) && (!empty($this->_password))) {
                $idbSecurity = new IdbSecurity(Yii::$app->security);
                $returnMessage = base64_encode($idbSecurity->encryptByPasswordSpeed($returnMessage, $this->_password));
                $returnMessage .= $this->_separator;
            }
            $returnValue .= $returnMessage;
        }

        return $returnValue;
    }

    private static $forbiddenItems =
        [
            'IdbLoginForm' => 'password',
            'IdbUserChangePasswordForm' => 'password',
            'IdbUserChangePasswordForm' => 'verifyPassword'
        ];

    /**
     * @param      $source
     * @param null $forbidden
     *
     * @return void
     */
    private function removeForbidden(&$source, $forbidden = null)
    {
        if (empty($forbidden)) {
            foreach (self::$forbiddenItems as $key => $forbiddenItem) {
                if (!empty($source[$key])) {
                    if (is_array($forbiddenItem)) {
                        $this->removeForbidden($source[$key], $forbiddenItem);
                    } elseif (!empty($source[$key][$forbiddenItem])) {
                        $source[$key][$forbiddenItem] = "***";
                    }
                }
            }
        } elseif (is_array($forbidden)) {
            foreach ($forbidden as $key => $forbiddenItem) {
                if (!empty($source[$key])) {
                    if (is_array($forbiddenItem)) {
                        $this->removeForbidden($source[$key], $forbiddenItem);
                    } elseif (!empty($source[$key][$forbiddenItem])) {
                        $source[$key][$forbiddenItem] = "***";
                    }
                }
            }
        } else {
            if (!empty($source[$forbidden])) {
                $source[$forbidden] = "***";
            }
        }
    }
}

################################################################################
#                                End of file                                   #
################################################################################
