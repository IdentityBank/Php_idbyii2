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

################################################################################
# Class(es)                                                                    #
################################################################################
use stdClass;

/**
 * Class IdbBusinessMetadataPeopleMappingModel
 *
 * @package idbyii2\models\idb
 */
class IdbBusinessMetadataPeopleMappingModel extends IdbBusinessMetadataHeaderMappingModel
{

    private $uuidEmail;
    private $uuidMobile;
    private $uuidName;
    private $uuidSurname;

    /**
     * Allow to create Column mapping for people model data
     *
     * @param \stdClass $data stdClass data extracted from metadata
     *
     * @return \idbyii2\models\idb\IdbBusinessMetadataPeopleMappingModel return model with all columns mapping which
     *                                                                   exist at metadata
     */
    public static function fromData(stdClass $data): IdbBusinessMetadataPeopleMappingModel
    {
        $model = new static();

        if (!empty($data->email_no)) {
            $model->uuidEmail = $data->email_no;
        }
        if (!empty($data->mobile_no)) {
            $model->uuidMobile = $data->mobile_no;
        }
        if (!empty($data->name_no)) {
            $model->uuidName = $data->name_no;
        }
        if (!empty($data->surname_no)) {
            $model->uuidSurname = $data->surname_no;
        }

        return $model;
    }
}

################################################################################
#                                End of file                                   #
################################################################################
