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

use DateTime;
use Exception;
use idbyii2\models\db\BusinessUserAccount;
use idbyii2\models\form\NotificationsForm;
use idbyii2\models\idb\IdbBankClientBusiness;
use idbyii2\models\identity\IdbBusinessUser;

################################################################################
# Class(es)                                                                    #
################################################################################

/**
 * Class ChangeRequest
 *
 * @package idbyii2\helpers
 */
class ChangeRequest
{

    const NOTIFICATION_TYPE_NEW = 'new';
    const NOTIFICATION_TYPE_EXPIRED = 'expired';
    const NOTIFICATION_TYPE_CLOSE_EXPIRED = 'close_expired';
    const NOTIFICATION_TYPE_ACCEPTED = 'accepted';
    const NOTIFICATION_TYPE_TO_REVERSE = 'reverse';

    /**
     * @param $args
     *
     * @return void
     * @throws \Exception
     */
    public function process($args)
    {
        $users = BusinessUserAccount::find()->all();

        if (count($users)) {
            /** @var BusinessUserAccount $businessUser */
            foreach ($users as $businessUser) {
                $user = IdbBusinessUser::findIdentity($businessUser->uid);
                $businessId = IdbAccountId::generateBusinessDbId($user->oid, $user->aid, $user->dbid);
                $clientModel = IdbBankClientBusiness::model($businessId);
                $metadata = $clientModel->getAccountMetadata();
                $metadata = json_decode($metadata['Metadata'], true);

                $changeRequests = $clientModel->getAllAccountCRs();

                if (count($changeRequests['QueryData']) > 0) {
                    foreach ($changeRequests['QueryData'] as $changeRequest) {
                        $createdAt = new DateTime($changeRequest[3]);
                        $now = new DateTime();

                        if ($createdAt->diff($now)->days > 30) {
                            self::sendNotificationToBusiness(self::NOTIFICATION_TYPE_EXPIRED, $businessUser->uid);
                            $clientModel->deleteAccountCRbyUserId(intval($changeRequest[1]));
                        } elseif ($createdAt->diff($now)->days > 25) {
                            self::sendNotificationToBusiness(self::NOTIFICATION_TYPE_CLOSE_EXPIRED, $businessUser->uid);
                        }
                    }
                }
            }
        }
    }

    /**
     * @param string $type
     * @param string $uid
     *
     * @return void
     */
    public static function sendNotificationToBusiness(string $type, string $uid)
    {
        $notification = new NotificationsForm();
        $notification->uid = $uid;
        $notification->expires_at = date('Y-m-d-h-i-s', strtotime('+7 days'));
        $notification->type = 'green';
        $notification->status = 1;

        switch ($type) {
            case self::NOTIFICATION_TYPE_NEW:
                $notification->title = Translate::_('idbyii2', 'You have new change requests');
                $notification->body = Translate::_('idbyii2', 'You have new change requests');
                break;
            case self::NOTIFICATION_TYPE_ACCEPTED:
                $notification->title = Translate::_('idbyii2', 'Change requests accepted');
                $notification->body = Translate::_('idbyii2', 'Change requests accepted');
                break;
            case self::NOTIFICATION_TYPE_CLOSE_EXPIRED;
                $notification->title = Translate::_('idbyii2', '5 days left to automatic accept');
                $notification->body = Translate::_('idbyii2', '5 days left to automatic accept');
                break;
            case self::NOTIFICATION_TYPE_EXPIRED:
                $notification->title = Translate::_('idbyii2', 'Change requests accepted automatically, term expired');
                $notification->body = Translate::_('idbyii2', 'Change requests accepted automatically, term expired');
                break;
        }

        try {
            $notification->save();
        } catch (Exception $e) {

        }
    }
}

################################################################################
#                                End of file                                   #
################################################################################
