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

use idbyii2\models\db\BusinessDatabaseData;
use idbyii2\models\idb\IdbBankClientBusiness;
use yii\console\ExitCode;
use yii\helpers\Console;

################################################################################
# Class(es)                                                                    #
################################################################################

/**
 * Class IdbDatabaseHelper
 *
 * @package idbyii2\helpers
 */
class IdbDatabaseHelper
{

    /**
     * Check all tables and report if anything is missing.
     *
     * @param        $controler
     * @param string $restore - set the flag to restore missing tables
     *
     * @return int execution status
     * @throws \Exception
     */
    public static function executeIdbValidation($controler, $restore = 'IDB_NOT_ALLOW_ME_TO_RESTORE')
    {
        $migrationName = "IDB validation";
        $controler->migrationHeader($migrationName);
        $tablesCount = -1;
        $start = microtime(true);
        $databases = BusinessDatabaseData::find()->all();
        $missingTables = [];

        foreach ($databases as $tablesCount => $database) {

            try {
                $businessId = $database->business_db_id;

                echo 'Checking: ' . $businessId . PHP_EOL;
                echo 'Table name: ' . $database->idb_data_id . PHP_EOL;

                $clientModel = IdbBankClientBusiness::model($businessId);

                $response = $clientModel->countAll();

                if (
                    !empty($response['Query'])
                    && !empty($response['QueryData'])
                    && is_numeric($response['Query'])
                ) {
                    echo Console::ansiFormat(
                        "Table exist - $database->idb_data_id"
                        . PHP_EOL,
                        [Console::FG_GREEN, Console::BOLD]
                    );
                } elseif ($response == 457) {
                    $missingTables[$database->idb_data_id] = $database;
                    echo Console::ansiFormat(
                        "Table does not exist - $database->idb_data_id"
                        . PHP_EOL,
                        [Console::FG_RED, Console::BOLD]
                    );
                } else {
                    $response = json_encode($response);
                    $controler->migrationError("IDB API Error: [$response]");

                    return ExitCode::UNSPECIFIED_ERROR;
                }
            } catch (Exception $e) {
                $controler->migrationError($e->getMessage());

                return ExitCode::UNSPECIFIED_ERROR;
            }
        }
        $tablesCount++;

        $missingTablesCount = count($missingTables);
        if ($missingTablesCount > 0) {
            echo PHP_EOL;
            $controler->printSeparator('#');
            echo Console::ansiFormat(
                "Found $missingTablesCount missing table(s)."
                . PHP_EOL,
                [Console::FG_RED, Console::BOLD]
            );
            $controler->printSeparator('#');
            if (strtoupper($restore) === 'IDB_ALLOW_ME_TO_RESTORE') {
                echo Console::ansiFormat(
                    "Starting recreating the missing tables..."
                    . PHP_EOL,
                    [Console::FG_RED, Console::BOLD]
                );
            } else {
                echo Console::ansiFormat(
                    "Recreating the missing tables is disabled!"
                    . PHP_EOL,
                    [Console::FG_GREEN, Console::BOLD]
                );
            }
            $controler->printSeparator('#');
            echo PHP_EOL;
            $counter = 1;
            foreach ($missingTables as $indexMissingTables => $missingTable) {
                try {
                    foreach ($missingTables as $missingTable => $database) {
                        echo "[$counter]: " . $missingTable . PHP_EOL;
                        $counter++;
                        $clientModel = IdbBankClientBusiness::model($database->business_db_id);
                        $clientModel->recreateAccount([]);
                    }
                } catch (Exception $e) {
                    $controler->migrationError($e->getMessage());

                    return ExitCode::UNSPECIFIED_ERROR;
                }
            }
        } else {
            echo PHP_EOL;
            $controler->printSeparator('#');
            echo Console::ansiFormat(
                "All tables exist!"
                . PHP_EOL,
                [Console::FG_GREEN, Console::BOLD]
            );
            $controler->printSeparator('#');
            echo PHP_EOL;
        }

        $controler->migrationFooter($migrationName, (microtime(true) - $start), ["Checked $tablesCount table(s)"]);

        return ExitCode::OK;
    }

    /**
     * @param $path
     *
     * @return int
     */
    public static function getLatestMigrationVersion($path)
    {
        $latestMigration = 0;
        $path = realpath($path);
        if (
            !empty($path)
            && is_dir($path)
            && is_readable($path)
        ) {
            $directoryIterator = scandir($path, SCANDIR_SORT_DESCENDING);
            if (
                is_array($directoryIterator)
                && (count($directoryIterator) > 0)
            ) {
                if (
                    preg_match('/idb-upgrade-rev([0-9]+).inc/is', $directoryIterator[0], $matches)
                    && is_array($matches)
                    && (count($matches) > 1)
                ) {
                    $latestMigration = intval($matches[1]);
                }
            }
        }

        return $latestMigration;
    }
}

################################################################################
#                                End of file                                   #
################################################################################
