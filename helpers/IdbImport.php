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

use xmz\simplelog\SimpleLogLevel;
use xmz\simplelog\SNLog as Log;
use function xmz\simplelog\registerLogger;

################################################################################
# Class(es)                                                                    #
################################################################################

/**
 * Class IdbImportController
 *
 * Manage import and export console commands
 *
 * @package app\controllers
 */
class IdbImport
{

    /**
     * @param $dir
     * @param $file
     *
     * @return string
     */
    public static function convert($dir, $file)
    {
        self::logConvert(['function' => __FUNCTION__, 'dir' => $dir, 'file' => $file]);

        if (!empty($dir) && substr($dir, -1) !== DIRECTORY_SEPARATOR) {
            $dir .= DIRECTORY_SEPARATOR;
        }
        $convertOutputDir = $dir . $file . "_directory";
        if (!empty($convertOutputDir) && substr($convertOutputDir, -1) !== DIRECTORY_SEPARATOR) {
            $convertOutputDir .= DIRECTORY_SEPARATOR;
        }
        if (!file_exists($convertOutputDir)) {
            mkdir($convertOutputDir, 0777, true);
        }

        if (
            file_exists($convertOutputDir)
            && is_dir($convertOutputDir)
            && is_writable($convertOutputDir)
        ) {
            $json = json_decode(file_get_contents($dir . $file . ".info"));
            if (!empty($json)) {
                $ext = pathinfo($json->filename, PATHINFO_EXTENSION);

                $fileIn = $dir . $file;
                if ($ext === 'csv') {
                    $converter = 'csvconvert';
                    $ext = Translate::_("idbyii2", 'CSV Imported Data');
                } else {
                    $converter = 'ssconvert';
                    $ext = '%s';
                }
                $fileOut = $convertOutputDir . $file . ".%n.$ext";
                if ($converter === 'ssconvert') {
                    self::ssconvert($fileIn, $fileOut);
                } elseif ($converter === 'csvconvert') {
                    $fileOut = str_replace(".%n.", ".0.", $fileOut);
                    self::csvconvert($fileIn, $fileOut);
                }
            } else {
                self::logConvert(
                    [
                        'function' => __FUNCTION__,
                        'dir' => $dir,
                        'file' => $file,
                        'convertOutputDir' => $convertOutputDir,
                        'error' => 'Info file is corrupted and we cannot finish import!.'
                    ],
                    SimpleLogLevel::ERROR
                );
            }
        } else {
            self::logConvert(
                [
                    'function' => __FUNCTION__,
                    'dir' => $dir,
                    'file' => $file,
                    'convertOutputDir' => $convertOutputDir,
                    'error' => 'Cannot write to output dir.'
                ],
                SimpleLogLevel::ERROR
            );
        }

        return $convertOutputDir;
    }

    protected static function ssconvert(
        $fileIn,
        $fileOut,
        $importEncoding = 'UTF-8',
        $exporter = 'Gnumeric_stf:stf_assistant',
        $exportOptions = 'eol=unix locale=en_GB charset=UTF-8 quote="\"" separator=; format=raw transliterate-mode=escape quoting-mode=always quoting-on-whitespace=TRUE'
    ) {
        $output = null;
        $options = "-E '$importEncoding' -T '$exporter' -S -O '$exportOptions'";
        $command = "ssconvert $options '$fileIn' '$fileOut'";
        exec($command, $output);
        self::logConvert(['function' => __FUNCTION__, 'command' => $command, 'output' => $output]);
    }

    protected static function csvconvert(
        $fileIn,
        $fileOut,
        $enclosure = '"',
        $delimiter = ';',
        $encoding = 'UTF-8',
        $escape = '\\'
    ) {
        $exportDelimiter = ';';
        $exportEnclosure = '"';
        $exportEscapeChar = '\\';
        $output = [
            'fileIn' => $fileIn,
            'fileOut' => $fileOut,
            'enclosure' => $enclosure,
            'delimiter' => $delimiter,
            'encoding' => $encoding,
            'escape' => $escape,
        ];
        $start = microtime(true);
        $fileIn = fopen($fileIn, "r");
        if (false === $fileIn) {
            self::logConvert(
                [
                    'function' => __FUNCTION__,
                    'fileIn' => $fileIn,
                    'error' => 'Cannot open input file.'
                ],
                SimpleLogLevel::ERROR
            );
        }
        $fileOut = fopen($fileOut, 'w');
        if (false === $fileOut) {
            self::logConvert(
                [
                    'function' => __FUNCTION__,
                    'fileIn' => $fileIn,
                    'error' => 'Cannot open output file to import file.'
                ],
                SimpleLogLevel::ERROR
            );
        }
        if (false !== $fileIn) {
            while (($row = fgets($fileIn)) !== false) {
                $row = str_getcsv($row, $delimiter, $enclosure, $escape);
                $outputRow = [];
                foreach ($row as $col) {
                    $outputRow[] = $exportEnclosure . str_replace(
                            $exportEnclosure,
                            $exportEscapeChar . $exportEnclosure,
                            $col
                        ) . $exportEnclosure;
                }
                fwrite($fileOut, implode($exportDelimiter, $outputRow) . PHP_EOL);
            }
        }
        if (false !== $fileIn) {
            fclose($fileIn);
        }
        if (false !== $fileOut) {
            fclose($fileOut);
        }
        $output['Execution time'] = microtime(true) - $start;
        self::logConvert(['function' => __FUNCTION__, 'output' => $output]);
    }

    private static function logConvert($arguments, $level = SimpleLogLevel::DEBUG)
    {
        $logName = "p57b.idb_import";
        $logPath = "/var/log/p57b/$logName.log";
        registerLogger($logName, $logPath);

        $argumentsString = '';
        if (!empty($arguments) && is_array($arguments)) {
            foreach ($arguments as $argumentKey => $argumentValue) {
                if (is_array($argumentValue)) {
                    $argumentValue = json_encode($argumentValue);
                }
                $argumentsString .= "[$argumentKey: $argumentValue]";
            }
        }

        $pid = getmypid();
        Log::custom(
            $logName,
            "$pid - " .
            $argumentsString,
            $level
        );
    }
}

################################################################################
#                                End of file                                   #
################################################################################
