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
use idbyii2\models\db\BusinessPasswordPolicy;
use idbyii2\validators\PasswordValidator;

################################################################################
# Class(es)                                                                    #
################################################################################

/**
 * Class ChangePasswordForm
 *
 * @package idbyii2\models\form
 */
class ChangePasswordForm extends IdbModel
{

    public $oldPassword;
    public $password;
    public $repeatPassword;
    public $passwordPolicy;

    public function __construct()
    {
        $this->passwordPolicy = BusinessPasswordPolicy::getPasswordPolicyJSONByName();
    }

    /**
     * @return array
     */
    public function rules()
    {
        return [
            [['oldPassword'], 'required'],
            [['password'], 'required'],
            [['repeatPassword'], 'required'],
            [
                'repeatPassword',
                'compare',
                'compareAttribute' => 'password',
                'message' => Translate::_('idbyii2', "The passwords do not match.")
            ],
            ['password', PasswordValidator::className()],
        ];
    }

    /**
     * @return array
     */
    public function attributeLabels()
    {
        return [
            'oldPassword' => Translate::_('idbyii2', 'Old Password'),
            'password' => Translate::_('idbyii2', 'New Password'),
            'repeatPassword' => Translate::_('idbyii2', 'Repeat Password'),
        ];
    }

    /**
     * @return array
     */
    public function attributeHints()
    {
        return [
            'password' => $this->attributeHintTemplate(BusinessPasswordPolicy::getHelpByName()),
        ];
    }
}

################################################################################
#                                End of file                                   #
################################################################################
