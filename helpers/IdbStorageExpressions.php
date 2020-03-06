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
 * Class IdbSecurity
 *
 * @package idbyii2\helpers
 */
class IdbStorageExpressions
{
    private static $peopleItemWithoutSearch = [
        'o' => 'OR',
        'b' => '()',
        'l' => [
            'b' => '()',
            'o' => 'AND',
            'l' => [
                'o' => '=',
                'l' => '#col1',
                'r' => ':val1',
            ],
            'r' => [
                'o' => '=',
                'l' => '#col2',
                'r' => ':val2'
            ]
        ],
        'r' => [
            'o' => '=',
            'l' => '#col3',
            'r' => ':val3'
        ]
    ];

    private $peopleItemWithSearch = [];

    public static function getPeopleItemExpression($search = false) {
        if($search) {
            return [
                'o' => 'AND',
                'l' => self::$peopleItemWithoutSearch,
                'r' => [
                    'o' => 'ILIKE',
                    'l' => '#col4',
                    'r' => ':val4'
                ]
            ];
        }

        return self::$peopleItemWithoutSearch;
    }



}

################################################################################
#                                End of file                                   #
################################################################################
