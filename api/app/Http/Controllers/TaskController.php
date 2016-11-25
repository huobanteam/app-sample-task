<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Exceptions\APIException;
use Illuminate\Contracts\Validation\Validator;

class TaskController extends Controller {

    /**
     * __construct
     */
    public function __construct() {
        parent::__construct();
    }

    /**
     * create
     *
     * @param  Request $request
     * @return
     */
    public function create(Request $request) {
        try {

            $loggedUser = \App\User::getLoggedUser();
            $params = $request->all();

            $taskTitle = isset($params['task_title']) ? trim($params['task_title']) : '';
            $taskDescription = isset($params['task_description']) ? trim($params['task_description']) : '';
            $taskDueOn = isset($params['task_due_on']) ? trim($params['task_due_on']) : '';
            $taskExecutorId = isset($params['task_executor_id']) ? intval($params['task_executor_id']) : 0;
            $taskProjectId = isset($params['task_project_id']) ? intval($params['task_project_id']) : 0;
            $taskParentTaskId = isset($params['task_parent_task_id']) ? intval($params['task_parent_task_id']) : 0;
            $taskFiles = isset($params['task_files']) ? json_decode(trim($params['task_files']), true) : array();

            $fields = \App\Table::getFields();

            $data = array();

            if (!$taskTitle === '') {
                throw new APIException('任务标题不能为空');
            }

            // 跟产品确认字数
            if (\Illuminate\Support\Str::length($taskTitle) > 100) {
                throw new APIException('名称最多为100个字符');
            }

            $data[$fields['task_title']['field_id']] = $taskTitle;

            if (!$taskProjectId) {
                throw new APIException('项目id不能为空');
            }

            $projects = \App\Project::getAll();
            $projectIds = \Illuminate\Support\Arr::getCol($projects, 'project_id');

            if (!in_array($taskProjectId, $projectIds)) {
                throw new APIException('项目id不存在');
            }

            $data[$fields['task_project']['field_id']] = $taskProjectId;

            if ($taskDescription !== '') {
                if (\Illuminate\Support\Str::length($taskDescription) > 1000) {
                    throw new APIException('描述最多为1000个字符');
                }

                $data[$fields['task_title']['field_id']] = $taskDescription;
            }

            if ($taskDueOn) {
                if(!is_date($taskDueOn, 'Y-m-d')) {
                    throw new APIException('到期时间格式不正确');
                }

                $data[$fields['task_due_on']['field_id']] = $taskDueOn;
            }

            if ($taskExecutorId) {
                $data[$fields['task_executor']['field_id']] = array($taskExecutorId);
            }

            if ($taskParentTaskId) {
                $parentTask = \App\Task::get($taskParentTaskId, false);
                if ($parentTask['task_parent_task']) {
                    throw new APIException('子任务不能再添加子任务');
                }

                $data[$fields['task_parent_task']['field_id']] = array($taskParentTaskId);
            }

            if ($taskFiles) {
                $data[$fields['task_files']['field_id']] = $taskFiles;
            }

            $data[$fields['task_status']['field_id']] = array(\App\Task::TASK_STATUS_UNCOMPLETED_NUM);

            // 创建item
            $task = \App\Task::create($data);

            // 添加各类任务的排序
            \App\Task::addOrder($taskProjectId, 'priority', 0, $task['task_id']);
            \App\Task::addOrder($taskProjectId, 'executor', $taskExecutorId, $task['task_id']);
            \App\Task::addOrder($taskProjectId, 'due_on', 0, $task['task_id']);

            // 如果有parent_task_id 则添加的任务是子任务 更新父任务的关联
            if ($taskParentTaskId) {

                if ($parentTask['task_sub_tasks']) {
                    $parentSubTaskIds = \Illuminate\Support\Arr::getCol($parentTask['task_sub_tasks'], 'task_id');
                    $parentSubTaskIds[] = $task['task_id'];
                } else {
                    $parentSubTaskIds = array($task['task_id']);
                }

                $data = array(
                    $fields['task_sub_tasks']['field_id'] => $parentSubTaskIds,
                );

                \App\Task::update($taskParentTaskId, $data);

                if ($parentTask['task_executor']) {
                    $executor = $parentTask['task_executor'];
                } else {
                    $executor = array();
                }

                $followedUserIds = \App\Follow::getFollowedUserIds($parentTask['task_id']);

                \App\Notification::sendForAddChildTask($parentTask, $loggedUser, $executor, $followedUserIds, array(), $task);
            }

            // 发送通知
            if ($taskExecutorId) {
                \App\Notification::sendForUpdateExecutor($task, $task['task_created_by'], $task['task_executor']);
            }

        } catch (\Exception $e) {
            return $this->_handleException($e);
        }

        return $this->_handleResult($task);
    }

