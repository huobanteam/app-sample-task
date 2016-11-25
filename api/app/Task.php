<?php

namespace App;

use App\BaseModel;

class Task extends BaseModel
{

    /**
     * 开放平台颁发
     */
    const APPLICATION_ID = '11002';

    /**
     * 开放平台颁发
     */
    const APPLICATION_SECRET = 'xxxxxxx';

    /**
     *  未完成
     */
    const TASK_STATUS_UNCOMPLETED = 'uncompleted';

    /**
     * 已完成
     */
    const TASK_STATUS_COMPLETED = 'completed';

    /**
     *  未完成
     */
    const TASK_STATUS_UNCOMPLETED_NUM = 1;

    /**
     * 已完成
     */
    const TASK_STATUS_COMPLETED_NUM = 2;

    /**
     * 未过期
     */
    const TASK_DUE_STATUS_UNEXPIRED = 'unexpired';

    /**
     * 已过期
     */
    const TASK_DUE_STATUS_EXPIRED = 'expired';

    /**
     * 今天过期
     */
    const TASK_DUE_STATUS_TODAY = 'today';

    /**
     * 明天过期
     */
    const TASK_DUE_STATUS_TOMORROW = 'tomorrow';

    /**
     * getUncompletedNum
     *
     * @param  array  $project_ids
     * @return
     */
    public static function getUncompletedNum($projectIds = array()) {

        $select = array();
        $where = array();
        $groupBy = array();
        $orderBy = array();

        $fields = \App\Table::getFields();
        $select[] = array(
            'field' => '*',
            'aggregation' => 'count',
            'as' => 'select1',
        );
        $where['and'] = array(
            array(
                'field' => $fields['task_status']['field_id'],
                'query' => array(
                    'in' => array(1),
                ),
            ),
            array(
                'field' => $fields['task_parent_task']['field_id'],
                'query' => array(
                    'em' => true,
                ),
            )
        );
        $groupBy[] = array(
            'field' => $fields['task_project']['field_id'],
            'as' => 'group1',
        );

        $params = array(
            'select' => $select,
            'where' => $where,
            'group_by' => $groupBy,
            'order_by' => $orderBy,
        );

        $result = array();
        $statsResult = \App\Item::stats($params);

        if ($statsResult) {
            foreach ($statsResult as $key => $value) {
                $select = $value['select'][0];
                $groupBy = $value['group_by'][0];

                $projectId = $groupBy['values'][0]['value'];
                if ($projectIds && !in_array($projectId, $projectIds)) {
                    continue;
                }

                $result[$projectId] = $select['value'];
            }
        }

        return $result;
    }

    public static function get($taskId, $isFull = true) {
        // 需要额外取回关注状态
        $options = array(
            'fields' => array('*', 'followed')
        );

        $item = \App\Item::get($taskId, array(), $options);
        return self::format($item, 0, $isFull);
    }

    public static function create($params) {
        $data = array(
            'fields' => $params,
        );
        $item = \App\Item::create($data);

        return self::format($item);
    }

    public static function update($taskId, $params) {
        $data = array(
            'fields' => $params,
        );

        $item = \App\Item::update($taskId, $data);

        return self::format($item);
    }

    public static function find($params, $options = array(), $tableId = 0) {

        $result = \App\Item::find($params, $options, $tableId);
        $result['tasks'] = array();
        if ($result && $result['items']) {
            foreach ($result['items'] as $key => $item) {
                $result['tasks'][$key] = self::format($item, $tableId);
            }

        }
        unset($result['items']);

        return $result;
    }

    public static function findSubTasks($taskIds) {

        $result = array();

        if (!TABLE_ID) {
            return $result;
        }

        $findResult = \App\Item::findByItemsIds($taskIds);
        if (!$findResult) {
            return $result;
        }

        $items = $findResult['items'];
        if ($items) {
            foreach ($items as $key => $item) {
                $result[] = self::format($item);
            }

        }

        return $result;
    }

    public static function delete($taskId) {
        return \App\Item::delete($taskId);
    }

    public static function deleteByProjectId($projectId) {
        return \App\Item::deleteByProjectId($projectId);
    }

