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

namespace idbyii2\widgets;

################################################################################
# Use(s)                                                                       #
################################################################################

use idbyii2\helpers\Translate;
use idbyii2\models\form\IdbModel;
use idbyii2\widgets\assets\PasswordGeneratorAsset;
use yii\base\InvalidConfigException;
use yii\base\Widget;
use yii\bootstrap\ActiveForm;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;

################################################################################
# Class(es)                                                                    #
################################################################################

/**
 * Class PasswordGenerator
 *
 * @package idbyii2\widgets
 */
class PasswordGenerator extends Widget
{

    public $options = [];

    /** @var IdbModel $model */
    public $model;
    /** @var ActiveForm $form */
    public $form;

    /** @var string $style */
    public $style = 'business';

    private $styleOptions = [
        'options' => [],
        'class' => [],
        'tag' => '',
        'tagStyle' => 'margin-bottom: 25px;',
        'goBackStyle' => 'margin-bottom: 30px;',
        'generateStyle' => 'margin-bottom: 20px;',
        'generateClass' => '',
        'additionalAlertClass' => '',
    ];

    /**
     * Init code widget view
     *
     * @throws \yii\base\InvalidConfigException
     */
    public function init()
    {
        $view = $this->getView();
        PasswordGeneratorAsset::register($view);

        parent::init();

        if (!isset($this->options['id'])) {
            $this->options['id'] = $this->getId();
        }

        if ($this->form === null) {
            throw new InvalidConfigException('The "form" property must be set.');
        }

        if ($this->model === null) {
            throw new InvalidConfigException('The "model" property must be set.');
        }

        switch ($this->style) {
            case 'business':
                $this->styleOptions['tag'] = 'h2';
                break;
            case 'people-metronic':
                $this->styleOptions = [
                    'options' => ['options' => ['class' => 'field-idbpeoplesignupform-password form-group added-help']],
                    'tag' => 'h3',
                    'class' => ['class' => 'field-idbpeoplesignupform-password form-group'],
                    'tagStyle' => 'margin-bottom: 20px;',
                    'additionalAlertClass' => ' alert-success',
                    'goBackStyle' => 'margin-bottom: 15px;',
                    'generateStyle' => 'margin-bottom: 20px;',
                    'generateClass' => ' btn-warning',
                ];
                break;
            case 'people-idb':
                $this->styleOptions = [
                    'options' => ['options' => ['class' => 'field-idbpeoplesignupform-password form-group']],
                    'tag' => 'h3',
                    'class' => ['class' => 'field-idbpeoplesignupform-password form-group'],
                    'tagStyle' => '',
                    'goBackStyle' => 'margin-bottom: 15px;',
                    'generateStyle' => '',
                    'generateClass' => ' btn-warning',
                    'additionalAlertClass' => '',
                ];
                break;
            default:
                throw new InvalidConfigException('style allowed types: business, people-metronic, people-idb');
                break;
        }
    }

    /**
     * Base section to display our widget
     *
     * @return string|void
     */
    public function run()
    {
        $content = $this->renderMainSection();

        $options = $this->options;
        $tag = ArrayHelper::remove($options, 'tag', 'div');


        echo Html::tag($tag, $content, $options);
    }


    /**
     * @return string
     */
    private function renderMainSection()
    {
        $content = Html::tag(
            $this->styleOptions['tag'],
            Translate::_('idbyii2', 'Account password setup'),
            ['style' => $this->styleOptions['tagStyle']]
        );
        $content .= $this->renderTypePasswordSection();
        $content .= $this->renderGeneratePasswordSection();
        $content .= $this->renderJS();


        return Html::tag('div', $content, $this->options) . PHP_EOL;
    }

    /**
     * @return string
     */
    private function renderJS()
    {
        $innerContent = 'const copiedTxt = "' . Translate::_(
                'idbyii2',
                'Your password has been copied to the clipboard.'
            ) . '";';
        $innerContent .= 'const copyTxt = "' . Translate::_(
                'idbyii2',
                'Click here to copy your password to the clipboard.'
            ) . '";';
        $innerContent .= "const passwordPolicyJson = '" . html_entity_decode($this->model->passwordPolicy) . "';";

        return Html::script($innerContent);
    }

    /**
     * @return string
     */
    private function renderTypePasswordSection()
    {
        $insideContent = Html::tag(
            'div',
            Translate::_(
                'idbyii2',
                'Click on the generate password button to create a password.'
            ) . '<br/>' .
            Translate::_(
                'idbyii2',
                'Don’t forget to securely save the password you enter - preferably in a password manager app!'
            ),
            ['class' => 'alert alert-wizard' . $this->styleOptions['additionalAlertClass']]
        );

        $innerContent = Html::tag('div', $insideContent, $this->styleOptions['class']);

        $innerContent .= Html::tag(
            'span',
            '<i class="glyphicon glyphicon-log-in"></i> ' .
            Translate::_('idbyii2', 'Generate password'),
            [
                'class' => 'btn btn-block btn-app-yellow password-button' . $this->styleOptions['generateClass'],
                'style' => $this->styleOptions['generateStyle']
            ]
        );

        $inputOptions = array_merge(
            $this->styleOptions['options'],
            ['template' => '{label}{hint}{input}' . $this->renderPasswordMeterSection() . '{error}',]
        );

        $innerContent .= $this->form->field($this->model, 'password', $inputOptions)->passwordInput(
            [
                'id' => 'idbsignupform-password',
                'data-toggle' => 'password',
                'placeholder' => $this->model->getAttributeLabel('password'),
                'class' => 'password-input-meter form-control'
            ]
        );

        $innerContent .= $this->form->field($this->model, 'repeatPassword')->passwordInput(
            [
                'id' => 'idbsignupform-repeatpassword',
                'data-toggle' => 'password',
                'autocomplete' => 'off',
                'placeholder' => $this->model->getAttributeLabel('password')
            ]
        );

        $innerContent .= Html::input('hidden', 'test', '', ['id' => 'password-tocopy']);

        return Html::tag('div', $innerContent, ['id' => 'type-password-container']);
    }

    private function renderPasswordMeterSection()
    {
        $content = Html::tag(
            'div',
            '',
            [
                'id' => 'progress-strength-meter',
                'class' => 'idb-progress-bar'
            ]
        );

        return Html::tag('span', $content, ['class' => 'idb-progress']);
    }


    /**
     * @return string
     */
    private function renderGeneratePasswordSection()
    {
        $innerContent = Html::tag(
            'span',
            '<i class="glyphicon glyphicon-arrow-left"></i> ' .
            Translate::_('idbyii2', 'Go back and type your own password'),
            [
                'style' => $this->styleOptions['goBackStyle'],
                'class' => 'btn btn-block btn-default password-button'
            ]
        );

        $insideContent = Html::tag(
            'div',
            Translate::_(
                'idbyii2',
                'Store the generated password in your password manager app - you will need this later to access your account.'
            ),
            ['class' => 'alert alert-wizard' . $this->styleOptions['additionalAlertClass']]
        );

        $innerContent .= Html::tag('div', $insideContent, $this->styleOptions['class']);

        $innerContent .= Html::tag('pre', '', ['id' => 'text-generate-pass']);

        $innerContent .= Html::button(
            Translate::_('idbyii2', 'Click here to copy your password to the clipboard.'),
            [
                'id' => 'copy-link-button',
                'class' => 'btn btn-warning',
                'style' => 'width: 100%'
            ]
        );

        return Html::tag('div', $innerContent, ['style' => 'display: none', 'id' => 'generate-password-container']);
    }
}

################################################################################
#                                End of file                                   #
################################################################################