    /**
     * create
     *
     * @param  Request $request
     * @return
     */
    public function get(Request $request, $taskId) {
        try {

            $loggedUser = \App\User::getLoggedUser();

            $task = \App\Task::get($taskId);

        } catch (\Exception $e) {
            return $this->_handleException($e);
        }

        return $this->_handleResult($task);
    }

    /**
     * create
     *
     * @param  Request $request
     * @return
     */
    public function update(Request $request, $taskId) {
        try {

            $loggedUser = \App\User::getLoggedUser();
            $params = $request->all();

            $task = \App\Task::get($taskId);

            $fields = \App\Table::getFields();
            $data = array();

            $notificationData = array();

            if (array_key_exists('task_title', $params)) {
                $taskTitle = trim($params['task_title']);
                if (!$taskTitle) {
                    throw new APIException('任务标题不能为空');
                }

                // 跟产品确认字数
                if (\Illuminate\Support\Str::length($taskTitle) > 100) {
                    throw new APIException('名称最多为100个字符');
                }

                $data[$fields['task_title']['field_id']] = $taskTitle;
            }

            if (array_key_exists('task_description', $params)) {

                $taskDescription = trim($params['task_description']);

                if (\Illuminate\Support\Str::length($taskDescription) > 1000) {
                    throw new APIException('描述最多为1000个字符');
                }

                if ($taskDescription === '') {
                    $taskDescription = null;
                }

                $data[$fields['task_description']['field_id']] = $taskDescription;

                if ($taskDescription) {
                    $method = 'update_description';
                } else {
                    $method = 'remove_description';
                }
                $notificationData = array(
                    'method' => $method,
                    'data' => array(
                        'executor' => $task['executor'],
                    ),
                );
            }

            if (array_key_exists('task_due_on', $params)) {

                $taskDueOn = trim($params['task_due_on']);

                if ($taskDueOn == '0000-00-00' || !$taskDueOn) {
                    // 要清空某个字段的值 需要将某个字段值更新为 null
                    $taskDueOn = null;
                } else {
                    if (!is_date($taskDueOn, 'Y-m-d')) {
                        throw new APIException('到期时间格式不正确');
                    }
                }

                $data[$fields['task_due_on']['field_id']] = $taskDueOn;
            }

            if (array_key_exists('task_executor_id', $params)) {
                $taskExecutorId = intval($params['task_executor_id']);

                if ($taskExecutorId) {
                    $data[$fields['task_executor']['field_id']] = array($taskExecutorId);
                } else {
                    // 要清空某个字段的值 需要将某个字段值更新为 null
                    $data[$fields['task_executor']['field_id']] = null;
                }
            }

            if (array_key_exists('task_project_id', $params)) {
                $taskProjectId = intval($params['task_project_id']);

                if (!$taskProjectId) {
                    throw new APIException('项目id不能为空');
                }

                $projects = \App\Project::getAll();
                $projectIds = \Illuminate\Support\Arr::getCol($projects, 'project_id');
                if (!in_array($taskProjectId, $projectIds)) {
                    throw new APIException('项目id不存在');
                }

                $data[$fields['task_project']['field_id']] = $taskProjectId;
            }

            if (array_key_exists('task_files', $params)) {
                $taskFiles = $params['task_files'];
                if (!is_array($taskFiles)) {
                    throw new APIException('附件id必须为数组');
                }

                $data[$fields['task_files']['field_id']] = $taskFiles;
            }

            if (array_key_exists('task_status', $params)) {
                $taskStatus = trim($params['task_status']);
                if ($taskStatus == \App\Task::TASK_STATUS_UNCOMPLETED) {
                    $data[$fields['task_status']['field_id']] = array(\App\Task::TASK_STATUS_UNCOMPLETED_NUM);
                    // 要清空某个字段的值 需要将某个字段值更新为 null
                    $data[$fields['task_completed_on']['field_id']] = null;
                } elseif ($taskStatus == \App\Task::TASK_STATUS_COMPLETED) {
                    $data[$fields['task_status']['field_id']] = array(\App\Task::TASK_STATUS_COMPLETED_NUM);
                    $data[$fields['task_completed_on']['field_id']] = date('Y-m-d H:i:s');

                } else {
                    throw new APIException('项目的状态不正确');
                }
            }

            $newTask = $task;

            if ($data) {
                \App\Task::update($task['task_id'], $data);

                $newTask = \App\Task::get($task['task_id']);

                $followedUserIds = \App\Follow::getFollowedUserIds($task['task_id']);

                if ($task['task_parent_task']) {
                    $parentTask = \App\Task::get($task['task_parent_task']['task_id']);
                    if ($parentTask && $parentTask['task_executor']) {
                        $parentTaskExecutor = $parentTask['task_executor'];
                    }
                } else {
                    $parentTaskExecutor = array();
                }

                // 发送通知
                \App\Task::sendNotification($task, $newTask, $parentTaskExecutor, $followedUserIds, $loggedUser);
            }

        } catch (\Exception $e) {
            return $this->_handleException($e);
        }

        return $this->_handleResult($newTask);
    }

