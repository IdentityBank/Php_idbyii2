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

use idbyii2\components\IdbRabbitMq;
use idbyii2\components\Messenger;
use idbyii2\components\PortalApi;
use idbyii2\models\db\BusinessAccount;
use idbyii2\models\form\IdbModel;
use idbyii2\models\form\NotificationsForm;
use idbyii2\models\idb\IdbBankClientBusiness;
use Yii;
use yii\base\Exception;
use yii\web\NotFoundHttpException;

################################################################################
# Class(es)                                                                    #
################################################################################

/**
 * Class PeopleAccessHelper
 *
 * @package idbyii2\helpers
 */
class PeopleAccessHelper extends IdbModel
{

    public $email;
    public $mobile;
    public $name;
    public $surname;
    public $dbUserId;
    public $language;
    public $wrongData = false;
    public $bothValid = true;

    const channelName = "sendInvitationsIDB";
    const STATUS_ADDED_TO_QUEUE = 'added_to_queue';
    const STATUS_INV_SENT = 'inv_sent';
    const STATUS_ACCOUNT_CREATED = 'account_created';

    /**
     * @param array $data
     *
     * @throws \Exception
     */
    private static function addTaskToQueue($data)
    {
        if (!empty($data)) {
            $data = json_encode($data);
            $idbRabbitMq = IdbRabbitMq::get();
            $idbRabbitMq->produce(self::channelName, $data);
        } else {
            $msg = 'Invitation IDB - queue data cannot be empty!';
            echo $msg . PHP_EOL;
            throw new \Exception($msg);
        }
    }

    /**
     * @return array
     */
    public function rules()
    {
        return [
            [
                [
                    'name',
                    'surname',
                    'email',
                    'mobile'
                ],
                'string',
                'max' => 255
            ],
            [
                [
                    'dbUserId'
                ],
                'required'
            ],
            [
                [
                    'email',
                ],
                'required'
            ],
            [
                [
                    'mobile',
                ],
                'required',
                'when' => function ($model) {
                    return $model->bothValid == true;
                }
            ],
            [
                'email',
                'email'
            ],
            [
                'mobile',
                'string',
                'length' => [3, 20],
                'when' => function ($model) {
                    return $model->bothValid == true;
                }
            ],
            [
                'mobile',
                'match',
                'pattern' => '/^\+[1-9]{1}\d{3,14}$/s',
                'message' => Translate::_(
                    'idbyii2',
                    'The mobile number must be provided with the country code and starts with + character.'
                ),
                'when' => function ($model) {
                    return $model->bothValid == true;
                }
            ],
        ];
    }

    /**
     * @param $metadata
     *
     * @return array
     */
    public static function prepareDataTypes($metadata)
    {
        $model = ['dbUserId'];
        $dataTypes = ['database' => []];
        foreach ($metadata['PeopleAccessMap'] as $column => $columnUuid) {
            $dataTypes['database'][] = $columnUuid;
            switch ($column) {
                case 'email_no':
                    {
                        $model[] = 'email';
                    }
                    break;
                case 'mobile_no':
                    {
                        $model[] = 'mobile';
                    }
                    break;
                case 'name_no':
                    {
                        $model[] = 'name';
                    }
                    break;
                case 'surname_no':
                    {
                        $model[] = 'surname';
                    }
                    break;
            }
        }

        return ['model' => $model, 'dataTypes' => $dataTypes];
    }

    /**
     * @param $data
     *
     * @throws \Exception
     */
    public static function executeSendInvitations($data)
    {
        self::addTaskToQueue(["data" => $data]);
    }

