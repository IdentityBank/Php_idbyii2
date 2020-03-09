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

namespace idbyii2\models\idb;

################################################################################
# Use(s)                                                                       #
################################################################################

use yii\base\Model;

################################################################################
# Class(es)                                                                    #
################################################################################

/**
 * Class IdbBankModel - access model for IDB data
 *
 * That class allow to access IDB data via business, people and relation APIs
 *
 * Example usage:
 * $model =
 * IdbBankModel::business('oid.d8337a0e-d90a-56ba-9113-9413400decab.aid.0254333a-351a-5a1a-bdd5-37ee3639c6e2.dbid.3732e5f3-8ab8-5a34-8647-924cbdb7411a');
 *
 * @package idbyii2\models\idb
 */
class IdbBankModel extends Model
{

    /**
     * Creates an IDBank Interface instance for query purpose.
     *
     * @param string $businessDbId - business database id for query request
     *                             it should be created via @return \idbyii2\models\idb\IdbBusinessModel - business
     *                             model to access IDB business data
     *
     * @return \idbyii2\models\idb\IdbBusinessModel - business model for IDB client
     * @see                 IdbAccountId::generateBusinessDbId
     *                      or as string with format
     *
     * @see                 IdbAccountId::formatBusinessDbId
     *
     */
    public static function business(string $businessDbId): IdbBusinessModel
    {
        $businessModel = IdbBusinessModel::model($businessDbId);

        return $businessModel;
    }

    public static function people()
    {
    }

    public static function relation()
    {
    }
}
################################################################################
#                                End of file                                   #
################################################################################