    /**
     * delete
     *
     * @param  int $task_id
     * @return
     */
    public function delete($taskId) {
        try {

            $loggedUser = \App\User::getLoggedUser();

            $task = \App\Task::get($taskId);
            if (!$task) {
                throw new APIException('任务不存在');
            }

            $followedUserIds = \App\Follow::getFollowedUserIds($task['task_id']);

            \App\Task::delete($taskId);

            if ($task['task_executor']) {
                $executor = $task['task_executor'];
            } else {
                $executor = array();
            }

            \App\Notification::sendForDelete($task, $loggedUser, $executor, $followedUserIds);

        } catch (\Exception $e) {
            return $this->_handleException($e);
        }

        return $this->_handleResult();
    }

    /**
     * find 搜索
     *
     * @param  Request $request
     * @return
     */
    public function find(Request $request) {
        try {

            $loggedUser = \App\User::getLoggedUser();
            $params = $request->all();

            $keywords = isset($params['keywords']) ? trim($params['keywords']) : '';
            $status = isset($params['status']) ? trim($params['status']) : '';
            $limit = isset($params['limit']) ? trim($params['limit']) : 20;
            $offset = isset($params['offset']) ? trim($params['offset']) : 0;

            $fields = \App\Table::getFields();

            $where = array(
                'and' => array(),
            );
            if ($keywords !== '') {
                $searchValues = explode(' ', $keywords);

                $where['and'][] = array(
                    'or' => array(
                        array(
                            'field' => $fields['task_title']['field_id'],
                            'query' => array(
                                'in' => $searchValues,
                            ),
                        ),
                        array(
                            'field' => $fields['task_description']['field_id'],
                            'query' => array(
                                'in' => $searchValues,
                            ),
                        ),
                    ),
                );
            }

            if ($status) {
                if (!in_array($status, array(\App\Task::TASK_STATUS_COMPLETED, \App\Task::TASK_STATUS_UNCOMPLETED))) {
                    throw new APIException('状态不存在');
                }

                $where['and'][] = array(
                    'field' => $fields['task_status']['field_id'],
                    'query' => array(
                        'eq' => $status,
                    ),
                );
            }

            $orderBy = array(
                array(
                    'field' => 'created_on',
                    'sort' => 'desc',
                ),
            );

            $params = array(
                'where' => $where,
                'order_by' => $orderBy,
                'limit' => $limit,
                'offset' => $offset,
            );

            $result = \App\Task::find($params);

        } catch (\Exception $e) {
            return $this->_handleException($e);
        }

        return $this->_handleResult($result);
    }

