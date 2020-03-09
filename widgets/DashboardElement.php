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
use idbyii2\widgets\assets\DashboardElementAsset;
use yii\base\InvalidConfigException;
use yii\base\Widget;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Url;

################################################################################
# Class(es)                                                                    #
################################################################################

/**
 * Class DashboardElement
 *
 * @package idbyii2\widgets
 */
class DashboardElement extends Widget
{

    /** @var array $options */
    public $options = [];
    /** @var array $database */
    public $database;
    /** @var string $current */
    public $current;

    /**
     * Init dashboard element widget view
     *
     * @throws InvalidConfigException
     */
    public function init()
    {
        $view = $this->getView();
        DashboardElementAsset::register($view);

        parent::init();
        if ($this->database === null) {
            throw new InvalidConfigException('The "database" property must be set.');
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
        $content = $this->renderBoxSection();

        $options = [];
        $options['class'] = 'col-lg-3 col-xs-6';
        $tag = ArrayHelper::remove($options, 'tag', 'div');

        return Html::tag($tag, $content, $options) . PHP_EOL;
    }

    /**
     * @return string
     */
    private function renderBoxSection()
    {

        $name = Html::encode($this->database['name']);
        $content = Html::a(
            '',
            ['/idb-menu', 'dbid' => $this->database['dbid'], 'action' => '/idbdata/idb-data/show-all'],
            ['class' => 'a-mask',
                'data-toggle'=>"tooltip",
                'data-placement'=>"top",
                'title'=>$name]
        );
        $content .= $this->renderEditors();
        $content .= $this->renderInnerSection();
        $content .= $this->renderIconSection();
        $content .= $this->renderSearchSection();
        $content .= $this->renderAddSection();

        $options = [];
        if (!is_null($this->current) && !is_null($this->database) && ($this->current === $this->database['dbid'])) {
            $options['class'] = 'small-box bg-green vault-box';
        } else {
            $options['class'] = 'small-box bg-aqua vault-box';
        }
        $tag = ArrayHelper::remove($options, 'tag', 'div');

        return Html::tag($tag, $content, $options) . PHP_EOL;
    }

    private function renderEditors()
    {
        $inputContent = Html::input(
            'string',
            $this->database['dbid'],
            $this->database['name'],
            [
                'class' => 'title-input form-control',
            ]
        );

        $inputContent .= Html::tag(
            'div',
            Html::button(
                '<i data-dbid="' . $this->database['dbid'] . '" class="fa fa-close"></i>',
                ['class' => 'btn btn-default btn-close-title', 'data-dbid' => $this->database['dbid']]
            ) .
            Html::button(
                '<i data-dbid="' . $this->database['dbid'] . '" class="fa fa-edit"></i>',
                ['class' => 'btn btn-default btn-edit-title', 'data-dbid' => $this->database['dbid']]
            ),
            [
                'class' => 'input-group-btn btn-title-edit',
                'data-dbid' => $this->database['dbid']
            ]
        );

        $content = Html::tag(
            'div',
            $inputContent,
            [
                'class' => 'input-group input-group-sm hidden-xs edit-vault-group idb-hidden',
                'id' => 'title-group-' . $this->database['dbid']
            ]
        );

        $inputContent = Html::textarea(
            'string',
            $this->database['description'],
            [
                'class' => 'title-input form-control',
                'id' => 'desc-area-' . $this->database['dbid']
            ]
        );

        $inputContent .= Html::tag(
            'div',
            Html::button(
                '<i data-dbid="' . $this->database['dbid'] . '" class="fa fa-close"></i>',
                ['class' => 'btn btn-default btn-close-desc', 'data-dbid' => $this->database['dbid']]
            ) .
            Html::button(
                '<i data-dbid="' . $this->database['dbid'] . '" class="fa fa-edit"></i>',
                ['class' => 'btn btn-default btn-edit-desc', 'data-dbid' => $this->database['dbid']]
            ),
            [
                'class' => 'input-group-btn btn-title-edit',
                'data-dbid' => $this->database['dbid']
            ]
        );

        $content .= Html::tag(
            'div',
            $inputContent,
            [
                'class' => 'input-group input-group-sm hidden-xs edit-desc-vault-group idb-hidden',
                'id' => 'desc-group-' . $this->database['dbid']
            ]
        );

        return $content;
    }

    /**
     * @return string
     */
    private function renderInnerSection()
    {
        $fontSize = '38px';

        if (strlen($this->database['name']) > 15) {
            $fontSize = '18px';
        } elseif (strlen($this->database['name']) > 10) {
            $fontSize = '24px';
        } elseif (strlen($this->database['name']) > 8) {
            $fontSize = '32px';
        }

        $name = Html::encode(mb_strimwidth($this->database['name'], 0, 20, '...'));
        $description = Html::encode(mb_strimwidth($this->database['description'], 0, 45, '...'));

        $content = Html::tag(
            'h3',
            '<span>' . $name . '</span>' . Html::tag(
                'a',
                ' <i data-dbid="' . $this->database['dbid'] . '" class="fa fa-pencil pencil-vault"></i>',
                [
                    'href' => '#',
                    'id' => 'name.' . $this->database['dbid'],
                    'data-dbid' => $this->database['dbid'],
                    'data-type' => 'name',
                    'class' => 'edit-title'
                ]
            ),
            [
                'id' => 'db-name-h3-' . $this->database['dbid'],
                'style' => 'font-size: ' . $fontSize . '; line-height: 40px;'
            ]
        );
        $content .= Html::tag(
            'p',
            '<span>' . $description . '</span>' . Html::tag(
                'a',
                ' <i data-dbid="' . $this->database['dbid'] . '" class="fa fa-pencil pencil-vault"></i>',
                [
                    'href' => '#',
                    'id' => 'desc.' . $this->database['dbid'],
                    'data-dbid' => $this->database['dbid'],
                    'data-type' => 'description',
                    'class' => 'edit-desc'
                ]
            ),
            ['id' => 'db-desc-p-' . $this->database['dbid']]
        );

        $options = [];
        $options['class'] = 'inner';
        $tag = ArrayHelper::remove($options, 'tag', 'div');

        return Html::tag($tag, $content, $options) . PHP_EOL;
    }

    /**
     * @return string
     */
    private function renderIconSection()
    {
        $content = Html::tag('i', '', ['class' => 'fa fa-database']) . PHP_EOL;

        $options = [];
        $options['class'] = 'icon';
        $options['href'] = Url::toRoute(
            ['/idb-menu', 'dbid' => $this->database['dbid'], 'action' => '/idbdata/idb-data/show-all'],
            true
        );

        $tag = ArrayHelper::remove($options, 'tag', 'a');

        return Html::tag($tag, $content, $options) . PHP_EOL;
    }

    /**
     * @return string
     */
    private function renderSearchSection()
    {
        return Html::a(
                '<i class="fa fa-search-plus"></i> ' . Translate::_('idbyii2', 'Search'),
                ['/idbdata/idb-data/show-all'],
                [
                    'class' => 'small-box-footer',
                    'data' => [
                        'method' => 'post',
                        'params' => ['dbid' => $this->database['dbid']],
                    ],
                    'data-toggle' => 'tooltip',
                    'data-placement' => 'bottom',
                    'title' => Translate::_('idbyii2', 'Search people')
                ]
            ) . PHP_EOL;
    }

    private function renderAddSection()
    {
        return Html::a(
                '<i class="fa fa-plus-square"></i> ' . Translate::_('idbyii2', 'Add'),
                ['/idbdata/idb-data/create'],
                [
                    'class' => 'small-box-footer',
                    'data' => [
                        'method' => 'post',
                        'params' => ['dbid' => $this->database['dbid']],
                    ],
                    'data-toggle' => 'tooltip',
                    'data-placement' => 'bottom',
                    'title' => Translate::_('idbyii2', 'Add new person')
                ]
            ) . PHP_EOL;
    }
}

################################################################################
#                                End of file                                   #
################################################################################
