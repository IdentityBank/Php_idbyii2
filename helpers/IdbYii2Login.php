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

use xmz\simplelog\SNLog as Log;
use Yii;
use yii\validators\IpValidator;
use function xmz\simplelog\registerLogger;

################################################################################
# Class(es)                                                                    #
################################################################################

/**
 * Class IdbYii2Login
 *
 * @package idbyii2\helpers
 */
class IdbYii2Login
{

    /**
     * @param $jwt
     * @param $destination
     * @param $controller
     * @param $model
     *
     * @return mixed
     * @throws \ReflectionException
     * @throws \yii\base\ExitException
     * @throws \yii\base\InvalidConfigException
     */
    public static function idbLogin($jwt, $destination, $controller, $model)
    {
        $session = Yii::$app->session;
        $session->open();
        try {
            Yii::$app->session->destroy();
        } catch (Exception $e) {
        } finally {
            $session->close();
        }

        $request = Yii::$app->request;
        if (empty($request)) {
            Yii::$app->end();
        }
        if ($request->isAjax) {
            IdbYii2Login::checkIdbLoginFirewall($destination);
            $headers = $request->headers;
            if (empty($headers) || !$headers->has('IDB-RequestVerificationToken')) {
                Yii::$app->end();
            }
            $dateTimestamp = $headers->get('IDB-RequestVerificationToken');
            if (empty($jwt)) {
                $jwt = $request->getBodyParam('idbjwt', null);
            }
        } else {
            if (empty($jwt)) {
                $jwt = $request->get('idbjwt', null);
            }
            $verify = $request->get('verify', null);
            if (
                (empty($jwt))
                || (empty($verify))
                || (empty(self::decodeVerify($destination, $verify)))
                || (!Yii::$app->user->isGuest)
            ) {
                return $controller->goHome();
            }

            $dateTimestamp = base64_encode(Localization::getDateTimeNumberString());
            $jwtLength = strlen($jwt);
            $dateTimestampLength = strlen($dateTimestamp);

            if ($dateTimestampLength < $jwtLength) {
                $dateTimestampPos = (($jwtLength - $dateTimestampLength) / 2);
                $dateTimestamp = substr($jwt, $dateTimestampPos, $dateTimestampLength);
                $dateTimestamp = base64_decode($dateTimestamp);
                $jwtData = substr($jwt, 0, $dateTimestampPos);
                $jwtData .= substr($jwt, ($dateTimestampPos + $dateTimestampLength));
                $jwt = $jwtData;
            }
        }
        if (empty($jwt) || empty($dateTimestamp)) {
            Yii::$app->end();
        }
        $accountDestinationPasswords = IdbYii2Config::get()->getAccountDestinationPasswords();
        $password = ((is_array($accountDestinationPasswords) && !empty($accountDestinationPasswords[$destination]))
            ? $accountDestinationPasswords[$destination] : '');
        $accountDestinationTokens = IdbYii2Config::get()->getAccountDestinationTokens();
        $token = ((is_array($accountDestinationTokens) && !empty($accountDestinationTokens[$destination]))
            ? $accountDestinationTokens[$destination] : '');
        $token .= $dateTimestamp;
        $password = $token . $password;
        $idbSecurity = new IdbSecurity(Yii::$app->security);
        $jwt = $idbSecurity->decryptByPassword(base64_decode($jwt), $password);
        $jwt = json_decode($jwt, true);
        if (empty($jwt)) {
            Yii::$app->end();
        }
        if (!empty($jwt['IdbLoginForm'])) {
            if (empty($jwt[$model->getShortClassName()])) {
                $jwt[$model->getShortClassName()] = $jwt['IdbLoginForm'];
                $jwt['jwt'] = true;
            }
            if ($request->isAjax) {
                $login = [];

                if ($model->load($jwt) && $model->login()) {
                    $login['status'] = 'success';
                    $login['idb'] = self::encodeVerify($destination, $jwt, $dateTimestamp);
                } else {
                    $login['status'] = 'failed';
                }

                echo(json_encode($login));
                Yii::$app->end();
            }
            if (
            !IdbYii2Login::checkIdbLoginVerify(
                $destination,
                ($verify ?? null),
                $jwt,
                $dateTimestamp
            )
            ) {
                return $controller->goHome();
            }

            return $controller->actionLogin($jwt);
        } else {
            if (!$request->isAjax) {
                return $controller->goHome();
            }
        }
    }

