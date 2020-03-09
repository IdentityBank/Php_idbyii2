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

use app\helpers\PeopleConfig;
use app\helpers\Translate;
use idbyii2\enums\SignUpNamespace;
use idbyii2\helpers\IdbAccountId;
use idbyii2\helpers\IdbAccountNumber;
use idbyii2\helpers\IdbAccountNumberDestination;
use idbyii2\helpers\IdbSecurity;
use idbyii2\helpers\PasswordToken;
use idbyii2\helpers\Uuid;
use idbyii2\models\db\BusinessSignup;
use idbyii2\models\db\BusinessUserData;
use idbyii2\models\db\PeopleUserAccount;
use idbyii2\models\db\PeopleUserData;
use idbyii2\models\db\SearchPeopleUserData;
use idbyii2\models\db\SignupPeople;
use idbyii2\models\idb\IdbBankClientPeople;
use Yii;
use yii\base\NotSupportedException;

################################################################################
# Class(es)                                                                    #
################################################################################

/**
 * Class IdbPeopleUser
 *
 * @package idbyii2\models\identity
 */
class IdbPeopleUser extends IdbUser
{

    public $name;
    public $surname;
    public $mobile;
    public $idbClient;
    public $peopleDbUserId;

    public $businessUserId;
    public $businessAccountid;
    public $businessDatabaseId;
    public $businessDatabaseUserId;
    public $businessAccountName;
    public $businessOrgazniationId;

    protected static $userDataKeysProvider = ['userId', 'accountNumber', 'email', 'phone'];
    protected static $peopleKeys = ['mobile', 'surname', 'name'];

    const DATA_TYPES = [
        'name',
        'surname',
        'mobile',
        'email',
        'businessAccountName'
    ];

    /**
     * @param $id
     *
     * @return array
     */
    public static function getPeopleUserDataKeysProvider($id)
    {
        $userData = new PeopleUserData();
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
            if ($data instanceof PeopleUserData) {
                $key = $data->getKey();
            }
            if (!empty($key) && (in_array($key, self::$userAuthKeysRequired))) {
                $counter++;
            }
        }

