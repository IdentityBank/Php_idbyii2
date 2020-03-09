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

use app\helpers\BillingConfig;
use app\helpers\Translate;
use idbyii2\helpers\IdbAccountNumber;
use idbyii2\helpers\IdbAccountNumberDestination;
use idbyii2\helpers\IdbSecurity;
use idbyii2\helpers\Uuid;
use idbyii2\models\db\BillingUserAccount;
use idbyii2\models\db\BillingUserData;
use idbyii2\models\db\SearchBillingUserData;
use idbyii2\models\db\SignupBilling;
use idbyii2\models\idb\IdbBankClientBilling;
use Yii;
use yii\base\NotSupportedException;

################################################################################
# Class(es)                                                                    #
################################################################################

/**
 * Class IdbBillingUser
 *
 * @package idbyii2\models\identity
 */
class IdbBillingUser extends IdbUser
{

    /**
     * @param $id
     *
     * @return array
     */
    public static function getBillingUserDataKeysProvider($id)
    {
        $userData = new BillingUserData();
        $userDataKeysProviderHash = [];
        foreach (self::$userDataKeysProvider as $key) {
            $userDataKeysProviderHash[] = $userData->getKeyHash($id, $key);
        }

        return $userDataKeysProviderHash;
    }

    /**
     * @param $userData
     *
     * @return bool
     */
    private static function checkUserAuthKeysRequired($userData)
    {
        $counter = 0;
        foreach ($userData as $key => $data) {
            if ($data instanceof BillingUserData) {
                $key = $data->getKey();
            }
            if (!empty($key) && (in_array($key, self::$userAuthKeysRequired))) {
                $counter++;
            }
        }

        return ($counter == count(self::$userAuthKeysRequired));
    }

    /**
     * @param $id
     *
     * @return null|static
     */
    public static function findIdentity($id)
    {
        $userData = new BillingUserData();
        $userAuthKeysHash = [];
        $userAuthKeys = array_merge(self::$userAuthKeysRequired, self::$userAuthKeysOptional);
        foreach ($userAuthKeys as $userAuthKey) {
            $userAuthKeysHash[] = $userData->getKeyHash($id, $userAuthKey);
        }
        $userData = BillingUserData::findAll(['uid' => $id, 'key_hash' => $userAuthKeysHash]);
        if ((is_array($userData)) && (self::checkUserAuthKeysRequired($userData))) {
            $userBasicAuthentication = [];
            foreach ($userData as $data) {
                if ($data) {
                    $userBasicAuthentication[$data->getKey()] = $data->getValue();
                }
            }
            $userBasicAuthentication['id'] = $id;
            if (!empty(Yii::$app->session)) {
                $session = Yii::$app->session;
                if (!$session->isActive) {
                    $session->open();
                }
                if ($session->isActive) {
                    $userBasicAuthentication['login'] = $session->get('login_' . $id);
                    $session->close();
                }
            }
            if ((is_array($userBasicAuthentication)) && (!empty($userBasicAuthentication))) {
                return new static($userBasicAuthentication);
            }
        }

        return null;
    }

    /**
     * @param $login
     *
     * @return null
     */
    public static function findUserAccountByLogin($login)
    {
        if (!empty($login)) {
            return BillingUserAccount::findOne(
                ['login' => BillingUserAccount::instantiate()->generateSecureLogin($login)]
            );
        }

        return null;
    }

    /**
     * @param $login
     *
     * @return \idbyii2\models\identity\IdbBillingUser|null
     */
    public static function findByLogin($login)
    {
        if (!empty($login)) {
            $userAccount = BillingUserAccount::findOne(
                ['login' => BillingUserAccount::instantiate()->generateSecureLogin($login)]
            );
            if ($userAccount) {
                return self::findIdentity($userAccount->uid);
            }
        }

        return null;
    }

