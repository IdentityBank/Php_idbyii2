<?php

namespace idbyii2\models\db;

use Yii;

/**
 * This is the model class for table "requests_files".
 *
 * @property string $request_id
 * @property string $oid
 * @property string $timestamp
 */
class PeopleRequestsFiles extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'requests_files';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['request_id', 'oid'], 'required'],
            [['request_id', 'oid'], 'string'],
            [['oid'], 'default', 'value' => null],
            [['timestamp'], 'safe'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'request_id' => 'Request ID',
            'oid' => 'Oid',
            'timestamp' => 'Timestamp',
        ];
    }
}