        return ($counter == count(self::$userAuthKeysRequired));
    }

    /**
     * @param null $email
     * @param null $mobile
     * @return string
     */
    public function generateToken($email = null, $mobile = null)
    {
        $data = BusinessUserData::getUserDataByKeys(
            $this->id,
            [
                'name',
                'surname',
                'userId',
                'businessAccountName',
            ]
        );

        $tokenData = [];
        foreach ($data as $userData) {
            $tokenData[$userData->key] = $userData->value;
        }


        $tokenData['uid'] = $this->id;
        $tokenData['mobile'] = $mobile ?? $this->mobile;
        $tokenData['email'] = $email ?? $this->email;

        $json = SignupPeople::generateTokenJSON($tokenData);

        return (new PasswordToken())->encodeToken($json);
    }
    /**
     * @param $id
     *
     * @return null|static
     */
    public static function findIdentity($id)
    {
        $userData = new PeopleUserData();
        $userAuthKeysHash = [];
        $userAuthKeys = array_merge(self::$userAuthKeysRequired, self::$userAuthKeysOptional, self::$peopleKeys);
        foreach ($userAuthKeys as $userAuthKey) {
            $userAuthKeysHash[] = $userData->getKeyHash($id, $userAuthKey);
        }
        $userData = PeopleUserData::findAll(['uid' => $id, 'key_hash' => $userAuthKeysHash]);
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
     * @param $uid
     * @param $newPassword
     */
    public static function changePassword($uid, $newPassword)
    {
        $identity = IdbPeopleUser::findIdentity($uid);
        $peopleUserData = new PeopleUserData();


        $password = PeopleUserData::find()->where(
            [
                'uid' => $uid,
                'key_hash' => $peopleUserData->getKeyHash($uid, 'password')
            ]
        )->one();

        $password->value = $peopleUserData->generateSecurePassword(
            $newPassword,
            ((empty($identity)) ? null : $identity->accountNumber)
        );

        $password->save();
    }

    /**
     * @param $login
     *
     * @return null
     */
    public static function findUserAccountByLogin($login)
    {
        if (!empty($login)) {
            return PeopleUserAccount::findOne(
                ['login' => PeopleUserAccount::instantiate()->generateSecureLogin($login)]
            );
        }

        return null;
    }

    /**
     * @param $login
     *
     * @return \idbyii2\models\identity\IdbPeopleUser|null
     */
    public static function findByLogin($login)
    {
        if (!empty($login)) {
            $userAccount = PeopleUserAccount::findOne(
                ['login' => PeopleUserAccount::instantiate()->generateSecureLogin($login)]
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
     * @return \idbyii2\models\identity\IdbPeopleUser|\yii\web\IdentityInterface|null
     * @throws \yii\base\NotSupportedException
     */
    public static function findIdentityByAccessToken($token, $type = null)
    {
        if ((PeopleConfig::get()->isIdentityByAccessTokenEnabled()) && (!empty($token))) {
            $userAccount = PeopleUserAccount::findOne(
                ['access_token' => PeopleUserAccount::instantiate(null)->generateSecureToken($token)]
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
            return ($this->password === PeopleUserData::instantiate()->generateSecurePassword(
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
        $uidUsed = PeopleUserData::instantiate()->isUidUsed($uid);
        while ($uidUsed) {
            $uid = Uuid::uuid5($login . Uuid::uuid4());
            $uidUsed = PeopleUserData::instantiate()->isUidUsed($uid);
        }

        return ((!is_null($uid) && ($uid instanceof Uuid)) ? $uid->toString() : null);
    }

    /**
     * @param \idbyii2\models\db\SignupPeople $model
     *
     * @return array
     */
    public static function createFromSignUpModel(SignupPeople $model)
    {
        $data = (json_decode($model->data, true))[SignUpNamespace::PEOPLE];

        unset($data['smsCode']);
        unset($data['emailCode']);
        unset($data['tryCount']);

        return self::create($data['userId'], $data);
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
                    $destination = IdbAccountNumberDestination::fromId(IdbAccountNumberDestination::people);
                }
                $accountNumber = IdbAccountNumber::generate($destination);
                $accountNumber = $accountNumber->toString();
            }
            $accountNumber = strtoupper($accountNumber);

            $login = self::createLogin($userId, $accountNumber);

            $accountIdUsed = !is_null(
                SearchPeopleUserData::findOneUserData(['key' => 'accountNumber', 'value' => $accountNumber])
            );

            $loginUsed = PeopleUserAccount::instantiate()->isLoginUsed($login);

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
        $model = PeopleUserAccount::instantiate(['login' => $login, 'uid' => $uid]);
        if ($model->validate() && $model->save()) {
            if ((is_array($userData)) && empty($userData['authKey'])) {
                $userData['authKey'] = self::generateAuthKey();
            }
            if ((is_array($userData)) && (self::checkUserAuthKeysRequired($userData))) {
                PeopleUserData::deleteAll(['uid' => $uid]);
                foreach ($userData as $key => $value) {
                    if ($key === 'password') {
                        $value = PeopleUserData::instantiate()->generateSecurePassword(
                            $value,
                            ((empty($userData['accountNumber'])) ? null : $userData['accountNumber'])
                        );
                    }
                    $model = PeopleUserData::instantiate(['uid' => $uid, 'key' => $key, 'value' => $value]);
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
        PeopleUserAccount::deleteByUid($uid);

        return PeopleUserData::deleteAll(['uid' => $uid]);
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
     * @param $peopleDatabaseId - ID for people database
     *
     * @return mixed|string
     * @throws \Exception
     */
    public function getPeopleDbUserId($peopleDatabaseId)
    {
        return IdbAccountId::generatePeopleUserId($peopleDatabaseId, $this->businessUserId);
    }

    /**
     * @throws \Exception
     */
    public function configureIdentityDataForMessages()
    {
        $accuountid = PeopleConfig::get()->getYii2PeopleAccountId();
        Yii::$app->user->identity->idbClient = IdbBankClientPeople::model($accuountid);

        if (!empty(Yii::$app->user->identity->id)) {
            Yii::$app->user->identity->peopleDbUserId = IdbAccountId::generatePeopleUserId(
                $accuountid,
                Yii::$app->user->identity->id
            );
        };
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
            $model = PeopleUserData::instantiate();
            $model = PeopleUserData::find()
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
                $model = PeopleUserData::instantiate();
                $model = PeopleUserData::find()
                                       ->where(['uid' => $uid, 'key_hash' => $model->getKeyHash($uid, $key)])
                                       ->one();
                if (is_null($model)) {
                    $value = json_encode([$attributeName => $attributeValue]);
                    $model = PeopleUserData::instantiate(['uid' => $uid, 'key' => $key, 'value' => $value]);
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
