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

use app\helpers\BusinessConfig;
use Exception;
use idbyii2\components\IdbRabbitMq;
use idbyii2\models\data\IdbDataProvider;
use idbyii2\models\db\BusinessExport;
use yii\web\NotFoundHttpException;

################################################################################
# Class(es)                                                                    #
################################################################################

/**
 * Class Export
 *
 * @package idbyii2\helpers
 */
class Export
{

    const channelName = "exportIDB";

    /**
     * @param array $data
     *
     * @throws \Exception
     */
    private static function addTaskToQueue($data)
    {
        if (!empty($data)) {
            $data = json_encode($data);
            $idbRabbitMq = IdbRabbitMq::get();
            $idbRabbitMq->produce(self::channelName, $data);
        } else {
            $msg = 'Export IDB - queue data cannot be empty!';
            echo $msg . PHP_EOL;
            throw new Exception($msg);
        }
    }

    /**
     * @param $args
     *
     * @return string
     * @throws \Exception
     */
    public static function export($args)
    {
        print_r("Start export process\n");

        $filesToExport = BusinessExport::findAll(['status' => [FileHelper::STATUS_ADDED, FileHelper::STATUS_ERROR]]);

        if (count($filesToExport)) {
            /** @var BusinessExport $file */
            foreach ($filesToExport as $file) {
                $file->status = FileHelper::STATUS_IN_PROGRESS;
                $file->save();

                $delimiter = BusinessConfig::get()->getYii2BusinessExportDelimiter();
                $enclosure = BusinessConfig::get()->getYii2BusinessExportEnclosure();
                $escapeChar = BusinessConfig::get()->getYii2BusinessExportEscapeChar();

                Export::createFolderIfNotExists($file->file_path);

                $file = BusinessExport::findOne(['id' => $file->id]);
                $businessId = IdbAccountId::generateBusinessDbId($file->oid, $file->aid, $file->dbid);

                /** @var IdbDataProvider $dataProvider */
                $dataProvider = new IdbDataProvider($businessId);
                $dataProvider->init();

                if (!array_key_exists('data', $dataProvider->metadata)) {
                    $file->status = FileHelper::STATUS_ERROR;
                    $file->save();

                    return 'Please, Fill data of metadata';
                }

                $file = BusinessExport::findOne(['id' => $file->id]);

                $attributes = json_decode($file->attributes, true);
                if (!empty($attributes['exportAttributes'])) {
                    $exportAttributes = $attributes['exportAttributes'];
                    if (!empty($exportAttributes['delimiter'])) {
                        $delimiter = $exportAttributes['delimiter'];
                    }
                    if (!empty($exportAttributes['enclosure'])) {
                        $enclosure = $exportAttributes['enclosure'];
                    }
                    if (!empty($exportAttributes['escapeChar'])) {
                        $escapeChar = $exportAttributes['escapeChar'];
                    }
                }

                print_r("Start to prepare headers.\n");
                $headers = Export::prepareHeaders($file->attributes, $dataProvider->metadata);

                print_r("Start to prepare dataProvider.\n");
                $dataProvider = Export::prepareDataProvider($file->attributes, $dataProvider);
                $total = $dataProvider->getTotalCount();

                print_r("Start to process file.\n");

                $fp = fopen($file->getFullPath(), 'w');
                if (false !== $fp) {
                    fputcsv(
                        $fp,
                        Export::putHeaders($headers),
                        $delimiter,
                        $enclosure,
                        $escapeChar
                    );
                    fclose($fp);
                    print_r("Headers successfully put\n");
                } else {
                    $file->status = FileHelper::STATUS_ERROR;
                    $file->save();

                    return 'Can not put headers into file';
                }

                for ($i = 0; $i < ceil($total / FileHelper::PAGE_SIZE); ++$i) {
                    $dataProvider = new IdbDataProvider($businessId);
                    $dataProvider->init();
                    $dataProvider = Export::prepareDataProvider($file->attributes, $dataProvider);
                    $dataProvider->setPagination(
                        [
                            'pageSize' => FileHelper::PAGE_SIZE,
                            'page' => $i
                        ]
                    );
                    $models = $dataProvider->getModels();

                    $fp = fopen($file->getFullPath(), 'a+');
                    foreach ($models as $model) {
                        unset($model[0]);
                        if (false !== $fp) {
                            fputcsv(
                                $fp,
                                array_values($model),
                                $delimiter,
                                $enclosure,
                                $escapeChar
                            );
                            print_r("Line successfully put into file\n");
                        } else {
                            $file->status = FileHelper::STATUS_ERROR;
                            $file->save();
                            print_r("Can not put line into file\n");
                        }
                    }
                    if (false !== $fp) {
                        fclose($fp);
                    }
                    unset($models);
                }

                $file->status = FileHelper::STATUS_TO_DOWNLOAD;
                $file->url = json_encode(['export/download', 'id' => $file->id]);
                $file->save();

                print_r("Export finished\n");
            }
        }
    }


