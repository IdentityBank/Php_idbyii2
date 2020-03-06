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

use DirectoryIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RecursiveRegexIterator;
use RegexIterator;
use SplFileInfo;
use yii\helpers\ArrayHelper;

################################################################################
# Class(es)                                                                    #
################################################################################

/**
 * Tools for messages for IDB portals.
 *
 * Tools used to manage messages used at IDB.
 *
 **/
class IdbMessagesHelper
{

    /**
     * @param string     $sourcePath
     * @param string     $sourceLanguage
     * @param array|null $categories
     *
     * @return array
     */
    public static function getAllMessageKeys(
        string $sourcePath,
        string $sourceLanguage = "([a-z]{2}[_-][A-Z]{2})",
        array $categories = null
    ) {
        $messageKeys = [];
        $languageMessagesFiles = IdbMessagesHelper::getAllMessagesLocations($sourcePath, $sourceLanguage);
        foreach ($languageMessagesFiles as $languageMessagesFileCategory => $languageCategoryFiles) {
            if (
                empty($categories)
                || (is_array($categories)
                    && in_array($languageMessagesFileCategory, $categories))
            ) {
                foreach ($languageCategoryFiles as $languageCategoryFile) {
                    $messageKeys = ArrayHelper::merge($messageKeys, array_keys(include($languageCategoryFile)));
                    $messageKeys = ArrayHelper::merge($messageKeys, array_values(include($languageCategoryFile)));
                }
            }
        }

        return $messageKeys;
    }

    /**
     * @param string $sourcePath
     * @param string $sourceLanguage
     *
     * @return array
     */
    public static function getAllMessagesLocations(
        string $sourcePath,
        string $sourceLanguage = "([a-z]{2}[_-][A-Z]{2})"
    ) {
        $messagesLocations = [];
        if (!empty($sourcePath) && substr($sourcePath, -1) !== DIRECTORY_SEPARATOR) {
            $sourcePath .= DIRECTORY_SEPARATOR;
        }
        $projects = new DirectoryIterator($sourcePath);
        foreach ($projects as $project) {
            $messages = self::getAllProjectMessages(
                $project,
                $sourceLanguage
            );
            if (!empty($messages)) {
                $messagesLocations[basename($project->getPathname())] = $messages;
            }
        }

        return $messagesLocations;
    }

    /**
     * @param \DirectoryIterator $project
     * @param string             $sourceLanguage
     *
     * @return array
     */
    public static function getAllProjectMessages(
        string $project,
        string $sourceLanguage = "([a-z]{2}[_-][A-Z]{2})"
    ) {
        $messagesLocation = [];
        $project = new SplFileInfo($project);
        $messagesPath = $project->getPathname() . DIRECTORY_SEPARATOR . 'messages';
        if (
            $project->isDir()
            && is_dir($messagesPath)
        ) {
            $messagesLocationPaths = self::scanFiles(
                $messagesPath,
                "/^(.*)\\/messages\\/$sourceLanguage\\/(.*)\.(php|inc)$/i"
            );
            $messagesLocation = [];
            foreach ($messagesLocationPaths as $messagesLocationPath) {
                if (basename($messagesLocationPath) !== 'idbexclude.php') {
                    $messagesLocation[] = $messagesLocationPath;
                }
            }
        }

        return $messagesLocation;
    }

    /**
     * @param string $sourcePath
     *
     * @param string $regexFilter - regex filer (default looks for php/inc files)
     *                            default is : '/^(.*)\.(php|inc)$/i'
     *
     * @return array
     */
    public static function scanFiles(
        string $sourcePath,
        string $regexFilter = '/^(.*)\.(php|inc)$/i'
    ) {
        $files = [];
        $directory = new RecursiveDirectoryIterator($sourcePath);
        $iterator = new RecursiveIteratorIterator($directory);
        $regex = new RegexIterator($iterator, $regexFilter, RecursiveRegexIterator::GET_MATCH);
        foreach ($regex as $info) {
            $files[] = $info[0];
        }

        return $files;
    }

    /**
     * @param string $portalPath
     *
     * @param string $sourceLanguage
     *
     * @return array
     */
    public static function loadExcludes(
        string $portalPath,
        string $sourceLanguage
    ) {
        $excludes = [];
        $excludesKeyFilePath = $portalPath
            . DIRECTORY_SEPARATOR
            . "messages"
            . DIRECTORY_SEPARATOR
            . $sourceLanguage
            . DIRECTORY_SEPARATOR
            . "idbexclude.php";
        if (is_readable($excludesKeyFilePath)) {
            $excludes = include($excludesKeyFilePath);
        }

        return $excludes;
    }

    /**
     * @param array  $translationsFiles
     * @param array  $files
     *
     * @param array  $excludes
     *
     * @param string $translator
     *
     * @return array
     */
    public static function executeOriginalTranslationFiles(
        array $translationsFiles,
        array $files,
        array $excludes,
        string $translator
    ) {
        $update = [];
        foreach ($translationsFiles as $translationsFile) {
            $updateCounter = 0;
            $translationStrings = include($translationsFile);
            foreach ($excludes as $exclude) {
                ArrayHelper::remove($translationStrings, $exclude);
            }
            foreach ($files as $file) {
                $updateCounter += self::findReplaceFile(
                    $file,
                    $translationStrings,
                    $translator
                );
            }
            $update[$translationsFile] = $updateCounter;

        }

        return $update;
    }

    /**
     * @param string $file
     * @param string $translationStrings
     *
     * @param string $translator
     *
     * @return int
     */
    private static function findReplaceFile(
        string $file,
        array $translationStrings,
        string $translator
    ) {
        $countAll = 0;
        if (!is_writable($file)) {
            return $countAll;
        }
        $fileContent = file_get_contents($file);
        if (empty($translator)) {
            $translator = 'Translate::_';
        }
        $regexPatternApostrophe = '/(' . $translator . '\\s*?\\(\\s*?[\'|"]\\w+[\'|"]\\s*?,\\s*?\')(%s)(\')/m';
        $regexPatternQuote = '/(' . $translator . '\\s*?\\(\\s*?[\'|"]\\w+[\'|"]\\s*?,\\s*?")(%s)(")/m';
        foreach ($translationStrings as $translationKey => $translationValue) {
            if (empty($translationValue)) {
                continue;
            }
            $translationKey = str_replace('/', '\\/', $translationKey);
            // Apply escape options - apostrophe
            $translationKeySafe = str_replace('\'', '\\\\\'', $translationKey);
            $regex = sprintf($regexPatternApostrophe, $translationKeySafe);
            $fileContent = preg_replace(
                $regex,
                '${1}' . str_replace('\'', '\\\'', $translationValue) . '${3}',
                $fileContent,
                -1,
                $count
            );
            $countAll += $count;
            // Apply escape options - quote
            $translationKeySafe = str_replace('"', '\\\\"', $translationKey);
            $regex = sprintf($regexPatternQuote, $translationKeySafe);
            $fileContent = preg_replace(
                $regex,
                '${1}' . str_replace('"', '\\"', $translationValue) . '${3}',
                $fileContent,
                -1,
                $count
            );
            $countAll += $count;
        }
        file_put_contents($file, $fileContent);

        return $countAll;
    }
}

################################################################################
#                                End of file                                   #
################################################################################
