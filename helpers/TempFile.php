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

################################################################################
# Class(es)                                                                    #
################################################################################
use Yii;
use yii\web\NotFoundHttpException;

/**
 * Class TempFile
 *
 * @package idbyii2\helpers
 */
class TempFile
{

    /**
     * @param $file
     */
    public static function removeTempFile($file)
    {
        @unlink($file);
    }

    /**
     * @param string $prefix (optional) Name prefix
     *
     * @param bool   $fixTmp
     *
     * @return false|resource
     * @throws \yii\web\NotFoundHttpException When tmp directory doesn't exist or failed to create
     */
    public static function getTempFile($prefix = 'temp', $fixTmp = false)
    {
        return fopen(self::getTempFileName($prefix, $fixTmp), 'w');
    }

    /**
     * Create a temp file and get full path.
     *
     * @param string $prefix (optional) Name prefix
     *
     * @param bool   $fixTmp
     *
     * @return string Full temp file path
     * @throws \yii\web\NotFoundHttpException When tmp directory doesn't exist or failed to create
     */
    public static function getTempFileName($prefix = 'temp', $fixTmp = false)
    {
        $tmpDir = Yii::$app->runtimePath . '/tmp';

        if (!is_dir($tmpDir) && (!@mkdir($tmpDir) && !is_dir($tmpDir))) {
            throw new NotFoundHttpException ('temp directory does not exist');
        }

        if ($fixTmp) {
            return $tmpDir . '/' . $prefix;
        } else {
            return tempnam($tmpDir, $prefix);
        }
    }

    /**
     * @param string $prefix
     * @param bool   $fixTmp
     *
     * @return false|string
     * @throws \yii\web\NotFoundHttpException
     */
    public static function getTempFileContent($prefix = 'temp', $fixTmp = false)
    {
        return file_get_contents(TempFile::getTempFileName($prefix, $fixTmp));
    }

    /**
     * @param        $content
     * @param string $prefix
     *
     * @param bool   $fixTmp
     *
     * @throws \yii\web\NotFoundHttpException
     */
    public static function writeTempFile($content, $prefix = 'temp', $fixTmp = false)
    {
        $file = self::getTempFileName($prefix, $fixTmp);
        file_put_contents($file, $content);
    }
}

#################################################################################
##                                End of file                                   #
#################################################################################
