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

use DateInterval;
use DateTime;
use Exception;
use idbyii2\components\PortalApi;
use idbyii2\models\db\BusinessDatabaseData;
use idbyii2\models\idb\IdbBankClientBusiness;
use idbyii2\models\idb\IdbBankClientPeople;
use Yii;
use yii\helpers\ArrayHelper;
use yii\web\NotFoundHttpException;

################################################################################
# Class(es)                                                                    #
################################################################################

/**
 * Class Event
 *
 * @package idbyii2\helpers
 */
class Event
{
    /**
     * @param $client
     * @param int $lastId
     * @param array $data
     * @param array $metadata
     * @throws Exception
     */
    public static function importAddEvents($client, int $lastId, array $data, array $metadata)
    {
        $ids = ArrayHelper::getValue($client->findWithBiggerId($lastId), 'QueryData', []);

        $minimumDate = new DateTime();
        $reviewDate = new DateTime();
        $maximumDate = new DateTime();
        if (!empty($data['maximum'])) {
            $maximumDate->add(new DateInterval('PT' . $data['maximum'] . 'H'));
        }
        if (!empty($data['minimum'])) {
            $minimumDate->add(new DateInterval('PT' . $data['minimum'] . 'H'));
        }
        if (!empty($data['reviewCycle'])) {
            $reviewDate->add(new DateInterval('PT' . $data['reviewCycle'] . 'H'));
        }

        foreach ($ids as $id) {
            foreach(Metadata::getTypes($metadata) as $column) {
                if (!empty($data['maximum'])) {
                    $client->addAccountEvent(
                        'uid.' . $id[0] . '.uuid.' . $column['uuid'],
                        'AE',
                        json_encode(['action' => 'delete']),
                        Localization::getDatabaseDateTime($maximumDate),
                        [
                            'retentionPeriod' => [
                                'minimum' => $data['minimum'] ?? '0',
                                'maximum' => $data['maximum'] ?? '0',
                                'reviewCycle' => $data['reviewCycle'] ?? '0'
                            ]
                        ]
                    );
                }
                if (!empty($data['minimum'])) {
                    $client->addAccountEvent(
                        'uid.' . $id[0] . '.uuid.' . $column['uuid'],
                        'AE',
                        json_encode(['action' => 'allowDelete']),
                        Localization::getDatabaseDateTime($minimumDate),
                        [
                            'retentionPeriod' => [
                                'minimum' => $data['minimum'] ?? '0',
                                'maximum' => $data['maximum'] ?? '0',
                                'reviewCycle' => $data['reviewCycle'] ?? '0'
                            ]
                        ]
                    );
                }
                if (!empty($data['reviewCycle'])) {
                    $client->addAccountEvent(
                        'uid.' . $id[0] . '.uuid.' . $column['uuid'],
                        'AE',
                        json_encode(
                            [
                                'action' => 'reviewCycle',
                                'data' => [
                                    'message' => $data['explanation']
                                ]
                            ]
                        ),
                        Localization::getDatabaseDateTime($reviewDate),
                        [
                            'retentionPeriod' => [
                                'minimum' => $data['minimum'] ?? '0',
                                'maximum' => $data['maximum'] ?? '0',
                                'reviewCycle' => $data['reviewCycle'] ?? '0'
                            ]
                        ]
                    );
                }
            }
        }
    }

    /**
     * @throws NotFoundHttpException
     * @throws Exception
     */
    public static function cacheDailyEvents()
    {
        $pageSize = 25;
        $databaseOffset = 0;
        TempFile::writeTempFile('', 'events', true);
        $file = fopen(TempFile::getTempFileName('events', true), 'w');
        while ($databases = BusinessDatabaseData::find()->offset($databaseOffset * $pageSize)->limit($pageSize)->all()) {
            /** @var BusinessDatabaseData $database */
            foreach ($databases as $database) {
                $dbid = $database->business_db_id;
                $client = IdbBankClientBusiness::model($dbid);
                $eventsOffset = 0;
                var_dump($dbid);
                while ($events = $client->setPage($eventsOffset)->setPageSize($pageSize)->findCountAllEventsToCache($database->idb_data_id)['QueryData']) {
                    foreach ($events as $event) {
                        $event['dbid'] = $dbid;
                        $event['dataId'] = $database->idb_data_id;
                        fwrite($file, json_encode($event) . PHP_EOL);
                    }

                    $eventsOffset++;
                }
            }
            $databaseOffset++;
        }

        fclose($file);
    }

