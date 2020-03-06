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

use Exception;
use idbyii2\helpers\IdbSecurity;
use idbyii2\helpers\Localization;
use Yii;
use yii\web\NotFoundHttpException;

################################################################################
# Class(es)                                                                    #
################################################################################

/**
 * Class IdbPortalApi
 *
 * @package idbyii2\components
 */
class IdbPortalApiBase
{

    private $configuration = null;
    private $baseUrl = null;
    private $password = null;
    private $token = null;

    /**
     * @param $configuration
     *
     * @throws \Exception
     */
    public function setConfiguration($configuration)
    {
        if (
            empty($configuration)
            || empty($configuration['baseUrl'])
            || empty($configuration['password'])
            || empty($configuration['token'])
        ) {
            throw new Exception('Invalid IDB Portal API configuration.');
        }
        $this->configuration = $configuration;
        $this->baseUrl = $configuration['baseUrl'];
        $this->password = $configuration['password'];
        $this->token = $configuration['token'];
    }

    /**
     * @param $jwt
     * @param $request
     *
     * @return array|null
     */
    public function decodeRequest($jwt, $request)
    {
        $password = $this->password;
        $token = $this->token;

        return self::decode($password, $token, $jwt, $request);
    }

    /**
     * @param $password
     * @param $token
     * @param $jwt
     * @param $request
     *
     * @return array|null
     */
    private static function decode($password, $token, $jwt, $request)
    {
        $requestData = null;
        try {
            if (!empty($jwt)) {
                $idbSecurity = new IdbSecurity(Yii::$app->security);

                $jwt_data = explode('.', $jwt);
                if (sizeof($jwt_data) == 3) {
                    $jwt = [];
                    $jwt['header_b64'] = $jwt_data[0];
                    $jwt['payload_b64'] = $jwt_data[1];
                    $jwt['signature'] = $jwt_data[2];

                    $jwt['header_json'] = base64_decode($jwt['header_b64']);
                    $jwt['payload_json'] = base64_decode($jwt['payload_b64']);
                    $jwt['signature'] = base64_decode($jwt['signature']);

                    $password = $token . $password;

                    $payload = json_decode($jwt['payload_json'], true);
                    if (
                        (json_last_error() === JSON_ERROR_NONE)
                        && (!empty($payload['iat']))
                        && (!empty($payload['info']))
                    ) {
                        $iat = $payload['iat'];
                        $infoEncrypted = $payload['info'];
                        $infoEncrypted = base64_decode($infoEncrypted);
                        $info_json = $idbSecurity->decryptByPasswordSpeed($infoEncrypted, $password, false);
                        $info = json_decode($info_json, true);
                        if (
                            (json_last_error() === JSON_ERROR_NONE)
                            && (!empty($info['id']))
                            && (!empty($info['server']['REQUEST_TIME_NUMBER']))
                        ) {
                            $id = $info['id'];
                            $dateTimestamp = $info['server']['REQUEST_TIME_NUMBER'];
                            $token .= $dateTimestamp;
                            $signature = $idbSecurity->hash($jwt['header_b64'] . '.' . $jwt['payload_b64'], $token);

                            if (
                                ($signature === $jwt['signature'])
                                && (!empty($request['idbdata']))
                            ) {
                                $idbData = $request['idbdata'];
                                $idbData = base64_decode($idbData);
                                $idbDataJson = $idbSecurity->decryptByPasswordSpeed(
                                    $idbData,
                                    $token . $password,
                                    false
                                );

                                $idbData = json_decode($idbDataJson, true);
                                if (json_last_error() === JSON_ERROR_NONE) {
                                    $requestData = [
                                        'iat' => $iat,
                                        'id' => $id,
                                        'data' => $idbData
                                    ];

                                    return $requestData;
                                }
                            }
                        }
                    }
                }
            }
        } catch (Exception $error) {
            $requestData = null;
        }

        return $requestData;
    }

    /**
     * @param $id
     * @param $data
     *
     * @return |null
     * @throws \yii\web\NotFoundHttpException
     */
    protected function request($id, $data)
    {
        $url = $this->baseUrl;
        $password = $this->password;
        $token = $this->token;

        return self::executeRequest($id, $url, $password, $token, $data);
    }

    /**
     * @param $id
     * @param $url
     * @param $password
     * @param $token
     * @param $data
     *
     * @return |null
     * @throws \yii\web\NotFoundHttpException
     */
    private static function executeRequest($id, $url, $password, $token, $data)
    {
        if (!empty($data)) {
            if (substr($url, -1) !== '/') {
                $url = $url . '/';
            }
            $url = $url . 'idb-api';
            $dateTimestamp = Localization::getDateTimeNumberString();
            $jwtPayloadData = ['id' => $id, 'server' => $_SERVER, 'request' => $_REQUEST];
            $jwtPayloadData['server'] = array_intersect_key(
                $jwtPayloadData['server'],
                [
                    "LANGUAGE" => null,
                    "SSL_SERVER_S_DN_CN" => null,
                    "SERVER_ADDR" => null,
                    "SERVER_PORT" => null,
                    "SCRIPT_NAME" => null,
                ]
            );
            $password = $token . $password;
            $token .= $jwtPayloadData['server']['REQUEST_TIME_NUMBER'] = $dateTimestamp;
            $jwtPayloadData = json_encode($jwtPayloadData);

            if (empty($password) || empty($token) || empty($url) || empty($jwtPayloadData)) {
                throw new NotFoundHttpException();
            }

            $idbSecurity = new IdbSecurity(Yii::$app->security);
            $jwtPayloadData = base64_encode($idbSecurity->encryptByPasswordSpeed($jwtPayloadData, $password, false));
            $header = ["alg" => "IDB.V1", "typ" => "JWT"];
            $header = json_encode($header);
            $payload = ["iat" => Localization::getDateTimeLogString(), 'info' => $jwtPayloadData];
            $payload = json_encode($payload);
            $signature = $idbSecurity->hash(base64_encode($header) . '.' . base64_encode($payload), $token);
            $jwt = base64_encode($header) . '.' . base64_encode($payload) . '.' . base64_encode($signature);
            $idbData = base64_encode(
                $idbSecurity->encryptByPasswordSpeed(json_encode($data), $token . $password, false)
            );

            try {
                $postData = http_build_query(['idbdata' => $idbData]);
                $authorization = "Authorization: Bearer $jwt";

                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                curl_setopt(
                    $ch,
                    CURLOPT_HTTPHEADER,
                    ["X-Requested-With: XMLHttpRequest", $authorization]
                );
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
                curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
                curl_setopt($ch, CURLOPT_POST, 1);
                $response = curl_exec($ch);
                if ($response === false) {
                    throw new Exception(curl_error($ch), curl_errno($ch));
                } else {
                    $response = json_decode($response, true);
                    if (
                        !empty($response['data'])
                        && !empty($response['status'])
                        && $response['status'] === 'success'
                    ) {
                        $data = $response['data'];
                    }
                }
                curl_close($ch);
            } catch (Exception $error) {
                $data = null;
            }
        } else {
            throw new NotFoundHttpException();
        }

        return $data;
    }
}

################################################################################
#                                End of file                                   #
################################################################################
