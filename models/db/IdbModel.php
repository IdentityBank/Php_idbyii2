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

namespace idbyii2\models\db;

################################################################################
# Use(s)                                                                       #
################################################################################

use DateTime;
use idbyii2\helpers\Uuid;
use Yii;

################################################################################
# Class(es)                                                                    #
################################################################################

/**
 * Class IdbModel
 *
 * @package idbyii2\models\db
 */
abstract class IdbModel extends IdbActiveRecords
{

    protected static $db = 'p57b';

    /**
     * @return object|\yii\db\Connection|null
     * @throws \yii\base\InvalidConfigException
     */
    public static function getDb()
    {
        return Yii::$app->get(self::$db);
    }

    /**
     * @param $salt
     * @param $column
     *
     * @return string|null
     * @throws \Exception
     */
    protected function newUid($salt, $column)
    {
        $className = get_called_class();
        $date = new DateTime();
        $uid = Uuid::uuid5($date->format("YmdHisu") . $salt);
        $uidUsed = $className::instantiate()->isUidUsed($uid, $column);

        while ($uidUsed) {
            $date = new DateTime();
            $uid = Uuid::uuid5($date->format("YmdHisu") . $salt . Uuid::uuid4());
            $uidUsed = $className::instantiate()->isUidUsed($uid, $column);
        }

        return ((!is_null($uid) && ($uid instanceof Uuid)) ? $uid->toString() : null);
    }

    /**
     * @param        $uid
     * @param string $column
     *
     * @return bool
     */
    protected function isUidUsed($uid, $column = 'uid')
    {
        if (!empty($uid)) {
            return !is_null(self::findOne([$column => $uid]));
        }

        return false;
    }
}

################################################################################
#                                End of file                                   #
################################################################################
