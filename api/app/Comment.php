<?php

namespace App;

use App\BaseModel;

class Comment extends BaseModel
{
    public static function create($taskId, $content, $parentCommentId = 0, $fileIds = array()) {
        $params = array(
            'content' => $content,
            'parent_comment_id' => $parentCommentId,
            'file_ids' => $fileIds,
        );
        $response = \App\Http\Request\Huoban::post('/v2/comment/item/' . $taskId, $params);
        $result = $response->getContent();
        $result = json_decode($result, true);
        if ($result) {
            return $result;
        } else {
            return array();
        }
    }

    public static function getAll($taskId, $lastCommentId, $limit) {
        $params = array(
            'last_comment_id' => $lastCommentId,
            'limit' => $limit,
        );
        $response = \App\Http\Request\Huoban::get('/v2/comment/item/' . $taskId, $params);
        $result = $response->getContent();
        $result = json_decode($result, true);
        if ($result) {
            return $result;
        } else {
            return array();
        }
    }

    public static function delete($commentId) {
        $response = \App\Http\Request\Huoban::delete('/v2/comment/' . $commentId);
        $result = $response->getContent();
        $result = json_decode($result, true);
        if ($result) {
            return $result;
        } else {
            return array();
        }
    }
}