    /**
     * @param $data
     *
     * @throws \Exception
     */
    public static function executeTaskFromSendInvitationsQueue($data)
    {
        $data = json_decode($data, true);
        echo("OK we have new INVITATION task with business id: [" . $data['data']['businessId'] . "] ..." . PHP_EOL);

        if (json_last_error() === JSON_ERROR_NONE) {
            if (empty($data['data']['businessId'])) {
                $msg = 'INVITATION IDB - missing business id!';
                echo $msg . PHP_EOL;
                throw new \Exception($msg);
            } else {
                echo "Execute INVITATION task for business id: [" . $data['data']['businessId'] . "]" . PHP_EOL;
                $clientModel = IdbBankClientBusiness::model($data['data']['businessId']);
                try {
                    if (array_key_exists('first', $data['data']) && array_key_exists('last', $data['data'])) {
                        try {
                            $requests = self::prepareDataFromImport($data['data'], $clientModel);
                        } catch (\Exception $e) {
                            var_dump($e->getMessage()) . PHP_EOL;
                        }

                    } else {
                        $requests = self::prepareData($data['data']);
                    }

                    $emailList = '';

                    if (empty($requests)) {
                        $msg = 'INVITATION IDB - requests are empty!';
                        echo $msg . PHP_EOL;
                        throw new \Exception($msg);
                    }

                    foreach ($requests as $request) {
                        $response = self::initSignup($request);
                        $url = $response['response'];
                        $errors = $response['errors'];

                        if (!empty($url) && empty($errors)) {

                            try {
                                self::sendInitRegistrationEmail(
                                    $request['email'] ?? '',
                                    $request['businessAccountName'] ?? '',
                                    $request['name'] ?? '',
                                    $request['surname'] ?? '',
                                    $url,
                                    $request['language'] ?? null,
                                    '@app/views/emails/startRegister'
                                );
                                $emailList .= $request['email'] . PHP_EOL;

                                $toSend = [
                                    'businessId' => $data['data']['businessId']
                                ];
                                $clientModel = IdbBankClientBusiness::model($data['data']['businessId']);
                                $response = $clientModel->getAccountSTbyUserId($request['dbUserId']);
                                if (is_null($response)) {
                                    $response = $clientModel->addAccountSTbyUserId(
                                        intval($request['dbUserId']),
                                        json_encode($toSend),
                                        self::STATUS_INV_SENT
                                    );
                                } else {
                                    $response = $clientModel->updateAccountSTbyUserId(
                                        intval($request['dbUserId']),
                                        json_encode($toSend),
                                        self::STATUS_INV_SENT
                                    );
                                }
                                var_dump($response);

                            } catch (\Exception $e) {
                                var_dump(
                                    'FILE: ' . $e->getFile() . 'LINE: ' . $e->getLine() . 'MESSAGE: ' . $e->getMessage()
                                );
                            }

                            $msg = 'INVITATION IDB - complete send invitation to people with email ['
                                . $request['email'] . '].';
                            echo $msg . PHP_EOL;
                        }
                    }
                } catch (\Exception $e) {
                    var_dump('FILE: ' . $e->getFile() . 'LINE: ' . $e->getLine() . 'MESSAGE: ' . $e->getMessage())
                    . PHP_EOL;
                }

                try {
                    $notification = new NotificationsForm();
                    $notification->uid = $data['data']['uid'] ?? $data['uid'];
                    $notification->type = 'green';
                    $notification->status = 1;
                    $notification->title = Translate::_('idbyii2', 'Invitations have been sent.');
                    $notification->body = Translate::_(
                        'idbyii2',
                        'Invitation emails have been sent to the following people: '
                    );
                    $notification->body .= $emailList;
                    $notification->save();
                } catch (\Exception $e) {
                    var_dump('FILE: ' . $e->getFile() . 'LINE: ' . $e->getLine() . 'MESSAGE: ' . $e->getMessage());
                }
            }
        } else {
            $msg = 'INVITATION IDB - task data are empty or not complete!';
            echo $msg . PHP_EOL;
            throw new \Exception($msg);
        }
    }

    /**
     * @param $data
     *
     * @return array
     * @throws NotFoundHttpException
     */
    public static function initSignup($data)
    {
        $response = null;
        $errors = [];
        if (!empty($data)) {
            try {
                $portalPeopleApi = PortalApi::getPeopleApi();
                $response = $portalPeopleApi->requestPeopleSignUp($data);
            } catch (\Exception $error) {
                $errors['Exception'] = $error->getMessage();
                var_dump($errors['Exception']);
            }
        }
        if (empty($response)) {
            $errors['Id'] = [Translate::_('idbyii2', 'Missing signup data from people portal.')];
            throw new NotFoundHttpException();
        }

        return ['response' => $response, 'errors' => $errors];
    }


