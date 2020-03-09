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
use Yii;

################################################################################
# Class(es)                                                                    #
################################################################################

class IdbMfaHelper
{

    /**
     * @param $number
     *
     * @return mixed
     */
    public static function getMfaViewVariables($model, $config)
    {
        $mfaVariables = [];
        $mfaVariables['title'] = $title = Translate::_('idbyii2', 'Setup Multi Factor Authentication (MFA)');
        $mfaVariables['userId'] = $userId = ((empty(Yii::$app->user->identity->userId)) ? ''
            : Yii::$app->user->identity->userId);
        $mfaVariables['accountNumber'] = $accountNumber = ((empty(Yii::$app->user->identity->accountNumber)) ? ''
            : Yii::$app->user->identity->accountNumber);

        $mfaVariables['mfaName'] = $mfaName = "$userId@$accountNumber";
        $mfaVariables['issuer'] = $issuer = $config->getYii2MfaIssuer();
        $mfaQr = Totp::getQrCode($mfaName, $model->mfa, $issuer);
        $mfaQr = base64_encode($mfaQr);
        $mfaVariables['mfaQr'] = $mfaQr = 'data:image/jpeg;base64,' . $mfaQr;

        $googleImg = Yii::getAlias('@app') . '/views/assets/img/google-play.png';
        if (!file_exists($googleImg)) {
            $googleImg = Yii::getAlias('@app') . '/views/assets/images/google-play.png';
        }
        if (file_exists($googleImg)) {
            $googleImg = file_get_contents($googleImg);
            $googleImg = base64_encode($googleImg);
        } else {
            $googleImg = null;
        }
        $mfaVariables['googleImg'] = $googleImg = 'data:image/png;base64,' . $googleImg;

        $appleImg = Yii::getAlias('@app') . '/views/assets/img/app-store.svg';
        if (!file_exists($appleImg)) {
            $appleImg = Yii::getAlias('@app') . '/views/assets/images/app-store.svg';
        }
        if (file_exists($appleImg)) {
            $appleImg = file_get_contents($appleImg);
            $appleImg = base64_encode($appleImg);
        } else {
            $appleImg = null;
        }
        $mfaVariables['appleImg'] = $appleImg = 'data:image/svg+xml;base64,' . $appleImg;

        return $mfaVariables;
    }

    /**
     * @return bool|string|\Webpatser\Uuid\Uuid
     * @throws \Exception
     */
    public static function generateMfaSecurityKey()
    {
        $idbSecurity = new IdbSecurity(Yii::$app->security);
        $cost = ((date('s') % 8) + 2);
        $securityKeyString = Localization::getDateTimeLogString() . Yii::$app->user->identity->id;
        $securityKeyString = Uuid::uuid5($securityKeyString)->toString();
        $securityKeyString = $idbSecurity->secureHash($securityKeyString, $idbSecurity->generatePassword(32), $cost);
        $securityKey = Totp::securityKeyFromString($securityKeyString);

        return $securityKey;
    }

    /**
     * @param null $mfaValue
     * @param null $code
     *
     * @return bool
     */
    public static function validateMfa($mfaValue = null, $code = null)
    {
        if (
            !empty($mfaValue['type'])
            && !empty($code)
        ) {
            switch ($mfaValue['type']) {
                case 'totp':
                    {
                        $securityKey = $mfaValue['value'] ?? null;
                        $code = preg_replace('/\s+/', "", $code);
                        $code = substr($code, 0, 6);
                        $code = preg_replace("/[^0-9]/", "", $code);

                        return self::validateMfaTotp($securityKey, $code);
                    }
                    break;
                case 'skip':
                    {
                        return self::validateMfaSkip($mfaValue['value'] ?? null, $code);
                    }
                    break;
            }
        } else {
            $session = Yii::$app->session;
            $session->open();
            try {
                return (!empty($session['mfa']));
            } catch (Exception $e) {
            } finally {
                $session->close();
            }
        }

        return false;
    }

    /**
     * @param      $securityKey
     * @param      $code
     * @param null $time
     *
     * @return bool
     */
    private static function validateMfaTotp($securityKey, $code, $time = null)
    {

        $session = Yii::$app->session;
        $session->open();
        try {
            if (
                !empty($securityKey)
                && !empty($code)
                && Totp::verify($securityKey, $code)
            ) {
                $session->set('mfa', $code);

                return true;
            } else {
                return (!empty($session['mfa']));
            }
        } catch (Exception $e) {
        } finally {
            $session->close();
        }

        return false;
    }

    /**
     * @param $login
     * @param $code
     *
     * @return bool
     */
    private static function validateMfaSkip($login, $code)
    {
        $session = Yii::$app->session;
        $session->open();
        try {
            if (
                !empty($login)
                && !empty($code)
                && ($login === $code)
            ) {
                $session->set('mfa', $code);

                return true;
            } else {
                return (!empty($session['mfa']));
            }
        } catch (Exception $e) {
        } finally {
            $session->close();
        }

        return false;
    }
}

################################################################################
#                                End of file                                   #
################################################################################
