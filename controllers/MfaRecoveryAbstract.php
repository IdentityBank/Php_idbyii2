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

namespace idbyii2\controllers;

################################################################################
# Use(s)                                                                       #
################################################################################

use idbyii2\helpers\EmailTemplate;
use idbyii2\helpers\Sms;
use idbyii2\helpers\Translate;
use idbyii2\helpers\VerificationCode;
use idbyii2\models\db\BusinessUserData;
use idbyii2\models\form\EmailVerificationForm;
use idbyii2\models\form\SmsVerificationForm;
use Yii;
use yii\web\Controller;

################################################################################
# Interface(es)                                                                    #
################################################################################

/**
 * Class MfaRecoveryAbstract
 *
 * @package idbyii2\controllers
 */
abstract class MfaRecoveryAbstract extends Controller implements MfaRecoveryInterface
{

    protected $captchaEnabled = false;
    protected $emailAction;

    /**
     * @param $action
     *
     * @return bool
     * @throws \yii\web\BadRequestHttpException
     * @throws \yii\base\ExitException
     */
    public function beforeAction($action)
    {
        if (Yii::$app->user->isGuest) {
            $this->goHome();
            Yii::$app->end();
        }

        return parent::beforeAction($action);
    }

    /**
     * @return mixed
     * @throws \Exception
     */
    public function actionEmailVerification()
    {
        $request = Yii::$app->request;
        $model = new EmailVerificationForm();
        $model->captchaEnabled = $this->captchaEnabled;
        if (
        $this->verifyCode(
            $request->post('code'),
            'emailCode',
            [
                'info' => Translate::_('idbyii2', 'Email code was incorrect please try again.')
            ]
        )
        ) {
            Yii::$app->session->set('tryCount', 3);

            return $this->redirect(['sms-verification']);
        }

        $code = $this->generateVerificationCodeStatic();
        Yii::$app->session->set('emailCode', $code);

        EmailTemplate::sendEmailByAction(
            $this->emailAction,
            array_merge(['code' => $code], $this->getMandatoryParameters()),
            Translate::_('idbyii2', 'Confirm code'),
            Yii::$app->user->identity->email,
            Yii::$app->language
        );

        $code = explode('.', $code);
        $codeFirst = $code[0];
        $codeThird = $code[2];

        return $this->render('emailVerification', compact('model', 'codeFirst', 'codeThird'));
    }

    /**
     * @param $code
     * @param $type
     * @param $flashMessages
     *
     * @return bool
     */
    private function verifyCode($code, $type, $flashMessages)
    {
        if (empty($code)) {
            return false;
        }

        if (!empty($code) && count($code) > 11) {

            if (Yii::$app->session->get($type) == VerificationCode::parseFromArray($code)) {
                return true;
            } else {
                foreach ($flashMessages as $key => $value) {
                    Yii::$app->session->setFlash($key, $value);
                }
            }
        }

        return false;
    }

    abstract protected function generateVerificationCodeStatic();
    abstract protected function getMandatoryParameters();

    /**
     * @return mixed
     * @throws \Throwable
     */
    public function actionSmsVerification()
    {
        $request = Yii::$app->request;
        $model = new SmsVerificationForm();
        $model->captchaEnabled = $this->captchaEnabled;

        if (
        $this->verifyCode(
            $request->post('code'),
            'smsCode',
            [
                'info' => Translate::_('idbyii2', 'Incorrect email or SMS code'),
                'tryCount' => Yii::$app->session->get('tryCount') - 1
            ]
        )
        ) {
            $this->deleteMfa(Yii::$app->user->identity->id);

            return $this->redirect(['/mfa']);
        }

        $tryCount = Yii::$app->session->get('tryCount');

        $code = $this->generateVerificationCodeStatic();
        Yii::$app->session->set('smsCode', $code);

        Sms::sendVerificationCode(
            Yii::$app->user->identity->mobile,
            $code
        );

        $code = explode('.', $code);
        $codeFirst = $code[0];
        $codeThird = $code[2];

        return $this->render('smsVerification', compact('model', 'codeFirst', 'codeThird', 'tryCount'));
    }

    abstract protected function deleteMfa($id);
}

################################################################################
#                                End of file                                   #
################################################################################
