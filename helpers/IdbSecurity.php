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

use Yii;
use yii\base\Security;

################################################################################
# Class(es)                                                                    #
################################################################################

/**
 * Class IdbSecurity
 *
 * @package idbyii2\helpers
 */
class IdbSecurity
{

    public static $magic_shift_value = 5;
    protected $security;

    /**
     * IdbSecurity constructor.
     *
     * @param \yii\base\Security $security
     */
    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    /**
     * @param      $data
     * @param      $key
     * @param int  $cost
     * @param null $salt
     *
     * @return string
     */
    public function secureHash($data, $key, $cost = 1, $salt = null)
    {
        $secureHash = $data;
        for ($it = 0; $it < $cost; $it++) {
            $saltValue = $salt . $this->hash($secureHash, $key);
            $secureHash = hash("sha256", $secureHash . $saltValue);
        }

        return $secureHash;
    }

    /**
     * @param      $data
     * @param      $key
     * @param bool $rawHash
     *
     * @return string
     */
    public function hash($data, $key, $rawHash = false)
    {
        if (!is_string($data)) {
            $data = json_encode($data);
        }
        $hashData = $this->hashData($data, $key, $rawHash);
        $hashData = substr($hashData, 0, strlen($hashData) - strlen($data));

        return $hashData;
    }

    /**
     * @param      $data
     * @param      $key
     * @param bool $rawHash
     *
     * @return mixed
     */
    private function hashData($data, $key, $rawHash = false)
    {
        return $this->security->hashData($data, $key, $rawHash);
    }

    /**
     * @param      $password
     * @param null $cost
     *
     * @return mixed
     */
    public function generatePasswordHash($password, $cost = null)
    {
        return $this->security->generatePasswordHash($password, $cost);
    }

    /**
     * @param int $length
     *
     * @return mixed
     */
    public function generateRandomString($length = 32)
    {
        return $this->security->generateRandomString($length);
    }

    /**
     * @param $password
     * @param $hash
     *
     * @return mixed
     */
    public function validatePassword($password, $hash)
    {
        return $this->security->validatePassword($password, $hash);
    }

    /**
     * @param $length
     *
     * @return string
     */
    public function generatePassword($length)
    {
        $password = Yii::$app->security->generateRandomString($length);
        for ($it = 0; $it < rand(7, 21); $it++) {
            $password = str_shuffle($password);
        }

        return $password;
    }

    /**
     * @param $data
     * @param $password
     *
     * @return mixed
     */
    public function encryptByPassword($data, $password)
    {
        return $this->security->encryptByPassword($data, $password);
    }

    /**
     * @param $data
     * @param $password
     *
     * @return mixed
     */
    public function decryptByPassword($data, $password)
    {
        return $this->security->decryptByPassword($data, $password);
    }

    /**
     * @param $data
     *
     * @return string
     */
    public function magicShift($data)
    {
        $data_len = (strlen(bin2hex($data)) / 2);
        $magic_shift = round($data_len / 3);
        $magic_shift = (($magic_shift > self::$magic_shift_value) ? self::$magic_shift_value : $magic_shift);
        if ($magic_shift > 0) {
            $data_start = substr($data, 0, $magic_shift);
            $data_end = substr($data, ($magic_shift * (-1)));

            return ($data_end . substr($data, $magic_shift, ($data_len - (2 * $magic_shift))) . $data_start);
        }

        return $data;
    }

    /**
     * @param $data
     * @param $password
     *
     * @return string
     */
    public function encryptByPasswordSpeed($data, $password, $use_shift = true)
    {
        $ivlen = openssl_cipher_iv_length($cipher = "AES-128-CBC");
        $iv = openssl_random_pseudo_bytes($ivlen);
        $cipher_raw = openssl_encrypt($data, $cipher, $password, $options = OPENSSL_RAW_DATA, $iv);
        $hmac = hash_hmac('sha256', $cipher_raw, $password, $as_binary = true);
        if ($use_shift) {
            $cipher_raw = $this->magicShift($cipher_raw);
        }

        return ($iv . $hmac . $cipher_raw);
    }

    /**
     * @param $data
     * @param $password
     *
     * @return null|string
     */
    public function decryptByPasswordSpeed($data, $password, $use_shift = true)
    {
        $dataLength = strlen($data);
        $sha2len = 32;
        $ivlen = openssl_cipher_iv_length($cipher = "AES-128-CBC");
        if (
            ($dataLength <= 0)
            || ($dataLength < $ivlen)
            || ($dataLength < $sha2len)
        ) {
            return null;
        }
        $iv = substr($data, 0, $ivlen);
        $hmac = substr($data, $ivlen, $sha2len);
        if (empty($hmac)) {
            return null;
        }

        $cipher_raw = substr($data, $ivlen + $sha2len);
        if ($use_shift) {
            $cipher_raw = $this->magicShift($cipher_raw);
        }
        $original = openssl_decrypt($cipher_raw, $cipher, $password, $options = OPENSSL_RAW_DATA, $iv);
        $calcmac = hash_hmac('sha256', $cipher_raw, $password, $as_binary = true);
        if (hash_equals($hmac, $calcmac)) {
            return $original;
        }

        return null;
    }

    /**
     * @param      $data
     * @param      $inputKey
     * @param null $info
     *
     * @return mixed
     */
    public function encryptByKey($data, $inputKey, $info = null)
    {
        return $this->security->encryptByKey($data, $inputKey, $info);
    }

    /**
     * @param      $data
     * @param      $inputKey
     * @param null $info
     *
     * @return mixed
     */
    public function decryptByKey($data, $inputKey, $info = null)
    {
        return $this->security->decryptByKey($data, $inputKey, $info);
    }
}

################################################################################
#                                End of file                                   #
################################################################################