    public static function sendNotification($oldTask, $newTask, $parentTaskExecutor, $followedUserIds, $loggedUser) {

        $taskId = $oldTask['task_id'];

        $diffData = self::diff($oldTask, $newTask);

        if (!$diffData) {
            return ;
        }

        if ($newTask['task_executor']) {
            $executor = $newTask['task_executor'];
        } else {
            $executor = array();
        }

        foreach ($diffData as $key => $result) {
            if ($key == 'task_title') {
                \App\Notification::sendForUpdateTitle($newTask, $loggedUser, $executor, $followedUserIds, $parentTaskExecutor);
            }

            if ($key == 'task_description') {
                if ($result['type'] == 'update') {
                    \App\Notification::sendForUpdateDescription($newTask, $loggedUser, $executor, $followedUserIds, $parentTaskExecutor);
                } else {
                    \App\Notification::sendForRemoveDescription($newTask, $loggedUser, $executor, $followedUserIds, $parentTaskExecutor);
                }
            }

            if ($key == 'task_due_on') {
                if ($result['type'] == 'update') {
                    \App\Notification::sendForUpdateDueOn($newTask, $loggedUser, $newTask['task_due_on'], $executor, $followedUserIds, $parentTaskExecutor);

                    if (IS_TEST) {
                        $time = date('H:i', time() + 60);
                    } else {
                        $time = '08:00';
                    }

                    $taskDueOn = strtotime($newTask['task_due_on']);
                    $dateBegin = strtotime(date('Y-m-d'));
                    $dateEnd = strtotime(date('Y-m-d 23:59:59'));
                    $nowTime = time();
                    $todayDueTime = strtotime(date('Y-m-d ' . $time . ':00'));

                    // 时间更新为今天之后或者在今天8点之前更新的 可以添加到定时提醒中
                    if ($taskDueOn > $dateEnd || $taskDueOn == $dateBegin && $nowTime < $todayDueTime) {

                        $openUrl = '/item/' . $taskId;
                        $data = array(
                            'task_id' => $taskId,
                            'app_id' => APP_ID,
                            'table_id' => TABLE_ID,
                            'sender_id' => $loggedUser['user_id'],
                            'title' => '“' . $newTask['task_title'] . '”任务今天到期',
                            'content' => '',
                            'push_message' => '“' . $newTask['task_title'] . '”任务今天到期',
                            'open_url' => $openUrl,
                        );
                    }
                } else {
                    \App\Notification::sendForRemoveDueOn($newTask, $loggedUser, $newTask['task_due_on'], $executor, $followedUserIds, $parentTaskExecutor);
                }
            }

            if ($key == 'task_files') {
                if ($result['type'] == 'add') {
                    \App\Notification::sendForAddFile($newTask, $loggedUser, $executor, $followedUserIds, $parentTaskExecutor, $result['diff_value']);
                } else {
                    \App\Notification::sendForRemoveFile($newTask, $loggedUser, $executor, $followedUserIds, $parentTaskExecutor, $result['diff_value']);
                }
            }

            if ($key == 'task_status') {
                if ($result['type'] == \App\Task::TASK_STATUS_COMPLETED) {
                    \App\Notification::sendForComplete($newTask, $loggedUser, $executor, $followedUserIds, $parentTaskExecutor);
                } else {
                    \App\Notification::sendForUncomplete($newTask, $loggedUser, $executor, $followedUserIds, $parentTaskExecutor);
                }
            }

            if ($key == 'task_executor') {
                if ($result['type'] == 'update') {
                    \App\Task::addOrder($taskProjectId, 'executor', $executor['user_id'], $task['task_id']);

                    \App\Notification::sendForUpdateExecutor($newTask, $loggedUser, $executor, $result['old'], $followedUserIds, $parentTaskExecutor);
                } else {
                    \App\Task::addOrder($taskProjectId, 'executor', 0, $task['task_id']);

                    \App\Notification::sendForRemoveExecutor($newTask, $loggedUser, $result['old'], $followedUserIds, $parentTaskExecutor);
                }
            }

            if ($key == 'task_sub_tasks') {
                if ($result['type'] == 'add') {
                    \App\Notification::sendForAddChildTask($newTask, $loggedUser, $executor, $followedUserIds, $parentTaskExecutor, $result['diff_value']);
                } else {
                    \App\Notification::sendForRemoveChildTask($newTask, $loggedUser, $executor, $followedUserIds, $parentTaskExecutor, $result['diff_value']);
                }
            }
        }
    }

