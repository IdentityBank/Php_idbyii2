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

use app\helpers\AccessManagerHelper;
use app\helpers\Translate;
use Error;
use Exception;
use idbyii2\enums\SignUpNamespace;
use idbyii2\helpers\AccessManager;
use idbyii2\helpers\IdbAccountId;
use idbyii2\helpers\IdbAccountNumber;
use idbyii2\helpers\IdbAccountNumberDestination;
use idbyii2\helpers\IdbSecurity;
use idbyii2\helpers\PasswordToken;
use idbyii2\helpers\Uuid;
use idbyii2\models\db\BusinessAccount;
use idbyii2\models\db\BusinessDatabase;
use idbyii2\models\db\BusinessDatabaseUser;
use idbyii2\models\db\BusinessOrganization;
use idbyii2\models\db\BusinessSignup;
use idbyii2\models\db\BusinessUserAccount;
use idbyii2\models\db\BusinessUserData;
use idbyii2\models\db\SearchBusinessUserData;
use Yii;
use yii\base\NotSupportedException;
use yii\helpers\ArrayHelper;

################################################################################
# Class(es)                                                                    #
################################################################################

/**
 * Class IdbBusinessUser
 *
 * @package idbyii2\models\identity
 */
class IdbBusinessUser extends IdbUser
{

    // IDB variables
    public $oid;
    public $aid;
    public $dbid;
    public $mobile;

    public $oidInfo;
    public $aidInfo;
    public $dbidInfo;

    /**
     * @var array - all available user databases
     */
    private $userDatabases = [];
    /**
     * @var array - user databases for selected account
     */
    private $databases = [];
    /**
     * @var null - specify custom name for your account
     */
    public $accountName = null;

    protected static $userDataKeysProvider = ['userId', 'accountNumber', 'accountName', 'email', 'phone'];

    /**
     * @param $id
     *
     * @return array
     */
    public static function getUserDataKeysProvider($id)
    {
        $userData = new BusinessUserData();
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
            if ($data instanceof BusinessUserData) {
                $key = $data->getKey();
            }
            if (!empty($key) && (in_array($key, self::$userAuthKeysRequired))) {
                $counter++;
            }
        }

