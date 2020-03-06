<?php

namespace idbyii2\models\db;

use DateTime;
use idbyii2\enums\SignUpNamespace;
use idbyii2\helpers\IdbSecurity;
use idbyii2\helpers\Localization;
use idbyii2\helpers\Translate;
use idbyii2\helpers\Uuid;
use Yii;

/**
 * This is the model class for table "p57b_log.people_signup_log".
 *
 * @property int    $id
 * @property string $timestamp
 * @property string $data
 * @property string $auth_key
 * @property string $auth_key_hash
 */
class PeopleSignUpLog extends IdbModel
{

    private $idbSecurity;
    private $blowfishCost = 1;
    private $dataPassword = "password";
    private $authKeyPassword = "password";

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'p57b_log.people_signup_log';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['timestamp'], 'safe'],
            [['data', 'auth_key', 'auth_key_hash'], 'string'],
            [['auth_key', 'auth_key_hash'], 'required'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'timestamp' => 'Timestamp',
            'data' => 'Data',
            'auth_key' => 'Auth Key',
            'auth_key_hash' => 'Auth Key Hash',
        ];
    }


    /**
     * @param      $values
     * @param bool $safeOnly
     *
     * @return void
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
            if (!empty($values['authKeyPassword'])) {
                $this->authKeyPassword = $values['authKeyPassword'];
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
        if (!empty(Yii::$app->getModule('registration')->configSignUp)) {
            $this->setAttributes(Yii::$app->getModule('registration')->configSignUp);
        }
        $this->idbSecurity = new IdbSecurity(Yii::$app->security);
        parent::init();
    }

    /**
     * @param null $row
     *
     * @return static
     */
    public static function instantiate($row = null)
    {
        $model = new static();
        if (is_array($row) && !empty($row)) {
            $model->setAttributes($row, false);
        }

        return $model;
    }

    /**
     * @return bool
     */
    public function beforeValidate()
    {
        if (parent::beforeValidate()) {
            if (empty($this->auth_key_hash)) {
                $this->auth_key_hash = 'auth_key_hash';
            }

            return true;
        }

        return false;
    }

    /**
     * @param $insert
     *
     * @return bool
     */
    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {

            $this->data = base64_encode($this->idbSecurity->encryptByPasswordSpeed($this->data, $this->dataPassword));
            $this->auth_key_hash = $this->idbSecurity->secureHash(
                $this->auth_key,
                $this->authKeyPassword,
                $this->blowfishCost
            );

            $this->auth_key = base64_encode(
                $this->idbSecurity->encryptByPasswordSpeed($this->auth_key, $this->authKeyPassword)
            );

            return true;
        }

        return false;
    }

    /**
     * @param $insert
     * @param $changedAttributes
     *
     * @return void
     */
    public function afterSave($insert, $changedAttributes)
    {
        $this->data = $this->idbSecurity->decryptByPasswordSpeed(base64_decode($this->data), $this->dataPassword);
        $this->auth_key = $this->idbSecurity->decryptByPasswordSpeed(
            base64_decode($this->auth_key),
            $this->authKeyPassword
        );
        parent::afterSave($insert, $changedAttributes);
    }

    /**
     * @param $key
     *
     * @return mixed
     */
    public static function findByAuthKey($key)
    {
        $instance = self::instantiate();
        $keyHash = $instance->idbSecurity->secureHash($key, $instance->authKeyPassword, $instance->blowfishCost);

        return self::findOne(['auth_key_hash' => $keyHash]);
    }

    /**
     * @return void
     */
    public function afterFind()
    {
        $this->data = $this->idbSecurity->decryptByPasswordSpeed(base64_decode($this->data), $this->dataPassword);
        $this->auth_key = $this->idbSecurity->decryptByPasswordSpeed(
            base64_decode($this->auth_key),
            $this->authKeyPassword
        );
        parent::afterFind();
    }

    /**
     * @throws Exception
     * @throws \Exception
     */
    public function generateAuthKey()
    {
        $data = json_decode($this->data, true);
        if (!isset($data[SignUpNamespace::PEOPLE])) {
            throw new Exception(Translate::_('idbyii2', 'IdbPeopleSignUpForm doesn\'t exists in $data'));
        }

        $salt = substr(uniqid('', true), -8);
        $key = $data[SignUpNamespace::PEOPLE]['email'] ?? $data[SignUpNamespace::PEOPLE]['mobile'];
        $generateString = $salt . $data[SignUpNamespace::PEOPLE]['name']
            . $data[SignUpNamespace::PEOPLE]['surname']
            . $key;

        $this->auth_key =
            Uuid::uuid5($generateString) . '-' . (new DateTime())->format(Localization::getDateTimeNumberFormat());
    }
}
