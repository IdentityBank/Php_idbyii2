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

use Exception;
use xmz\simplelog\SNLog as Log;
use function xmz\simplelog\registerLogger;

################################################################################
# Class(es)                                                                    #
################################################################################

/**
 * Class IdbAccountId
 *
 * @package idbyii2\helpers
 */
class IdbAccountId
{

    const REGEX_UUID = "/[0-9a-fA-F]{8}\-[0-9a-fA-F]{4}\-[0-9a-fA-F]{4}\-[0-9a-fA-F]{4}\-[0-9a-fA-F]{12}/i";
    const REGEX_PEOPLE_ACCOUNT_ID = "/^([a-zA-Z0-9-_\S]+)$/";

    /**
     * @var string
     */
    private static $formatBusinessAccountId = "oid.{oid}.aid.{aid}";

    /**
     * @var string
     */
    private static $formatBusinessDbId = "oid.{oid}.aid.{aid}.dbid.{dbid}";

    /**
     * @var string
     */
    private static $formatBusinessDbUserId = "oid.{oid}.aid.{aid}.dbid.{dbid}.uid.{uid}";

    /**
     * @var string
     */
    private static $formatPeopleAccountId = "{idbid}";

    /**
     * @var string
     */
    private static $formatPeopleId = "idbid.{idbid}.pid.{pid}";

    /**
     * @var string
     */
    private static $formatForAssignOrganization = "oid.{oid}.uid.{uid}";
    /**
     * @var string
     */
    private static $formatForAssignAccount = "oid.{oid}.aid.{aid}.uid.{uid}";

    /**
     * @var string
     */
    private static $formatForAssignDatabase = "oid.{oid}.aid.{aid}.dbid.{dbid}.uid.{uid}";

    /**
     * @param $orgazniationId
     * @param $userId
     *
     * @return mixed|string
     * @throws \Exception
     */
    public static function generateAssignOrganization(
        $orgazniationId,
        $userId
    ) {
        return self::generateId(
            self::$formatForAssignOrganization,
            [
                'oid' => $orgazniationId,
                'uid' => $userId
            ]
        );
    }

    /**
     * @param $orgazniationId
     * @param $accountId
     * @param $userId
     *
     * @return mixed|string
     * @throws \Exception
     */
    public static function generateAssignAccount(
        $orgazniationId,
        $accountId,
        $userId
    ) {
        return self::generateId(
            self::$formatForAssignAccount,
            [
                'oid' => $orgazniationId,
                'aid' => $accountId,
                'uid' => $userId
            ]
        );
    }

    /**
     * @param $orgazniationId
     * @param $accountId
     * @param $databaseId
     * @param $userId
     *
     * @return mixed|string
     * @throws \Exception
     */
    public static function generateAssignDatabase(
        $orgazniationId,
        $accountId,
        $databaseId,
        $userId
    ) {
        return self::generateId(
            self::$formatForAssignDatabase,
            [
                'oid' => $orgazniationId,
                'aid' => $accountId,
                'dbid' => $databaseId,
                'uid' => $userId
            ]
        );
    }

    /**
     * @param $format
     * @param $attributes
     *
     * @return mixed|string
     * @throws \Exception
     */
    private static function generateId($format, $attributes)
    {
        $id = StringTemplate::render($format, $attributes);
        if ($format === self::$formatPeopleAccountId) {
            $format = self::REGEX_PEOPLE_ACCOUNT_ID;
        }
        self::validate($id, $format);

        return $id;
    }

    /**
     * @param $idData
     *
     * @return mixed|string
     * @throws \Exception
     */
    public static function generateBusinessAccountIdFromData(
        $idData
    ) {
        return self::generateId(
            self::$formatBusinessAccountId,
            $idData
        );
    }

    /**
     * @param $businessOrgazniationId
     * @param $businessAccountid
     *
     * @return mixed|string
     * @throws \Exception
     */
    public static function generateBusinessAccountId(
        $businessOrgazniationId,
        $businessAccountid
    ) {
        return self::generateId(
            self::$formatBusinessAccountId,
            [
                'oid' => $businessOrgazniationId,
                'aid' => $businessAccountid
            ]
        );
    }

