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
 * Class Tag
 *
 * @package idbyii2\helpers
 */
class Tag
{

    /**
     * Parse tags strings like '###tag+++ ###another+++' to array in format ['tag', 'another'].
     *
     * @param string $tags
     * @param string $tagPrefix
     * @param string $tagPostfix
     *
     * @return array
     */
    public static function parseTagsToArray(
        string $tags,
        string $tagPrefix = "###",
        string $tagPostfix = "\+\+\+"
    ) {
        $pattern = '/(?<=(' . $tagPrefix . '))(.*?)(?=(' . $tagPostfix . '))/s';
        $matches = [];
        preg_match_all($pattern, $tags, $matches);

        return $matches[0];
    }
}

#################################################################################
##                                End of file                                   #
#################################################################################
