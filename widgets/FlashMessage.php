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

use idbyii2\widgets\flashMessage\assets\FlashMessageAsset;
use yii\base\Widget;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;

################################################################################
# Class(es)                                                                    #
################################################################################

/**
 * Class FlashMessage
 *
 * @package idbyii2\widgets
 */
class FlashMessage extends Widget
{

    /** @var array $options */
    public $options = [];
    /** @var string $success */
    public $success;
    /** @var string $error */
    public $error;
    /** @var string $info */
    public $info;
    /** @var string $message */
    public $message;
    /** @var string $subjectSuccess */
    public $subjectSuccess = 'SUCCESS';
    /** @var string $subjectError */
    public $subjectError = 'ERROR';
    /** @var string $subjectInfo */
    public $subjectInfo = 'INFO';

    /**
     * Init flash message widget view
     */
    public function init()
    {
        $view = $this->getView();
        FlashMessageAsset::register($view);

        parent::init();

        if (!isset($this->options['id'])) {
            $this->options['id'] = $this->getId();
        }
    }

    /**
     * Base section to display our widget
     *
     * @return string|void
     */
    public function run()
    {
        $content = '';
        if (!is_null($this->success)) {
            if (is_array($this->success)) {
                $this->subjectSuccess = $this->success['subject'];
                $content .= $this->renderSuccessSection();
            } else {
                $content .= $this->renderSuccessSection();
            }
        }

        if (!is_null($this->error)) {
            if (is_array($this->error)) {
                $this->subjectError = $this->error['subject'];
                $content .= $this->renderErrorSection();
            } else {
                $content .= $this->renderErrorSection();
            }
        }

        if (!is_null($this->info)) {
            if (is_array($this->info)) {
                $this->subjectInfo = $this->info['subject'];
                $content .= $this->renderIndexSection();
            } else {
                $content .= $this->renderIndexSection();
            }
        }

        if (
            !empty($content)
            && !is_null($this->message)
        ) {
            $content .= $this->message;
        }

        if (!empty($content)) {
            $options = $this->options;
            $tag = ArrayHelper::remove($options, 'tag', 'div');
            echo Html::tag($tag, $content, $options);
        }
    }

    /**
     * @return string
     */
    private function renderCloseButton()
    {
        $options = [
            "aria-hidden" => "true",
            "data-dismiss" => "alert",
            "type" => "button",
            "class" => "close"
        ];
        $tag = ArrayHelper::remove($options, 'tag', 'button');

        return Html::tag($tag, 'x', $options) . PHP_EOL;
    }

    private function renderSuccessSection()
    {
        $content = $this->renderCloseButton();
        $content .= Html::tag(
            'h4',
            Html::tag(
                'i',
                '',
                [
                    'class' => "icon fa fa-check"
                ]
            ) . ' ' . $this->subjectSuccess
        );

        if (isset($this->success['message'])) {
            $content .= $this->success['message'];
        } else {
            $content .= $this->success;
        }

        $options = [
            'class' => "alert alert-success alert-dismissable"
        ];

        $tag = ArrayHelper::remove($options, 'tag', 'div');

        return Html::tag($tag, $content, $options) . PHP_EOL;
    }

    private function renderErrorSection()
    {
        $content = $this->renderCloseButton();
        $content .= Html::tag(
            'h4',
            Html::tag(
                'i',
                '',
                [
                    'class' => "icon fa fa-frown-o"
                ]
            ) . ' ' . $this->subjectError
        );

        if (isset($this->error['message'])) {
            $content .= $this->error['message'];
        } else {
            $content .= $this->error;
        }

        $options = [
            'class' => "alert alert-danger alert-dismissable"
        ];

        $tag = ArrayHelper::remove($options, 'tag', 'div');

        return Html::tag($tag, $content, $options) . PHP_EOL;
    }

    private function renderIndexSection()
    {
        $content = $this->renderCloseButton();
        $content .= Html::tag(
            'h4',
            Html::tag(
                'i',
                '',
                [
                    'class' => "icon fa fa-info-circle"
                ]
            ) . ' ' . $this->subjectInfo
        );

        if (isset($this->info['message'])) {
            $content .= $this->info['message'];
        } else {
            $content .= $this->info;
        }

        $options = [
            'class' => "alert alert-info alert-dismissable"
        ];

        $tag = ArrayHelper::remove($options, 'tag', 'div');

        return Html::tag($tag, $content, $options) . PHP_EOL;
    }
}

################################################################################
#                                End of file                                   #
################################################################################
