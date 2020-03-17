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

use BusinessConfig;
use Exception;
use idbyii2\components\IdbRabbitMq;
use idbyii2\models\db\BusinessImport;
use idbyii2\models\db\BusinessImportWorksheet;
use idbyii2\models\form\NotificationsForm;
use idbyii2\models\idb\IdbBankClientBusiness;
use Yii;

################################################################################
# Class(es)                                                                    #
################################################################################

/**
 * Class Import
 *
 * @package idbyii2\helpers
 */
class Import extends IdbImport
{

    const channelName = "importIDB";

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
            $msg = 'Import IDB - queue data cannot be empty!';
            echo $msg . PHP_EOL;
            throw new Exception($msg);
        }
    }

    /**
     * @param $args
     *
     * @return void
     * @throws \Throwable
     * @throws \yii\db\StaleObjectException
     */
    public static function remove($args)
    {
        $dbData = BusinessImport::findAll(['status' => FileHelper::STATUS_TO_REMOVE]);

        /** @var BusinessImport $file */
        foreach ($dbData as $file) {
            $dir = Import::getTargetDir(
                BusinessConfig::get()->getYii2BusinessUploadLocation(),
                $file->uid
            );
            $dirDestiny = $file->file_name . "_directory";
            exec("mv " . $dir . $file->file_name . " " . $dir . $dirDestiny);
            exec("mv " . $dir . $file->file_name . ".info" . " " . $dir . $dirDestiny);
            exec("rm -r " . $dir . $dirDestiny);

            $file->delete();
        }
    }

    /**
     * @param        $file
     * @param array  $metadata
     * @param        $businessUserId
     * @param string $delimiter
     * @param string $enclosure
     * @param string $escape
     *
     * @return array
     */
    protected static function prepareMap(
        $file,
        array $metadata,
        $businessUserId,
        $delimiter = ";",
        $enclosure = '"',
        $escape = "\\"
    ) {
        $file = fopen($file, "r");
        if (false === $file) {
            return [];
        }

        $map = [];
        $start = microtime(true);

        $headers = fgets($file); //pomijamy headers
        while (($row = fgets($file)) !== false) {
            $row = str_getcsv($row, $delimiter, $enclosure, $escape);
            $row = str_replace($escape . $enclosure, $enclosure, $row);
            $tmp = [];

            foreach ($metadata['headerMapping'] as $value) {
                $tmp[$value['uuid']] = $row[$value['index']];
            }

            array_push($map, $tmp);
        }
        $end = microtime(true) - $start;
        print_r("Map ready in $end seconds.\n");
        fclose($file);

        return $map;
    }

    /**
     * @param $location
     * @param $userId
     *
     * @return string
     */
    public static function getTargetDir($location, $userId)
    {
        $targetDir = $location;

        if (substr($targetDir, -1) !== '/') {
            $targetDir .= '/';
        }
        $targetDir .= $userId;

        if (!file_exists($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        if (substr($targetDir, -1) !== '/') {
            $targetDir .= '/';
        }

        return $targetDir;
    }

    /**
     * @param       $filepath
     * @param       $targetDir
     * @param       $name
     * @param array $uuid
     * @param bool  $isWizard
     *
     * @return array
     * @throws \Exception
     */
    public static function prepareRetrunFile($filepath, $targetDir, $name, $uuid = [], $isWizard = false)
    {
        $tempName = $filepath;
        $filename = Localization::getDateTimeFileString();
        $filepath = $targetDir . $filename;
        rename($tempName, $filepath);
        $md5file = md5_file($filepath);
        $return = [
            "file_md5" => $md5file,
            "file_id" => $filename
        ];
        if (!empty($_REQUEST['client_md5'])) {
            $return['client_md5'] = $_REQUEST['client_md5'];
        }
        $fileinfo = [
            "filename" => $name,
            "filesize" => filesize($filepath),
            "filemd5" => $md5file,
            "filetimestamp" => Localization::getDateTimeString()
        ];

        file_put_contents($filepath . ".info", json_encode($fileinfo));

        $model = new BusinessImport();
        $model->file_name = $filename;
        $model->status = FileHelper::STATUS_ADDED;
        $model->file_path = $name;
        $model->uid = $uuid['uid'];
        $model->aid = $uuid['aid'];
        $model->dbid = $uuid['dbid'];
        $model->oid = $uuid['oid'];
        if ($uuid['step']) {
            $model->steps = json_encode([$uuid['step'], 'index']);
            Yii::$app->session->remove('importSteps');
        } else {
            $model->steps = json_encode(['index']);
        }
        $model->import_attributes = json_encode(['language' => Yii::$app->language]);
        $model->save();

        if ($isWizard) {
            $return['redirect'] = Yii::$app->urlManager->createAbsoluteUrl(
                ['/tools/wizard/worksheets', 'file' => $model->id]
            );
            $return['file'] = $model->id;
        } else {
            $return['redirect'] = null;
        }

        return $return;
    }

    /**
     * @param $dir
     * @param $fileName
     * @param $worksheetId
     * @param $worksheetName
     *
     * @return array
     */
    public static function getHeadersFromFile(
        $dir,
        $fileName,
        $worksheetId,
        $worksheetName,
        $delimiter = ";",
        $enclosure = '"',
        $escape = "\\"
    ) {
        $file = $dir . $fileName . '_directory/' . $fileName . '.' . $worksheetId . '.' . $worksheetName;

        $file = fopen($file, "r");
        if (false === $file) {
            return [];
        }

        $headers = fgets($file);
        $headers = str_getcsv($headers, $delimiter, $enclosure, $escape);
        $headers = str_replace($escape . $enclosure, $enclosure, $headers);

        return $headers;
    }

    /**
     * @param      $fileId
     * @param null $worksheetId
     *
     * @throws \Exception
     */
    public static function executeImportsForDb($fileId, $worksheetId = null)
    {
        if (!empty($fileId) && is_numeric($fileId)) {
            $data = ["fileId" => $fileId, "worksheetId" => $worksheetId];
            self::addTaskToQueue($data);
        } else {
            $fileId = json_encode($fileId);
            $msg = "File id [$fileId] is not numeric. Skipping ...";
            Yii::error($msg);

            return;
        }
    }

    /**
     * @param $data
     *
     * @throws \Exception
     */
    public static function executeTaskFromImportQueue($data)
    {
        echo("OK we have new IMPORT task with data: [$data] ..." . PHP_EOL);
        $data = json_decode($data, true);

        if (json_last_error() === JSON_ERROR_NONE) {
            if (empty($data['fileId'])) {
                $msg = 'Import IDB - missing fileId!';
                echo $msg . PHP_EOL;
                throw new Exception($msg);
            } else {
                $fileId = $data['fileId'];
                $worksheetId = $data['worksheetId'] ?? null;

                echo "Execute import task for fileId: [$fileId]" . PHP_EOL;

                try {
                    echo "Start - Execute convert to worksheets: [$fileId]" . PHP_EOL;
                    self::convertToWorksheets($fileId);
                    echo "Done - Execute convert to worksheets: [$fileId]" . PHP_EOL;
                    echo "Start - Execute import from worksheet: [$worksheetId]" . PHP_EOL;
                    self::importFromWorksheet($fileId, $worksheetId);
                    echo "Done - Execute import from worksheet [$worksheetId]" . PHP_EOL;
                } catch (Exception $e) {
                    if (!empty($e)) {
                        if (!empty($e->getMessage())) {
                            $msg = $e->getMessage();
                            echo $msg . PHP_EOL;
                            Yii::error($msg);
                        }
                    }
                }
            }
        } else {
            $msg = 'Import IDB - task data are empty or not complete!';
            echo $msg . PHP_EOL;
            Yii::error($msg);
            throw new Exception($msg);
        }
    }

    /**
     * @param $languageDefault
     * @param $import
     *
     * @return mixed
     */
    private static function setLanguageFromAttributes($languageDefault, $import)
    {
        $language = $languageDefault;
        $importAttributes = $import->import_attributes;
        if (!empty($importAttributes)) {
            $importAttributes = json_decode($importAttributes, true);
            if (!empty($importAttributes['language'])) {
                $language = $importAttributes['language'];
            }
        }

        return $language;
    }

    /**
     * @param $import
     *
     * @return string|null
     */
    private static function setPhoneCodeFromAttributes($import)
    {
        $importAttributes = $import->import_attributes;
        if (!empty($importAttributes)) {
            $importAttributes = json_decode($importAttributes, true);
            if (!empty($importAttributes['phone_code']) && $importAttributes['phone_code'] !== '0') {
                return $importAttributes['phone_code'];
            } else {
                return null;
            }
        }
    }

    /**
     * @param $import
     *
     * @return bool
     */
    private static function setBothValidFromAttributes($import)
    {
        $importAttributes = $import->import_attributes;
        if (!empty($importAttributes)) {
            $importAttributes = json_decode($importAttributes, true);
            if (!empty($importAttributes['valid_both'])) {
                return $importAttributes['valid_both'];
            }
        }

        return false;
    }

    /**
     * @param $fileId
     */
    public static function convertToWorksheets($fileId)
    {
        $file = (is_numeric($fileId)) ? BusinessImport::findOne(['id' => $fileId]) : null;

        try {
            if (!empty($file) && ($file->status === FileHelper::STATUS_ADDED)) {
                $dir = Import::getTargetDir(
                    BusinessConfig::get()->getYii2BusinessUploadLocation(),
                    $file->uid
                );
                $dirDestiny = Import::convert($dir, $file->file_name);
                $file->status = FileHelper::STATUS_CONVERTED;
                $file->save();

                $language = self::setLanguageFromAttributes(Yii::$app->language, $file);

                $list = scandir($dirDestiny);

                foreach ($list as $worksheet) {
                    if (is_dir($worksheet)) {
                        continue;
                    }

                    $pieces = explode(".", $worksheet, 3);
                    /** @var \idbyii2\models\db\BusinessImportWorksheet $model */
                    $model = new BusinessImportWorksheet();
                    $model->name = $pieces[2];
                    $model->worksheet_id = $pieces[1];
                    $model->file_id = $file->id;
                    $model->status = FileHelper::STATUS_ADDED;
                    $model->uid = $file->uid;
                    $model->oid = $file->oid;
                    $model->aid = $file->aid;
                    $model->dbid = $file->dbid;
                    $model->import_attributes = json_encode(['language' => $language]);
                    $model->save();
                }
            }
            if (empty($file)) {
                $msg = "File id [$fileId] does not exist. Skipping ...";
                echo $msg . PHP_EOL;
                Yii::error($msg);
            }
        } catch (Exception $e) {
            if (!empty($e->getMessage())) {
                $msg = $e->getMessage();
                Yii::error($msg);
                var_dump($msg);
            }
        }

    }

    /**
     * @param      $fileId
     * @param null $worksheetId
     *
     * @throws \Exception
     */
    public static function importFromWorksheet($fileId, $worksheetId = null)
    {
        print_r("Start import process\n");

        if (is_null($worksheetId)) {
            $msg = "Worksheet id is empty. Skipping ...";
            echo $msg . PHP_EOL;
            Yii::error($msg);
            throw new Exception($msg);
        }

        $worksheet = BusinessImportWorksheet::findOne(
            [
                'file_id' => $fileId,
                'id' => $worksheetId
            ]
        );

        if (!$worksheet instanceof BusinessImportWorksheet) {
            $msg = "Worksheet with id [$worksheetId] not found for file id [$fileId]. Skipping ...";
            echo $msg . PHP_EOL;
            Yii::error($msg);
            throw new Exception($msg);
        }

        $worksheet->status = FileHelper::STATUS_IN_PROGRESS;
        $worksheet->save();

        $language = self::setLanguageFromAttributes(Yii::$app->language, $worksheet);
        print_r("Use language: " . $language . PHP_EOL);

        $dir = Import::getTargetDir(
            BusinessConfig::get()->getYii2BusinessUploadLocation(),
            $worksheet->uid
        );

        /** @var BusinessImport $fileToImport */
        $fileToImport = $worksheet->getFile()->one();
        $dirDestiny = $dir . $fileToImport->file_name . "_directory";
        $list = scandir($dirDestiny);

        foreach ($list as $file) {
            if (is_dir($file)) {
                continue;
            }

            $data = explode('.', $file);

            if ($data[2] !== $worksheet->name) {
                continue;
            }

            try {
                $businessId = IdbAccountId::generateBusinessDbId(
                    $worksheet->oid,
                    $worksheet->aid,
                    $worksheet->dbid
                );

                $businessUserId = $businessId . '.uid.' . $worksheet->uid;
                $clientModel = IdbBankClientBusiness::model($businessId);

                $metadata = $clientModel->getAccountMetadata();

                $metadata = json_decode($metadata['Metadata'], true);

                $map = self::prepareMap($dirDestiny . "/" . $file, $metadata, $businessUserId);

                $chunkedMap = array_chunk($map, FileHelper::PAGE_SIZE, false);

                print_r("Started to process data\n");
                print_r("To database: " . $businessId);
                $start = microtime(true);
                $peopleAccess = [];

                $clientModel = IdbBankClientBusiness::model($businessId);
                foreach ($chunkedMap as $k => $chunk) {
                    foreach ($chunk as &$type) {
                        foreach ($type as &$typeValue) {
                            $typeValue = trim($typeValue);
                        }
                    }
                    $response = $clientModel->putMultiple(array_values($chunk));
                    var_dump($response);
                    if ($response != 457) {
                        if (
                            is_array($response)
                            && !empty($response['QueryData'])
                            && !empty($response['QueryData'][0])
                            && !empty($response['QueryData'][0][0])
                        ) {
                            if ($k == 0) {
                                $peopleAccess['first'] = ($response['QueryData'][0][0] - count($chunk)) + 1;
                            }

                            $peopleAccess['last'] = $response['QueryData'][0][0];
                        }
                        $rows = ($k + 1) * FileHelper::PAGE_SIZE;
                        print_r("$rows rows put into dataabase\n");
                    } else {
                        print_r("Error connecting database\n");
                        $worksheet->status = FileHelper::STATUS_ERROR;
                        $worksheet->save();

                        return;
                    }
                }
                $worksheet->status = FileHelper::STATUS_IMPORTED;
                $worksheet->save();
                $importedFile = BusinessImport::findOne(['id' => $worksheet->file_id]);

                if ($importedFile instanceof BusinessImport) {
                    $importedFile->status = FileHelper::STATUS_IMPORTED;
                    $importedFile->save();

                    $notification = new NotificationsForm();
                    $notification->uid = $worksheet->uid;
                    $notification->type = 'green';
                    $notification->status = 1;
                    $notification->title = Translate::_(
                        'idbyii2',
                        'We finished process import of {filename}.',
                        ['filename' => $importedFile->file_path]
                    );
                    $notification->body = Translate::_(
                        'idbyii2',
                        'Data import finished - you can now edit your vault.'
                    );
                    $notification->save();
                }

                $records = count($map);
                $timeInSecs = microtime(true) - $start;
                print_r("Imported $records records in $timeInSecs seconds\n");

                $peopleAccess['language'] = $language;
                $peopleAccess['businessId'] = $businessId;
                $peopleAccess['uid'] = $worksheet->uid;
                $peopleAccess['both_valid'] = self::setBothValidFromAttributes($worksheet);
                $peopleAccess['phone_code'] = self::setPhoneCodeFromAttributes($worksheet);
                var_dump($peopleAccess);
                var_dump($worksheet->send_invitations);
                if ($worksheet->send_invitations) {
                    print_r("Add sending invitations to the queue..\n");
                    PeopleAccessHelper::executeSendInvitations($peopleAccess);
                }
            } catch (Exception $e) {
                print_r($e->getFile() . ':' . $e->getLine() . ':' . $e->getMessage());
            }
        }
    }
}

################################################################################
#                                End of file                                   #
################################################################################