    /**
     * @param mixed $token
     * @param null  $type
     *
     * @return \idbyii2\models\identity\IdbBillingUser|\yii\web\IdentityInterface|null
     * @throws \yii\base\NotSupportedException
     */
    public static function findIdentityByAccessToken($token, $type = null)
    {
        if ((BillingConfig::get()->isIdentityByAccessTokenEnabled()) && (!empty($token))) {
            $userAccount = BillingUserAccount::findOne(
                ['access_token' => BillingUserAccount::instantiate(null)->generateSecureToken($token)]
            );
            if ($userAccount) {
                return self::findIdentity($userAccount->uid);
            }
        } else {
            throw new NotSupportedException(Translate::_('idbyii2', 'Identity By Access Token is not supported.'));
        }

        return null;
    }

    /**
     * @return mixed
     */
    public function getAccountId()
    {
        return $this->aid;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getAuthKey()
    {
        return $this->authKey;
    }

    /**
     * @param $permissionName
     *
     * @return mixed
     */
    public function canOrganization($permissionName)
    {
        return Yii::$app->authManager->checkAccess(
            'oid.' . $this->businessOrgazniationId . '.uid.' . $this->businessUserId,
            $permissionName
        );
    }

    /**
     * @param $permissionName
     *
     * @return mixed
     */
    public function canAccount($permissionName)
    {
        return Yii::$app->authManager->checkAccess(
            'aid.' . $this->businessAccountid . '.uid.' . $this->businessUserId,
            $permissionName
        );
    }

    /**
     * @return mixed
     */
    public static function generateAuthKey()
    {
        $idbSecurity = new IdbSecurity(Yii::$app->security);

        return $idbSecurity->generateRandomString();
    }

    /**
     * @param $authKey
     *
     * @return bool
     */
    public function validateAuthKey($authKey)
    {
        if ((!empty($this->authKey)) && (!empty($authKey))) {
            return $this->authKey === $authKey;
        }

        return false;
    }

    /**
     * @param $password
     *
     * @return bool
     */
    public function validatePassword($password)
    {
        if ((!empty($this->password)) && (!empty($password))) {
            return ($this->password === BillingUserData::instantiate()->generateSecurePassword(
                    $password,
                    $this->accountNumber
                ));
        }

        return false;
    }

    /**
     * @param $login
     *
     * @return null|string
     */
    public static function newUserId($login)
    {
        $uid = Uuid::uuid5($login);
        $uidUsed = BillingUserData::instantiate()->isUidUsed($uid);
        while ($uidUsed) {
            $uid = Uuid::uuid5($login . Uuid::uuid4());
            $uidUsed = BillingUserData::instantiate()->isUidUsed($uid);
        }

        return ((!is_null($uid) && ($uid instanceof Uuid)) ? $uid->toString() : null);
    }

    /**
     * @param                                                   $userId
     * @param                                                   $userData
     * @param null                                              $accountNumber
     * @param \idbyii2\helpers\IdbAccountNumberDestination|null $destination
     *
     * @return array
     */
    public static function create(
        $userId,
        $userData,
        $accountNumber = null,
        IdbAccountNumberDestination $destination = null
    ) {
        $userId = trim($userId);
        $accountNumber = trim($accountNumber);
        do {
            if (empty($accountNumber)) {
                if (empty($destination)) {
                    $destination = IdbAccountNumberDestination::fromId(IdbAccountNumberDestination::billing);
                }
                $accountNumber = IdbAccountNumber::generate($destination);
                $accountNumber = $accountNumber->toString();
            }
            $accountNumber = strtoupper($accountNumber);

            $login = self::createLogin($userId, $accountNumber);

            $accountIdUsed = !is_null(
                SearchBillingUserData::findOneUserData(['key' => 'accountNumber', 'value' => $accountNumber])
            );

            $loginUsed = BillingUserAccount::instantiate()->isLoginUsed($login);

            if (($accountIdUsed || $loginUsed)) {
                $accountNumber = null;
            }
        } while ($accountIdUsed || $loginUsed);
        $userData['accountNumber'] = $accountNumber;

        return self::createIdbUser($login, $userData);
    }

    /**
     * @param $login
     * @param $userData
     *
     * @return array
     */
    private static function createIdbUser($login, $userData)
    {
        $status = ['uid' => null, 'errors' => null];
        $uid = self::newUserId($login);
        $model = BillingUserAccount::instantiate(['login' => $login, 'uid' => $uid]);
        if ($model->validate() && $model->save()) {
            if ((is_array($userData)) && empty($userData['authKey'])) {
                $userData['authKey'] = self::generateAuthKey();
            }
            if ((is_array($userData)) && (self::checkUserAuthKeysRequired($userData))) {
                BillingUserData::deleteAll(['uid' => $uid]);
                foreach ($userData as $key => $value) {
                    if ($key === 'password') {
                        $value = BillingUserData::instantiate()->generateSecurePassword(
                            $value,
                            ((empty($userData['accountNumber'])) ? null : $userData['accountNumber'])
                        );
                    }
                    $model = BillingUserData::instantiate(['uid' => $uid, 'key' => $key, 'value' => $value]);
                    if (!($model->validate() && $model->save())) {
                        $status['errors'] = $model->getErrors();
                        $status['errors'] = [$key => "[$key] : [$value]"];
                        break;
                    }
                }
            } else {
                $status['errors'] =
                    [
                        'userData' =>
                            Translate::_(
                                'idbyii2',
                                'Not all required user data was provided. Required: ' . json_encode(
                                    self::$userAuthKeysRequired
                                )
                            )
                    ];
            }
        } else {
            $status['errors'] = $model->getErrors();
        }

        if (empty($status['errors'])) {
            $status['uid'] = $uid;
        }

        return $status;
    }

    /**
     * @param $uid
     *
     * @return void
     * @throws \Throwable
     * @throws \yii\db\StaleObjectException
     */
    public static function delete($uid)
    {
        BillingUserAccount::deleteByUid($uid);

        return BillingUserData::deleteAll(['uid' => $uid]);
    }

    /**
     * @return string
     */
    public function toString()
    {
        $userInfo = "[$this->userId]";
        if ($this->accountNumber) {
            $userInfo .= " - [$this->accountNumber]";
        }
        if ($this->email) {
            $userInfo .= " - [$this->email]";
        }
        if ($this->mobile) {
            $userInfo .= " - [$this->mobile]";
        }

        return $userInfo;
    }

    /**
     * @return mixed|void
     * @throws \Throwable
     */
    public function clearIdbAttributes()
    {
        try {
            $key = 'idbAttributes';
            $uid = $this->getId();
            $model = BillingUserData::instantiate();
            $model = BillingUserData::find()
                                    ->where(['uid' => $uid, 'key_hash' => $model->getKeyHash($uid, $key)])
                                    ->one();
            if ($model) {
                if (!$model->delete()) {
                    Yii::error(json_encode($model->getErrors()));
                }
            }
        } catch (Exception $e) {
            Yii::error($e->getMessage());
        } catch (Error $e) {
            Yii::error($e->getMessage());
        }
    }

    /**
     * @param $attributeName
     * @param $attributeValue
     *
     * @return mixed|void
     */
    public function setIdbAttribute($attributeName, $attributeValue)
    {
        try {
            if (
                !empty($attributeName)
                && !empty($attributeValue)
            ) {
                $key = 'idbAttributes';
                $uid = $this->getId();
                $model = BillingUserData::instantiate();
                $model = BillingUserData::find()
                                        ->where(['uid' => $uid, 'key_hash' => $model->getKeyHash($uid, $key)])
                                        ->one();
                if (is_null($model)) {
                    $value = json_encode([$attributeName => $attributeValue]);
                    $model = BillingUserData::instantiate(['uid' => $uid, 'key' => $key, 'value' => $value]);
                } else {
                    $value = json_decode($model->value, true);
                    if (
                        empty($value)
                        || !is_array($value)
                    ) {
                        $value = json_encode([$attributeName => $attributeValue]);
                    } else {
                        $value[$attributeName] = $attributeValue;
                    }
                    $model->setAttributes(['uid' => $uid, 'key' => $key, 'value' => $value]);
                }
                if (
                    !$model->validate()
                    || !$model->save()
                ) {
                    Yii::error(json_encode($model->getErrors()));
                }
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
