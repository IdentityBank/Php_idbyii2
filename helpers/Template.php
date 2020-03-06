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
# Include(s)                                                                   #
################################################################################

use Smarty;

require_once('Smarty.class.php');

################################################################################
# Class(es)                                                                    #
################################################################################

/**
 * Class Template
 *
 * @package idbyii2\helpers
 */
class Template
{

    /**
     * @param       $file
     * @param array $data
     *
     * @return mixed
     */
    public static function stringFromFile($file, $data = [])
    {
        $smarty = new Smarty();
        $smarty->setCompileDir('/tmp/smarty/templates_c/');
        $smarty->setCacheDir('/tmp/smarty/cache/');
        $tpl = $smarty->createTemplate($file);
        if (!empty($data) && (is_array($data))) {
            foreach ($data as $key => $value) {
                $tpl->assign($key, $value);
            }
        }

        return $tpl->fetch();
    }
}

################################################################################
#                                End of file                                   #
################################################################################