    /**
     * @throws NotFoundHttpException
     */
    public static function hourlyEvents()
    {
        $file = fopen(TempFile::getTempFileName('events', true), 'r+') or die('Hourly events doesn\'t exist');
        while (!feof($file)) {
            $eventRaw = fgets($file);
            $event = json_decode($eventRaw, true);
            if (!empty($event)) {
                $eventTime = new DateTime($event[6]);
                $nextHour = new DateTime();
                $nextHour->add(new DateInterval('PT1H'));
                $nextHour->add(new DateInterval('P6D'));
                if ($nextHour->diff($eventTime)->format('%R') === "-") {
                    $event[7] = json_decode($event[7], true);

                    $method = $event[7]['action'] . 'Event';

                    if (is_callable('self::' . $method) && method_exists(self::class, $method)) {
                        if (call_user_func(self::class . "::$method", $event)) {
                            $client = IdbBankClientBusiness::model($event['dbid']);
                            $client->deleteAccountEvent($event[0]);

                            fclose($file);
                            $contents = file_get_contents(TempFile::getTempFileName('events', true));
                            $contents = str_replace($eventRaw, '', $contents);
                            file_put_contents(TempFile::getTempFileName('events', true), $contents);
                            $file = fopen(TempFile::getTempFileName('events', true), 'r+');
                        }
                    } else {
                        Yii::error('Event not implemented: ' . $method);
                    }
                }
            }
        }

        fclose($file);
    }


    /**
     * @param $event
     * @return bool
     */
    public static function allowDeleteEvent($event)
    {
        return true;
    }


    /**
     * @param $event
     * @return bool
     * @throws Exception
     */
    public static function deleteEvent($event)
    {
        $client = IdbBankClientBusiness::model($event['dbid']);
        $parsed = IdbAccountId::parse($event[2]);
        $data = $client->getRelatedPeoples($event['dbid'] . '.uid.' . $parsed['uid']);
        if (!empty($data['QueryData']) && !empty($data['QueryData'][0])) {
            $client->deleteRelationBusiness2People(
                $event['dbid'] . '.uid.' . $parsed['uid'],
                $data['QueryData'][0][0]
            );
        }
        $client = IdbBankClientBusiness::model($event['dbid']);
        $client->delete((int)$parsed['uid']);
        $deleted = true;
        if (!empty($client->findById((int)$parsed['uid'])['QueryData'])) {
            $deleted = false;
            for ($i = 0; $i < 10; $i++) {
                $client->delete((int)$parsed['uid']);
                if (empty($client->findById((int)$parsed['uid'])['QueryData'])) {
                    $deleted = true;
                    break;
                }
                sleep(1);
            }
        }

        return $deleted;
    }


    /**
     * @param $event
     * @return bool
     */
    public static function reviewCycleEvent($event)
    {
        $client = IdbBankClientBusiness::model($event['dbid']);
        $parsed = IdbAccountId::parse($event[2]);
        $data = $client->getRelatedPeoples($event['dbid'] . '.uid.' . $parsed['uid']);
        if (!empty($data['QueryData']) && !empty($data['QueryData'][0])) {
            $id = IdbAccountId::parse($data['QueryData'][0][0]);
            $portalPeopleApi = PortalApi::getPeopleApi();

            $request = $portalPeopleApi->requestPeopleNotification(
                [
                    'uid' => $id['pid'],
                    'businessId' => $event['dbid'] . '.uid.' . $parsed['uid'],
                    'peopleId' => $data['QueryData'][0][0],
                    'metadata' => $event[9],
                    'oid' => $event[2]
                ]
            );

            return is_bool($request) && $request;
        }

        return true;
    }
}

################################################################################
#                                End of file                                   #
################################################################################
