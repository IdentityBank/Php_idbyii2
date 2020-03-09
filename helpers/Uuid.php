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
# Include(s)                                                                   #
################################################################################

require_once('idbyii2/vendor/webpatser/laravel-uuid/tree/master/src/Webpatser/Uuid/Uuid.php');

################################################################################
# Class(es)                                                                    #
################################################################################

/**
 * Class Uuid
 *
 * @package idbyii2\helpers
 */
class Uuid extends \Webpatser\Uuid\Uuid
{

    /**
     * @param null $mac
     *
     * @return \Webpatser\Uuid\Uuid
     */
    public static function uuid1($mac = null)
    {
        return self::generate(1, $mac);
    }

    /**
     * @param        $namespace
     * @param string $name
     *
     * @return \Webpatser\Uuid\Uuid
     */
    public static function uuid3($namespace, $name = Uuid::NS_DNS)
    {
        return self::generate(3, $namespace, $name);
    }

    /**
     * @return \Webpatser\Uuid\Uuid
     */
    public static function uuid4()
    {
        return self::generate(4);
    }

    /**
     * @param        $namespace
     * @param string $name
     *
     * @return \Webpatser\Uuid\Uuid
     */
    public static function uuid5($namespace, $name = Uuid::NS_DNS)
    {
        return self::generate(5, $namespace, $name);
    }

    /**
     * @return string
     */
    public function toString()
    {
        return $this->__toString();
    }
}

################################################################################
#                                End of file                                   #
################################################################################
