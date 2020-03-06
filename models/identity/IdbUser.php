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

namespace idbyii2\models\identity;

################################################################################
# Use(s)                                                                       #
################################################################################

use idbyii2\helpers\IdbMfaHelper;
use Yii;
use yii\base\Model;
use yii\web\IdentityInterface;

################################################################################
# Class(es)                                                                    #
################################################################################

/**
 * Class IdbUser
 *
 * @package idbyii2\models\identity
 */
abstract class IdbUser extends Model implements IdentityInterface
{

    protected static $userAuthKeysRequired = ['userId', 'accountNumber', 'password', 'authKey'];
    protected static $userAuthKeysOptional = ['accountName', 'email', 'phone', 'mfa', 'idbAttributes'];

    // IdentityInterface variables
    public $id;
    public $login;
    public $userId;
    public $accountNumber;
    public $password;
    public $authKey;
    public $mfa;
    public $email;
    public $phone;
    public $auth_tf_type;
    public $isBlocked = true;
    public $isConfirmed = false;
    public $idbAttributes;

    /**
     * @param $userId
     * @param $accountId
     *
     * @return string
     */
    public static function createLogin($userId, $accountId)
    {
        return strtoupper($accountId) . "." . $userId;
    }

    /**
     * @return bool|string|\Webpatser\Uuid\Uuid
     * @throws \Exception
     */
    public function generateMfaSecurityKey()
    {
        return IdbMfaHelper::generateMfaSecurityKey();
    }

    /**
     * @param null $model
     *
     * @return bool
     */
    public function validateMfa($model = null)
    {
        if ($model) {
            if (!empty(Yii::$app->user->identity->mfa)) {
                $model->mfa = Yii::$app->user->identity->mfa;
            }
            $model->mfa = json_decode($model->mfa, true);

            return IdbMfaHelper::validateMfa($model->mfa, $model->code);
        }

        return IdbMfaHelper::validateMfa();
    }

    /**
     * @return mixed
     */
    public function getIdbAttributes()
    {
        return $this->idbAttributes;
    }

    /**
     * @return mixed
     */
    public abstract function clearIdbAttributes();

    /**
     * @param $attributeName
     * @param $attributeValue
     *
     * @return mixed|void
     */
    public abstract function setIdbAttribute($attributeName, $attributeValue);

    /**
     * @param $attributeName
     *
     * @return |null
     */
    public function getIdbAttribute($attributeName)
    {
        try {
            if (
            !empty($this->getIdbAttributes())
            ) {
                $value = json_decode($this->getIdbAttributes(), true);
                if (
                !empty($value[$attributeName])
                ) {
                    return $value[$attributeName];
                }

                return null;
            }
        } catch (Exception $e) {
            Yii::error($e->getMessage());
        } catch (Error $e) {
            Yii::error($e->getMessage());
        }
    }
}

################################################################################
#                                End of file                                   #
################################################################################
