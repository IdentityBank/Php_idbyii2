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

################################################################################
# Class(es)                                                                    #
################################################################################

/**
 * Class AvailableLanguage
 *
 * @package idbyii2\enums
 */
class AvailableLanguage extends Enum
{

    const nl_NL = 'nl_NL';
    const en_GB = 'en_GB';
    const pl_PL = 'pl_PL';
    const fr_FR = 'fr_FR';
    const de_DE = 'de_DE';

    /**
     * @param $enum
     *
     * @return mixed|null
     */
    public static function translate($enum)
    {
        switch ($enum) {
            case self::nl_NL:
                return Translate::_('idbyii2', 'Dutch');
            case self::en_GB:
                return Translate::_('idbyii2', 'English GB');
            case self::pl_PL:
                return Translate::_('idbyii2', 'Polish');
            case self::fr_FR:
                return Translate::_('idbyii2', 'French');
            case self::de_DE:
                return Translate::_('idbyii2', 'German');
        }

        return null;
    }
}

################################################################################
#                                End of file                                   #
################################################################################