    /**
     * getAll 根据项目id和分组获取任务列表
     *
     * @return
     */
    public function getAll(Request $request, $projectId) {
        try {

            $loggedUser = \App\User::getLoggedUser();
            $params = $request->all();

            $fields = \App\Table::getFields();

            $projects = \App\Project::getAll();
            $projectIds = \Illuminate\Support\Arr::getCol($projects, 'project_id');
            if (!in_array($projectId, $projectIds)) {
                throw new APIException('项目id不存在');
            }

            $groupBy = $params['group'] ? trim($params['group']) : '';
            $limit = isset($params['limit']) ? trim($params['limit']) : 200;
            $offset = isset($params['offset']) ? trim($params['offset']) : 0;

            $orderBy = array();

            if (!in_array($groupBy, array('priority', 'executor', 'due_on', 'completed'))) {
                throw new APIException('分组不存在');
            }

            // 各个分组不同的默认排序方式
            if ($groupBy == 'priority') {
                $orderBy[] = array(
                    'field' => 'created_on',
                    'sort' => 'desc',
                );
            } elseif ($groupBy == 'due_on') {
                $orderBy[] = array(
                    'field' => $fields['task_due_on']['field_id'],
                    'sort' => 'asc',
                );
            } elseif ($groupBy == 'completed') {
                $orderBy[] = array(
                    'field' => $fields['task_completed_on']['field_id'],
                    'sort' => 'desc',
                );
            } else {
                $orderBy[] = array(
                    'field' => 'created_on',
                    'sort' => 'asc',
                );
            }

            $where = array();
            $where['and'] = array();

            $where['and'][] = array(
                'field' => $fields['task_project']['field_id'],
                'query' => array(
                    'eq' => $projectId,
                ),
            );

            $where['and'][] = array(
                'field' => $fields['task_parent_task']['field_id'],
                'query' => array(
                    'em' => true,
                ),
            );

            if ($groupBy == 'completed') {
                $where['and'][] = array(
                    'field' => $fields['task_status']['field_id'],
                    'query' => array(
                        'eq' => array(\App\Task::TASK_STATUS_COMPLETED_NUM),
                    ),
                );
            } else {
                $where['and'][] = array(
                    'field' => $fields['task_status']['field_id'],
                    'query' => array(
                        'eq' => array(\App\Task::TASK_STATUS_UNCOMPLETED_NUM),
                    ),
                );
            }

            $params = array(
                'where' => $where,
                'order_by' => $orderBy,
                'limit' => $limit,
                'offset' => $offset,
            );

            $result = \App\Task::find($params);

            $result = $this->_formatByGroup($projectId, $result, $groupBy, $loggedUser);

        } catch (\Exception $e) {
            return $this->_handleException($e);
        }

        return $this->_handleResult($result);
    }

