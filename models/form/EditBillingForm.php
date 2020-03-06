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
use idbyii2\validators\IdbNameValidator;
use yii\helpers\ArrayHelper;

################################################################################
# Class(es)                                                                    #
################################################################################

/**
 * Class EditBillingForm
 *
 * @package idbyii2\models\form
 */
class EditBillingForm extends IdbModel
{

    // Contact person
    public $billingFirstName;
    public $billingLastName;
    public $billingAddressLine1;
    public $billingAddressLine2;
    public $billingCity;
    public $billingPostcode;
    public $billingRegion;
    public $billingCountry;
    public $billingName;
    public $billingRegistrationNumber;
    public $billingVat;

    /**
     * @return array
     */
    public function rules()
    {
        return ArrayHelper::merge(
            [
                [
                    [
                        'billingFirstName',
                        'billingLastName',
                        'billingName',
                        'billingAddressLine1',
                        'billingCity',
                        'billingPostcode',
                        'billingCountry',
                        'billingVat',
                        'billingRegistrationNumber'
                    ],
                    'required'
                ],
                [
                    [
                        'billingFirstName',
                        'billingLastName',
                        'billingName',
                        'billingAddressLine1',
                        'billingCity',
                        'billingPostcode',
                        'billingCountry',
                        'billingVat',
                        'billingRegistrationNumber'
                    ],
                    'trim'
                ],
                [
                    [
                        'billingFirstName',
                        'billingLastName',
                        'billingName',
                        'billingAddressLine1',
                        'billingAddressLine2',
                        'billingCity',
                        'billingPostcode',
                        'billingCountry',
                        'billingVat',
                        'billingRegistrationNumber',
                        'billingRegion'
                    ],
                    'string',
                    'max' => 255
                ],
            ],
            IdbNameValidator::customRules('billingName', $this->getAttributeLabel('billingName'))
        );
    }

    /**
     * @return array
     */
    public function attributeLabels()
    {
        return [
            'billingFirstName' => Translate::_('idbyii2', 'First name'),
            'billingLastName' => Translate::_('idbyii2', 'Surname'),
            'billingName' => Translate::_('idbyii2', 'Business Name'),
            'billingAddressLine1' => Translate::_('idbyii2', 'Address Line 1'),
            'billingAddressLine2' => Translate::_('idbyii2', 'Address Line 2'),
            'billingCity' => Translate::_('idbyii2', 'City'),
            'billingRegion' => Translate::_('idbyii2', 'Province / Region'),
            'billingPostcode' => Translate::_('idbyii2', 'Postcode'),
            'billingCountry' => Translate::_('idbyii2', 'Country'),
            'billingVat' => Translate::_('idbyii2', 'Tax Identification Number'),
            'billingRegistrationNumber' => Translate::_('idbyii2', 'Company Registration Number')
        ];
    }
}

################################################################################
#                                End of file                                   #
################################################################################
