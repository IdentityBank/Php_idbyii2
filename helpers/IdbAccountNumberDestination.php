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

/**
 * Class IdbAccountNumberDestination
 *
 * @package idbyii2\helpers
 */
class IdbAccountNumberDestination
{

    const __default = self::business;
    private $destination = self::__default;

    const business = 1;
    const people = 2;
    const admin = 3;
    const billing = 4;

    /**
     * IdbAccountNumberDestination constructor.
     *
     * @param $destinationId
     */
    protected function __construct($destinationId)
    {
        $this->destination = $destinationId;
    }

    /**
     * @param $destinationId
     *
     * @return \idbyii2\helpers\IdbAccountNumberDestination
     */
    public static function fromId($destinationId)
    {
        return new self($destinationId);
    }

    /**
     * @param string $destinationName
     *
     * @return \idbyii2\helpers\IdbAccountNumberDestination|null
     */
    public static function fromString(string $destinationName)
    {
        switch (strtolower($destinationName)) {
            case 'business':
                {
                    return new self(self::business);
                }
                break;
            case 'people':
                {
                    return new self(self::people);
                }
                break;
            case 'admin':
                {
                    return new self(self::admin);
                }
                break;
            case 'billing':
                {
                    return new self(self::billing);
                }
                break;
            default:
                {
                    return null;
                }
                break;
        }
    }

    /**
     * @return string
     */
    public function __toString()
    {
        switch ($this->destination) {
            case self::business:
                {
                    return 'business';
                }
                break;
            case self::people:
                {
                    return 'people';
                }
                break;
            case self::admin:
                {
                    return 'admin';
                }
                break;
            case self::billing:
                {
                    return 'billing';
                }
                break;
            default:
                {
                    return '';
                }
                break;
        }
    }

    /**
     * @return string
     */
    public function toString()
    {
        return $this->__toString();
    }

    /**
     * @return int
     */
    public function toId()
    {
        return $this->destination;
    }
}

################################################################################
#                                End of file                                   #
################################################################################
