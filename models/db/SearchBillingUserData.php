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
use idbyii2\models\identity\IdbBillingUser;
use Yii;

################################################################################
# Class(es)                                                                    #
################################################################################

/**
 * Class SearchBillingUserData
 *
 * @package idbyii2\models\db
 */
class SearchBillingUserData extends BillingSearchModel
{

    /**
     * @return string
     */
    public static function tableName()
    {
        return 'p57b_search.billing_user_data';
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
            $model = BillingUserData::find()
                                    ->where(
                                        [
                                            'uid' => $searchModel->uid,
                                            'key_hash' => BillingUserData::instantiate()->getKeyHash(
                                                $searchModel->uid,
                                                $key
                                            )
                                        ]
                                    )->one();
        }

        return $model;
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
     * @var array
     */
    public static $forbiddenKeys =
        [
            'password'
        ];

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
     * @param $uid
     *
     * @return mixed
     */
    protected function generateUidHash($uid)
    {
        return $this->idbSecurity->secureHash($uid, $this->searchPassword, ($this->blowfishCost + 1));
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
     * @return void
     */
    public static function refreshAll()
    {
        Yii::$app->db->createCommand()->truncateTable(self::tableName())->execute();

        $query = BillingUserData::find();
        $totalCount = $query->count();

        for ($offset = 0; $offset < $totalCount; $offset++) {
            $model = $query->offset($offset)->one();
            self::refreshModel($model);
        }
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
        $models = BillingUserData::find()->where(['uid' => $uid])->all();
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
     * @return null
     */
    public function getUserInfo()
    {
        $userInfo = null;
        $identity = IdbBillingUser::findIdentity($this->uid);
        if ($identity) {
            $userInfo = $identity->toString();
        }

        return $userInfo;
    }
}

################################################################################
#                                End of file                                   #
################################################################################
