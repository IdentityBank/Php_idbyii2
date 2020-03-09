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

use idbyii2\widgets\assets\IconBoxAsset;
use yii\base\Widget;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;

################################################################################
# Class(es)                                                                    #
################################################################################

/**
 * Class IconBox
 *
 * @package idbyii2\widgets
 */
class IconBox extends Widget
{

    /** color type */
    public $type = 'brand';
    /** Data for anchor tag */
    public $data = null;
    /** random color flag */
    public $random = false;
    public $options = [];
    public $icon = '';
    public $title = '';
    public $href = null;
    public $col = 4;

    private $colors = ['brand', 'warning', 'success'];

    /**
     * Init code widget view
     *
     * @throws \yii\base\InvalidConfigException
     */
    public function init()
    {
        parent::init();

        if ($this->random) {
            $this->type = $this->colors[rand(0, 2)];
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
        $options['class'] = 'main-tiles col-lg-' . $this->col;
        $options['href'] = $this->href;
        $options['data'] = $this->data;
        $tag = ArrayHelper::remove($options, 'tag', 'a');

        echo Html::tag($tag, $content, $options);
    }

    /**
     * @return string
     */
    private function renderMainSection()
    {
        $content = $this->renderBodySection();

        return Html::tag(
            'div',
            $content,
            ['class' => 'kt-portlet kt-iconbox kt-iconbox--' . $this->type . ' kt-iconbox--animate-slower']
        );
    }

    /**
     * @return string
     */
    private function renderBodySection()
    {
        $innerContent = $this->renderIconSection();
        $innerContent .= $this->renderDescriptionSection();
        $content = Html::tag('div', $innerContent, ['class' => 'kt-iconbox__body']);

        return Html::tag('div', $content, ['class' => 'kt-portlet__body']);
    }

    /**
     * @return string
     */
    private function renderIconSection()
    {
        $content = Html::tag(
            'i',
            '',
            ['class' => $this->icon . ' kt-font-' . $this->type, 'style' => 'color:'. $this->random_color() .' !important;']
        );

        return Html::tag(
            'div',
            $content,
            ['class' => 'kt-iconbox__icon', 'style' => 'font-size: 38px;']
        );
    }

    /**
     * @return string
     */
    private function renderDescriptionSection()
    {
        $content = Html::tag(
            'h3',
            $this->title,
            ['class' => 'kt-iconbox__title', 'style' => 'margin-bottom: 0;']
        );

        return Html::tag(
            'div',
            $content,
            [
                'class' => 'kt-iconbox__desc',
                'style' => 'align-items: center; justify-content: center;'
            ]
        );
    }

    /**
     * @return string
     */
    private function random_color_part()
    {
        return str_pad(dechex(mt_rand(0, 255)), 2, '0', STR_PAD_LEFT);
    }

    /**
     * @return string
     */
    private function random_color()
    {
        return '#' . $this->random_color_part() . $this->random_color_part() . $this->random_color_part();
    }
}

################################################################################
#                                End of file                                   #
################################################################################
