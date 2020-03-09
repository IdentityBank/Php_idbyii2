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

use DateTime;
use DateTimeZone;

################################################################################
# Class(es)                                                                    #
################################################################################

/**
 * Class Localization
 *
 * @package idbyii2\helpers
 */
class Localization
{

    /**
     * @param null $default
     *
     * @return mixed|null|string
     */
    public static function getBrowserLocalization($default = null)
    {
        if (empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'en-GB';
        }
        $language = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
        $language = self::getLocale($language);
        if (empty($language)) {
            $language = $default;
        }

        return $language;
    }

    /**
     * @param null $language
     *
     * @return mixed|null
     */
    public static function getLocale($language = null)
    {
        $supported_languages = [
            'de' => 'de-DE',
            'fr' => 'fr-FR',
            'ja' => 'ja-JP',
            'it' => 'it-IT',
            'en' => 'en-GB',
            'es' => 'es-ES',
            'nl' => 'nl-NL',
            'no' => 'no-NO',
            'ru' => 'ru-RU',
            'zh' => 'zh-CN',
            'da' => 'da-DK',
            'bg' => 'bg-BG',
            'el' => 'el-GR',
            'hr' => 'hr-HR',
            'ro' => 'ro-RO',
            'sr' => 'sr-RS',
            'is' => 'is-IS',
            'ca' => 'ca-ES',
            'sv' => 'sv-SE',
            'pt' => 'pt-PT',
            'pl' => 'pl-PL',
            'fi' => 'fi-FI',
            'sl' => 'sl-SL',
        ];

        if (!is_null($language) && isset($supported_languages[$language])) {
            return $supported_languages[$language];
        }

        return null;
    }

    /**
     * @param bool $show_seconds
     *
     * @return string
     */
    public static function geTimeFormat($show_seconds = true)
    {
        if ($show_seconds) {
            return "H:i:s";
        } else {
            return 'H:i';
        }
    }

    /**
     * @return string
     */
    public static function getDateFormat()
    {
        return "d/m/Y";
    }

    /**
     * @param bool $show_seconds
     *
     * @return string
     */
    public static function getDateTimeFormat($show_seconds = true)
    {
        if ($show_seconds) {
            return "d/m/Y H:i:s";
        } else {
            return 'd/m/Y H:i';
        }
    }

    /**
     * @param bool $show_seconds
     *
     * @return string
     */
    public static function getDateTimeLogFormat($show_seconds = true)
    {
        if ($show_seconds) {
            return "Y/m/d H:i:s";
        } else {
            return 'Y/m/d H:i';
        }
    }

    /**
     * @param bool $show_seconds
     *
     * @return string
     */
    public static function getDateTimeFileFormat($show_seconds = true)
    {
        if ($show_seconds) {
            return "Ymd_His";
        } else {
            return 'Ymd_Hi';
        }
    }

    /**
     * @param bool $show_seconds
     *
     * @return string
     */
    public static function getDateTimeNumberFormat($show_seconds = true)
    {
        if ($show_seconds) {
            return "YmdHis";
        } else {
            return 'YmdHi';
        }
    }

    /**
     * @param      $timestamp
     * @param null $format
     * @param null $timezone
     * @param bool $show_seconds
     *
     * @return string
     * @throws \Exception
     */
    public static function convertToDateTimeFormat($timestamp, $format = null, $timezone = null, $show_seconds = false)
    {
        if (empty($format)) {
            $format = self::getDateTimeFormat($show_seconds);
        }
        if (empty($timezone)) {
            $timezone = date_default_timezone_get();
        }
        $dateTimeZone = new DateTimeZone($timezone);
        $dateTime = $timestamp;
        if (!($dateTime instanceof DateTime)) {
            $dateTime = new DateTime($timestamp);
        }
        $dateTime->setTimezone($dateTimeZone);

        return $dateTime->format($format);
    }

    /**
     * @param null $format
     *
     * @return string
     * @throws \Exception
     */
    public static function getDate($format = null)
    {
        if (empty($format)) {
            $format = self::getDateFormat();
        }

        return self::convertToDateTimeFormat(null, $format);
    }

    /**
     * @param bool $show_seconds
     *
     * @return string
     * @throws \Exception
     */
    public static function getDateTimeString($show_seconds = true)
    {
        return self::convertToDateTimeFormat(null, null, null, $show_seconds);
    }

    /**
     * @param bool $show_seconds
     *
     * @return string
     * @throws \Exception
     */
    public static function getDateTimeLogString($show_seconds = true)
    {
        return self::convertToDateTimeFormat(null, self::getDateTimeLogFormat($show_seconds));
    }

    /**
     * @param bool $show_seconds
     *
     * @return string
     * @throws \Exception
     */
    public static function getDateTimeFileString($show_seconds = true)
    {
        return self::convertToDateTimeFormat(null, self::getDateTimeFileFormat($show_seconds));
    }

    /**
     * @param bool $show_seconds
     *
     * @return string
     * @throws \Exception
     */
    public static function getDateTimeNumberString($show_seconds = true)
    {
        return self::convertToDateTimeFormat(null, self::getDateTimeNumberFormat($show_seconds));
    }

    /**
     * Format DateTime object to pgSQL string style.
     *
     * @param \DateTime $dateTime
     *
     * @return string
     * @throws \Exception
     */
    public static function getDatabaseDateTime(DateTime $dateTime)
    {
        return self::convertToDateTimeFormat($dateTime, 'Y-m-d H:i:s');
    }

    /**
     * @param DateTime|null $dateTime
     * @param bool $show_seconds
     * @return string
     * @throws \Exception
     */
    public static function getDateTimePortalFormat(DateTime $dateTime = null, $show_seconds = false)
    {
        if (empty($dateTime)) {
            $dateTime = new DateTime();
        }

        return self::convertToDateTimeFormat($dateTime, 'Y-m-d H:i' . (($show_seconds) ? '.s' : null));
    }

    /**
     * @param DateTime $dateTime
     * @return string
     * @throws \Exception
     */
    public static function getDateTimeInvoiceFormat(DateTime $dateTime)
    {
        return self::convertToDateTimeFormat($dateTime, 'Y M d');
    }

    /**
     * @param DateTime $from
     * @param DateTime $to
     * @param int $addDays
     * @return int|mixed
     * @throws \Exception
     */
    public static function getDiffInDays(\DateTime $from , \DateTime $to, int $addDays = 0)
    {
        $to->add(new \DateInterval('P' . $addDays . 'D'));
        if (
            $from->diff($to)->days > 0
            && $from->diff($to)->invert === 0
        ) {
            return $from->diff($to)->days;
        }

        return 0;
    }
}

################################################################################
#                                End of file                                   #
################################################################################
