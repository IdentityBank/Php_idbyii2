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

use idbyii2\models\idb\IdbBankClientBusiness;

################################################################################
# Class(es)                                                                    #
################################################################################

/**
 * Class Export
 *
 * @package idbyii2\helpers
 */
class FileHelper
{

    const PAGE_SIZE = 50;
    const FILE_NAME = 'IdbClientData-';

    const STATUS_ADDED = "added";
    const STATUS_TO_CONVERT = "to convert";
    const STATUS_CONVERTED = "converted";
    const STATUS_IN_PROGRESS = "in progress";
    const STATUS_TO_MAP = "to map";
    const STATUS_TO_IMPORT = "to import";
    const STATUS_IMPORTED = "imported";
    const STATUS_ERROR = "error";
    const STATUS_TO_REMOVE = "to remove";
    const STATUS_REMOVED = "removed";
    const STATUS_TO_DOWNLOAD = "to download";
    const STATUS_DOWNLOADED = "downloaded";
    const STATUS_READY_TO_PROCESS = "ready";
    const STATUS_ACCEPTED = "accepted";
    const STATUS_TO_REVERSE = "to reverse";
    const STATUS_REVERSED = "reversed";
    const STATUS_VERIFIED = "verified";

    /**
     * @param $businessId
     *
     * @throws \Exception
     */
    public static function createTables($businessId)
    {
        $clientModel = IdbBankClientBusiness::model($businessId);
        $clientModel->CreateAccount([]);
        $clientModel->createAccountMetadata();
        $clientModel->createAccountEvents();
        $clientModel->createAccountCR();
        $clientModel->createAccountST();
    }

    /**
     * @param $status
     *
     * @return string
     */
    public static function getDisplayStatus($status)
    {
        switch ($status) {
            case self::STATUS_ADDED:
                return Translate::_("idbyii2", "Arrived on server");
            case self::STATUS_TO_CONVERT:
                return Translate::_("idbyii2", "Queued for extraction");
            case self::STATUS_CONVERTED:
                return Translate::_("idbyii2", "Extracted");
            case self::STATUS_IN_PROGRESS:
                return Translate::_("idbyii2", "Import in progress");
            case self::STATUS_TO_IMPORT:
                return Translate::_("idbyii2", "Queued for import");
            case self::STATUS_IMPORTED:
                return Translate::_("idbyii2", "Imported");
            case self::STATUS_ERROR:
                return Translate::_("idbyii2", "Error");
            default:
                return Translate::_("idbyii2", "Status not found");
        }
    }
}

################################################################################
#                                End of file                                   #
################################################################################
