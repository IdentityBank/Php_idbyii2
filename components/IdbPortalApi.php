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

namespace idbyii2\components;

################################################################################
# Use(s)                                                                       #
################################################################################

use yii\web\NotFoundHttpException;

################################################################################
# Class(es)                                                                    #
################################################################################

/**
 * Class IdbPortalApi
 *
 * @package idbyii2\components
 */
class IdbPortalApi extends IdbPortalApiBase
{

    /**
     * @param null $data
     *
     * @return |null
     * @throws NotFoundHttpException
     */
    public function requestPeopleSignUp($data = null)
    {
        return $this->request('peopleSignUp', $data);
    }

    /**
     * @param $user
     *
     * @return |null
     * @throws NotFoundHttpException
     */
    public function requestBusinessNameForUser($user)
    {
        return $this->request('businessNameForUser', $user);
    }

    public function requestSendEmail($data)
    {
        return $this->request('sendEmail', $data);
    }

    /**
     * @param $user
     *
     * @return |null
     * @throws NotFoundHttpException
     */
    public function requestBusinessDataForUser($user)
    {
        return $this->request('businessDataForUser', $user);
    }

    /**
     * @param $data
     *
     * @return |null
     * @throws NotFoundHttpException
     */
    public function requestUpdateMappedBusiness($data)
    {
        return $this->request('updateMappedBusiness', $data);
    }

    /**
     * @param $user
     *
     * @return |null
     * @throws NotFoundHttpException
     */
    public function requestBusinessMetadataForUser($user)
    {
        return $this->request('businessMetadataForUser', $user);
    }

    /**
     * @param $data
     *
     * @return |null
     * @throws NotFoundHttpException
     */
    public function requestOrganizationEmails($data)
    {
        return $this->request('organizationEmails', $data);
    }

    /**
     * @param $userId
     *
     * @return mixed
     * @throws NotFoundHttpException
     */
    public function requestPeopleInfo($userId)
    {
        return $this->request('peopleInfo', $userId);
    }

    /**
     * @param $data
     * @return mixed
     * @throws NotFoundHttpException
     */
    public function requestAddFileUploadRequests($data)
    {
        return $this->request('addFileUploadRequests', $data);
    }

    /**
     * @param $bundle
     *
     * @return mixed
     * @throws NotFoundHttpException
     */
    public function requestBusiness2PeopleMessageInfo($bundle)
    {
        return $this->request('business2PeopleMessageInfo', $bundle);
    }

    /**
     * @param $data
     * @return mixed
     * @throws NotFoundHttpException
     */
    public function requestPeopleNotification($data)
    {
        return $this->request('peopleNotification', $data);
    }

    /**
     * @param $data
     *
     * @return |null
     * @throws NotFoundHttpException
     */
    public function requestUpdateBusinessDataForUser($data)
    {
        return $this->request('updateBusinessDataForUser', $data);
    }

    /**
     * @param $vault
     * @return mixed
     * @throws NotFoundHttpException
     */
    public function requestUploadRequestsByVault($vault)
    {
        $response = $this->request('uploadRequestsByVault', $vault);

        return $response === 'empty' ? [] : $response;
    }

    /**
     * @param $vault
     * @return mixed
     * @throws NotFoundHttpException
     */
    public function requestUploadRequestsByVaultAndType($vault)
    {
        $response = $this->request('uploadRequestsByVaultAndType', $vault);

        return $response === 'empty' ? [] : $response;
    }

    /**
     * @param $vault
     * @return mixed
     * @throws NotFoundHttpException
     */
    public function requestUploadActiveRequestsByVault($vault)
    {
        $response = $this->request('uploadActiveRequestsByVault', $vault);

        return $response === 'empty' ? [] : $response;
    }

    /**
     * @param $id
     * @return mixed
     * @throws NotFoundHttpException
     */
    public function requestUploadRequest($id)
    {
        $response = $this->request('uploadRequest', $id);

        return $response === 'empty' ? null : $response;
    }

    /**
     * @param $request_id
     * @return |null
     * @throws NotFoundHttpException
     */
    public function requestFilesRequestsByRequest($request_id)
    {
        $response = $this->request('filesRequestsByRequest', $request_id);

        return $response === 'empty' ? [] : $response;
    }

    /**
     * @param $id
     * @return mixed
     * @throws NotFoundHttpException
     */
    public function requestDeleteUploadRequest($id)
    {
        return $this->request('deleteUploadRequest', $id);
    }

    /**
     * @param $id
     * @return |null
     * @throws NotFoundHttpException
     */
    public function requestDeleteFromBusiness($id)
    {
        return $this->request('deleteFromBusiness', $id);
    }

    /**
     * @param $data
     * @return mixed
     * @throws NotFoundHttpException
     */
    public function requestUpdateFileUploadRequest($data)
    {
        return $this->request('updateFileUploadRequest', $data);
    }
    /**
     * @param $data
     * @return |null
     * @throws NotFoundHttpException
     */
    public function requestNotifyBusinessUser($data)
    {
        return $this->request('notifyBusinessUser', $data);
    }

    /**
     * @param $data
     * @return |null
     * @throws NotFoundHttpException
     */
    public function requestDeleteBusinessDataForUser($data)
    {
        return $this->request('deleteBusinessDataForUser', $data);
    }

    /**
     * @param $data
     *
     * @return |null
     * @throws NotFoundHttpException
     */
    public function requestDeleteAllBusinessDataForUser($data)
    {
        return $this->request('deleteAllBusinessDataForUser', $data);
    }

    /**
     * @param $businessId
     * @return |null
     * @throws NotFoundHttpException
     */
    public function requestBusinessPrivacyDetails($businessId)
    {
        return $this->request('businessPrivacyDetails', $businessId);
    }

    /**
     * @param $businessId
     * @return |null
     * @throws NotFoundHttpException
     */
    public function requestBusinessGPDR($businessId)
    {
        return $this->request('businessGPDR', $businessId);
    }
}

################################################################################
#                                End of file                                   #
################################################################################