    /**
     * @param $destination
     * @param $jwt
     *
     * @return string
     */
    private static function encodeVerify($destination, $jwt, $dateTimestamp)
    {
        $userId = $jwt['IdbLoginForm']['userId'] ?? null;
        $accountNumber = $jwt['IdbLoginForm']['accountNumber'] ?? null;

        $loginVerifyPassword = IdbYii2Config::get()->getLoginVerifyPassword();
        $loginVerifyTemplate = IdbYii2Config::get()->getLoginVerifyTemplate();
        $verifyAttributes = [
            'timestamp' => $dateTimestamp,
            'timestamp_md5' => md5($dateTimestamp),
            'timestamp_sha256' => hash('sha256', $dateTimestamp, false),
            'userId' => $userId,
            'accountNumber' => $accountNumber,
            'userId_accountNumber_sha256' => hash('sha256', $userId . $accountNumber, false),
        ];
        if (preg_match_all("/{(.*?)}/", $loginVerifyTemplate, $m)) {
            foreach ($m[1] as $i => $varname) {
                $loginVerifyTemplate = str_replace(
                    $m[0][$i],
                    sprintf('%s', $verifyAttributes[$varname]),
                    $loginVerifyTemplate
                );
            }
        }
        $idbSecurity = new IdbSecurity(Yii::$app->security);

        return base64_encode($idbSecurity->encryptByPasswordSpeed($loginVerifyTemplate, $loginVerifyPassword));
    }

    /**
     * @param $destination
     * @param $verify
     *
     * @return string
     */
    private static function decodeVerify($destination, $verify)
    {
        $loginVerifyPassword = IdbYii2Config::get()->getLoginVerifyPassword();
        $idbSecurity = new IdbSecurity(Yii::$app->security);

        return $idbSecurity->decryptByPasswordSpeed(base64_decode($verify), $loginVerifyPassword);
    }

    /**
     * @param $destination
     * @param $verify
     * @param $jwt
     * @param $dateTimestamp
     *
     * @return bool
     */
    private static function checkIdbLoginVerify($destination, $verify, $jwt, $dateTimestamp)
    {
        if (!empty($verify)) {
            $verifyToCheck = self::encodeVerify($destination, $jwt, $dateTimestamp);
            $verifyDecoded = self::decodeVerify($destination, $verify);
            $verifyToCheck = self::decodeVerify($destination, $verifyToCheck);
            if ($verifyDecoded === $verifyToCheck) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param $portal
     * @param $config
     *
     * @throws \yii\base\ExitException
     * @throws \yii\base\InvalidConfigException
     */
    private static function checkIdbLoginFirewall($portal)
    {
        $loginFirewall = IdbYii2Config::get()->getLoginFirewall();
        $ip = Yii::$app->getRequest()->getUserIP();
        $ipValidator = new IpValidator();
        $ipValidator->setRanges($loginFirewall);
        $ipValidator->init();
        if (!$ipValidator->validate($ip)) {
            self::logLogin(['portal' => $portal, 'ip' => $ip]);
            sleep(10);
            Yii::$app->end();
        }
    }

    /**
     * @param $arguments
     */
    private static function logLogin($arguments)
    {
        $logName = "p57b.idb_login";
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
        Log::error(
            $logName,
            "$pid - " .
            $argumentsString
        );
    }
}

################################################################################
#                                End of file                                   #
################################################################################
