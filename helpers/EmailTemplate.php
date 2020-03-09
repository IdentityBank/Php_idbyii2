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
use idbyii2\components\Messenger;
use idbyii2\models\db\BusinessEmailTemplate;
use idbyii2\models\db\BusinessSignup;
use Yii;

################################################################################
# Class(es)                                                                    #
################################################################################

/**
 * Class EmailTemplate
 *
 * @package idbyii2\helpers
 */
class EmailTemplate
{

    private static $mandatoryParams = [
        'firstName',
        'lastName',
        'businessName'
    ];

    /**
     * Deactivate all templates of specific type for logged organization.
     *
     * @param string $action
     * @param string $iso
     */
    public static function deactivateByAction(string $action, string $iso)
    {
        $templates = BusinessEmailTemplate::find()->where(
            [
                'oid' => Yii::$app->user->identity->oid,
                'action_type' => $action,
                'language' => $iso,
                'active' => true
            ]
        )->all();

        foreach ($templates as $template) {
            $template->active = false;
            $template->save();
        }
    }

    /**
     * Validate correct html for mail.
     *
     * @param string $html
     * @param array  $parameters
     *
     * @return bool
     */
    public static function validate(string $html, $parameters = [])
    {
        $errors = [];
        if ($html === strip_tags($html)) {
            $errors [] = Translate::_('idbyii2', 'Template must be a correct html');
        }

        if (preg_match('/<script/', $html)) {
            $errors [] = Translate::_('idbyii2', 'Javascript not allowed');
        }

        if (preg_match('/<link/', $html)) {
            $errors [] = Translate::_('idbyii2', 'External css styles not allowed');
        }

        foreach ($parameters as $parameter) {
            if (!preg_match('/{{' . $parameter . '}}/', $html)) {
                $errors [] = Translate::_(
                    'idbyii2',
                    'Parameter \'{{{parameter}}}\' is required',
                    ['$parameter' => $parameter]
                );
            }
        }

        if (empty($errors)) {
            return true;
        } else {
            $errorString = '<ul>';
            foreach ($errors as $error) {
                $errorString .= '<li>' . $error . '</li>';
            }
            $errorString .= '</ul>';

            Yii::$app->session->setFlash('dangerMessage', $errorString);

            return false;
        }
    }

    /**
     * @param string $action
     * @param array  $parameters
     * @param string $title
     * @param string $to
     * @param string $iso
     * @param string $oid
     *
     * @return bool
     */
    public static function sendEmailByAction(
        string $action,
        array $parameters,
        string $title,
        string $to = null,
        string $iso = null,
        string $oid = null
    ) {
        try {
            if (!self::validateMandatory($parameters)) {
                Throw new Exception(
                    'There\'s no mandatory param, to send email we need mandatory params like: firstName, lastName, person, BusinessName'
                );
            }

            self::appendMandatory($parameters);

            if (empty($iso)) {
                $iso = Yii::$app->sourceLanguage;
            }

            $iso = str_replace('-', '_', $iso);

            if (empty($parameters['emailTemplateFooter'])) {
                $parameters['emailTemplateFooter'] = StaticContentHelper::getFooter(['footer_language' => $iso]);
            }
            if (empty($to)) {
                $to = Yii::$app->user->identity->email;
            }

            $templateContent = '';
            if (!empty($oid)) {
                /** @var BusinessEmailTemplate $template */
                $template = BusinessEmailTemplate::find()->where(
                    [
                        'language' => $iso,
                        'oid' => $oid,
                        'action_type' => $action,
                        'active' => true
                    ]
                )->one();
                if (!empty($template)) {
                    $templateContent = file_get_contents($template->path);
                }
            }

            if ($templateContent === '') {
                $templateContent = StaticContentHelper::getEmailTemplate($action, $iso);
            }

            $templateContent = self::appendParametersToTemplate($templateContent, $parameters);

            $messenger = Messenger::get();

            $messenger->email($to, $title, $templateContent);

            return true;
        } catch (Exception $e) {
            return false;
        }

    }

    /**
     * @param $parameters
     *
     * @return bool
     */
    private static function validateMandatory($parameters)
    {
        foreach (self::$mandatoryParams as $mandatoryParam) {
            if (!isset($parameters[$mandatoryParam])) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param $parameters
     */
    private static function appendMandatory(&$parameters)
    {
        $parameters['person'] = $parameters['firstName'] . ' ' . $parameters['lastName'];
    }

    /**
     * @param string $template
     * @param array  $parameters
     *
     * @return mixed|string
     */
    public static function appendParametersToTemplate(string $template, array $parameters)
    {
        /**
         * @var string $key
         * @var string $value
         */
        foreach ($parameters as $key => $value) {
            $template = str_replace('{{' . $key . '}}', $value, $template);
        }

        return $template;
    }

    /**
     * @throws \Exception
     */
    private function sendEmailVerificationCode()
    {
        $code = BusinessSignup::generateVeryficationCodeStatic();
        Yii::$app->session->set('emailCode', $code);

        $body = $this->renderPartial(
            '@app/themes/idb/modules/mfarecovery/views/emails/veryficationCodes.php',
            ['authKey' => 'test', 'code' => $code]
        );
    }
}

################################################################################
#                                End of file                                   #
################################################################################
