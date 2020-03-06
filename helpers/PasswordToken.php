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

################################################################################
# Class(es)                                                                    #
################################################################################

/**
 * Class Localization
 *
 * @package idbyii2\helpers
 */
class PasswordToken
{

    /** @var IdbSecurity $idbSecurity */
    private $idbSecurity;
    /** @var string $tokenPassword */
    private $tokenPassword = "password";

    /**
     * PasswordToken constructor.
     */
    public function __construct()
    {
        if (!empty(Yii::$app->getModule('registration')->configSignUp)) {
            $config = Yii::$app->getModule('registration')->configSignUp;
            if (!empty($config['tokenPassword'])) {
                $this->tokenPassword = $config['tokenPassword'];
            }
        }

        $this->idbSecurity = new IdbSecurity(Yii::$app->security);
    }

    /**
     * @param $uid
     * @param $token
     *
     * @return string
     */
    public function encodeToken($token)
    {
        $salt = substr(md5(mt_rand()), 0, 16);

        return $salt . base64_encode(
                $this->idbSecurity->encryptByPasswordSpeed(
                    $token,
                    $this->tokenPassword . $salt
                )
            );
    }

    /**
     * @param $uid
     * @param $token
     *
     * @return string|null
     */
    public function decodeToken($token)
    {
        $salt = substr($token, 0, 16);

        return json_decode(
            $this->idbSecurity->decryptByPasswordSpeed(
                base64_decode(substr($token, 16)),
                $this->tokenPassword . $salt
            ),
            true
        );
    }
}

################################################################################
#                                End of file                                   #
################################################################################