    /**
     * @param $model
     *
     * @return array
     */
    public static function prepareData($model)
    {
        $data = IdbAccountId::parse($model['businessId']);
        $peopleData = json_decode($model['data'], true);
        $businessName = BusinessAccount::findOne(['oid' => $data['oid'], 'aid' => $data['aid']]);
        $request = [];

        foreach ($peopleData as $people) {
            $request[] = [
                'dbUserId' => $people['dbId'],
                'email' => $people['email'],
                'mobile' => $people['mobile'],
                'name' => $people['name'],
                'surname' => $people['surname'],
                'language' => $people['language'] ?? null,
                'businessOrgazniationId' => $data['oid'],
                'businessAccountid' => $data['aid'],
                'businessDatabaseId' => $data['dbid'],
                'businessUserId' => $people['dbId'],
                'businessDatabaseUserId' => $model['businessId'] . '.uid.' . $people['dbId'],
                'businessAccountName' => $businessName->name
            ];
        }

        return $request;
    }

    /**
     * @param string      $email
     * @param string      $business
     * @param string      $firstname
     * @param string      $lastname
     * @param string      $url
     * @param string|null $language
     * @param string|null $template
     */
    public static function sendInitRegistrationEmail(
        string $email,
        string $business,
        string $firstname,
        string $lastname,
        string $url,
        string $language = null,
        string $template = null
    ) {
        EmailTemplate::sendEmailByAction(
            'PEOPLE_START_REGISTER',
            [
                'business' => $business,
                'firstname' => $firstname,
                'lastname' => $lastname,
                'businessName' => $business,
                'firstName' => $firstname,
                'lastName' => $lastname,
                'invitation_link' => $url,
            ],
            Translate::_(
                'idbyii2',
                'Good news: Check how {business} protects your identity',
                ['business' => $business],
                $language
            ),
            $email,
            $language,
            Yii::$app->user->identity->oid ?? null
        );
    }

    /**
     * @param string $mobile
     * @param string $url
     */
    public static function sendInitRegistrationMobile(string $mobile, string $url)
    {
        $messenger = Messenger::get();

        $messenger->sms(
            $mobile,
            Translate::_(
                'idbyii2',
                "Start registration here: {url}.",
                [
                    'url' => $url
                ]
            )
        );
    }

    /**
     * @param $data
     * @param $clientModel
     *
     * @return array
     * @throws \Exception
     */
    public static function prepareDataFromImport($data, $clientModel)
    {
        echo "Start prepare data from Import.." . PHP_EOL;

        if (!array_key_exists('businessId', $data)) {
            $msg = 'INVITATION IDB - missing business id!';
            echo $msg . PHP_EOL;
            throw new \Exception($msg);
        }

        var_dump($data);

        $request = [];
        $response = null;
        $model = new PeopleAccessHelper();
        $businessData = IdbAccountId::parse($data['businessId']);
        $businessName = BusinessAccount::findOne(['oid' => $businessData['oid'], 'aid' => $businessData['aid']]);
        $metadata = $clientModel->getAccountMetadata();
        echo "Get metadata.." . PHP_EOL;

        if (array_key_exists('Metadata', $metadata)) {
            $metadata = json_decode($metadata['Metadata'], true);
        } else {
            throw new \Exception('Metadata are empty');
        }

        $query = self::prepareQuery($metadata, $data);

        $clientModel->setPageSize($query['pageSize']);

        echo "Get people.." . PHP_EOL;
        $response = $clientModel->findCountAll(
            $query['query']['FilterExpression'],
            $query['query']['ExpressionAttributeNames'],
            $query['query']['ExpressionAttributeValues'],
            ["database" => $query['columns']]
        );

        if (is_array($response) && !empty($response['QueryData'])) {
            foreach ($response['QueryData'] as $gueryData) {

                $model->dbUserId = $gueryData[0];
                $model->email = $gueryData[1];
                if (
                    !empty($gueryData[2])
                    && ($gueryData[2][0] === '+'
                        || ($gueryData[2][0] === '0'
                            && $gueryData[2][1] === '0'))
                ) {
                    $model->mobile = $gueryData[2];
                } else {
                    if ($data['phone_code'] !== '0') {
                        $model->mobile = $data['phone_code'] . $gueryData[2];
                    } else {
                        $model->mobile = $gueryData[2];
                    }
                }
                $model->name = $gueryData[3];
                $model->surname = $gueryData[4];
                $model->language = $data['language'];
                $model->bothValid = $data['both_valid'];

                if ($model->validate()) {
                    $request[] = [
                        'dbUserId' => $model->dbUserId,
                        'email' => $model->email ?? '',
                        'mobile' => $model->mobile ?? '',
                        'name' => $model->name,
                        'surname' => $model->surname,
                        'language' => $model->language ?? null,
                        'businessOrgazniationId' => $businessData['oid'],
                        'businessAccountid' => $businessData['aid'],
                        'businessDatabaseId' => $businessData['dbid'],
                        'businessUserId' => $model->dbUserId,
                        'businessDatabaseUserId' => $data['businessId'] . '.uid.' . $model->dbUserId,
                        'businessAccountName' => $businessName->name
                    ];
                }
            }
        } else {
            throw new \Exception('Error in API response');
        }

        return $request;
    }

