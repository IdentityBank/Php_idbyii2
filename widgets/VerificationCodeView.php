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

use idbyii2\widgets\verificationCode\assets\VerificationCodeAsset;
use yii\base\InvalidConfigException;
use yii\base\Widget;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;

################################################################################
# Class(es)                                                                    #
################################################################################

/**
 * Class IdbNameValidator
 *
 * @package idbyii2\validators
 */
class VerificationCodeView extends Widget
{

    public $code;
    public $options = [];
    private $sectionsCount = 4;
    private $sectionItemsCount = 3;
    private $sectionIndex = 10;

    /**
     * Init code widget view
     *
     * @throws \yii\base\InvalidConfigException
     */
    public function init()
    {
        $view = $this->getView();
        VerificationCodeAsset::register($view);

        parent::init();
        if ($this->code === null) {
            throw new InvalidConfigException('The "code" property must be set.');
        }
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
        $content = '';
        $this->sectionIndex = 10;
        foreach (range(0, $this->sectionsCount - 1) as $sectionId) {
            $content .= $this->renderSection($sectionId);
        }

        $options = [];
        $options['id'] = 'id-verification';
        $options['class'] = 'verification-input';
        $options['align'] = 'center';
        $tag = ArrayHelper::remove($options, 'tag', 'div');

        return Html::tag($tag, $content, $options) . PHP_EOL;
    }

    /**
     * @param $id
     *
     * @return string
     */
    private function renderSection($id)
    {
        $content = PHP_EOL;
        if ($id != 0) {
            $content = $this->renderSectionSeparator() . PHP_EOL;
        }

        foreach (range(0, $this->sectionItemsCount - 1) as $sectionItemId) {
            $content .= $this->renderSectionItem($id, $sectionItemId);
        }

        return $content . PHP_EOL;
    }

    /**
     * @return string
     */
    private function renderSectionSeparator()
    {
        if (empty($this->options['separator'])) {
            $this->options['separator'] = '&nbsp;-&nbsp;';
        }

        return $this->options['separator'] . PHP_EOL;
    }

    /**
     * @param $sectionId
     * @param $id
     *
     * @return string
     */
    private function renderSectionItem($sectionId, $id)
    {
        $options = [];
        $options['type'] = 'text';
        $options['maxLength'] = '1';
        $options['name'] = 'code[]';
        $options['size'] = '1';
        $options['min'] = '0';
        $options['max'] = '9';
        $options['pattern'] = '[0-9]{1}';

        if (!($sectionId % 2)) {
            $options['readonly'] = 'readonly';
            $options['tabindex'] = '-1';
            $options['value'] = $this->code[$sectionId][$id] ?? 0;
        } else {
            $options['required'] = 'required';
            $options['data-index'] = $options['tabindex'] = "$this->sectionIndex";
            $this->sectionIndex++;
        }

        return Html::tag('input', null, $options) . PHP_EOL;
    }
}

################################################################################
#                                End of file                                   #
################################################################################
