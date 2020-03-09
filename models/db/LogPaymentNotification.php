<?php

namespace app\models;

use idbyii2\models\db\IdbLogModel;

/**
 * This is the model class for table "p57b_log.idb_payment_notification".
 *
 * @property int    $id
 * @property string $data
 */
class LogPaymentNotification extends IdbLogModel
{

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'p57b_log.idb_payment_notification';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['data'], 'required'],
            [['data'], 'string'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'data' => 'Data',
        ];
    }
}
