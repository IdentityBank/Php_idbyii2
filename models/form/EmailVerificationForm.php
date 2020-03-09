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

use idbyii2\helpers\Translate;

################################################################################
# Class(es)                                                                    #
################################################################################

/**
 * Class EmailVerificationForm
 *
 * @package idbyii2\models\form
 */
class EmailVerificationForm extends IdbModel
{

    public $emailCode;
    public $captchaEnabled = true;
    public $verificationCode;

    /**
     * @return array
     */
    public function rules()
    {
        return [
        ];
    }

    /**
     * @return array
     */
    public function attributeLabels()
    {
        return [
            'emailCole',
            Translate::_('idbyii2', 'Email Code')
        ];
    }

    /**
     * @return bool
     */
    public function beforeValidate()
    {
        if (parent::beforeValidate()) {
            foreach ($this->attributes as $attributeName => $attributeValue) {
                $attributeValue = trim($attributeValue);

                $this->$attributeName = $attributeValue;
            }

            return true;
        }

        return false;
    }

    /**
     * @param $attribute
     * @param $params
     *
     * @return void
     */
    public function validateCaptcha($attribute, $params)
    {
        if (
            !$this->hasErrors() && ($this->captchaEnabled)
            && (!Yii::$app->signUpAuthCaptcha->validate(
                $this->verificationCode
            ))
        ) {
            $this->addError(
                $attribute,
                Translate::_(
                    'idbyii2',
                    '{attribute} is incorrect.',
                    ['attribute' => $this->getAttributeLabel($attribute)]
                ) . ' ' . Translate::_(
                    'idbyii2',
                    'The system detected that you have to be bot. I am sorry but you cannot continue.'
                )
            );
        }
    }
}

################################################################################
#                                End of file                                   #
################################################################################