    /**
     * @param $headers
     *
     * @return array
     */
    public static function putHeaders($headers)
    {
        $array = [];

        foreach ($headers as $header) {
            array_push($array, $header['display_name']);
        }

        return $array;
    }

    /**
     * @param $attributes
     * @param $dataProvider
     *
     * @return mixed
     */
    public static function prepareDataProvider($attributes, $dataProvider)
    {
        $attributes = json_decode($attributes, true);

        if (array_key_exists('sort-by', $attributes) && array_key_exists('sort-dir', $attributes)) {
            $dataProvider->sort = [
                DataHTML::getUuid(
                    explode('-', $attributes['sort-by']),
                    $dataProvider->metadata
                ) => $attributes['sort-dir']
            ];
        }

        if (array_key_exists('search', $attributes)) {
            $dataProvider->prepareSearch(json_decode($attributes['search'], true));
        }

        if (array_key_exists('columns', $attributes)) {
            $dataProvider->metadata['settings'] = $attributes['columns'];
        } else {
            unset($dataProvider->metadata['settings']);
        }


        print_r("DataProvider prepared.\n");

        return $dataProvider;
    }

    /**
     * @param $attributes
     * @param $metadata
     *
     * @return array
     */
    public static function prepareHeaders($attributes, $metadata)
    {
        $attributes = json_decode($attributes, true);
        $headers = [];
        $index = 0;

        if (array_key_exists('columns', $attributes) && !empty($attributes['columns'])) {
            foreach ($attributes['columns'] as $uuid => $on) {
                foreach ($metadata['data'] as $key => $value) {
                    if ($value['object_type'] === 'type') {
                        if ($value['uuid'] === $uuid) {
                            $headers[$index] = [
                                'uuid' => $uuid,
                                'display_name' => $value['display_name']
                            ];
                            $index++;
                        }
                    } elseif ($value['object_type'] === 'set') {
                        foreach ($value['data'] as $no => $set) {
                            if ($set['uuid'] === $uuid) {
                                $headers[$index] = [
                                    'uuid' => $uuid,
                                    'display_name' => $set['display_name']
                                ];
                                $index++;
                            }
                        }
                    }
                }
            }
        } else {
            foreach ($metadata['data'] as $key => $value) {
                if ($value['object_type'] === 'type') {
                    if ($value['object_type'] === 'type') {
                        $headers[$index] = [
                            'uuid' => $value['uuid'],
                            'display_name' => $value['display_name']
                        ];
                        $index++;
                    }
                } elseif ($value['object_type'] === 'set') {
                    foreach ($value['data'] as $no => $set) {
                        $headers[$index] = [
                            'uuid' => $set['uuid'],
                            'display_name' => $set['display_name']
                        ];
                        $index++;
                    }
                }
            }
        }

        print_r("Headers prepared.\n");

        return $headers;
    }

    /**
     * @param $exportFolder
     *
     * @throws \yii\web\NotFoundHttpException
     */
    public static function createFolderIfNotExists($exportFolder)
    {
        if (!file_exists($exportFolder)) {
            mkdir($exportFolder, 0777, true);
        }
        if (!file_exists($exportFolder)) {
            throw new NotFoundHttpException(Translate::_('idbyii2', 'The export folder does not exist.'));
        }
    }

    /**
     * @param $fileId
     *
     * @throws \Exception
     */
    public static function executeExportsForDb($fileId)
    {
        self::addTaskToQueue(["fileId" => $fileId]);
    }

    /**
     * @param $data
     *
     * @throws \Exception
     */
    public static function executeTaskFromExportQueue($data)
    {
        echo("OK we have new EXPORT task with data: [$data] ..." . PHP_EOL);
        $data = json_decode($data, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            if (empty($data['fileId'])) {
                $msg = 'Export IDB - missing fileId!';
                echo $msg . PHP_EOL;
                throw new Exception($msg);
            } else {
                $fileId = $data['fileId'];
                echo "Execute export task for fileId: [$fileId]" . PHP_EOL;
                try {
                    self::exportFromQueue($fileId);
                } catch (Exception $e) {
                    var_dump('FILE: ' . $e->getFile() . ' LINE: ' . $e->getLine() . ' MESSAGE: ' . $e->getMessage());
                }
            }
        } else {
            $msg = 'Export IDB - task data are empty or not complete!';
            echo $msg . PHP_EOL;
            throw new Exception($msg);
        }
    }

