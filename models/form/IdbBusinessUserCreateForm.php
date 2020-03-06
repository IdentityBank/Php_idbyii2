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
use idbyii2\models\identity\IdbBusinessUser;
use idbyii2\validators\PasswordValidator;
use yii\helpers\ArrayHelper;

################################################################################
# Class(es)                                                                    #
################################################################################

/**
 * Class IdbBusinessUserCreateForm
 *
 * @package idbyii2\models\form
 */
class IdbBusinessUserCreateForm extends IdbModel
{

    public $uid = null;
    public $userId;
    public $accountNumber;
    public $password;
    public $aid = null;

    /**
     * @return mixed
     */
    public function rules()
    {
        return ArrayHelper::merge(
            (new IdbBusinessLoginForm())->rulesUserId(),
            [
                [['userId', 'password'], 'required'],
                ['password', PasswordValidator::className()],
            ]
        );
    }

    /**
     * @return array
     */
    public function attributeLabels()
    {
        $modelIdbLoginForm = new IdbBusinessLoginForm();

        return $modelIdbLoginForm->attributeLabels();
    }

    /**
     * @param $insert
     *
     * @return bool
     */
    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            if (empty($this->password)) {
                $idbSecurity = new IdbSecurity(Yii::$app->security);
                $this->password = $idbSecurity->generateRandomString();
            }

            return true;
        }

        return false;
    }

    /**
     * @param bool $runValidation
     * @param null $attributeNames
     *
     * @return bool
     */
    public function save($runValidation = true, $attributeNames = null)
    {
        $userData =
            [
                'userId' => $this->userId,
                'password' => $this->password,
            ];
        $createIdbUserStatus = IdbBusinessUser::create($this->userId, $userData);

        if (empty($createIdbUserStatus['uid'])) {
            $this->addError('userId', Translate::_('idbyii2', 'Cannot create user!'));
            if (!empty($createIdbUserStatus['errors']) && is_array($createIdbUserStatus['errors'])) {
                foreach ($createIdbUserStatus['errors'] as $error) {
                    $this->addError('userId', json_encode($error));
                }
            }

            return false;
        }
        $this->uid = $createIdbUserStatus['uid'];

        return $this;
    }

    /**
     * @return array
     */
    public function attributeHints()
    {
        return
            [
                'password' => $this->attributeHintTemplate(BusinessPasswordPolicy::getHelpByName()),
            ];
    }
}

################################################################################
#                                End of file                                   #
################################################################################
