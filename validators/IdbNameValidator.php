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

namespace idbyii2\validators;

################################################################################
# Use(s)                                                                       #
################################################################################

use app\helpers\Translate;
use yii\validators\Validator;

################################################################################
# Class(es)                                                                    #
################################################################################

/**
 * Class IdbNameValidator
 *
 * @package idbyii2\validators
 */
class IdbNameValidator extends Validator
{

    private static $minLength = 3;
    private static $maxLength = 64;
    private static $regexSpecialCharacters = "\-\+\.,:;_@&";

    /**
     * Custom rules for attribute based on the validation requirement
     *
     * @param      $attribute      - attribute name
     * @param null $attributeLabel - attribute display name (Label)
     *
     * @return array
     */
    public static function customRules($attribute, $attributeLabel = null)
    {
        if (empty($attributeLabel)) {
            $attributeLabel = $attribute;
        }

        return [
            [$attribute, 'string', 'length' => [self::$minLength, self::$maxLength]],
            [
                $attribute,
                'match',
                'pattern' => sprintf('/^[\w\p{L} %s]+$/su', self::$regexSpecialCharacters),
                'message' => Translate::_(
                    'idbyii2',
                    '{attribute} only allow alphanumeric characters, space, full stop, comma, colon, semi-colon, hyphen, plus sign or characters: @ &',
                    ['attribute' => $attributeLabel]
                )
            ],
            [
                $attribute,
                'match',
                'pattern' => sprintf('/^[a-zA-Z0-9\p{L}][\w\p{L} %s]+$/su', self::$regexSpecialCharacters),
                'message' => Translate::_(
                    'idbyii2',
                    '{attribute} must start with alphanumeric character.',
                    ['attribute' => $attributeLabel]
                ),
            ],
            [$attribute, 'trim'],
            [$attribute, 'required'],
        ];
    }

    /**
     * Adjust string name based on the validation requirement
     *
     * @param $name - original string
     *
     * @return bool|string|string[]|null - new string where all not acceptable characters was removed
     */
    public static function adjustName($name)
    {
        $name = substr($name, 0, self::$maxLength);
        $name = trim($name);
        $name = preg_replace(sprintf('/[^\w\p{L} %s]+/su', self::$regexSpecialCharacters), '', $name);
        $start = preg_replace('/[^a-zA-Z0-9\p{L}]/su', '', substr($name, 0, 1));
        if (empty($start)) {
            $name = substr($name, 1);
        }

        return $name;
    }
}

################################################################################
#                                End of file                                   #
################################################################################
