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

use Yii;
use yii\base\InvalidConfigException;
use yii\base\Widget;
use yii\helpers\Html;
use yii\web\View;

################################################################################
# Class(es)                                                                    #
################################################################################

/**
 * Class ReCaptcha
 *
 * @package idbyii2\helpers
 */
class ReCaptcha extends Widget
{

    const GOOGLE_JS_API_URL = '//www.google.com/recaptcha/api.js';
    const GOOGLE_SITE_VERIFY_API_URL = 'https://www.google.com/recaptcha/api/siteverify';
    public $name = 'IdbCaptcha';
    public $siteKey;
    public $secret;
    public $inputName;
    public $inputId;

    /**
     * @return void
     */
    public function init()
    {
        parent::init();
    }

    /**
     * @throws \yii\base\InvalidConfigException
     */
    protected function checkConfig()
    {
        if (is_null($this->inputName)) {
            throw new InvalidConfigException('Required `captcha` component isn\'t set.');
        }
        if (empty($this->siteKey) || empty($this->secret)) {
            throw new InvalidConfigException('Required `siteKey` param isn\'t set.');
        }
    }

    /**
     * @return void
     */
    protected function registerJs()
    {
        $googleApiUrl = self::GOOGLE_JS_API_URL . "?render=$this->siteKey&onload=onloadIdbNewRecaptchaCallback";
        $this->getView()->registerJs($this->getCallbackFunction(), View::POS_END);
        $this->getView()->registerJsFile(
            $googleApiUrl,
            ['async' => 'async', 'defer' => 'defer', 'position' => View::POS_END]
        );
    }

    /**
     * @return string
     */
    protected function getCallbackFunction()
    {
        return '
var onloadIdbNewRecaptchaCallback = function ()
{
    grecaptcha.ready(function()
    {
        grecaptcha.execute(\'' . $this->siteKey . '\', {action: \'' . preg_replace("/[^a-zA-Z_]+/", "_", $this->name) . '\'}).then(function(token)
        {
            document.getElementById("' . $this->inputId . '").value = token;
        });
    });
};
        ';
    }

    /**
     * @param $response
     *
     * @return bool
     */
    protected function validateReCaptchaVerify($response)
    {
        $responseStatus = false;
        $responseData = json_decode($response, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            if (!empty($responseData['success']) && !empty($responseData['score']) && $responseData['success']) {
                $score = $responseData['score'];
                $responseStatus = ($score > 0.5);
            }
        }

        return $responseStatus;
    }

    /**
     * @param $response
     *
     * @return array
     */
    protected function getValidationParams($response)
    {
        return
            [
                'secret' => $this->secret,
                'response' => $response,
                'remoteip' => Yii::$app->request->userIP
            ];
    }

    /**
     * @param $response
     *
     * @return bool
     */
    public function validate($response)
    {
        $curl = curl_init();

        curl_setopt($curl, CURLOPT_URL, self::GOOGLE_SITE_VERIFY_API_URL);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $this->getValidationParams($response));

        $curlData = curl_exec($curl);

        curl_close($curl);

        return $this->validateReCaptchaVerify($curlData);
    }

    /**
     * @param array $config
     *
     * @return $this
     */
    public function config($config = [])
    {
        foreach ($config as $itemKey => $itemValue) {
            $this->$itemKey = $itemValue;
        }

        return $this;
    }

    /**
     * @return string
     * @throws \yii\base\InvalidConfigException
     */
    public function run()
    {
        $this->checkConfig();
        $this->registerJs();

        return Html::hiddenInput($this->inputName, null, ['id' => $this->inputId]);
    }
}

################################################################################
#                                End of file                                   #
################################################################################
