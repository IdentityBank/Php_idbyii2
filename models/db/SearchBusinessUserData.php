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

namespace idbyii2\models\db;

################################################################################
# Use(s)                                                                       #
################################################################################

use app\helpers\Translate;
use idbyii2\models\identity\IdbBusinessUser;
use Yii;

################################################################################
# Class(es)                                                                    #
################################################################################

/**
 * Class SearchBusinessUserData
 *
 * @package idbyii2\models\db
 */
class SearchBusinessUserData extends BusinessSearchModel
{

    /**
     * @var array
     */
    public static $forbiddenKeys = [
        'password'
    ];

    /**
     * @param $searchParams
     *
     * @return null
     */
    public static function findOneUserData($searchParams)
    {
        $model = $key = null;
        $searchModel = new self;
        $searchParamsHash = [];
        if (!empty($searchParams['key'])) {
            $key = $searchParams['key'];
            $searchParamsHash['key_hash'] = $searchModel->generateKeyHash($searchParams['key']);
        }
        if (!empty($searchParams['value'])) {
            $searchParamsHash['value_hash'] = $searchModel->generateValueHash($searchParams['value']);
        }
        $searchModel = self::find()->where($searchParamsHash)->one();
        if ($searchModel) {
            $model = BusinessUserData::find()
                                     ->where(
                                         [
                                             'uid' => $searchModel->uid,
                                             'key_hash' => BusinessUserData::instantiate()->getKeyHash(
                                                 $searchModel->uid,
                                                 $key
                                             )
                                         ]
                                     )->one();
        }

        return $model;
    }

    /**
     * @param $key
     *
     * @return mixed
     */
    protected function generateKeyHash($key)
    {
        return $this->idbSecurity->secureHash($key, $this->searchPassword, $this->blowfishCost);
    }

    /**
     * @param $value
     *
     * @return mixed
     */
    protected function generateValueHash($value)
    {
        return $this->idbSecurity->secureHash($value, $this->searchPassword, ($this->blowfishCost + 2));
    }

    /**
     * Get all data by keys, optional uid, supported arrays.
     *
     * @param      $keys
     * @param null $uid
     *
     * @return array
     */
    public static function findAllByKeys($keys, $uid = null)
    {
        $searchModel = new self;
        if (!empty($uid)) {
            if (gettype($uid) === "string") {
                $uid = $searchModel->generateUidHash($uid);
            } else {
                foreach ($uid as $key => $id) {
                    $uid[$key] = $searchModel->generateUidHash($id);
                }
            }
        }

        $hashes = [];
        foreach ($keys as $key) {
            $hashes[$key]['key_hash'] = $searchModel->generateKeyHash($key);
        }

        foreach ($hashes as $key => $value) {
            if (!empty($uid)) {
                $hashes[$key]['data'] = self::find()->where(
                    [
                        'key_hash' => $value['key_hash'],
                        'uid_hash' => $uid
                    ]
                )->all();
            } else {
                $hashes[$key]['data'] = self::find()->where(['key_hash' => $value['key_hash']])->all();
            }
        }

        $usersData = [];
        foreach ($hashes as $key => $value) {
            if (empty($value['data'])) {
                continue;
            }
            foreach ($value['data'] as $searchModel) {
                $usersData[$key] [] = BusinessUserData::find()
                                                      ->where(
                                                          [
                                                              'uid' => $searchModel->uid,
                                                              'key_hash' => BusinessUserData::instantiate()->getKeyHash(
                                                                  $searchModel->uid,
                                                                  $key
                                                              )
                                                          ]
                                                      )->one();
            }
        }

        return $usersData;
    }

    /**
     * @param $uid
     *
     * @return mixed
     */
    protected function generateUidHash($uid)
    {
        return $this->idbSecurity->secureHash($uid, $this->searchPassword, ($this->blowfishCost + 1));
    }

    /**
     * @return void
     */
    public static function refreshAll()
    {
        Yii::$app->db->createCommand()->truncateTable(self::tableName())->execute();

        $query = BusinessUserData::find();
        $totalCount = $query->count();

        for ($offset = 0; $offset < $totalCount; $offset++) {
            $model = $query->offset($offset)->one();
            self::refreshModel($model);
        }
    }

    /**
     * @return string
     */
    public static function tableName()
    {
        return 'p57b_search.business_user_data';
    }

