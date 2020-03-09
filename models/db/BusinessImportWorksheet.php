<?php

namespace idbyii2\models\db;

use idbyii2\helpers\Translate;

/**
 * This is the model class for table "import_worksheet".
 *
 * @property int                               $id
 * @property string                            $uid
 * @property string                            $aid
 * @property string                            $oid
 * @property string                            $dbid
 * @property string                            $name
 * @property int                               $file_id
 * @property int                               $worksheet_id
 * @property string                            $status
 * @property bool                              $send_invitations
 * @property bool                              $import_attributes
 * @property bool                              $steps
 *
 * @property \idbyii2\models\db\BusinessImport $file
 */
class BusinessImportWorksheet extends IdbModel
{

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'import_worksheet';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['name', 'file_id', 'worksheet_id', 'status', 'uid'], 'required'],
            [['file_id'], 'default', 'value' => null],
            [['file_id', 'worksheet_id'], 'integer'],
            [['name', 'status'], 'string', 'max' => 255],
            [['import_attributes'], 'string'],
            [
                ['file_id'],
                'exist',
                'skipOnError' => true,
                'targetClass' => BusinessImport::className(),
                'targetAttribute' => ['file_id' => 'id']
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Translate::_('idbyii2', 'ID'),
            'name' => Translate::_('idbyii2', 'Sheet Name'),
            'file_id' => Translate::_('idbyii2', 'File ID'),
            'worksheet_id' => Translate::_('idbyii2', 'Worksheet ID'),
            'status' => Translate::_('idbyii2', 'Status'),
            'import_attributes' => Translate::_('idbyii2', 'Attributes'),
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getFile()
    {
        return $this->hasOne(BusinessImport::className(), ['id' => 'file_id']);
    }

    /**
     * @return mixed|null
     */
    public function getBeforeStep()
    {
        $steps = json_decode($this->steps, true);

        if (!is_array($steps)) {
            return null;
        }

        return end($steps);
    }

    /**
     * @return array|null
     */
    public function getBackUrl()
    {
        $step = $this->getBeforeStep();

        switch ($step) {
            case 'select-db':
                return ['/tools/wizard/select-db'];
            case 'index':
                return ['/tools/wizard/index'];
            case 'worksheets':
                return ['/tools/wizard/worksheets', 'file' => $this->file_id];
            case 'data-types':
                return ['/tools/wizard/data-types', 'file' => $this->file_id, 'id' => $this->id];
            case 'send-emails':
                return null;
        }
    }
}
