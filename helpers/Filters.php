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
 * Class Filters
 *
 * @package idbyii2\helpers
 */
class Filters
{

    /**
     * @param $array
     *
     * @return array
     */
    public static function filterByIds($array)
    {
        $result = [];
        $tmp = &$result;
        $names = [];
        $values = [];
        foreach ($array as $i => $value) {
            if ($i + 1 == count($array)) {
                $tmp = [
                    "o" => '=',
                    'l' => '#col' . $i,
                    'r' => ':col' . $i
                ];
            } else {
                $tmp = [
                    "o" => 'OR',
                    'l' => [
                        'o' => '=',
                        'l' => '#col' . $i,
                        'r' => ':col' . $i
                    ],
                    'r' => []
                ];
            }

            $tmp = &$tmp['r'];

            $names['#col' . $i] = 'id';
            $values[':col' . $i] = str_replace('*', '%', $value);
        }

        return ['exprAttrNames' => $names, 'exprAttrVal' => $values, 'filterExpr' => $result];
    }
}

################################################################################
#                                End of file                                   #
################################################################################