        return ($counter == count(self::$userAuthKeysRequired));
    }

    /**
     * @param int|string $id
     * @param null       $aid
     * @param null       $dbid
     *
     * @return \idbyii2\models\identity\IdbBusinessUser|\yii\web\IdentityInterface|null
     */
    public static function findIdentity($id, $aid = null, $dbid = null)
    {
        $userData = new BusinessUserData();
        $userAuthKeysHash = [];
        $userAuthKeys = array_merge(
            self::$userAuthKeysRequired,
            self::$userAuthKeysOptional,
            ['accountName', 'aid', 'oid', 'dbid', 'mobile']
        );
        foreach ($userAuthKeys as $userAuthKey) {
            $userAuthKeysHash[] = $userData->getKeyHash($id, $userAuthKey);
        }
        $userData = BusinessUserData::findAll(['uid' => $id, 'key_hash' => $userAuthKeysHash]);
        if ((is_array($userData)) && (self::checkUserAuthKeysRequired($userData))) {
            $userBasicAuthentication = [];
            foreach ($userData as $data) {
                if ($data) {
                    $userBasicAuthentication[$data->getKey()] = $data->getValue();
                }
            }
            if (empty($userBasicAuthentication['accountName'])) {
                $userBasicAuthentication['accountName'] = $userBasicAuthentication['userId'];
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

            if (
                (is_array($userBasicAuthentication))
                && (!empty($userBasicAuthentication))
            ) {
                if (
                    !empty($userBasicAuthentication['oid'])
                    && !empty($userBasicAuthentication['aid'])
                    && !empty($userBasicAuthentication['dbid'])
                ) {
                    $identity = new static($userBasicAuthentication);
                    $identity->oid = $userBasicAuthentication['oid'];
                    $identity->aid = $userBasicAuthentication['aid'];
                    $identity->dbid = $userBasicAuthentication['dbid'];
                    $isValidDatabase = $identity->validateUserDatabase();
                    if (!$isValidDatabase) {
                        AccessManager::resetUserDatabase($identity->id);
                        Yii::$app->user->logout();
                        $identity = null;

                    }
                    if (!empty($identity)) {
                        $identity->userDatabases = BusinessDatabaseUser::find()
                                                                       ->select('dbid')
                                                                       ->where(['uid' => $id])
                                                                       ->asArray()
                                                                       ->all();

                        $identity->userDatabases = ArrayHelper::getColumn($identity->userDatabases, 'dbid');
                        $identity->databases = BusinessDatabase::find()
                                                               ->select(['dbid', 'name', 'description'])
                                                               ->orderBy(
                                                                   ['name' => SORT_ASC]
                                                               )
                                                               ->where(
                                                                   [
                                                                       'aid' => $identity->aid,
                                                                       'dbid' => $identity->userDatabases
                                                                   ]
                                                               )
                                                               ->asArray()
                                                               ->all();

                        $identity->oidInfo = BusinessOrganization::find()->select(['oid', 'name'])->where(
                            ['oid' => $identity->oid]
                        )->asArray()->one();
                        $identity->aidInfo = BusinessAccount::find()->select(['oid', 'aid', 'name'])->where(
                            ['oid' => $identity->oid, 'aid' => $identity->aid]
                        )->asArray()->one();
                        $identity->dbidInfo = BusinessDatabase::find()->select(['aid', 'dbid', 'name'])->where(
                            ['dbid' => $identity->dbid, 'aid' => $identity->aid]
                        )->asArray()->one();
                    }
                } else {
                    $identity = new static($userBasicAuthentication);
                    $identity->changeUserDatabase();
                }

                return $identity;
            }
        }

        return null;
    }

    /**
     * @param null $uid
     * @param null $oid
     * @param null $aid
     * @param null $dbid
     */
    protected function changeUserDatabase($uid = null, $oid = null, $aid = null, $dbid = null)
    {
        if (empty($uid)) {
            $uid = $this->id;
        }
        if (empty($oid)) {
            $oid = $this->oid;
        }
        if (empty($aid)) {
            $aid = $this->aid;
        }
        if (empty($dbid)) {
            $dbid = $this->dbid;
        }

        $userDb = AccessManager::resetUserDatabase($uid, $oid, $aid, $dbid);
        $this->oid = $userDb['oid'] ?? null;
        $this->aid = $userDb['aid'] ?? null;
        $this->dbid = $userDb['dbid'] ?? null;

        $isValidDatabase = $this->validateUserDatabase();
        if (!$isValidDatabase) {
            $userDb = AccessManager::resetUserDatabase($uid);
            $this->oid = $userDb['oid'] ?? null;
            $this->aid = $userDb['aid'] ?? null;
            $this->dbid = $userDb['dbid'] ?? null;
        }
    }

    /**
     * @param null $uid
     * @param null $oid
     * @param null $aid
     * @param null $dbid
     *
     * @return bool
     */
    protected function validateUserDatabase($uid = null, $oid = null, $aid = null, $dbid = null)
    {
        if (empty($uid)) {
            $uid = $this->id;
        }
        if (empty($oid)) {
            $oid = $this->oid;
        }
        if (empty($aid)) {
            $aid = $this->aid;
        }
        if (empty($dbid)) {
            $dbid = $this->dbid;
        }

        return AccessManager::isValidDatabase($uid, $oid, $aid, $dbid);
    }

    /**
     * @param $login
     *
     * @return \idbyii2\models\db\BusinessUserAccount|null
     */
    public static function findUserAccountByLogin($login)
    {
        if (!empty($login)) {
            return BusinessUserAccount::findOne(
                ['login' => BusinessUserAccount::instantiate()->generateSecureLogin($login)]
            );
        }

        return null;
    }

    /**
     * @param $uid
     * @param $newPassword
     */
    public static function changePassword($uid, $newPassword)
    {
        $identity = IdbBusinessUser::findIdentity($uid);
        $businessUserData = new BusinessUserData();


        $password = BusinessUserData::find()->where(
            [
                'uid' => $uid,
                'key_hash' => $businessUserData->getKeyHash($uid, 'password')
            ]
        )->one();

        $password->value = $businessUserData->generateSecurePassword(
            $newPassword,
            ((empty($identity)) ? null : $identity->accountNumber)
        );

        $password->save();
    }

    /**
     * @param $login
     *
     * @return \idbyii2\models\identity\IdbBusinessUser|\yii\web\IdentityInterface|null
     */
    public static function findByLogin($login)
    {
        if (!empty($login)) {
            $userAccount = BusinessUserAccount::findOne(
                ['login' => BusinessUserAccount::instantiate()->generateSecureLogin($login)]
            );
            if ($userAccount) {
                return self::findIdentity($userAccount->uid);
            }
        }

        return null;
    }

    /**
     * @param null $email
     * @param null $mobile
     *
     * @return string
     */
    public function generateToken($email = null, $mobile = null)
    {
        $data = BusinessUserData::getUserDataByKeys(
            $this->id,
            [
                'firstname',
                'lastname',
                'initials',
                'name',
            ]
        );

        $tokenData = [];
        foreach ($data as $userData) {
            $tokenData[$userData->key] = $userData->value;
        }
        $tokenData['uid'] = $this->id;
        $tokenData['mobile'] = $mobile ?? $this->mobile;
        $tokenData['email'] = $email ?? $this->email;

        $json = BusinessSignup::generateTokenJSON($tokenData);

        return (new PasswordToken())->encodeToken($json);
    }

    /**
     * @param mixed $token
     * @param null  $type
     *
     * @return \idbyii2\models\identity\IdbBusinessUser|\yii\web\IdentityInterface|null
     * @throws \yii\base\NotSupportedException
     */
    public static function findIdentityByAccessToken($token, $type = null)
    {
        if ((BusinessConfig::get()->isIdentityByAccessTokenEnabled()) && (!empty($token))) {
            $userAccount = BusinessUserAccount::findOne(
                ['access_token' => BusinessUserAccount::instantiate(null)->generateSecureToken($token)]
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
     * @return int|string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getAuthKey()
    {
        return $this->authKey;
    }

    /**
     * @param $permissionName
     *
     * @return bool
     * @throws \Exception
     */
    public function canOrganization($permissionName)
    {
        return Yii::$app->authManager->checkAccess(
            IdbAccountId::generateAssignOrganization($this->oid, $this->id),
            $permissionName
        );
    }

    /**
     * @param $permissionName
     *
     * @return bool
     * @throws \Exception
     */
    public function canAccount($permissionName)
    {
        return Yii::$app->authManager->checkAccess(
            IdbAccountId::generateAssignAccount($this->oid, $this->aid, $this->id),
            $permissionName
        );
    }

    /**
     * @param $permissionName
     *
     * @return bool
     * @throws \Exception
     */
    public function canDatabase($permissionName)
    {
        return Yii::$app->authManager->checkAccess(
            IdbAccountId::generateAssignDatabase($this->oid, $this->aid, $this->dbid, $this->id),
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
     * @param string $authKey
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
            return ($this->password === BusinessUserData::instantiate()->generateSecurePassword(
                    $password,
                    $this->accountNumber
                ));
        }

        return false;
    }

    /**
     * @param $login
     *
     * @return string|null
     */
    public static function newUserId($login)
    {
        $uid = Uuid::uuid5($login);
        $uidUsed = BusinessUserData::instantiate()->isUidUsed($uid);
        while ($uidUsed) {
            $uid = Uuid::uuid5($login . Uuid::uuid4());
            $uidUsed = BusinessUserData::instantiate()->isUidUsed($uid);
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
                    $destination = IdbAccountNumberDestination::fromId(IdbAccountNumberDestination::business);
                }
                $accountNumber = IdbAccountNumber::generate($destination);
                $accountNumber = $accountNumber->toString();
            }
            $accountNumber = strtoupper($accountNumber);
            $login = self::createLogin($userId, $accountNumber);

            $accountIdUsed = !is_null(
                SearchBusinessUserData::findOneUserData(['key' => 'accountNumber', 'value' => $accountNumber])
            );
            $loginUsed = BusinessUserAccount::instantiate()->isLoginUsed($login);
            if (($accountIdUsed || $loginUsed)) {
                $accountNumber = null;
            }
        } while ($accountIdUsed || $loginUsed);
        $userData['accountNumber'] = $accountNumber;

        return self::createIdbUser($login, $userData);
    }

    /**
     * @param \idbyii2\models\db\BusinessSignup $model
     *
     * @return array
     */
    public static function createFromSignUpModel(BusinessSignup $model)
    {
        $data = (json_decode($model->data, true))[SignUpNamespace::BUSINESS];

        unset($data['smsCode']);
        unset($data['emailCode']);
        unset($data['tryCount']);
        $data['userId'] = $data['name'];

        return self::create($data['name'], $data);
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
        $model = BusinessUserAccount::instantiate(['login' => $login, 'uid' => $uid]);
        if ($model->validate() && $model->save()) {
            if ((is_array($userData)) && empty($userData['authKey'])) {
                $userData['authKey'] = self::generateAuthKey();
            }
            if ((is_array($userData)) && (self::checkUserAuthKeysRequired($userData))) {
                BusinessUserData::deleteAll(['uid' => $uid]);
                foreach ($userData as $key => $value) {
                    if ($key === 'password') {
                        $value = BusinessUserData::instantiate()->generateSecurePassword(
                            $value,
                            ((empty($userData['accountNumber'])) ? null : $userData['accountNumber'])
                        );
                    }
                    $model = BusinessUserData::instantiate(['uid' => $uid, 'key' => $key, 'value' => $value]);
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
     */
    public static function delete($uid)
    {
        BusinessUserAccount::deleteByUid($uid);

        return BusinessUserData::deleteAll(['uid' => $uid]);
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
        if ($this->phone) {
            $userInfo .= " - [$this->phone]";
        }

        return $userInfo;
    }

    /**
     * @return mixed|string
     * @throws \Exception
     */
    public function getBusinessAccountId()
    {
        return IdbAccountId::generateBusinessAccountId($this->oid, $this->aid);
    }

    /**
     * @return mixed|string
     * @throws \Exception
     */
    public function getBusinessDbId()
    {
        return IdbAccountId::generateBusinessDbId($this->oid, $this->aid, $this->dbid);
    }

    /**
     * @param $databaseUserId
     *
     * @return mixed|string
     * @throws \Exception
     */
    public function getBusinessDbUserId($databaseUserId)
    {
        return IdbAccountId::generateBusinessDbUserId($this->oid, $this->aid, $this->dbid, $databaseUserId);
    }

    /**
     * @return array
     */
    public function getUserCurrentDatabases()
    {
        return $this->databases;
    }

    /**
     * @return array
     */
    public function getAllUserDatabases()
    {
        return $this->userDatabases;
    }

    /**
     * @throws \Exception
     */
    public function resetUserDatabases()
    {
        $this->dbid = null;
        $this->dbidInfo = null;
        $this->userDatabases = null;
        $this->databases = null;
        $userDb = AccessManager::resetUserDatabase($this->id);
        if (!empty($userDb['dbid'])) {
            $this->dbid = $userDb['dbid'];
        }
        $isValidDatabase = $this->validateUserDatabase();
        if (!$isValidDatabase) {
            AccessManagerHelper::createDatabase($this->id, $this->userId);
            AccessManager::resetUserDatabase($this->id);
        }
    }

    /**
     * @return bool
     */
    public function isUserDatabaseUsedForApproved()
    {
        return $this->getIdbAttribute('UserDatabaseUsedForApproved') === 'IDB_AUDIT_LOG';
    }

    /**
     * Set audit log flag
     */
    public function setUserDatabaseUsedForApproved()
    {
        $this->setIdbAttribute('UserDatabaseUsedForApproved', 'IDB_AUDIT_LOG');
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
            $model = BusinessUserData::instantiate();
            $model = BusinessUserData::find()
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
                $model = BusinessUserData::instantiate();
                $model = BusinessUserData::find()
                                         ->where(['uid' => $uid, 'key_hash' => $model->getKeyHash($uid, $key)])
                                         ->one();
                if (is_null($model)) {
                    $value = json_encode([$attributeName => $attributeValue]);
                    $model = BusinessUserData::instantiate(['uid' => $uid, 'key' => $key, 'value' => $value]);
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
