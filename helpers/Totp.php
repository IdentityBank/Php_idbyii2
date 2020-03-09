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

use Sonata\GoogleAuthenticator\FixedBitNotation;
use Sonata\GoogleAuthenticator\GoogleAuthenticator;
use Sonata\GoogleAuthenticator\GoogleQrUrl;
use Yii;

################################################################################
# Include(s)                                                                   #
################################################################################

require_once('idbyii2/vendor/otp/src/FixedBitNotation.php');
require_once('idbyii2/vendor/otp/src/GoogleQrUrl.php');
require_once('idbyii2/vendor/otp/src/GoogleAuthenticatorInterface.php');
require_once('idbyii2/vendor/otp/src/GoogleAuthenticator.php');

################################################################################
# Class(es)                                                                    #
################################################################################

/**
 * Class Totp
 *
 * @package idbyii2\helpers
 */
class Totp
{

    /**
     * @param      $securityKey
     * @param      $code
     * @param null $time
     *
     * @return bool
     */
    public static function verify($securityKey, $code, $time = null)
    {
        return (new GoogleAuthenticator(
            strlen($code), strlen($securityKey), $time
        ))->checkCode($securityKey, $code);
    }

    /**
     * @param      $name
     * @param      $securityKey
     * @param null $issuer
     * @param int  $size
     *
     * @return false|string|null
     */
    public static function getQrCode($name, $securityKey, $issuer = null, $size = 300)
    {
        try {
            $url = self::getQrCodeUrl($name, $securityKey, $issuer, $size);
            if (!empty($url)) {
                return file_get_contents($url);
            }
        } catch (\Exception $e) {
        }

        return null;
    }

    /**
     * @param      $name
     * @param      $securityKey
     * @param null $issuer
     * @param int  $size
     *
     * @return string|null
     */
    public static function getQrCodeUrl($name, $securityKey, $issuer = null, $size = 300)
    {
        if (!empty($securityKey)) {
            return GoogleQrUrl::generate($name, $securityKey, $issuer, $size);
        }

        return null;
    }

    /**
     * @param $securityKey
     *
     * @return string
     */
    public static function decodeB32SecurityKey($securityKey)
    {
        $base32 = new FixedBitNotation(5, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567', true, true);

        return $base32->decode($securityKey);
    }

    /**
     * @param        $securityString
     * @param string $securityPassword
     *
     * @return string
     */
    public static function codeFromString($securityString, $securityPassword = 'idb')
    {
        return Totp::getCode(self::securityKeyFromString($securityString, $securityPassword));
    }

    /**
     * @param      $securityKey
     * @param null $time
     *
     * @return string
     */
    public static function getCode($securityKey, $time = null)
    {
        return (new GoogleAuthenticator())->getCode($securityKey, $time);
    }

    /**
     * @param        $securityString
     * @param string $securityPassword
     *
     * @return bool|string|\Webpatser\Uuid\Uuid
     */
    public static function securityKeyFromString($securityString, $securityPassword = 'idb')
    {
        $securityKey = Uuid::uuid5($securityString);
        $idbSecurity = new IdbSecurity(Yii::$app->security);
        $securityKey = $idbSecurity->secureHash($securityKey->toString(), $securityPassword);
        $securityKey = Totp::encodeB32SecurityKey($securityKey);
        $securityKey = substr($securityKey, 0, 32);

        return $securityKey;
    }

    /**
     * @param $securityKey
     *
     * @return string
     */
    public static function encodeB32SecurityKey($securityKey)
    {
        $base32 = new FixedBitNotation(5, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567', true, true);

        return $base32->encode($securityKey);
    }
}

################################################################################
#                                End of file                                   #
################################################################################
