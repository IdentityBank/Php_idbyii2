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
# Use(s)                                                                       #
################################################################################

use Exception;
use idbyii2\components\IdbRabbitMq;
use yii\console\Controller;
use yii\console\ExitCode;

################################################################################
# Class(es)                                                                    #
################################################################################

/**
 * Class IdbTask
 *
 * @package idbyii2\helpers
 */
class IdbTask extends Controller
{

    /**
     * Custom task
     *
     * Execute custom task
     *
     * param $args - name of the action and all task arguments
     **/
    public function actionCustom($data)
    {
        try {
        } catch (Exception $e) {
            return ExitCode::UNSPECIFIED_ERROR;
        }

        return ExitCode::OK;
    }

    /**
     * Import data task
     *
     * Import data from uploaded file.
     *
     * @param $message - echo message for queue system
     *
     * @return int
     */
    public function actionEcho($message)
    {
        try {
            $channelName = "echoIDB";
            IdbQueueHelper::echoLog(" [x] Sent [$message]");
            $idbRabbitMq = IdbRabbitMq::get();
            $idbRabbitMq->produce($channelName, $message);
        } catch (Exception $e) {
            return ExitCode::UNSPECIFIED_ERROR;
        }

        return ExitCode::OK;
    }

    /**
     * Import data task
     *
     * Import data from uploaded file.
     *
     **/
    public function actionImport($data)
    {
        try {
            Import::executeImportsForDb($data);
        } catch (Exception $e) {
            return ExitCode::UNSPECIFIED_ERROR;
        }

        return ExitCode::OK;
    }

    /**
     * Export data task
     *
     * Export data from data view.
     *
     **/
    public function actionExport($data)
    {
        try {
            Export::executeExportsForDb($data);
        } catch (Exception $e) {
            return ExitCode::UNSPECIFIED_ERROR;
        }

        return ExitCode::OK;
    }

    /**
     * Process invitation
     *
     * Process invitations to people portal
     *
     **/
    public function actionPeopleAccess($data)
    {
        try {
            PeopleAccessHelper::executeSendInvitations($data);
        } catch (Exception $e) {
            return ExitCode::UNSPECIFIED_ERROR;
        }

        return ExitCode::OK;
    }

    /**
     * Process notification - used for
     *
     * Process notification (used for) to people portal
     *
     **/
    public function actionSendUsedFor($data)
    {
        try {
            SendUsedForHelper::executeSendUsedFor($data);
        } catch (Exception $e) {
            return ExitCode::UNSPECIFIED_ERROR;
        }

        return ExitCode::OK;
    }

    /**
     * Process notification - used for
     *
     * Process notification (used for) to people portal
     *
     **/
    public function actionTakeCredits($data)
    {
        try {
            Credits::executeTakeCredits($data);
        } catch (Exception $e) {
            return ExitCode::UNSPECIFIED_ERROR;
        }

        return ExitCode::OK;
    }
}

################################################################################
#                                End of file                                   #
################################################################################
