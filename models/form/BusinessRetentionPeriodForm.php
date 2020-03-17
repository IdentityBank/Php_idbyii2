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

namespace idbyii2\models\form;

################################################################################
# Use(s)                                                                       #
################################################################################

use app\helpers\Translate;

################################################################################
# Class(es)                                                                    #
################################################################################

class BusinessRetentionPeriodForm extends IdbModel
{
    public $minimum;
    public $maximum;
    public $explanation;
    public $reviewCycle;
    public $onExpiry;

    public function rules()
    {
        return [
            [['minimum', 'maximum', 'reviewCycle'], 'integer', 'min' => 1 ],
            [['onExpiry'], 'required', 'when' => function($model) {
                return !empty($model->maximum);
            }, 'whenClient' => 'function(attribute,value) { return !isNaN(parseInt($("#businessretentionperiodform-maximum").val()))}'],
            [['explanation'], 'required', 'when' => function($model) {
                return !empty($model->reviewCycle);
            }, 'whenClient' => 'function(attribute,value) { return !isNaN(parseInt($("#businessretentionperiodform-reviewcycle").val()))}'],
            [['explanation', 'onExpiry'], 'string'],
            [
                'minimum',
                'compare',
                'compareAttribute'=>'maximum',
                'operator' => '<=',
                'type' => 'number',
                'message' => Translate::_('idbyii2', 'Minimum must be less than Maximum')
            ],
            [
                'maximum',
                'compare',
                'compareAttribute'=>'minimum',
                'operator' => '>=',
                'type' => 'number',
                'message' => Translate::_('idbyii2', 'Minimum must be less than Maximum')
            ],
            [
                'reviewCycle',
                'compare',
                'compareAttribute'=>'minimum',
                'operator' => '>=',
                'type' => 'number',
                'message' => Translate::_('idbyii2', 'Review cycle must be greater or equal minimum')
            ],
            [
                'reviewCycle',
                'compare',
                'compareAttribute'=>'maximum',
                'operator' => '<=',
                'type' => 'number',
                'message' => Translate::_('idbyii2', 'Review cycle must be less or equal maximum')
            ]
        ];
    }

    public function attributeLabels()
    {
        return [
            'people_user' => Translate::_('idbyii2', 'Share to:'),
        ];
    }
}

################################################################################
#                                End of file                                   #
################################################################################
