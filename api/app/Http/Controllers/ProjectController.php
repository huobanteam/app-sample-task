<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Exceptions\APIException;
use Illuminate\Contracts\Validation\Validator;

class ProjectController extends Controller {

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

            $name = isset($params['name']) ? trim($params['name']) : '';
            if ($name === '') {
                throw new APIException('名称不能为空');
            }

            if (\Illuminate\Support\Str::length($params['name']) > 20) {
                throw new APIException('名称最多为20个字符');
            }

            $projects = \App\Project::getAll();
            // 自定义的自增ID 建议直接使用 uniqid() 来定义主键id
            if ($projects) {
                $maxId = \Illuminate\Support\Arr::maxCol($projects, 'project_id');
            } else {
                $maxId = 0;
            }

            $project = new \App\Project();
            $project->project_id = $maxId + 1;
            $project->name = $name;
            $project->order = count($projects) + 1;
            $project->is_normal = true;
            $project->uncompleted_num = 0;
            $project->created_on = date('Y-m-d H:i:s');
            $project->created_by_id = $loggedUser['user_id'];
            $project->save();

            $result = $project->toArray();
        } catch (\Exception $e) {
            return $this->_handleException($e);
        }

        return $this->_handleResult($result);
    }

    /**
     * delete
     *
     * @param  int $project_id
     * @return
     */
    public function delete($projectId) {
        try {

            $loggedUser = \App\User::getLoggedUser();

            $project = \App\Project::get($projectId);
            if (!$project) {
                throw new APIException('项目不存在');
            }

            $project->delete();

            // 删除项目操作会连同项目下的任务一同删除
            \App\Task::deleteByProjectId($projectId);

        } catch (\Exception $e) {
            return $this->_handleException($e);
        }

        return $this->_handleResult();
    }

    /**
     * getAll
     *
     * @return
     */
    public function getAll() {
        try {

            $loggedUser = \App\User::getLoggedUser();

            $projects = \App\Project::getAll();
            $projectIds = \Illuminate\Support\Arr::getCol($projects, 'project_id');

            // 获取未完成任务数
            $projectsUncompletedNum = \App\Task::getUncompletedNum($projectIds);
            foreach ($projects as $key => $value) {
                $projects[$key]['uncompleted_num'] = intval($projectsUncompletedNum[$value['project_id']]);
            }

            $projects = array_values($projects);
            $projects = \App\Project::sort($projects);

            // 将所有项目分为“正常”和“已归档” 返回
            $result = \App\Project::getAllByCategory($projects);

        } catch (\Exception $e) {
            return $this->_handleException($e);
        }

        return $this->_handleResult($result);
    }

    /**
     * update
     *
     * @param  Request $request
     * @param  int  $project_id
     * @return
     */
    public function update(Request $request, $projectId) {
        try {

            $loggedUser = \App\User::getLoggedUser();

            $project = \App\Project::get($projectId);
            if (!$project) {
                throw new APIException('项目不存在');
            }

            $params = $request->all();

            // 更新名称
            if (isset($params['name'])) {

                $params['name'] = trim($params['name']);

                if ($params['name'] === '') {
                    throw new APIException('名称不能为空');
                }

                if (\Illuminate\Support\Str::length($params['name']) > 20) {
                    throw new APIException('名称最多为20个字符');
                }

                $project->name = $params['name'];
            }

            // 更新是否为归档的状态
            $updatedNormal = '';
            if (isset($params['is_normal'])) {
                if (!$params['is_normal'] || $params['is_normal'] === 'false') {
                    $isNormal = false;
                    // 更新顺序
                } else {
                    $isNormal = true;
                }

                // 从归档设置为正常
                if ($project->is_normal === false && $isNormal === true) {
                    $updatedNormal = true;
                }

                // 从正常设置为归档
                if ($project->is_normal === true && $isNormal === false) {
                    $updatedNormal = false;
                }

                $project->is_normal = $isNormal;
            }

            $project->save($updatedNormal);

            $result = $project->toArray();

        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->_handleValidationException($e);
        } catch (\Exception $e) {
            return $this->_handleException($e);
        }

        return $this->_handleResult($result);
    }

    /**
     * updateOrder
     *
     * @param  Request $request
     * @param  int  $app_id
     * @return
     */
    public function updateOrder(Request $request) {
        try {

            $loggedUser = \App\User::getLoggedUser();

            $params = $request->all();

            if (!$params || !$params['project_ids']) {
                throw new APIException('排序的ids不能为空');
            }

            $projectIds = $params['project_ids'];

            $isNormal = true;
            $result = \App\Project::getAllByCategory();
            $projectsNormal = $result['normal'];

            if (count($projectsNormal) != count($projectIds)) {
                throw new APIException('order中项目id的数量不对');
            }

            $projects = array();
            $order = 1;
            $projectsNormal = \Illuminate\Support\Arr::rebuildByCol($projectsNormal, 'project_id');
            foreach ($projectIds as $projectId) {
                if (!isset($projectsNormal[$projectId])) {
                    throw new APIException('order中的有些项目id不存在');
                }

                if (!$projectsNormal[$projectId]['is_normal']) {
                    throw new APIException('归档的项目不能排序');
                }

                $projectsNormal[$projectId]['order'] = $order;
                $order ++;
                $projects[$projectId] = $projectsNormal[$projectId];
            }

            $projectsArchived = $result['archived'];
            foreach ($projectsArchived as $key => $value) {
                $projectsArchived[$key]['order'] = 0;
                $projects[$value['project_id']] = $projectsArchived[$key];
            }

            \App\Project::saveAll($projects);

            $result = \App\Project::getAllByCategory($projects);
            $projects = $result['normal'];

            // 获取未完成任务数
            $projectIds = \Illuminate\Support\Arr::getCol($projects, 'project_id');
            $projectsUncompletedNum = \App\Task::getUncompletedNum($projectIds);
            foreach ($projects as $key => $value) {
                $projects[$key]['uncompleted_num'] = intval($projectsUncompletedNum[$value['project_id']]);
            }

            $projects = \App\Project::sort($projects);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->_handleValidationException($e);
        } catch (\Exception $e) {
            return $this->_handleException($e);
        }

        return $this->_handleResult($projects);
    }
}