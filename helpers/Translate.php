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
use yii\helpers\Html;

################################################################################
# Class(es)                                                                    #
################################################################################

/**
 * Class Translate
 *
 * @package idbyii2\helpers
 */
class Translate
{

    /**
     * Create Translates to js, input ["name_of_variable" => "message"].
     *
     * @param array $messages
     *
     * @return string
     */
    public static function js(Array $messages)
    {
        $variables = '';
        foreach ($messages as $key => $message) {
            $variables .= 'const ' . $key . ' = "' . addslashes($message) . '";';
        }

        return Html::script($variables);
    }

    /**
     * Translate messages from variables
     *
     * @param       $message
     * @param array $params
     * @param null  $language
     *
     * @return mixed
     */
    public static function external($message, $params = [], $language = null)
    {
        return self::_('idbexternal', $message, $params, $language);
    }

    /**
     * @param       $category
     * @param       $message
     * @param array $params
     * @param null  $language
     *
     * @return mixed
     */
    public static function _($category, $message, $params = [], $language = null)
    {
        return self::t($category, $message, $params, $language);
    }

    /**
     * @param       $category
     * @param       $message
     * @param array $params
     * @param null  $language
     *
     * @return mixed
     */
    public static function t($category, $message, $params = [], $language = null)
    {
        $return = Yii::t($category, $message, $params, $language);

        return $return;
    }
}

################################################################################
#                                End of file                                   #
################################################################################