    public static function diff($oldTask, $newTask) {

        $result = array();
        foreach ($oldTask as $key => $oldValue) {
            $newValue = $newTask[$key];

            if (!$newValue) {
                $type = 'empty';
            } else {
                $type = 'update';
            }

            switch ($key) {
                case 'task_executor':
                    if ($newValue['user_id'] != $oldValue['user_id']) {
                        $result[$key] = array(
                            'old' => $oldValue,
                            'new' => $newValue,
                            'type' => $type,
                        );
                    }
                    break;
                case 'task_files':
                    $oldFileIds = \Illuminate\Support\Arr::getCol($oldValue, 'file_id');
                    $newFileIds = \Illuminate\Support\Arr::getCol($newValue, 'file_id');
                    if (array_diff($oldFileIds, $newFileIds) || array_diff($newFileIds, $oldFileIds)) {
                        $diffValue = array();
                        if (count($oldFileIds) < count($newFileIds)) {
                            $type = 'add';
                            foreach ($newValue as $fileKey => $fileValue) {
                                if (!in_array($fileValue['file_id'], $oldFileIds)) {
                                    $diffValue = $fileValue;
                                }
                            }
                        } else {
                            $type = 'remove';
                            foreach ($oldValue as $fileKey => $fileValue) {
                                if (!in_array($fileValue['file_id'], $newFileIds)) {
                                    $diffValue = $fileValue;
                                }
                            }
                        }

                        $result[$key] = array(
                            'old' => $oldValue,
                            'new' => $newValue,
                            'type' => $type,
                            'diff_value' => $diffValue,
                        );
                    }

                    break;
                case 'task_sub_tasks':
                    $oldTaskIds = \Illuminate\Support\Arr::getCol($oldValue, 'task_id');
                    $newTaskIds = \Illuminate\Support\Arr::getCol($newValue, 'task_id');
                    if (array_diff($oldTaskIds, $newTaskIds) || array_diff($newTaskIds, $oldTaskIds)) {
                        $diffValue = array();
                        if (count($oldTaskIds) < count($newTaskIds)) {
                            $type = 'add';
                            foreach ($newValue as $subTaskValue) {
                                if (!in_array($subTaskValue['task_id'], $oldTaskIds)) {
                                    $diffValue = $subTaskValue;
                                }
                            }
                        } else {
                            $type = 'remove';
                            foreach ($oldValue as $subTaskValue) {
                                if (!in_array($subTaskValue['task_id'], $newTaskIds)) {
                                    $diffValue = $subTaskValue;
                                }
                            }
                        }

                        $result[$key] = array(
                            'old' => $oldValue,
                            'new' => $newValue,
                            'type' => $type,
                            'diff_value' => $diffValue,
                        );
                    }
                    break;
                case 'task_parent_task':
                case 'task_complete_rate':
                case 'task_completed_on':
                    break;
                case 'task_status':
                    if ($oldValue != $newValue) {
                        if ($newValue == \App\Task::TASK_STATUS_COMPLETED) {
                            $type = \App\Task::TASK_STATUS_COMPLETED;
                        } else {
                            $type = \App\Task::TASK_STATUS_UNCOMPLETED;
                        }

                        $result[$key] = array(
                            'old' => $oldValue,
                            'new' => $newValue,
                            'type' => $type
                        );
                    }
                    break;
                case 'task_title':
                case 'task_description':
                    if ($newValue === '') {
                        $type = 'empty';
                    } else {
                        $type = 'update';
                    }
                default:
                    if ($oldValue != $newValue) {
                        $result[$key] = array(
                            'old' => $oldValue,
                            'new' => $newValue,
                            'type' => $type
                        );
                    }
                    break;
            }
        }

        return $result;
    }

    // 将item转换为正常task结构
    public static function format($item, $tableId = 0, $isFull = true) {
        $fieldValues = $item['fields'];
        if ($fieldValues) {
            $fieldValues = \Illuminate\Support\Arr::rebuildByCol($fieldValues, 'field_id');
        }

        $fields = \App\Table::getFields($tableId);

        $result = array();

        // todo 严格验证
        $result['task_id'] = $item['item_id'];
        if (isset($fieldValues[$fields['task_title']['field_id']])) {
            $result['task_title'] = $fieldValues[$fields['task_title']['field_id']]['values'][0]['value'];
        }

        if (isset($fieldValues[$fields['task_description']['field_id']])) {
            $result['task_description'] = $fieldValues[$fields['task_description']['field_id']]['values'][0]['value'];
        } else {
            $result['task_description'] = '';
        }

        if (isset($fieldValues[$fields['task_due_on']['field_id']])) {
            $result['task_due_on'] = $fieldValues[$fields['task_due_on']['field_id']]['values'][0]['value'];
            $result['task_due_status'] = self::__getDueStatus($result['task_due_on']);
        } else {
            $result['task_due_on'] = '';
            $result['task_due_status'] = '';
        }

        if (isset($fieldValues[$fields['task_completed_on']['field_id']])) {
            $result['task_completed_on'] = $fieldValues[$fields['task_completed_on']['field_id']]['values'][0]['value'];
        } else {
            $result['task_completed_on'] = '';
        }

        if (isset($fieldValues[$fields['task_executor']['field_id']])) {
            $result['task_executor'] = $fieldValues[$fields['task_executor']['field_id']]['values'][0];
        } else {
            $result['task_executor'] = null;
        }

        if (isset($fieldValues[$fields['task_project']['field_id']])) {
            $result['task_project'] = $fieldValues[$fields['task_project']['field_id']]['values'][0]['value'];
        }

        if (isset($fieldValues[$fields['task_status']['field_id']])) {
            if ($fieldValues[$fields['task_status']['field_id']]['values'][0]['id'] == self::TASK_STATUS_COMPLETED_NUM) {
                $result['task_status'] = self::TASK_STATUS_COMPLETED;
            } else {
                $result['task_status'] = self::TASK_STATUS_UNCOMPLETED;
            }
        }

        if (isset($fieldValues[$fields['task_files']['field_id']])) {
            $result['task_files'] = $fieldValues[$fields['task_files']['field_id']]['values'];
        } else {
            $result['task_files'] = array();
        }

        if (isset($fieldValues[$fields['task_complete_rate']['field_id']]) && $fieldValues[$fields['task_complete_rate']['field_id']]['values'][0]['value']) {
            $result['task_complete_rate'] = $fieldValues[$fields['task_complete_rate']['field_id']]['values'][0]['value'];
        } else {
            $result['task_complete_rate'] = '0%';
        }

        if (isset($fieldValues[$fields['task_parent_task']['field_id']])) {
            $result['task_parent_task'] = self::__formatSimpleTask($fieldValues[$fields['task_parent_task']['field_id']]['values'][0]);
        } else {
            $result['task_parent_task'] = null;
        }

        if (isset($fieldValues[$fields['task_sub_tasks']['field_id']])) {

            if ($isFull) {
                $itemIds = \Illuminate\Support\Arr::getCol($fieldValues[$fields['task_sub_tasks']['field_id']]['values'], 'item_id');
                if ($itemIds) {
                    $subTasks = self::findSubTasks($itemIds);
                } else {
                    $subTasks = array();
                }
            } else {
                $subTasks = self::__formatSimpleTasks($fieldValues[$fields['task_sub_tasks']['field_id']]['values']);
            }

            $result['task_sub_tasks'] = $subTasks;

        } else {
            $result['task_sub_tasks'] = array();
        }

        $result['followed'] = $item['followed'] ? true : false;
        $result['task_created_on'] = $item['created_on'];
        $result['task_created_by'] = $item['created_by'];

        return $result;
    }

