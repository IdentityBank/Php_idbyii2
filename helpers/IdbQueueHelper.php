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

use ErrorException;
use Exception;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Exception\AMQPRuntimeException;
use PhpAmqpLib\Message\AMQPMessage;
use RuntimeException;

################################################################################
# Class(es)                                                                    #
################################################################################

/**
 * Class IdbQueueHelper
 *
 * @package idbyii2\helpers
 */
class IdbQueueHelper
{

    const debugMode = false;

    private $host = null;
    private $port = null;
    private $user = null;
    private $password = null;

    /**
     * IdbQueueHelper constructor.
     *
     * @param $host
     * @param $port
     * @param $user
     * @param $password
     */
    function __construct(
        $host,
        $port,
        $user,
        $password
    ) {
        $this->host = $host;
        $this->port = $port;
        $this->user = $user;
        $this->password = $password;
    }

    /**
     * @param $channelName
     * @param $body
     *
     * @throws \Exception
     */
    public function produce($channelName, $body)
    {
        self::producer(
            $channelName,
            $body,
            $this->host,
            $this->port,
            $this->user,
            $this->password
        );
    }

    /**
     * @param $channelName
     * @param $body
     * @param $host
     * @param $port
     * @param $user
     * @param $password
     *
     * @throws \Exception
     */
    public static function producer(
        $channelName,
        $body,
        $host,
        $port,
        $user,
        $password
    ) {
        $connection = new AMQPStreamConnection($host, $port, $user, $password);
        $channel = $connection->channel();

        $properties = ['content_type' => 'text/plain', 'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT];
        $exchange = null;
        $msg = new AMQPMessage($body, $properties);
        $channel->basic_publish($msg, $exchange, $channelName);

        self::cleanupQueue($connection, $channel);
    }

    /**
     * @param $connection
     * @param $channel
     *
     * @throws \Exception
     */
    private static function cleanupQueue($connection, $channel)
    {
        if (YII_ENV_DEV && self::debugMode) {
            self::echoLog(" [>] Connection cleanup");
        }
        if ($channel) {
            $channel->close();
        }
        if ($connection) {
            $connection->close();
        }
    }

    /**
     * @param $msg
     */
    public static function echoLog($msg)
    {
        try {
            $timestamp = Localization::getDateTimeLogString();
            echo("[$timestamp] - $msg" . PHP_EOL);
        } catch (Exception $e) {
            Yii2::error($e->getMessage());
        }
    }

    /**
     * @param $channelName
     * @param $callback
     *
     * @throws \Exception
     */
    public function consume($channelName, $callback)
    {
        self::startConsumer(
            $channelName,
            $callback,
            $this->host,
            $this->port,
            $this->user,
            $this->password
        );
    }

    /**
     * @param $channelName
     * @param $callback
     * @param $host
     * @param $port
     * @param $user
     * @param $password
     *
     * @throws \Exception
     */
    public static function startConsumer(
        $channelName,
        $callback,
        $host,
        $port,
        $user,
        $password
    ) {

        if (YII_ENV_DEV && self::debugMode) {
            self::echoLog(" [*] Host [$host] ...");
            self::echoLog(" [*] Port [$port] ...");
            self::echoLog(" [*] User [$user] ...");
            self::echoLog(" [*] Password [$password] ...");
        }
        self::echoLog(" [*] Starting channel [$channelName] ...");

        $connection = null;
        $channel = null;
        while (true) {
            try {
                $connection = new AMQPStreamConnection($host, $port, $user, $password);
                $channel = $connection->channel();
                $channel->queue_declare(
                    $channelName, // queue - queue name
                    false, // passive - If set, the server will reply with Declare-Ok if the exchange already exists with the same name, and raise an error if not.
                    false, // durable - A durable subscriber is a message consumer that receives all messages published on a topic, including messages published while the subscriber is inactive.
                    false, // exclusive - Exclusive queues may only be accessed by the current connection, and are deleted when that connection closes.
                    true); // auto_delete - If set, the exchange is deleted when all queues have finished using it.
                $channel->basic_consume(
                    $channelName, // queue
                    '', // consumer_tag
                    false, // no_local
                    true, // no_ack
                    false, // exclusive
                    false, // nowait
                    $callback); // callback
                while (!empty($channel) && count($channel->callbacks)) {
                    $channel->wait();
                }
            } catch (AMQPRuntimeException $e) {
                echo $e->getMessage();
                echo PHP_EOL;
                self::cleanupQueue($connection, $channel);
                usleep(WAIT_BEFORE_RECONNECT_uS);
            } catch (RuntimeException $e) {
                self::cleanupQueue($connection, $channel);
                usleep(WAIT_BEFORE_RECONNECT_uS);
            } catch (ErrorException $e) {
                self::cleanupQueue($connection, $channel);
                usleep(WAIT_BEFORE_RECONNECT_uS);
            }
        }

    }
}

################################################################################
#                                End of file                                   #
################################################################################
