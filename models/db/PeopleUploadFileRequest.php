<?php

namespace idbyii2\models\db;

use idbyii2\helpers\Translate;
use idbyii2\helpers\Uuid;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "upload_file_request".
 *
 * @property int $id
 * @property string $dbid
 * @property string $pid
 * @property int $upload_limit
 * @property int $uploads
 * @property string $timestamp
 * @property string $type
 * @property string $name
 * @property string $request_uuid
 * @property string $message
 */
class PeopleUploadFileRequest extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'upload_file_request';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['pid', 'type', 'name', 'request_uuid'], 'required'],
            [['pid', 'type', 'name', 'request_uuid'], 'string'],
            [['upload_limit', 'uploads'], 'default', 'value' => null],
            [['upload_limit', 'uploads'], 'integer'],
            [['timestamp'], 'safe'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Translate::_('idbyii2', 'ID'),
            'dbid' => Translate::_('idbyii2', 'Database ID'),
            'pid' => Translate::_('idbyii2', 'Pid'),
            'upload_limit' => Translate::_('idbyii2', 'Upload Limit'),
            'uploads' => Translate::_('idbyii2', 'Uploads'),
            'timestamp' => Translate::_('idbyii2', 'Timestamp'),
            'request_uuid' => Translate::_('idbyii2', 'Request uuid'),
            'type' => Translate::_('idbyii2', 'Type'),
            'name' => Translate::_('idbyii2', 'Name'),
            'message' => Translate::_('idbyii2', 'Message'),
        ];
    }

    /**
     * @param $name
     * @return null|string
     * @throws \Exception
     */
    public static function newRequestUuid($name)
    {
        $uid = Uuid::uuid5($name . (new \DateTime())->getTimestamp());
        $uidUsed = self::isUidUsed($uid);
        while ($uidUsed) {
            $uid = Uuid::uuid5($name . Uuid::uuid4());
            $uidUsed = self::isUidUsed($uid);
        }

        return ((!is_null($uid) && ($uid instanceof Uuid)) ? $uid->toString() : null);
    }


    /**
     * @param        $uid
     * @return bool
     */
    public static function isUidUsed($uid)
    {
        if (!empty($uid)) {
            return !is_null(self::findOne(['request_uuid' => $uid]));
        }

        return false;
    }
}
