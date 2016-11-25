<?php

namespace App;

use App\BaseModel;

class Stream extends BaseModel {

    const ACTION_TASK_CREATE = 'task_created';
    const ACTION_COMMENT_CREATE = 'comment_created';
    const ACTION_TASK_TITLE_UPDATE = 'task_title_updated';
    const ACTION_TASK_DESCRIPTION_UPDATE = 'task_description_updated';
    const ACTION_TASK_DUE_ON_UPDATE = 'task_due_on_updated';
    const ACTION_TASK_STATUS_UPDATE = 'task_status_updated';
    const ACTION_TASK_EXECUTOR_UPDATE = 'task_executor_updated';
    const ACTION_TASK_FILE_UPDATE = 'task_file_updated';
    const ACTION_TASK_SUB_TASK_UPDATE = 'task_sub_task_update';
    const ACTION_TASK_COMPLETE_RATE_UPDATE = 'task_complete_rate_update';
    const ACTION_TASK_PROJECT_UPDATE = 'task_project_update';

    public static function getAll($taskId, $limit, $lastStreamId) {

        $params = array(
            'limit' => $limit,
            'last_stream_id' => $lastStreamId,
        );

        $response = \App\Http\Request\Huoban::get('/v2/streams/item/' . $taskId, $params);
        $result = $response->getContent();
        $result = json_decode($result, true);
        if ($result) {
            return $result;
        } else {
            return array();
        }
    }

    public static function format($stream, $fields) {

        $result = array(
            'action' => '',
            'stream_id' => $stream['stream_id'],
            'text' => '',
            'data' => array(),
            'created_on' => $stream['created_on'],
            'created_by' => $stream['created_by'],
        );

        if ($stream['object_action'] == 'item_created') {

            $result['action'] = self::ACTION_TASK_CREATE;
            // $result['text'] = '创建了 ' . $stream['data'][0]['title'];
            $result['text'] = '创建了任务';

        } elseif ($stream['object_action'] == 'comment_created') {

            $result['action'] = self::ACTION_COMMENT_CREATE;
            $result['text'] = $stream['data'][0]['value'];
            $result['data']['comment'] = $stream['data'][0];

        } elseif ($stream['object_action'] == 'item_updated') {
            $field = $stream['data'][0]['item_diff'][0];
            // 修改了标题
            if ($field['field_id'] == $fields['task_title']['field_id']) {

                $result['action'] = self::ACTION_TASK_TITLE_UPDATE;
                $result['text'] = '修改了标题';
                // todo 是否进行比对
                $result['data']['diff'] = array(
                    'from_revision_id' => $stream['data'][0]['from_revision_id'],
                    'to_revision_id' => $stream['data'][0]['to_revision_id'],
                    'field_id' => $field['field_id'],
                );
            } elseif ($field['field_id'] == $fields['task_description']['field_id']) {
                $result['action'] = self::ACTION_TASK_DESCRIPTION_UPDATE;
                $result['text'] = '修改了描述';
                // todo 是否进行比对
                $result['data']['diff'] = array(
                    'from_revision_id' => $stream['data'][0]['from_revision_id'],
                    'to_revision_id' => $stream['data'][0]['to_revision_id'],
                    'field_id' => $field['field_id'],
                );
            } elseif ($field['field_id'] == $fields['task_due_on']['field_id']) {
                $result['action'] = self::ACTION_TASK_DUE_ON_UPDATE;
                if ($field['value']) {
                    $result['text'] = '修改到期时间为 ' . date('Y-m-d', strtotime($field['value'])); // todo 人性化时间
                } else {
                    $result['text'] = '将到期时间清除';
                }
            } elseif ($field['field_id'] == $fields['task_completed_on']['field_id']) {
                $result['action'] = self::ACTION_TASK_STATUS_UPDATE;
                if ($field['value']) {
                    $result['text'] = '将任务标为已完成';
                } else {
                    $result['text'] = '将任务标为未完成';
                }
            } elseif ($field['field_id'] == $fields['task_status']['field_id']) {
                $result['action'] = self::ACTION_TASK_STATUS_UPDATE;
                if ($field['value'][0] == '已完成') {
                    $result['text'] = '完成了任务';
                } else {
                    $result['text'] = '重新开启了任务';
                }
            } elseif ($field['field_id'] == $fields['task_executor']['field_id']) {
                $result['action'] = self::ACTION_TASK_EXECUTOR_UPDATE;
                if (!$field['value']) {
                    $result['text'] = '取消了对 ' . $field['old_value'][0] . ' 的任务分配';
                } else {
                    $result['text'] = '将任务分配给 ' . $field['value'][0];
                }
            } elseif ($field['field_id'] == $fields['task_files']['field_id']) {
                $result['action'] = self::ACTION_TASK_FILE_UPDATE;
                if (!$field['value']) {
                    $result['text'] = '删除了附件 ' . $field['old_value'][0];
                } else {
                    $result['text'] = '添加了附件 ' . $field['value'][0];
                }
            } elseif ($field['field_id'] == $fields['task_sub_tasks']['field_id']) {
                $result['action'] = self::ACTION_TASK_SUB_TASK_UPDATE;
                if (!$field['value']) {
                    $result['text'] = '删除了子任务 ' . $field['old_value'][0];
                } else {
                    $result['text'] = '添加了子任务“' . $field['value'][0] . '”';
                }
            } elseif ($field['field_id'] == $fields['task_complete_rate']['field_id']) {
                $result['action'] = self::ACTION_TASK_COMPLETE_RATE_UPDATE;
                $result['text'] = '任务完成度修改为 ' . $field['value'];
            } elseif ($field['field_id'] == $fields['task_project']['field_id']) {
                $result['action'] = self::ACTION_TASK_PROJECT_UPDATE;
                $result['text'] = '更改了任务所属项目';
            } else {
                return false;
            }
        }

        return $result;
    }
}
