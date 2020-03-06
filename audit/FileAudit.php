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

use app\helpers\BusinessConfig;
use app\helpers\Translate;
use xmz\simplelog\SNLog as Log;
use Yii;
use yii\base\InvalidConfigException;

################################################################################
# Class(es)                                                                    #
################################################################################

/**
 * Class FileAudit
 *
 * @package idbyii2\audit
 */
class FileAudit
{

    private $_auditPath;
    private $_auditFile = 'audit.log';

    /**
     * FileAudit constructor.
     *
     * @param null $config
     */
    public function __construct($config = null)
    {
        if (!empty($config['auditFile'])) {
            $this->_auditFile = $config['auditFile'];
        }
        if (!empty($config['auditPath'])) {
            $this->setAuditPath($config['auditPath']);
        } elseif (empty($this->_auditPath)) {
            $this->setAuditPath(Yii::getAlias('@runtime/logs'));
        }
    }

    /**
     * @param $value
     *
     * @return void
     */
    private function setAuditPath($value)
    {
        $this->_auditPath = realpath($value);
        if ($this->_auditPath === false || !is_dir($this->_auditPath) || !is_writable($this->_auditPath)) {
            throw new InvalidConfigException(
                Translate::_(
                    'idbyii2',
                    'Internal error number 34. Please notify Identity Bank.',
                    ['path' => $value]
                )
            );
        }
    }

    /**
     * @return string
     */
    private function getFullAuditFilePath()
    {
        return $this->_auditPath . '/' . $this->_auditFile;
    }

    /**
     * @param $message
     *
     * @return void
     */
    public function save($message)
    {
        $auditFilePath = $this->getFullAuditFilePath();
        $fileHandle = fopen($auditFilePath, 'a+');
        if ($fileHandle) {
            fwrite($fileHandle, $message . PHP_EOL);
            fclose($fileHandle);
        } else {
            $pid = getmypid();
            Log::error(
                BusinessConfig::get()->getLogName(),
                "$pid - ${_SERVER['REQUEST_URI']} - " .
                "Cannot open audit file : " . $auditFilePath
            );
        }
    }
}

################################################################################
#                                End of file                                   #
################################################################################
