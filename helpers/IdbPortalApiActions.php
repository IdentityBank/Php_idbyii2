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

use app\helpers\PeopleConfig;
use idbyii2\models\db\Business2PeopleMessagesModel;
use idbyii2\models\db\BusinessAccount;
use idbyii2\models\db\BusinessAccountUser;
use idbyii2\models\db\BusinessDatabase;
use idbyii2\models\db\BusinessUserData;
use idbyii2\models\db\PeopleNotification;
use idbyii2\models\db\PeopleRequestsFiles;
use idbyii2\models\db\PeopleUploadFileRequest;
use idbyii2\models\db\SignupPeople;
use idbyii2\models\form\IdbBusinessSignUpDPOForm;
use idbyii2\models\form\NotificationsForm;
use idbyii2\models\idb\IdbBankClientBusiness;
use idbyii2\models\idb\IdbBankClientPeople;
use Yii;
use yii\base\Exception;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;
use yii\web\NotFoundHttpException;

################################################################################
# Class(es)                                                                    #
################################################################################

/**
 * Class IdbPortalApiActions
 *
 * @package idbyii2\helpers
 */
class IdbPortalApiActions
{

    /**
     * @param $actionId
     * @param $actionData
     *
     * @return string
     * @throws \Exception
     * @throws \Throwable
     */
    private static function executeAction($actionId, $actionData)
    {
        switch ($actionId) {
            case 'peopleSignUp':
                if (!empty($actionData)) {
                    $signUpModel = new SignupPeople();
                    $signUpModel->setDataFromPost($actionData);
                    $signUpModel->generateAuthKey();
                    $authKey = $signUpModel->auth_key;
                    $signUpModel->save();

                    $url = Url::toRoute(['signup/have-account', 'id' => $authKey], true);

                    return $url;
                }

                break;

            case 'uploadRequest':
                try {
                    $requests = PeopleUploadFileRequest::find()->where(['id' => $actionData])->asArray()->one();
                    if(empty($requests)) {
                        return 'empty';
                    }

                    return $requests;
                } catch (\Exception $exception) {
                    Yii::error('REQUEST UPLOAD REQUESTS BY VAULT');
                    Yii::error($exception->getMessage());

                    return 'empty';
                }

            case 'addFileUploadRequests':
                if(!empty($actionData)) {
                    try {
                        foreach($actionData['requests'] as $request) {
                            $uploadRequest = new PeopleUploadFileRequest();
                            $uploadRequest->pid = $request['pid'];
                            $uploadRequest->name = htmlspecialchars($request['name']);
                            $uploadRequest->message = htmlspecialchars($request['message']);
                            $uploadRequest->dbid = $request['dbid'];
                            $uploadRequest->type = $request['type'];
                            $uploadRequest->uploads = $request['uploads'];
                            $uploadRequest->request_uuid = PeopleUploadFileRequest::newRequestUuid($uploadRequest->name);
                            $uploadRequest->upload_limit = $request['upload_limit'];
                            $uploadRequest->save();
                        }

                        return true;
                    } catch (\Exception $exception) {
                        Yii::error('ADD FILE UPLOAD REQUEST');
                        Yii::error($exception->getMessage());

                        return false;
                    }
                }
                return false;

            case 'updateFileUploadRequest':
                if(!empty($actionData)) {
                    try {
                        $requests = PeopleUploadFileRequest::findOne($actionData['id']);
                        $requests->upload_limit = $actionData['upload_limit'];
                        $requests->type = $actionData['type'];
                        $requests->save();
                        return true;
                    } catch (\Exception $exception) {
                        Yii::error('UPDATE FILE UPLOAD REQUEST');
                        Yii::error($exception->getMessage());

                        return false;
                    }
                }
                return false;

            case 'uploadRequestsByVault':
                try {
                    $requests = PeopleUploadFileRequest::find()->where(['dbid' => $actionData])->orderBy(['timestamp' => SORT_DESC])->asArray()->all();
                    if(empty($requests)) {
                        return 'empty';
                    }

                    return $requests;
                } catch (\Exception $exception) {
                    Yii::error('REQUEST UPLOAD REQUESTS BY VAULT');
                    Yii::error($exception->getMessage());

                    return 'empty';
                }

            case 'uploadActiveRequestsByVault':
                try {
                    $requests = PeopleUploadFileRequest::find()->where(['dbid' => $actionData])->andWhere(['<>', 'type', 'complete'])->orderBy(['timestamp' => SORT_DESC])->asArray()->all();
                    if(empty($requests)) {
                        return 'empty';
                    }

                    return $requests;
                } catch (\Exception $exception) {
                    Yii::error('REQUEST UPLOAD REQUESTS BY VAULT');
                    Yii::error($exception->getMessage());

                    return 'empty';
                }

            case 'uploadRequestsByVaultAndType':
                try {

                    $requests = PeopleUploadFileRequest::find()->where(['dbid' => $actionData['id']]);
                    switch($actionData['type']){
                        case 'inactive':
                            $requests = $requests->andWhere(['type' => 'complete']);
                            break;
                        case 'active':
                            $requests = $requests->andWhere(['<>', 'type', 'complete']);
                            break;
                        default:
                            break;
                    }

                    $requests = $requests->orderBy(['timestamp' => SORT_DESC])->asArray()->all();
                    if(empty($requests)) {
                        return 'empty';
                    }

                    return $requests;
                } catch (\Exception $exception) {
                    Yii::error('REQUEST UPLOAD REQUESTS BY VAULT');
                    Yii::error($exception->getMessage());

                    return 'empty';
                }

            case 'filesRequestsByRequest':
                try {
                    $filesRequests = PeopleRequestsFiles::find()->where(['request_id' => $actionData])->asArray()->all();

                    if(empty($filesRequests)) {
                        return  'empty';
                    }

                    return $filesRequests;
                } catch (\Exception $exception) {
                    Yii::error('REQUEST UPLOAD REQUESTS BY VAULT');
                    Yii::error($exception->getMessage());

                    return 'empty';
                }

            case 'deleteUploadRequest':
                try {
                    PeopleUploadFileRequest::find()->where(['id' => $actionData])->one()->delete();

                    return true;
                } catch (\Exception $exception) {
                    Yii::error('DELETE UPLOAD REQUEST BY VAULT');
                    Yii::error($exception->getMessage());

                    return false;
                }

            case 'deleteFromBusiness':
                try {
                    $parsedIds = IdbAccountId::parse($actionData);
                    $businessId = IdbAccountId::generateBusinessDbId(
                        $parsedIds['oid'],
                        $parsedIds['aid'],
                        $parsedIds['dbid']
                    );
                    $businessClient = IdbBankClientBusiness::model($businessId);

                    $businessClient->delete((int)$parsedIds['uid']);
                    return true;

                } catch (\Exception $exception) {
                    Yii::error('DELETE FROM BUSINESS');
                    Yii::error($exception->getMessage());

                    return $exception->getMessage();
                }

            case 'businessNameForUser':
                if (!empty($actionData)) {
                    $data = IdbAccountId::parse($actionData);

                    $businessName = BusinessAccount::findOne(['oid' => $data['oid'], 'aid' => $data['aid']]);
                    $databaseName = BusinessDatabase::findOne(['aid' => $data['aid'], 'dbid' => $data['dbid']]);

                    return json_encode(['name' => $businessName->name, 'database' => $databaseName->name]);
                }

                break;
            case 'businessDataForUser':
                if (!empty($actionData)) {
                    $data = IdbAccountId::parse($actionData);
                    $businessId = IdbAccountId::generateBusinessDbId($data['oid'], $data['aid'], $data['dbid']);
                    $clientModel = IdbBankClientBusiness::model($businessId);
                    $users = $clientModel->get(intval($data['uid']));

                    return $users;
                }

                break;
            case 'businessMetadataForUser':
                if (!empty($actionData)) {
                    $data = IdbAccountId::parse($actionData);
                    $businessId = IdbAccountId::generateBusinessDbId($data['oid'], $data['aid'], $data['dbid']);
                    $clientModel = IdbBankClientBusiness::model($businessId);
                    $users = $clientModel->getAccountMetadata();

                    return $users;
                }

                break;
            case 'organizationEmails':
                if(!empty($actionData)) {
                    try {
                        $account = BusinessAccount::find()->select('aid')->asArray()->where(['oid'=> $actionData['oid']])->one();
                        if(!empty($account)) {
                            $uid = BusinessAccountUser::find()->asArray()->select(['uid'])->where(['aid' => $account['aid']])->one();

                            $model = BusinessUserData::find()
                                                     ->where(
                                                         [
                                                             'uid' => $uid['uid'],
                                                             'key_hash' => BusinessUserData::instantiate()->getKeyHash(
                                                                 $uid['uid'],
                                                                 'email'
                                                             )
                                                         ]
                                                     )->one();

                            if(!empty($model)) {
                                return $model->value;
                            }
                        }



                        return -1;
                    } catch (\Exception $exception) {
                        Yii::error('ORGANIZATION EMAILS');
                        Yii::error($exception->getMessage());

                        return -1;
                    }
                }
                break;
            case 'peopleInfo':
                $peopleInfo = [];
                if (!empty($actionData)) {
                    $accuountid = PeopleConfig::get()->getYii2PeopleAccountId();
                    $idbClient = IdbBankClientPeople::model($accuountid);
                    foreach ($actionData as $userId) {
                        $userId = $userId[0];
                        $userName = ['userId' => $userId];
                        $userData = $idbClient->get($userId);
                        foreach ($userData['dataTypes'] as $itemData) {
                            if ($itemData['attribute'] === 'name') {
                                $userName['name'] = $itemData['value'];
                            } elseif ($itemData['attribute'] === 'surname') {
                                $userName['surname'] = $itemData['value'];
                            }
                        }
                        $peopleInfo[] = $userName;
                    }

                    return $peopleInfo;
                }

                break;
            case 'updateMappedBusiness':
                if (!empty($actionData)) {
                    try {
                        foreach ($actionData as $dataKey => $data) {
                            $parsedIds = IdbAccountId::parse($dataKey);
                            $businessId = IdbAccountId::generateBusinessDbId(
                                $parsedIds['oid'],
                                $parsedIds['aid'],
                                $parsedIds['dbid']
                            );
                            $businessClient = IdbBankClientBusiness::model($businessId);

                            $businessClient->update((int)$parsedIds['uid'], $data);
                        }

                        return true;
                    } catch (\Exception $e) {
                        Yii::error('Update Mapped Businesses Failed');
                        Yii::error($e->getMessage());
                    }
                }

                return false;
            case 'sendEmail':
                if (
                    !empty($actionData)
                    && !empty($actionData['action'])
                    && !empty($actionData['parameters'])
                    && !empty($actionData['to'])
                    && !empty($actionData['title'])
                    && !empty($actionData['iso'])
                    && !empty($actionData['oid'])
                ) {
                    try {
                        EmailTemplate::sendEmailByAction(
                            $actionData['action'],
                            $actionData['parameters'],
                            $actionData['title'],
                            $actionData['to'],
                            $actionData['iso'],
                            $actionData['oid']
                        );

                        return true;
                    } catch (\Exception $e) {
                        Yii::error('Send Email Failed');
                        Yii::error($e->getMessage());
                    }

                }

                return false;
            case 'business2PeopleMessageInfo':
                try {
                    $businessUser = $actionData['Business2PeopleFormModel']['business_user'];
                    $subject = $actionData['Business2PeopleFormModel']['subject'];
                    $message = $actionData['Business2PeopleFormModel']['message'];
                    $message = strip_tags($message);
                    $subject = strip_tags($subject);
                    $expires_at = $actionData['Business2PeopleFormModel']['expires_at'];
                    $peopleUsers = $actionData['people_users'];
                    $messageContent = ['subject' => $subject, 'message' => $message];
                    $messageContent = json_encode($messageContent);

                    foreach ($peopleUsers as $user) {
                        $model = new Business2PeopleMessagesModel();
                        $model->business_user = $businessUser;
                        $model->people_user = $user;
                        $model->expires_at = $expires_at;
                        $model->messagecontent = $messageContent;
                        $model->save();
                    }

                    return true;
                } catch (\Exception $e) {
                    Yii::error('Business 2 people message info');
                    Yii::error($e->getMessage());

                    return false;
                }

                break;

            case 'peopleNotification':
                try {
                    $old = PeopleNotification::find()->where(['uid' => $actionData['uid'], 'type' => 'amber'])->one();
                    if(empty($old)) {
                        $notification = new PeopleNotification();
                        $notification->type = 'amber';
                        $notification->data = json_encode([
                            'title' => Translate::_('idbyii2', 'Review cycle'),
                            'body' => '',
                            'type' => 'reviewCycle',
                            'businessId' => $actionData['businessId'],
                            'peopleId' => $actionData['peopleId'],
                            'metadata' => $actionData['metadata']
                        ]);
                        $notification->status = 1;
                        $notification->uid = $actionData['uid'];
                        $notification->save();
                    } else {
                        Throw new \Exception('There is a notification');
                    }

                    return true;
                } catch(\Exception $e) {
                    Yii::error('PEOPLE NOTIFICATION');
                    Yii::error($e->getMessage());

                    return $e->getMessage();
                }
            case 'deleteBusinessDataForUser':
                if (!empty($actionData)) {
                    try {
                        $saveSuccess = false;
                        $parsedIds = IdbAccountId::parse($actionData['businessId']);
                        $businessId = IdbAccountId::generateBusinessDbId(
                            $parsedIds['oid'],
                            $parsedIds['aid'],
                            $parsedIds['dbid']
                        );
                        $businessClient = IdbBankClientBusiness::model($businessId);

                        if (ArrayHelper::getValue($actionData, 'deleteAll', false)) {
                            if ($businessClient->delete((int)$parsedIds['uid']) !== null) {
                                $saveSuccess = true;
                            }
                        }

                        if ($saveSuccess) {
                            $uid = BusinessAccountUser::find()->asArray()->select('uid')->where(['aid' => $parsedIds['aid']])->one();

                            $notification = new NotificationsForm();
                            $notification->uid = $uid['uid'];
                            $notification->type = 'green';
                            $notification->status = 1;
                            $notification->title = Translate::_(
                                'idbyii2',
                                '{user} are disconnected.',
                                ['user' => $actionData['userId']]
                            );
                            $notification->body = Translate::_(
                                'idbyii2',
                                '{user} are disconnected.',
                                ['user' => $actionData['userId']]
                            );
                            $notification->save();

                            return true;
                        }
                    } catch (\Exception $e) {
                        Yii::error('Delete Business Data For User failed!');
                        Yii::error($e->getMessage());
                    }
                }

                return false;

            case 'notifyBusinessUser':
                try {
                    //TODO: Fix to attach everyone from vault owners
                    $parsedIds = IdbAccountId::parse($actionData['businessId']);
                    $uid = BusinessAccountUser::find()->asArray()->select('uid')->where(['aid' => $parsedIds['aid']])->one();

                    $notification = new NotificationsForm();
                    $notification->uid = $uid['uid'];
                    $notification->type = 'green';
                    $notification->status = 1;
                    $notification->title = $actionData['title'];
                    $notification->body = $actionData['body'];
                    $notification->save();

                    return true;
                } catch (\Exception $e) {
                    Yii::error('Notify Business User failed!');
                    Yii::error($e->getMessage());
                    return false;
                }
                break;

            case 'updateBusinessDataForUser':
                if (!empty($actionData)) {
                    try {
                        $saveSuccess = false;
                        $changes = [];
                        $types = [];
                        $parsedIds = IdbAccountId::parse($actionData['businessId']);
                        $businessId = IdbAccountId::generateBusinessDbId(
                            $parsedIds['oid'],
                            $parsedIds['aid'],
                            $parsedIds['dbid']
                        );
                        $businessClient = IdbBankClientBusiness::model($businessId);

                        if (!ArrayHelper::getValue($actionData, 'deleteAll', false)) {
                            foreach ($actionData['data'] as $key => $data) {
                                $changes[$key] = $data['value'];
                                $types['database'] [] = $key;
                            }
                        } else {
                            $saveSuccess = true;
                        }

                        if (
                            !ArrayHelper::getValue($actionData, 'deleteAll', false)
                            && $businessClient->update((int)$parsedIds['uid'], $changes) !== null
                        ) {
                            $currentData = $businessClient->findById((int)$parsedIds['uid'], $types);
                            if (
                                !empty($currentData) && !empty($currentData['QueryData'])
                                && !empty($currentData['QueryData'][0])
                            ) {
                                $saveSuccess = true;
                                $currentData = $currentData['QueryData'][0];
                                $counter = 0;
                                foreach ($changes as $change) {
                                    Yii::error($change);
                                    Yii::error($currentData[$counter]);
                                    if ($change !== $currentData[$counter]) {
                                        $saveSuccess = false;
                                        break;
                                    }
                                    $counter++;
                                }
                            }
                        }

                        if ($saveSuccess) {
                            //TODO: Fix to attach everyone from vault owners
                            $uid = BusinessAccountUser::find()->asArray()->select('uid')->where(['aid' => $parsedIds['aid']])->one();

                            $notification = new NotificationsForm();
                            $notification->uid = $uid['uid'];
                            $notification->type = 'green';
                            $notification->status = 1;
                            $notification->title = Translate::_(
                                'idbyii2',
                                '{user} changed their data.',
                                ['user' => $actionData['userId']]
                            );
                            $notification->body = Translate::_(
                                'idbyii2',
                                '{user} changed their data. You can review this change in the review change requests option.',
                                ['user' => $actionData['userId']]
                            );
                            $notification->save();

                            $id = $businessClient->addAccountCRbyUserId(
                                (int)$parsedIds['uid'],
                                json_encode($actionData),
                                'toRevert',
                                '###PEOPLE+++'
                            );

                            return ArrayHelper::getValue($id, 'QueryData.0.0', false);
                        }
                    } catch (\Exception $e) {
                        Yii::error('Update Business Data For User failed!');
                        Yii::error($e->getMessage());
                    }
                }

                return false;

            case 'businessPrivacyDetails':
                if(!empty($actionData)) {
                    try {
                        $parsedIds = IdbAccountId::parse($actionData);
                        $uid = BusinessAccountUser::find()->select(['uid'])->where(['aid' => $parsedIds['aid']])->asArray()->one()['uid'];
                        $userData = BusinessUserData::getUserDataByKeys($uid,IdbBusinessSignUpDPOForm::dpoDetailsAttributes());
                        $data = [];
                        foreach($userData as $uData) {
                            $data[$uData->key] = $uData->value;
                        }

                        return $data;

                    } catch(\Exception $e) {
                        return $e->getMessage();
                    }
                }

                return false;

            case 'businessGPDR':
                if(!empty($actionData)) {
                    try {
                        $data = IdbAccountId::parse($actionData);
                        $businessId = IdbAccountId::generateBusinessDbId($data['oid'], $data['aid'], $data['dbid']);
                        $clientModel = IdbBankClientBusiness::model($businessId);
                        $metadata = $clientModel->getAccountMetadata();

                        $gdpr = Metadata::getGDPRWithProcessors(json_decode($metadata['Metadata'], true));

                        return $gdpr;
                    } catch(\Exception $e) {
                        return $e->getMessage();
                    }
                }

                return false;
            case 'deleteAllBusinessDataForUser':
                if (!empty($actionData)) {
                    $data = IdbAccountId::parse($actionData);
                    $businessId = IdbAccountId::generateBusinessDbId($data['oid'], $data['aid'], $data['dbid']);
                    $clientModel = IdbBankClientBusiness::model($businessId);

                    $actionData['data'] = "Delete all";

                    $dataCr = $clientModel->getAccountCRbyUserId(intval($data['uid']))['QueryData'];
                    if (empty($dataCr)) {
                        return $clientModel->addAccountCRbyUserId(
                            intval($data['uid']),
                            json_encode($actionData['data']),
                            FileHelper::STATUS_ADDED
                        );
                    } else {
                        return $clientModel->updateAccountCRbyUserId(
                            intval($data['uid']),
                            json_encode($actionData['data']),
                            FileHelper::STATUS_ADDED
                        );
                    }
                }

                break;
        }

        return 'Not implemented';
    }

    /**
     * @param $portalApi
     * @param $headers
     * @param $request
     *
     * @return array|false|string|null
     * @throws \yii\base\ExitException
     * @throws \yii\web\NotFoundHttpException
     * @throws \Exception
     */
    public static function execute($portalApi, $headers, $request)
    {
        $requestData = null;
        try {
            if (
                $portalApi
                && !empty($headers['Authorization'])
            ) {
                list($jwtString) = sscanf($headers['Authorization'], 'Bearer %s');
                if (!empty($jwtString)) {
                    $requestData = $portalApi->decodeRequest($jwtString, $request);
                }
            }
            if (
                !empty($requestData)
                && !empty($requestData['iat'])
                && !empty($requestData['id'])
                && !empty($requestData['data'])
            ) {
                $respond = ['status' => 'success'];
                $respond['data'] = self::executeAction($requestData['id'], $requestData['data']);
                $respond = json_encode($respond);

                return $respond;
            } else {
                throw new NotFoundHttpException();
                Yii::$app->end();
            }

        } catch (Exception $error) {
            throw new NotFoundHttpException();
            Yii::$app->end();
        }

        return null;
    }
}

################################################################################
#                                End of file                                   #
################################################################################
