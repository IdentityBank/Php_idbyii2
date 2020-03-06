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
class Business2PeopleFormModel extends IdbModel
{

    public $business_user;
    public $people_user;
    public $subject;
    public $message;
    public $expires_at;

    public function rules()
    {
        return [
            [['business_user'], 'required'],
            [['people_user'], 'required'],
            [['subject'], 'required'],
            [['message'], 'required'],
            [['expires_at'], 'safe']
        ];
    }

    public function attributeLabels()
    {
        return [
            'business_user' => Translate::_('idbyii2', 'Business'),
            'people_user' => Translate::_('idbyii2', 'The contact person'),
            'subject' => Translate::_('idbyii2', 'Subject'),
            'message' => Translate::_('idbyii2', 'Message'),
            'expires_at' => Translate::_('idbyii2', 'Expires At'),
        ];
    }
}

################################################################################
#                                End of file                                   #
################################################################################
