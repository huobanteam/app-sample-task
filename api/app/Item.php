<?php

namespace App;

use App\BaseModel;

class Item extends BaseModel
{

    public static function stats($params) {

        $response = \App\Http\Request\Huoban::post('/v2/item/table/' . TABLE_ID . '/stats', $params);
        $result = $response->getContent();
        $result = json_decode($result, true);
        if (!$result) {
            $result = array();
        }
        return $result;
    }

    public static function find($params, $options = array(), $tableId = 0) {
        if (!$tableId) {
            $tableId = TABLE_ID;
        }

        $response = \App\Http\Request\Huoban::post('/v2/item/table/' . $tableId . '/find', $params, $options);
        $result = $response->getContent();
        $result = json_decode($result, true);
        if (!$result) {
            $result = array();
        }
        return $result;
    }

    public static function findByItemsIds($itemIds) {

        $where = array(
            'and' => array(),
        );

        $where['and'][] = array(
            'field' => "item_id",
            'query' => array(
                'in' => $itemIds,
            ),
        );

        $params = array(
            'where' => $where,
            'limit' => 500,
        );

        $response = \App\Http\Request\Huoban::post('/v2/item/table/' . TABLE_ID . '/find', $params);
        $result = $response->getContent();
        $result = json_decode($result, true);
        if (!$result) {
            $result = array();
        }
        return $result;
    }

    public static function deleteByProjectId($projectId) {

        $where = array(
            'and' => array(),
        );

        $fields = \App\Table::getFields();

        $where['and'][] = array(
            'field' => $fields['task_project']['field_id'],
            'query' => array(
                'eq' => $projectId,
            ),
        );

        $params = array(
            'where' => $where,
        );

        $response = \App\Http\Request\Huoban::post('/v2/item/table/' . TABLE_ID . '/delete', $params);
        $result = $response->getContent();
    }

    public static function create($params) {
        $response = \App\Http\Request\Huoban::post('/v2/item/table/' . TABLE_ID, $params);
        $result = $response->getContent();
        $result = json_decode($result, true);
        if (!$result) {
            $result = array();
        }
        return $result;
    }

    public static function update($itemId, $params) {
        $response = \App\Http\Request\Huoban::put('/v2/item/' . $itemId, $params);
        $result = $response->getContent();
        $result = json_decode($result, true);
        if (!$result) {
            $result = array();
        }
        return $result;
    }

    public static function get($itemId, $params = array(), $options = array()) {
        $response = \App\Http\Request\Huoban::get('/v2/item/' . $itemId, $params, $options);
        $result = $response->getContent();
        $result = json_decode($result, true);
        if (!$result) {
            $result = array();
        }
        return $result;
    }

    public static function delete($itemId) {
        $response = \App\Http\Request\Huoban::delete('/v2/item/' . $itemId);
        $result = $response->getContent();
        return $result;
    }
}