    /**
     * @param $idData
     *
     * @return mixed|string
     * @throws \Exception
     */
    public static function generateBusinessDbIdFromData(
        $idData
    ) {
        return self::generateId(
            self::$formatBusinessDbId,
            $idData
        );
    }

    /**
     * @param $businessOrgazniationId
     * @param $businessAccountid
     * @param $databaseId
     *
     * @return mixed|string
     * @throws \Exception
     */
    public static function generateBusinessDbId(
        $businessOrgazniationId,
        $businessAccountid,
        $databaseId
    ) {
        return self::generateId(
            self::$formatBusinessDbId,
            [
                'oid' => $businessOrgazniationId,
                'aid' => $businessAccountid,
                'dbid' => $databaseId
            ]
        );
    }

    /**
     * @param $idData
     *
     * @return mixed|string
     * @throws \Exception
     */
    public static function generateBusinessDbUserIdFromData(
        $idData
    ) {
        return self::generateId(
            self::$formatBusinessDbUserId,
            $idData
        );
    }

    /**
     * @param $businessOrgazniationId
     * @param $businessAccountid
     * @param $databaseId
     * @param $databaseUserId
     *
     * @return mixed|string
     * @throws \Exception
     */
    public static function generateBusinessDbUserId(
        $businessOrgazniationId,
        $businessAccountid,
        $databaseId,
        $databaseUserId
    ) {
        return self::generateId(
            self::$formatBusinessDbUserId,
            [
                'oid' => $businessOrgazniationId,
                'aid' => $businessAccountid,
                'dbid' => $databaseId,
                'uid' => $databaseUserId
            ]
        );
    }

    /**
     * @param $idData
     *
     * @return mixed|string
     * @throws \Exception
     */
    public static function generatePeopleUserIdFromData(
        $idData
    ) {
        return self::generateId(
            self::$formatPeopleId,
            $idData
        );
    }

    /**
     * @param $peopleDatabaseId
     * @param $peopleUserId
     *
     * @return mixed|string
     * @throws \Exception
     */
    public static function generatePeopleUserId(
        $peopleDatabaseId,
        $peopleUserId
    ) {
        return self::generateId(
            self::$formatPeopleId,
            [
                'idbid' => $peopleDatabaseId,
                'pid' => $peopleUserId
            ]
        );
    }

    /**
     * @param $id
     *
     * @return array
     */
    public static function parse(
        $id
    ) {
        $idData = [];
        try {
            if (!empty($id)) {
                do {
                    $pos = strpos($id, '.', 1);
                    if ($pos) {
                        $key = substr($id, 0, $pos++);
                        $id = substr($id, $pos);
                        $pos = strpos($id, '.', 1);
                        if ($pos) {
                            $value = substr($id, 0, $pos++);
                            $id = substr($id, $pos);
                        } else {
                            $value = $id;
                            $id = null;
                        }
                        $idData[$key] = $value;
                    } else {
                        $id = null;
                    }
                } while (!empty($id));
            }
        } catch (Exception $e) {
            $idData = [];
        }

        return $idData;
    }

    /**
     * @param $id
     * @param $required
     *
     * @throws \Exception
     */
    private static function validate(
        $id,
        $required
    ) {
        self::logValidation(
            [
                'id' => $id,
                'required' => $required
            ]
        );
        if (!empty($id) && !empty($required)) {
            $idData = self::parse($id);
            $requiredData = self::parse($required);
            self::logValidation(
                [
                    'id' => $id,
                    'required' => $required,
                    'idData' => $idData,
                    'requiredData' => $requiredData
                ]
            );
            if (is_array($requiredData) and !empty($requiredData)) {
                $noError = true;
                foreach ($requiredData as $key => $value) {
                    if (empty($idData[$key]) || $idData[$key] === $value) {
                        $noError = false;
                        break;
                    }
                }
                if ($noError) {
                    return;
                }
                self::logValidation(
                    [
                        'id' => $id,
                        'required' => $required,
                        'idData' => $idData,
                        'requiredData' => $requiredData,
                        'Error' => 'Failed validation'
                    ]
                );
            } elseif (preg_match($required, $id) == 1) {
                return;
            } else {
                self::logValidation(
                    [
                        'id' => $id,
                        'required' => $required,
                        'idData' => $idData,
                        'requiredData' => $requiredData,
                        'Error' => 'Failed REGEX validation'
                    ]
                );
            }
        }
        $exceptionMessage = 'IDB ID Failed validation!';
        self::logValidation(
            [
                'id' => $id,
                'required' => $required,
                'Exception' => $exceptionMessage
            ]
        );
        throw new Exception($exceptionMessage);
    }

