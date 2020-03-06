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
use Exception;
use idbyii2\helpers\IdbAccountNumber;
use idbyii2\validators\IdbNameValidator;
use Yii;
use yii\base\Model;
use yii\helpers\ArrayHelper;

################################################################################
# Class(es)                                                                    #
################################################################################

/**
 * Class IdbLoginForm
 *
 * @package idbyii2\models\form
 */
abstract class IdbLoginForm extends Model
{

    const SHOW_ATTRIBUTES_ERRORS = false;
    public $userId;
    public $accountNumber;
    public $accountPassword;
    public $rememberMe = false;

    protected $_user = false;

    /**
     * @return array
     */
    public function attributeLabels()
    {
        return
            [
                'userId' => Translate::_('idbyii2', 'Login name'),
                'accountNumber' => Translate::_('idbyii2', 'Account number'),
                'accountPassword' => Translate::_('idbyii2', 'Password'),
                'rememberMe' => Translate::_('idbyii2', 'Remember Me'),
            ];
    }

    /**
     * @return mixed
     */
    public function rules()
    {
        return ArrayHelper::merge(
            [
                [['userId', 'accountNumber', 'accountPassword'], 'required'],
                [['userId', 'accountNumber'], 'trim'],
                ['rememberMe', 'boolean'],
                ['accountNumber', 'string', 'length' => IdbAccountNumber::length],
                ['accountNumber', 'validateAccountNumber'],
                ['accountPassword', 'validatePassword'],
            ],
            IdbNameValidator::customRules('userId', $this->getAttributeLabel('userId'))
        );
    }

    /**
     * @param null $attributeNames
     * @param bool $clearErrors
     *
     * @return mixed
     * @throws \Exception
     */
    public function validate($attributeNames = null, $clearErrors = true)
    {
        $classPath = explode('\\', get_class($this));
        $className = array_pop($classPath);
        if (!empty($_REQUEST[$className])) {
            foreach ($_REQUEST[$className] as $attribute => $value) {
                if (!in_array($attribute, $this->attributes())) {
                    throw new Exception('Invalid request.');
                    Yii::$app->end();
                }
            }
        }
        if ($this->rememberMe === '1') {
            $this->rememberMe = true;
        } elseif ($this->rememberMe === '0') {
            $this->rememberMe = false;
        }
        if (!is_bool($this->rememberMe)) {
            $this->rememberMe = false;
        }

        return parent::validate($attributeNames, $clearErrors);
    }

    /**
     * @return bool
     */
    final private function isAccountNumberCheckumValid()
    {
        return (new IdbAccountNumber($this->accountNumber))->isValid();
    }

    /**
     * @param $attribute
     *
     * @return void
     */
    final private function addValidationError($attribute)
    {
        $this->addError(null, Translate::_('idbyii2', 'Login details are incorrect.'));
        if (self::SHOW_ATTRIBUTES_ERRORS) {
            $this->addError(
                $attribute,
                Translate::_(
                    'idbyii2',
                    'Incorrect {attribute}.',
                    ['attribute' => $this->getAttributeLabel($attribute)]
                )
            );
        }
    }

    /**
     * @return void
     */
    final public function addFormError()
    {
        $this->addValidationError(null);
    }

    /**
     * @return null|string
     */
    public function getAccountDestination()
    {
        $idbAccountNumber = (new IdbAccountNumber($this->accountNumber));
        $destination = $idbAccountNumber->getDestination();
        if ($destination) {
            return strval($destination);
        }

        return null;
    }

    /**
     * @param $attribute
     * @param $params
     *
     * @return void
     */
    public function validateAccountNumber($attribute, $params)
    {
        if (!$this->hasErrors()) {
            if (!$this->isAccountNumberCheckumValid()) {
                $this->addValidationError($attribute);
            }
        }
    }

    /**
     * @param $attribute
     * @param $params
     *
     * @return void
     */
    public function validatePassword($attribute, $params)
    {
        if (!$this->hasErrors()) {
            $user = $this->getUser();
            if (!$user || !$user->validatePassword($this->accountPassword)) {
                $this->addValidationError($attribute);
            }
        }
    }

    /**
     * @param float|int $rememberMeTimeout
     *
     * @return bool
     * @throws \Exception
     */
    public function login($rememberMeTimeout = 3600 * 24 * 30)
    {
        if ($this->validate()) {
            return Yii::$app->user->login($this->getUser(), $this->rememberMe ? $rememberMeTimeout : 0);
        }

        return false;
    }

    /**
     * @return string
     */
    public function getClassName()
    {
        return static::class;
    }

    /**
     * @return string
     * @throws \ReflectionException
     */
    public function getShortClassName()
    {
        $reflect = new \ReflectionClass($this);
        return $reflect->getShortName();
    }
}

################################################################################
#                                End of file                                   #
################################################################################
