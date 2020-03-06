<?php

namespace idbyii2\models\db;


class IdbCrossTable extends IdbModel
{

    /**
     * @param array $attrs
     */
    public static function create(array $attrs)
    {
        $className = get_called_class();
        $model = new $className();
        foreach ($attrs as $attr => $value) {
            $model->{$attr} = $value;
        }

        $model->save();
    }
}