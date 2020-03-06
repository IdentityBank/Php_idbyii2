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

use idbyii2\helpers\IdbSecurity;
use Yii;
use yii\db\ActiveRecord;

################################################################################
# Class(es)                                                                    #
################################################################################

/**
 * This is the model class for table "p57b_people.messages_business_people".
 *
 * @property int    $id
 * @property string $issued_at
 * @property string $expires_at
 * @property string $business_user
 * @property string $people_user
 * @property string $messagecontent
 */
class Business2PeopleMessagesModel extends ActiveRecord
{

    private $idbSecurity;
    private $blowfishCost = 1;
    private $dataPassword = 'password';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'p57b_people.messages_business_people';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['issued_at', 'expires_at'], 'safe'],
            [['business_user', 'people_user', 'messagecontent'], 'required'],
            [['messagecontent'], 'string'],
            [['business_user', 'people_user'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'issued_at' => 'Issued At',
            'expires_at' => 'Expires At',
            'business_user' => 'Business',
            'people_user' => 'The contact person',
            'messagecontent' => 'Messagecontent',
        ];
    }

    /**
     * @param array $values
     * @param bool  $safeOnly
     */
    public function setAttributes($values, $safeOnly = true)
    {
        if (is_array($values) && !empty($values)) {
            if (!empty($values['blowfishCost'])) {
                $this->blowfishCost = $values['blowfishCost'];
            }
            if (!empty($values['dataPassword'])) {
                $this->dataPassword = $values['dataPassword'];
            }
        }
        $attributesKeys = array_keys($this->getAttributes());
        $attributes = [];
        foreach ($values as $key => $val) {
            if (in_array($key, $attributesKeys)) {
                $attributes[$key] = $val;
            }
        }
        parent::setAttributes($attributes, $safeOnly);
    }

    /**
     * @return void
     */
    public function init()
    {
        if (!empty(Yii::$app->getModule('business2peoplemessages')->configB2Pmessages)) {
            $this->setAttributes(Yii::$app->getModule('business2peoplemessages')->configB2Pmessages);
        }
        $this->idbSecurity = new IdbSecurity(Yii::$app->security);

        parent::init();
    }

    /**
     * @param bool $insert
     *
     * @return bool
     */
    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            $this->messagecontent = base64_encode(
                $this->idbSecurity->encryptByPasswordSpeed($this->messagecontent, $this->dataPassword)
            );

            return true;
        }

        return false;
    }

    /**
     * @param bool  $insert
     * @param array $changedAttributes
     */
    public function afterSave($insert, $changedAttributes)
    {
        $this->messagecontent = $this->idbSecurity->decryptByPasswordSpeed(
            base64_decode($this->messagecontent),
            $this->dataPassword
        );

        parent::afterSave($insert, $changedAttributes);
    }

    /**
     * @return void
     */
    public function afterFind()
    {
        $this->messagecontent = $this->idbSecurity->decryptByPasswordSpeed(
            base64_decode($this->messagecontent),
            $this->dataPassword
        );

        parent::afterFind();
    }
}

################################################################################
#                                End of file                                   #
################################################################################
