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
use Yii;
use yii\helpers\ArrayHelper;

################################################################################
# Class(es)                                                                    #
################################################################################

/**
 * Class StaticContentHelper
 *
 * @package idbyii2\helpers
 */
class StaticContentHelper
{

    /**
     * @param null $tacLanguage
     *
     * @return false|string|null
     */
    public static function getTermsAndConditions($tacLanguage = null)
    {
        try {
            $staticLocation = Yii::getAlias('@idbyii2/static');
            $tacLocationTemplate = ($staticLocation . "/tacContent/{tacLanguage}.php");

            if (!empty($tacLanguage)) {
                $tacLanguage = str_replace('-', '_', $tacLanguage);
            } else {
                $tacLanguage = Yii::$app->sourceLanguage;
            }

            $tacLocation = str_replace('{tacLanguage}', $tacLanguage, $tacLocationTemplate);
            if (!is_readable($tacLocation)) {
                $tacLanguage = 'en_GB';
                $tacLocation = str_replace('{tacLanguage}', $tacLanguage, $tacLocationTemplate);
            }

            return file_get_contents($tacLocation);
        } catch (Exception $e) {
        }

        return null;
    }

    /**
     * @param null $iso
     *
     * @return false|string|null
     */
    public static function getPrivacyNotice($iso = null)
    {
        try {
            $staticLocation = Yii::getAlias('@idbyii2/static');
            $privacyLocation = ($staticLocation . "/privacyNoticeContent/{iso}.php");

            if (!empty($iso)) {
                $iso = str_replace('-', '_', $iso);
            } else {
                $iso = Yii::$app->sourceLanguage;
            }

            $tacLocation = str_replace('{iso}', $iso, $privacyLocation);
            if (!is_readable($tacLocation)) {
                $iso = 'en_GB';
                $tacLocation = str_replace('{iso}', $iso, $privacyLocation);
            }

            return file_get_contents($tacLocation);
        } catch (Exception $e) {
        }

        return null;
    }

    /**
     * Get template for specific action and iso.
     *
     * @param string      $emailAction
     * @param string|null $iso
     *
     * @return string|null
     */
    public static function getEmailTemplate(string $emailAction, string $iso = null)
    {
        try {
            if (!empty($iso)) {
                $iso = str_replace('-', '_', $iso);
            } else {
                $iso = str_replace('-', '_', Yii::$app->sourceLanguage);
            }

            $staticLocation = Yii::getAlias('@idbyii2/static');
            $templateLocation = $staticLocation . '/templates/emails/' . $emailAction . '/' . $iso . '.html';
            if (!is_readable($templateLocation)) {
                $iso = 'en_GB';
                $templateLocation = $staticLocation . '/templates/emails/' . $emailAction . '/' . $iso . '.html';
            }
            if (is_readable($templateLocation)) {
                return file_get_contents($templateLocation);
            }

            return null;
        } catch (Exception $e) {
            var_dump($e->getMessage());
        }
    }

    /**
     * @param array $attributes
     *
     * @return mixed
     */
    public static function getFooter(array $attributes = [])
    {
        try {
            $attributes = ArrayHelper::merge(
                [
                    'footer_begin' => null,
                    'footer_end' => null,
                    'footer_year' => date('Y')
                ],
                $attributes
            );
            $footerData = self::footerFileContent($attributes['footer_language'] ?? Yii::$app->sourceLanguage);
            if (empty($footerData)) {
                $footerData = self::footerFileContent();
            }

            return Translate::external(
                $footerData,
                $attributes
            );
        } catch (Exception $e) {
            var_dump($e->getMessage());
            exit();
        }
    }

    /**
     * @param string|null $iso
     *
     * @return false|string|null
     */
    private static function footerFileContent(string $iso = null)
    {
        if (!empty($iso)) {
            $iso = str_replace('-', '_', $iso);
            if (substr($iso, 0) !== '_') {
                $iso = '_' . $iso;
            }
        }
        $filepath = Yii::getAlias('@idbyii2/static') . "/templates/footer/footer$iso.inc";
        if (is_readable($filepath)) {
            return file_get_contents($filepath);
        }

        return null;
    }
}

################################################################################
#                                End of file                                   #
################################################################################