    private static function __getDueStatus($dueOn) {

        $dueOnTs = strtotime($dueOn);
        $todayEarliest = strtotime(date('Y-m-d 00:00:00'));
        $todayLatest = $todayEarliest + 86399;
        $tomorrowLatest = $todayLatest + 86400;

        if ($dueOnTs < $todayEarliest) {
            return self::TASK_DUE_STATUS_EXPIRED;
        } elseif ($dueOnTs <= $todayLatest) {
            return self::TASK_DUE_STATUS_TODAY;
        } elseif ($dueOnTs <= $tomorrowLatest) {
            return self::TASK_DUE_STATUS_TOMORROW;
        } else {
            return self::TASK_DUE_STATUS_UNEXPIRED;
        }
    }

    private static function __formatSimpleTask($task) {

        $task['task_id'] = $task['item_id'];
        unset($task['item_id']);
        unset($task['app_id']);

        return $task;
    }

    private static function __formatSimpleTasks($tasks) {

        foreach ($tasks as $key => $value) {
            $value['task_id'] = $value['item_id'];
            unset($value['item_id']);
            unset($value['app_id']);
            $tasks[$key] = $value;
        }

        return $tasks;
    }

    /**
     * add_order
     *
     * @param int  $projectId
     * @param string  $groupBy
     * @param integer $userId
     * @param int  $taskId
     * @return  void
     */
    public static function addOrder($projectId, $groupBy, $userId = 0, $taskId) {
        $orders = self::getOrders($projectId, $groupBy, $userId);
        if (!$orders) {
            return ;
        }

        foreach ($orders as $key => $value) {
            if ($value == $taskId) {
                unset($orders[$key]);
            }
        }

        array_unshift($orders, $taskId);

        $orders = array_values($orders);

        self::setOrders($projectId, $groupBy, $userId, $orders);
    }

    public static function getOrders($projectId, $groupBy, $userId = 0) {
        $key = 'ORDER_' . TABLE_ID . '_APP_ID_' . APP_ID . '_' . $projectId . '_' . $groupBy . '_' . $userId;
        $response = \App\Http\Request\Huoban::get('/v2/storage', array('key' => $key));
        $orders = $response->getContent();
        $orders = json_decode($orders, true);
        if (!$orders || !$orders['value']) {
            return array();
        }

        return $orders['value'];
    }

    /**
     * set_order
     *
     * @param integer  $project_id
     * @param string  $group_by
     * @param integer $user_id
     * @param array  $orders = [10001, 10002, 10003]
     *
     */
    public static function setOrders($projectId, $groupBy, $userId = 0, $orders) {
        $key = 'ORDER_' . TABLE_ID . '_APP_ID_' . APP_ID . '_' . $projectId . '_' . $groupBy . '_' . $userId;
        $data = array(
            'key' => $key,
            'value' => $orders,
        );
        $response = \App\Http\Request\Huoban::post('/v2/storage', $data);
    }
}