    /**
     * @param $model
     *
     * @return void
     */
    private static function refreshModel($model)
    {
        if (!empty($model->key) && !in_array($model->key, self::$forbiddenKeys)) {
            $searchModel = self::instantiate
            (
                [
                    'uid' => $model->uid,
                    'key' => $model->key,
                    'value' => $model->value,
                ]
            );
            $searchModel->save();
        }
    }

    /**
     * @param null $row
     *
     * @return static
     */
    public static function instantiate($row = null)
    {
        $model = new static();
        if (empty($row['key_hash']) && !empty($row['key'])) {
            $row['key_hash'] = $model->generateKeyHash($row['key']);
        }
        if (empty($row['value_hash']) && !empty($row['value'])) {
            $row['value_hash'] = $model->generateValueHash($row['value']);
        }
        if (empty($row['uid_hash']) && !empty($row['uid'])) {
            $row['uid_hash'] = $model->generateUidHash($row['uid']);
        }
        if (is_array($row) && !empty($row)) {
            $model->setAttributes($row, false);
        }

        return $model;
    }

    /**
     * @param $uid
     *
     * @return void
     */
    public static function refreshUser($uid)
    {
        $searchModel = new static
        (
            [
                'uid' => $uid,
            ]
        );
        self::deleteAll(['uid_hash' => $searchModel->generateUidHash($uid)]);
        $models = BusinessUserData::find()->where(['uid' => $uid])->all();
        foreach ($models as $model) {
            self::refreshModel($model);
        }
    }

    /**
     * @param $uid
     *
     * @return void
     */
    public static function clearUser($uid)
    {
        $searchModel = new static
        (
            [
                'uid' => $uid,
            ]
        );
        self::deleteAll(['uid_hash' => $searchModel->generateUidHash($uid)]);
    }

    /**
     * @param $uid
     * @param $key
     * @param $value
     *
     * @return void
     */
    public static function addUserKeyValue($uid, $key, $value)
    {
        if (!empty($key) && !in_array($key, self::$forbiddenKeys)) {
            self::clearUserKey($uid, $key);
            $searchModel = self::instantiate
            (
                [
                    'uid' => $uid,
                    'key' => $key,
                    'value' => $value,
                ]
            );
            $searchModel->save();
        }
    }

    /**
     * @param $uid
     * @param $key
     *
     * @return void
     */
    public static function clearUserKey($uid, $key)
    {
        if (!empty($key) && !in_array($key, self::$forbiddenKeys)) {
            $searchModel = self::instantiate
            (
                [
                    'uid' => $uid,
                    'key' => $key,
                ]
            );
            $searchModel = self::findOne(['uid_hash' => $searchModel->uid_hash, 'key_hash' => $searchModel->key_hash]);
            if ($searchModel) {
                $searchModel->delete();
            }
        }
    }

    /**
     * @return array
     */
    public function rules()
    {
        return [
            [['key_hash', 'value_hash', 'uid_hash', 'uid'], 'required'],
            [['key_hash', 'value_hash', 'uid_hash', 'uid'], 'string', 'max' => 255],
            [
                ['key_hash', 'value_hash', 'uid_hash'],
                'unique',
                'targetAttribute' => ['key_hash', 'value_hash', 'uid_hash']
            ],
        ];
    }

    /**
     * @return array
     */
    public function attributeLabels()
    {
        return
            [
                'key_hash' => Translate::_('idbyii2', 'Key Hash'),
                'value_hash' => Translate::_('idbyii2', 'Value Hash'),
                'uid_hash' => Translate::_('idbyii2', 'UID Hash'),
                'uid' => Translate::_('idbyii2', 'User ID'),
            ];
    }

    /**
     * @return void
     */
    public function afterFind()
    {
        $this->uid = $this->idbSecurity->decryptByPasswordSpeed(
            base64_decode($this->uid),
            $this->key_hash . $this->searchPassword
        );
        parent::afterFind();
    }

    /**
     * @param $insert
     *
     * @return bool
     */
    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            $this->uid = base64_encode(
                $this->idbSecurity->encryptByPasswordSpeed($this->uid, $this->key_hash . $this->searchPassword)
            );

            return true;
        }

        return false;
    }

    /**
     * @return null|string
     */
    public function getUserInfo()
    {
        $userInfo = null;
        $identity = IdbBusinessUser::findIdentity($this->uid);
        if ($identity) {
            $userInfo = $identity->toString();
        }

        return $userInfo;
    }
}

################################################################################
#                                End of file                                   #
################################################################################
