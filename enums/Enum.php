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

namespace idbyii2\enums;

################################################################################
# Use(s)                                                                       #
################################################################################

use idbyii2\helpers\Translate;
use ReflectionClass;

################################################################################
# Class(es)                                                                    #
################################################################################

/**
 * Class Enum, is a base to inherit for Enums classes
 *
 * @package idbyii2\enums
 */
class Enum
{

    /**
     * Returns all constants in format [key1 => value1, ..., keyN => valueN].
     *
     * @return array
     * @throws \ReflectionException
     */
    public static function getAllKeyValuePairs()
    {
        $reflection = new ReflectionClass(get_called_class());

        return $reflection->getConstants();
    }

    /**
     * Returns all constants key in format [key1, ..., keyN].
     *
     * @return array
     * @throws \ReflectionException
     */
    public static function getAllKeys()
    {
        return array_keys(self::getAllKeyValuePairs());
    }

    /**
     * Returns all constants values in format [value1, ..., valueN].
     *
     * @return array
     * @throws \ReflectionException
     */
    public static function getAllValues()
    {
        return array_values(self::getAllKeyValuePairs());
    }

    /**
     * @param $enum
     *
     * @return mixed
     */
    public static function translate($enum)
    {
        return Translate::external($enum);
    }
}

################################################################################
#                                End of file                                   #
################################################################################
