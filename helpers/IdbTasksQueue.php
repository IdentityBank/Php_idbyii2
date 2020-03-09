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

use Error;
use Exception;
use idbyii2\components\IdbRabbitMq;
use yii\console\Controller;
use yii\console\ExitCode;

################################################################################
# Class(es)                                                                    #
################################################################################

/**
 * Class IdbTasksQueue
 *
 * @package idbyii2\helpers
 */
class IdbTasksQueue extends Controller
{

    /**
     * Custom task
     *
     * Execute custom task
     *
     * param $args - name of the action and all task arguments
     **/
    public function actionCustom($args = null)
    {
        try {
        } catch (Exception $e) {
            return ExitCode::UNSPECIFIED_ERROR;
        }

        return ExitCode::OK;
    }

    /**
     * Echo task
     *
     * Test task for queue system.
     *
     **/
    public function actionEcho($args = null)
    {
        $channelName = "echoIDB";
        $callback = function ($msg) {
            try {
                IdbQueueHelper::echoLog(" [=] Received [$msg->body]");
            } catch (Exception $e) {
                IdbQueueHelper::echoLog(" [Ex] ERROR executing Echo task. - " . $e->getMessage());
            } catch (Error $e) {
                IdbQueueHelper::echoLog(" [E] ERROR executing Echo task. - " . $e->getMessage());
            }
        };

        IdbQueueHelper::echoLog(' [*] Waiting for messages. To exit press CTRL+C');
        try {
            $idbRabbitMq = IdbRabbitMq::get();
            $idbRabbitMq->consume($channelName, $callback);
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
    public function actionImport($args = null)
    {
        $callback = function ($msg) {
            $pid = getmypid();
            IdbQueueHelper::echoLog(" [=] Import [$pid]");
            try {
                Import::executeTaskFromImportQueue($msg->body ?? null);
            } catch (Exception $e) {
                IdbQueueHelper::echoLog(" [Ex] ERROR executing Import [$pid] - " . $e->getMessage());
            } catch (Error $e) {
                IdbQueueHelper::echoLog(" [E] ERROR executing Import [$pid] - " . $e->getMessage());
            }
        };

        IdbQueueHelper::echoLog(' [*] Import queue started. To exit press CTRL+C');
        try {
            $idbRabbitMq = IdbRabbitMq::get();
            $idbRabbitMq->consume(Import::channelName, $callback);
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
    public function actionExport($args = null)
    {
        $callback = function ($msg) {
            $pid = getmypid();
            IdbQueueHelper::echoLog(" [=] Export [$pid]");
            try {
                Export::executeTaskFromExportQueue($msg->body ?? null);
            } catch (Exception $e) {
                IdbQueueHelper::echoLog(" [Ex] ERROR executing Export [$pid] - " . $e->getMessage());
            } catch (Error $e) {
                IdbQueueHelper::echoLog(" [E] ERROR executing Export [$pid] - " . $e->getMessage());
            }
        };

        IdbQueueHelper::echoLog(' [*] Export queue started. To exit press CTRL+C');
        try {
            $idbRabbitMq = IdbRabbitMq::get();
            $idbRabbitMq->consume(Export::channelName, $callback);
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
    public function actionPeopleAccess($args = null)
    {
        $callback = function ($msg) {
            $pid = getmypid();
            IdbQueueHelper::echoLog(" [=] People Access [$pid]");
            try {
                PeopleAccessHelper::executeTaskFromSendInvitationsQueue($msg->body ?? null);
            } catch (Exception $e) {
                IdbQueueHelper::echoLog(" [Ex] ERROR executing People Access [$pid] - " . $e->getMessage());
            } catch (Error $e) {
                IdbQueueHelper::echoLog(" [E] ERROR executing People Access [$pid] - " . $e->getMessage());
            }
        };

        IdbQueueHelper::echoLog(' [*] Invitation queue started. To exit press CTRL+C');
        try {
            $idbRabbitMq = IdbRabbitMq::get();
            $idbRabbitMq->consume(PeopleAccessHelper::channelName, $callback);
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
    public function actionSendUsedFor($args = null)
    {
        $callback = function ($msg) {
            $pid = getmypid();
            IdbQueueHelper::echoLog(" [=] Send Used for [$pid]");
            try {
                SendUsedForHelper::executeTaskFromSendUsedForQueue($msg->body ?? null);
            } catch (Exception $e) {
                IdbQueueHelper::echoLog(" [Ex] ERROR executing Send Used for [$pid] - " . $e->getMessage());
            } catch (Error $e) {
                IdbQueueHelper::echoLog(" [E] ERROR executing Send Used for [$pid] - " . $e->getMessage());
            }
        };

        IdbQueueHelper::echoLog(' [*] Send Used for queue started. To exit press CTRL+C');
        try {
            $idbRabbitMq = IdbRabbitMq::get();
            $idbRabbitMq->consume(SendUsedForHelper::channelName, $callback);
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
    public function actionTakeCredits($args = null)
    {
        $callback = function ($msg) {
            $pid = getmypid();
            IdbQueueHelper::echoLog(" [=] Take credits [$pid]");
            try {
                Credits::executeTaskFromTakeCreditsQueue($msg->body ?? null);
            } catch (Exception $e) {
                IdbQueueHelper::echoLog(" [Ex] ERROR executing Take credits [$pid] - " . $e->getMessage());
            } catch (Error $e) {
                IdbQueueHelper::echoLog(" [E] ERROR executing Take credits [$pid] - " . $e->getMessage());
            }
        };

        IdbQueueHelper::echoLog(' [*] Take credits queue started. To exit press CTRL+C');
        try {
            $idbRabbitMq = IdbRabbitMq::get();
            $idbRabbitMq->consume(Credits::channelName, $callback);
        } catch (Exception $e) {
            return ExitCode::UNSPECIFIED_ERROR;
        }

        return ExitCode::OK;
    }
}

################################################################################
#                                End of file                                   #
################################################################################