    /**
     * @param      $id
     * @param      $service
     * @param null $action
     * @param null $validateAttributes
     *
     * @throws \Exception
     */
    public static function validateServiceIdbId(
        $id,
        $service,
        $action = null,
        $validateAttributes = null
    ) {

        self::logValidation(
            [
                'id' => $id,
                'service' => $service,
                'action' => $action,
                'validateAttributes' => $validateAttributes
            ]
        );

        $required = null;
        switch ($service) {
            case 'business':
                if ($action) {
                    switch ($action) {
                        case 'action':
                            $required = self::$formatBusinessAccountId;
                            self::validate($id, $required);

                            break;
                        case 'findBase':
                        case 'findCRBase':
                        case 'countAllCRBase':
                        case 'findCountAllBase':
                        case 'putMultipleBase':
                        case 'countBase':
                        case 'countAllBase':
                        case 'deleteBase':
                        case 'getBase':
                        case 'putBase':
                        case 'updateBase':
                        case 'createAccountBase':
                        case 'deleteAccountBase':
                        case 'recreateAccountBase':
                        case 'updateDataTypesBase':
                        case 'deleteAccountBase':
                        case 'deleteRelationBusiness2PeopleBase':
                        case 'getAccountMetadata':
                        case 'setAccountMetadata':
                        case 'deleteAccountMetadata':
                        case 'createAccountMetadata':
                        case 'recreateAccountPseudonymisation':
                        case 'updatePseudonymisationBase':
                        case 'createAccountCRBase':
                        case 'deleteAccountCRBase':
                        case 'recreateAccountCRBase':
                        case 'getAllAccountCRsBase':
                        case 'getAllCRsBase':
                        case 'getAccountCRbyUserIdBase':
                        case 'getAccountCRbyStatusBase':
                        case 'deleteAccountCRbyUserIdBase':
                        case 'addAccountCRbyUserIdBase':
                        case 'updateAccountCRbyUserIdBase':
                        case 'createAccountSTBase':
                        case 'recreateAccountSTBase':
                        case 'deleteAccountSTBase':
                        case 'findCountAllEventsToCacheBase':
                        case 'getAllAccountSTsBase':
                        case 'getAllSTsBase':
                        case 'getAccountSTbyUserIdBase':
                        case 'deleteAccountEventsBase':
                        case 'addAccountEventBase':
                        case 'putPseudonymisationBase':
                        case 'createAccountEventsBase':
                        case 'deleteAccountEventBase':
                        case 'getAccountSTbyStatusBase':
                        case 'deleteAccountSTbyUserIdBase':
                        case 'addAccountSTbyUserIdBase':
                        case 'updateAccountSTbyUserIdBase':
                            $required = self::$formatBusinessDbId;
                            self::validate($id, $required);

                            break;
                        case 'find':
                        case 'findCR':
                        case 'countAllCR':
                        case 'findCountAll':
                        case 'putMultiple':
                        case 'count':
                        case 'deleteRelationBusiness2People':
                        case 'countAll':
                        case 'delete':
                        case 'get':
                        case 'put':
                        case 'update':
                        case 'createAccount':
                        case 'deleteAccount':
                        case 'recreateAccount':
                        case 'deleteAccountEvents':
                        case 'addAccountEvent':
                        case 'createAccountEvents':
                        case 'findCountAllEventsToCache':
                        case 'deleteAccountEvent':
                        case 'updateDataTypes':
                        case 'putPseudonymisation':
                        case 'updatePseudonymisation':
                        case 'deleteAccount':
                        case 'createAccountCR':
                        case 'deleteAccountCR':
                        case 'recreateAccountCR':
                        case 'getAllAccountCRs':
                        case 'getAllCRs':
                        case 'getAccountCRbyUserId':
                        case 'getAccountCRbyStatus':
                        case 'deleteAccountCRbyUserId':
                        case 'addAccountCRbyUserId':
                        case 'updateAccountCRbyUserId':
                        case 'createAccountST':
                        case 'recreateAccountST':
                        case 'deleteAccountST':
                        case 'getAllAccountSTs':
                        case 'getAllSTs':
                        case 'getAccountSTbyUserId':
                        case 'getAccountSTbyStatus':
                        case 'deleteAccountSTbyUserId':
                        case 'addAccountSTbyUserId':
                        case 'updateAccountSTbyUserId':
                            $required = self::REGEX_UUID;
                            self::validate($id, $required);

                            break;
                        case 'action':
                            $required = self::$formatBusinessDbUserId;
                            self::validate($id, $required);

                            break;
                    }
                } else {
                    $required = self::$formatBusinessAccountId;
                    self::validate($id, $required);
                }

                break;
            case 'people':
                if ($action) {
                    switch ($action) {
                        case 'get':
                        case 'put':
                        case 'delete':
                        case 'update':
                            $required = self::$formatPeopleId;
                            self::validate($validateAttributes['idbId'], $required);
                            $required = self::REGEX_PEOPLE_ACCOUNT_ID;
                            self::validate($id, $required);

                            break;
                        case 'getAccountMetadata':
                        case 'setAccountMetadata':
                        case 'deleteAccountMetadata':
                        case 'createAccountMetadata':
                            $required = self::REGEX_PEOPLE_ACCOUNT_ID;
                            self::validate($id, $required);

                            break;
                    }
                } else {
                    $required = self::REGEX_PEOPLE_ACCOUNT_ID;
                    self::validate($id, $required);
                }

                break;
            case 'relation':
                switch ($action) {
                    case 'setRelationBusiness2People':
                    case 'deleteRelationBusiness2People':
                    case 'deleteRelationsForBusiness':
                    case 'deleteRelationsForPerson':
                    case 'checkRelationBusiness2People':
                        $required = self::$formatBusinessAccountId;
                        if (!empty($validateAttributes['businessId'])) {
                            self::validate($validateAttributes['businessId'], $required);
                        }
                        $required = self::$formatPeopleId;
                        if (!empty($validateAttributes['peopleId'])) {
                            self::validate($validateAttributes['peopleId'], $required);
                        }

                        break;
                    case 'getRelatedPeoples':
                    case 'getRelatedPeoplesBusinessId':
                        $required = self::$formatBusinessAccountId;
                        self::validate($validateAttributes['businessId'], $required);

                        break;
                    case 'getRelatedBusinesses':
                        $required = self::$formatPeopleId;
                        self::validate($validateAttributes['peopleId'], $required);

                        break;
                }

                break;
        }
        if (empty($required)) {
            $exceptionMessage = 'IDB ID validation is not correct!';
            self::logValidation(
                [
                    'id' => $id,
                    'service' => $service,
                    'action' => $action,
                    'validateAttributes' => $validateAttributes,
                    'Exception' => $exceptionMessage
                ]
            );
            throw new Exception($exceptionMessage);
        }
    }

    private static function logValidation($arguments)
    {
        $logName = "p57b.idb_account_validation";
        $logPath = "/var/log/p57b/$logName.log";
        registerLogger($logName, $logPath);

        $argumentsString = '';
        if (!empty($arguments) && is_array($arguments)) {
            foreach ($arguments as $argumentKey => $argumentValue) {
                if (is_array($argumentValue)) {
                    $argumentValue = json_encode($argumentValue);
                }
                $argumentsString .= "[$argumentKey: $argumentValue]";
            }
        }

        $pid = getmypid();
        Log::debug(
            $logName,
            "$pid - " .
            $argumentsString
        );
    }
}

################################################################################
#                                End of file                                   #
################################################################################