    // 处理各个分组的排序等
    private function _formatByGroup($projectId, $taskResult, $groupBy, $loggedUser) {
        $result = array();

        $tasks = $taskResult['tasks'];
        $tasks = \Illuminate\Support\Arr::rebuildByCol($tasks, 'task_id');

        if (!$tasks) {
            return $result;
        }

        if ($groupBy == 'priority') {

            $tasks = $this->_formatOrderedTasks($projectId, $tasks, $groupBy);

            $data = array(
                'group_name' => '优先级',
                'group_id' => 0,
                'tasks' => $tasks,
            );

            $result[] = $data;
        } elseif ($groupBy == 'due_on') {

            $tasksWithDueOn = array();
            $tasksWithoutDueOn = array();

            foreach ($tasks as $key => $value) {
                if (!$value['task_due_on'] || $value['task_due_on'] == '0000-00-00') {
                    $tasksWithoutDueOn[$value['task_id']] = $value;
                } else {
                    $tasksWithDueOn[] = $value;
                }
            }

            $tasksWithoutDueOn = $this->_formatOrderedTasks($projectId, $tasksWithoutDueOn, $groupBy);

            $result[] = array(
                'group_name' => '无到期时间',
                'group_id' => 0,
                'tasks' => $tasksWithoutDueOn,
            );
            $result[] = array(
                'group_name' => '有到期时间',
                'group_id' => 0,
                'tasks' => $tasksWithDueOn,
            );
        } elseif ($groupBy == 'executor') {

            $tasksWithExecutor = array();
            $tasksWithoutExecutor = array();

            foreach ($tasks as $key => $value) {
                if ($value['task_executor']) {
                    $name = $value['task_executor']['name'];
                    $userId = $value['task_executor']['user_id'];

                    $tasksWithExecutor[$userId]['group_name'] = $name;
                    $tasksWithExecutor[$userId]['tasks'][$value['task_id']] = $value;

                } else {
                    $tasksWithoutExecutor[$value['task_id']] = $value;
                }
            }

            $rebuildTasks = array();
            $rebuildTasks[0] = array(
                'group_name' => '无执行人',
                'tasks' => $tasksWithoutExecutor,
            );

            if (isset($tasksWithExecutor[$loggedUser['user_id']])) {
                $rebuildTasks[$loggedUser['user_id']] = $tasksWithExecutor[$loggedUser['user_id']];
                unset($tasksWithExecutor[$loggedUser['user_id']]);
            }

            foreach ($tasksWithExecutor as $userId => $value) {
                $rebuildTasks[$userId] = $value;
            }

            foreach ($rebuildTasks as $userId => $tasksWithOneExecutor) {
                $executorTasks = $tasksWithOneExecutor['tasks'];

                $executorTasks = $this->_formatOrderedTasks($projectId, $executorTasks, $groupBy, intval($userId));
                $result[] = array(
                    'group_name' => $tasksWithOneExecutor['group_name'],
                    'group_id' => intval($userId),
                    'tasks' => $executorTasks,
                );
            }
        } elseif ($groupBy == 'completed') {
            $result[] = array(
                'group_name' => '',
                'group_id' => 0,
                'tasks' => array_values($tasks),
            );
        }

        return $result;
    }

    private function _formatOrderedTasks($projectId, $tasks, $groupBy, $userId = 0) {

        $orders = \App\Task::getOrders($projectId, $groupBy, $userId);

        $orderedTasks = array();
        $unOrderedTasks = array();
        foreach ($orders as $taskId) {
            if (isset($tasks[$taskId])) {
                $orderedTasks[] = $tasks[$taskId];
                unset($tasks[$taskId]);
            }
        }
        $unOrderedTasks = array_values($tasks);

        return array_values(array_merge($orderedTasks, $unOrderedTasks));
    }

    /**
     * updateOrder
     *
     * @param  Request $request
     * @param  int  $app_id
     * @return
     */
    public function updateOrder(Request $request, $projectId) {
        try {

            $loggedUser = \App\User::getLoggedUser();

            $params = $request->all();
            $groupBy = $params['group'];
            $groupId = intval($params['group_id']);
            $taskIds = $params['task_ids'];

            $projects = \App\Project::getAll();
            $projectIds = \Illuminate\Support\Arr::getCol($projects, 'project_id');
            if (!in_array($projectId, $projectIds)) {
                throw new APIException('项目id不存在');
            }

            if (!in_array($groupBy, array('priority', 'executor', 'due_on'))) {
                throw new APIException('分组的方式不正确');
            }

            if (!$taskIds) {
                throw new APIException('任务id不能为空');
            }

            \App\Task::setOrders($projectId, $groupBy, $groupId, $taskIds);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->_handleValidationException($e);
        } catch (\Exception $e) {
            return $this->_handleException($e);
        }

        return $this->_handleResult();
    }
}