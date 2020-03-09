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
# Class(es)                                                                    #
################################################################################

/**
 * Class VerificationCode
 *
 * @package idbyii2\helpers
 */
class VerificationCode
{

    /**
     * Parse code from array like ['3','4','5','4'...'n'] to string like '345.4...n'.
     *
     * @param array $digits
     *
     * @return string
     */
    public static function parseFromArray(array $digits)
    {
        $verificationCode = '';

        $counter = 1;
        foreach ($digits as $digit) {
            $verificationCode .= $digit;
            if ($counter % 3 == 0 && $counter !== 12) {
                $verificationCode .= '.';
            }

            $counter++;
        }

        return $verificationCode;
    }

}