    /**
     * Function to prepare query for search in people data
     *
     * @param $metadata
     * @param $data
     *
     * @return array
     * @throws \Exception
     */
    public static function prepareQuery($metadata, $data)
    {
        echo "Peopare query for search.." . PHP_EOL;
        if (!Metadata::hasPeopleAccessMap($metadata)) {
            $msg = 'INVITATION IDB - missing people access column mapping!';
            echo $msg . PHP_EOL;
            throw new \Exception($msg);
        }

        $columns = [];
        array_push($columns, 'id');
        array_push($columns, $metadata['PeopleAccessMap']['email_no']);
        array_push($columns, $metadata['PeopleAccessMap']['mobile_no']);
        array_push($columns, $metadata['PeopleAccessMap']['name_no']);
        array_push($columns, $metadata['PeopleAccessMap']['surname_no']);

        $query = [
            "FilterExpression" => [
                "o" => 'AND',
                'l' => [
                    "o" => ">=",
                    "l" => "#first",
                    "r" => ":first"
                ],
                'r' => [
                    "o" => "<=",
                    "l" => "#last",
                    "r" => ":last"
                ]
            ],
            "ExpressionAttributeNames" => ["#first" => "id", '#last' => "id"],
            "ExpressionAttributeValues" => [":first" => $data['first'], ":last" => $data['last']],
        ];
        $pageSize = intval((intval($data['last']) - intval($data['first'])) + 1);

        return ['columns' => $columns, 'pageSize' => $pageSize, 'query' => $query];
    }

    /**
     * @param $clientModel
     * @param $metadata
     * @param $id
     *
     * @return array
     * @throws Exception
     */
    public static function prepareDataForPeopleAccess($clientModel, $metadata, $id)
    {
        $data = PeopleAccessHelper::prepareDataTypes($metadata);
        $dataTypes = $data['dataTypes'];

        foreach ($dataTypes['database'] as $key => $value) {
            if (strlen($value) < 36) {
                unset($dataTypes['database'][$key]);
            }
        }

        $user = $clientModel->get(intval($id), $dataTypes);

        $return = [];

        if ($user == 457) {
            throw new Exception('Connection with database is not possible. Please contact with administrator');
        }
        if (!array_key_exists('QueryData', $user) || !array_key_exists(0, $user['QueryData'])) {
            $return['error'] = Translate::_('idbyii2', 'User data not found.');
        }

        if (!Metadata::hasPeopleAccessMap($metadata)) {
            $return['error'] = Translate::_('idbyii2', 'Vault data not found.');

        }

        if (empty($return['error'])) {
            $user = $user['QueryData'][0];
            $model = new PeopleAccessHelper();
            $model->dbUserId = $id;
            $columns = [];

            foreach ($dataTypes['database'] as $index => $columnUuid) {
                if ($metadata['PeopleAccessMap']['name_no'] === $columnUuid) {
                    $model->name = $user[$index];
                    $columns['name'] = $columnUuid;
                }
                if ($metadata['PeopleAccessMap']['surname_no'] === $columnUuid) {
                    $model->surname = $user[$index];
                    $columns['surname'] = $columnUuid;
                }
                if ($metadata['PeopleAccessMap']['email_no'] === $columnUuid) {
                    $model->email = $user[$index];
                    $columns['email'] = $columnUuid;
                }
                if ($metadata['PeopleAccessMap']['mobile_no'] === $columnUuid) {
                    $model->mobile = $user[$index];
                    $columns['mobile'] = $columnUuid;
                }

                $return['model'] = $model;
                $return['columns'] = $columns;
            }
        }

        return $return;
    }
}

################################################################################
#                                End of file                                   #
################################################################################
