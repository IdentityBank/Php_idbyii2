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

use idbyii2\helpers\Translate;
use Yii;
use yii\db\ActiveRecord;

################################################################################
# Class(es)                                                                    #
################################################################################

/**
 * This is the model class for table "p57b_log.idb_audit_log".
 *
 * @property int    $id
 * @property int    $order
 * @property string $tag
 * @property string $portal_uuid
 * @property string $message
 */
class IdbAuditMessage extends ActiveRecord
{

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'p57b_business.idb_audit_message';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['tag', 'portal_uuid', 'message'], 'string'],
            [['portal_uuid', 'message'], 'required'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'tag' => 'Tag',
            'order' => 'Order',
            'portal_uuid' => 'Portal Uuid',
            'message' => 'Message',
        ];
    }

    /**
     * @return void
     */
    public function afterFind()
    {
        if(!empty($this->message)) {
            $this->message = Translate::_('idbexternal', $this->message);
        }
        parent::afterFind();
    }

    /**
     * @param $message
     *
     * @throws \Throwable
     * @throws \yii\db\StaleObjectException
     */
    static function saveMessage($message)
    {
        $messages = self::find()->where(['portal_uuid' => Yii::$app->user->identity->id])->orderBy('order')->all();

        $newMessage = new self();
        $newMessage->portal_uuid = Yii::$app->user->identity->id;
        $newMessage->order = 1;
        $newMessage->message = $message;
        $newMessage->save();

        $similar = false;
        foreach ($messages as $m) {
            $percent = 0;
            similar_text($m->message, $message, $percent);
            if (intval($percent) >= 85) {
                $m->delete();
                $similar = true;
                break;
            } else {
                $m->order = $m->order + 1;
                $m->save();
            }
        }
        if (!$similar && count($messages) >= 11 && !empty($message[count($messages) - 1])) {
            $message[count($messages) - 1]->delete();
        }
    }
}

################################################################################
#                                End of file                                   #
################################################################################
