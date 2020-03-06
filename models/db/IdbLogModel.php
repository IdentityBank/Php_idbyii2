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
use Yii;
use yii\db\ActiveRecord;

################################################################################
# Class(es)                                                                    #
################################################################################

/**
 * Class IdbLogModel
 *
 * @package idbyii2\models\db
 */
abstract class IdbLogModel extends ActiveRecord
{

    protected $idbSecurity;
    protected $logPassword = 'password';
    protected $blowfishCost = 1;

    /**
     * @return array
     */
    public function rules()
    {
        return [
            [['uid', 'event', 'ip'], 'required'],
            [['event_data'], 'string'],
            [['timestamp'], 'safe'],
            [['uid', 'ip'], 'string', 'max' => 255],
            [['event'], 'string', 'max' => 64],
            [['uid', 'event', 'ip', 'timestamp'], 'unique', 'targetAttribute' => ['uid', 'event', 'ip', 'timestamp']],
        ];
    }

    /**
     * @return array
     */
    public function attributeLabels()
    {
        return
            [
                'uid' => Translate::_('idbyii2', 'User ID'),
                'event' => Translate::_('idbyii2', 'Event'),
                'event_data' => Translate::_('idbyii2', 'Event Data'),
                'ip' => Translate::_('idbyii2', 'IP'),
                'timestamp' => Translate::_('idbyii2', 'Timestamp'),
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
        if (empty($row['uid_hash']) && !empty($row['uid'])) {
            $row['uid_hash'] = $model->generateUidHash($row['uid']);
        }
        if (!empty($row['uid_hash'])) {
            $row['uid'] = $row['uid_hash'];
        }
        if (is_array($row) && !empty($row)) {
            $model->setAttributes($row, false);
        }

        return $model;
    }

    /**
     * @param      $uid
     * @param null $limit
     *
     * @return mixed
     */
    public static function findAllByUid($uid, $limit = null)
    {
        $model = self::instantiate(['uid' => $uid]);

        return self::find()->where(['uid' => $model->uid])->limit($limit)->orderBy(['timestamp' => SORT_DESC])->all();
    }

    /**
     * @return void
     */
    public function afterFind()
    {
        $this->ip = $this->idbSecurity->decryptByPasswordSpeed(base64_decode($this->ip), $this->logPassword);
        parent::afterFind();
    }

    /**
     * @return bool
     */
    public function beforeValidate()
    {
        if (parent::beforeValidate()) {
            $this->ip = base64_encode($this->idbSecurity->encryptByPasswordSpeed($this->ip, $this->logPassword));

            return true;
        }

        return false;
    }

    /**
     * @param $uid
     *
     * @return mixed
     */
    protected function generateUidHash($uid)
    {
        return $this->idbSecurity->secureHash($uid, $this->logPassword, ($this->blowfishCost));
    }

    /**
     * @param       $uid
     * @param array $event_data
     *
     * @return void
     */
    public static function error($uid, $event_data = [])
    {
        $model = self::instantiate(['uid' => $uid]);
        $model->event = 'error';
        $model->event_data = json_encode(
            yii\helpers\ArrayHelper::merge
            (
                [
                    'sessionid' => Yii::$app->session->id,
                ],
                $event_data
            )
        );
        $model->ip = Yii::$app->request->userIP;
        $model->save();
        sleep(1);
    }

    /**
     * @param $uid
     *
     * @return void
     */
    public static function login($uid)
    {
        $model = self::instantiate(['uid' => $uid]);
        $model->event = 'login';
        $model->event_data = json_encode(['sessionid' => Yii::$app->session->id]);
        $model->ip = Yii::$app->request->userIP;
        $model->save();
    }

    /**
     * @param $uid
     *
     * @return void
     */
    public static function logout($uid)
    {
        $model = self::instantiate(['uid' => $uid]);
        $model->event = 'logout';
        $model->event_data = json_encode(['sessionid' => Yii::$app->session->id]);
        $model->ip = Yii::$app->request->userIP;
        $model->save();
    }

    /**
     * Check if is enough errors to send password recovery token.
     *
     * @param $errors
     * @param $limit
     *
     * @return bool
     */
    public static function sendPasswordRecoveryCheck($errors, $limit)
    {
        if (count($errors) >= $limit) {
            foreach ($errors as $error) {
                if ($error->event !== 'error') {
                    return false;
                }
            }
        } else {
            return false;
        }

        return true;
    }

}

################################################################################
#                                End of file                                   #
################################################################################