    /**
     * @param $fileId
     *
     * @return string
     * @throws \yii\web\NotFoundHttpException
     */
    public static function exportFromQueue($fileId)
    {
        print_r("Start export process\n");

        $file = BusinessExport::findOne(
            [
                'id' => $fileId
            ]
        );

        if (empty($file)) {
            print_r("Cannot find find export file [$fileId]." . PHP_EOL);
            print_r("Export cancelled." . PHP_EOL);

            return;
        }

        if ($file->status === FileHelper::STATUS_ADDED || $file->status === FileHelper::STATUS_ERROR) {
            $file->status = FileHelper::STATUS_IN_PROGRESS;
            $file->save();

            $delimiter = BusinessConfig::get()->getYii2BusinessExportDelimiter();
            $enclosure = BusinessConfig::get()->getYii2BusinessExportEnclosure();
            $escapeChar = BusinessConfig::get()->getYii2BusinessExportEscapeChar();

            Export::createFolderIfNotExists($file->file_path);

            $file = BusinessExport::findOne(['id' => $file->id]);
            $businessId = IdbAccountId::generateBusinessDbId($file->oid, $file->aid, $file->dbid);

            /** @var IdbDataProvider $dataProvider */
            $dataProvider = new IdbDataProvider($businessId);
            $dataProvider->init();

            if (!array_key_exists('data', $dataProvider->metadata)) {
                $file->status = FileHelper::STATUS_ERROR;
                $file->save();

                return 'Please, Fill data of metadata';
            }

            $file = BusinessExport::findOne(['id' => $file->id]);

            $attributes = json_decode($file->attributes, true);
            if (!empty($attributes['exportAttributes'])) {
                $exportAttributes = $attributes['exportAttributes'];
                if (!empty($exportAttributes['delimiter'])) {
                    $delimiter = $exportAttributes['delimiter'];
                }
                if (!empty($exportAttributes['enclosure'])) {
                    $enclosure = $exportAttributes['enclosure'];
                }
                if (!empty($exportAttributes['escapeChar'])) {
                    $escapeChar = $exportAttributes['escapeChar'];
                }
            }

            print_r("Start to prepare headers.\n");
            $headers = Export::prepareHeaders($file->attributes, $dataProvider->metadata);

            print_r("Start to prepare dataProvider.\n");
            $dataProvider = Export::prepareDataProvider($file->attributes, $dataProvider);
            $total = $dataProvider->getTotalCount();

            print_r("Start to process file.\n");

            $fp = fopen($file->getFullPath(), 'w');
            if (false !== $fp) {
                fputcsv(
                    $fp,
                    Export::putHeaders($headers),
                    $delimiter,
                    $enclosure,
                    $escapeChar
                );
                fclose($fp);
                print_r("Headers successfully put\n");
            } else {
                $file->status = FileHelper::STATUS_ERROR;
                $file->save();

                return 'Can not put headers into file';
            }

            for ($i = 0; $i < ceil($total / FileHelper::PAGE_SIZE); ++$i) {
                $dataProvider = new IdbDataProvider($businessId);
                $dataProvider->init();
                $dataProvider = Export::prepareDataProvider($file->attributes, $dataProvider);
                $dataProvider->setPagination(
                    [
                        'pageSize' => FileHelper::PAGE_SIZE,
                        'page' => $i
                    ]
                );
                $models = $dataProvider->getModels();

                $fp = fopen($file->getFullPath(), 'a+');
                foreach ($models as $model) {
                    unset($model[0]);
                    if (false !== $fp) {
                        fputcsv(
                            $fp,
                            array_values($model),
                            $delimiter,
                            $enclosure,
                            $escapeChar
                        );
                        print_r("Line successfully put into file\n");
                    } else {
                        $file->status = FileHelper::STATUS_ERROR;
                        $file->save();
                        print_r("Can not put line into file\n");
                    }
                }
                if (false !== $fp) {
                    fclose($fp);
                }
                unset($models);
            }

            $file->status = FileHelper::STATUS_TO_DOWNLOAD;
            $file->url = json_encode(['export/download', 'id' => $file->id]);
            $file->save();

            print_r("Export finished\n");
        }
    }
}

################################################################################
#                                End of file                                   #
################################################################################
