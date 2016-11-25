<?php

namespace App;

use App\BaseModel;

class Follow extends BaseModel
{

    public static function create($taskId) {
        \App\Http\Request\Huoban::post('/v2/follow/item/' . $taskId);
        return ;
    }

    public static function delete($taskId) {
        \App\Http\Request\Huoban::delete('/v2/follow/item/' . $taskId);
        return ;
    }

    public static function getFollowedUserIds($taskId) {
        $follows = self::getAll($taskId);
        $userIds = array();
        if ($follows) {
            foreach ($follows as $key => $value) {
                $userIds[] = $value['user']['user_id'];
            }
        }

        return $userIds;
    }

    public static function getAll($taskId) {

        $params = array(
            'limit' => 10000,
            'offset' => 0,
            'order' => array(
                'created_on' => 'desc',
            ),
        );
        $response = \App\Http\Request\Huoban::post('/v2/follow/item/' . $taskId . '/find', $params);
        $result = $response->getContent();
        $result = json_decode($result, true);
        if ($result && $result['follows']) {
            return $result['follows'];
        } else {
            return array();
        }
    }
}
