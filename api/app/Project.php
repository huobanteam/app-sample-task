<?php

namespace App;

use App\BaseModel;

class Project extends BaseModel {

    static $projects = array();

    public function __construct($properties = array()) {
        if ($properties) {
            $this->properties = $properties;
        }
    }

    public function save($updatedNormal = '') {

        $projects = self::getAll();

        $projects[$this->project_id] = $this->toArray();

        return self::saveAll($projects);
    }

    public static function get($projectId) {
        $projects = \App\Project::getAll();
        $project = $projects[$projectId];

        if (!$project) {
            return false;
        }

        return new self($project);
    }

    public function delete() {
        $projectId = $this->project_id;

        $projects = self::getAll();
        if ($projects[$projectId]) {
            unset($projects[$projectId]);
        }

        return self::saveAll($projects);
    }

    public function toJson() {
        return json_encode($this->properties);
    }

    public function toArray() {
        return $this->properties;
    }

    public static function getAllByCategory($projects = array()) {
        if (!$projects) {
            $projects = self::getAll();
            $projects = array_values($projects);
        }

        $result = array(
            'normal' => array(),
            'archived' => array(),
        );
        if ($projects) {
            foreach ($projects as $key => $project) {
                if ($project['is_normal']) {
                    $result['normal'][] = $project;
                } else {
                    $result['archived'][] = $project;
                }
            }
        }

        return $result;
    }

    public static function getAll() {
        if (self::$projects) {
            return self::$projects;
        }

        $key = 'TABLE_' . TABLE_ID . '_APP_ID_' . APP_ID;

        $response = \App\Http\Request\Huoban::get('/v2/storage', array('key' => $key));
        $projects = $response->getContent();
        $projects = json_decode($projects, true);
        if ($projects && $projects['value']) {
            $projects = $projects['value'];
        } else {
            $projects = array();
        }

        self::$projects = $projects;

        return $projects;
    }

    public static function saveAll($projects) {
        $key = 'TABLE_' . TABLE_ID . '_APP_ID_' . APP_ID;

        $data = array(
            'key' => $key,
            'value' => $projects,
        );
        \App\Http\Request\Huoban::post('/v2/storage', $data);

        self::$projects = array();
        return true;
    }

    public static function sort($projects) {
        $projectId2Order = array();
        foreach ($projects as $key => $value) {
            if (!$value['project_id']) {
                unset($projects[$key]);
                continue;
            }

            $projectId2Order[$value['project_id']] = $value['order'];
        }

        asort($projectId2Order);

        $projects = \Illuminate\Support\Arr::rebuildByCol($projects, 'project_id');

        $result = array();
        foreach ($projectId2Order as $projectId => $value) {
            $result[] = $projects[$projectId];
        }

        return $result;
    }
}
