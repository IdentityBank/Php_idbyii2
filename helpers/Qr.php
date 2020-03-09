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

use QRcode;

################################################################################
# Class(es)                                                                    #
################################################################################

/**
 * Class Uuid
 *
 * @package idbyii2\helpers
 */
class Qr extends QRcode
{

    /**
     * @param $text
     *
     * @return false|string
     */
    public static function pngHtml($text, $size = 8)
    {
        ob_start();
        parent::png($text, false, QR_ECLEVEL_L, $size);
        $png = ob_get_contents();
        ob_end_clean();
        $png = base64_encode($png);
        $png = 'data:image/png;base64,' . $png;

        return $png;
    }

    /**
     * @param $text
     *
     * @return string
     */
    public static function svgHtml($text, $width = false)
    {
        $svg = parent::svg($text, false, false, QR_ECLEVEL_L, $width);
        $svg = base64_encode($svg);
        $svg = 'data:image/svg+xml;base64,' . $svg;

        return $svg;
    }
}

################################################################################
#                                End of file                                   #
################################################################################
