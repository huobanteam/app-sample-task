<?php

namespace App;

use App\BaseModel;

class Table extends BaseModel
{
    static $tableInfo = array();

    static $fields = array();

    public static function get($tableId = 0) {
        if (!$tableId) {
            $tableId = TABLE_ID;
        }

        // 应用中 每次访问肯定只调用一个table的信息 可以使用内存缓存
        if (self::$tableInfo) {
            return self::$tableInfo;
        }

        $response = \App\Http\Request\Huoban::get('/v2/table/' . $tableId);

        $result = $response->getContent();
        $result = json_decode($result, true);

        self::$tableInfo = $result;

        return $result;
    }

    public static function filter($tableId, $params) {

        $response = \App\Http\Request\Huoban::post('/v2/item/table/' . $tableId . '/stats', $params);
        $result = $response->getContent();
        $result = json_decode($result, true);
        if (!$result) {
            $result = array();
        }
        return $result;
    }

    // 得到应用添加的字段的配置
    public static function getFields($tableId = 0) {
        if (self::$fields) {
            return self::$fields;
        }

        $table = self::get($tableId);
        $fields = array();
        if ($table && $table['fields']) {
            foreach ($table['fields'] as $key => $value) {
                if ($value['app_id'] != APP_ID) {
                    continue;
                }

                $fields[$value['application_alias']] = $value;
            }
        }

        self::$fields = $fields;
        return $fields;
    }
}
