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

use Yii;
use yii\base\Component;

################################################################################
# Class(es)                                                                    #
################################################################################

/**
 * Class AuditComponent
 *
 * @package idbyii2\audit
 */
class AuditComponent extends Component
{

    public $auditMessage = null;
    public $auditConfig = null;
    public $auditFile = null;
    private $_auditConfig = false;
    private $_auditFile = false;

    /**
     * @return void
     */
    public function init()
    {
        parent::init();

        if (($this->_auditConfig === false) && (!empty($this->auditConfig['class']))) {
            $this->_auditConfig = new $this->auditConfig['class']($this->auditConfig);
        }
        if (($this->_auditFile === false) && (!empty($this->auditFile['class']))) {
            $this->_auditFile = new $this->auditFile['class']($this->auditFile);
        }
    }

    /**
     * @param $event
     *
     * @return void
     */
    public function execute($event)
    {
        if (
            $this->_auditConfig
            && $this->_auditConfig->isEnabled()
            && $this->_auditFile
        ) {
            if (!empty($this->auditMessage['class'])) {
                $message = new $this->auditMessage['class']($this->auditMessage);
                $this->_auditFile->save($message->create($event));
            }
        }
    }

    /**
     * @param $action
     *
     * @return void
     */
    public static function actionAudit($action)
    {
        if (!empty(Yii::$app->audit)) {
            Yii::$app->audit->execute($action);
        }
    }
}

################################################################################
#                                End of file                                   #
################################################################################
