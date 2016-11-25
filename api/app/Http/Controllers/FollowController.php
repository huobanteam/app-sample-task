<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Exceptions\APIException;

class FollowController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    // 关注操作
    public function create(Request $request, $taskId) {
        try {

            \App\Follow::create($taskId);

        } catch (\Exception $e) {
            return $this->_handleException($e);
        }

        return $this->_handleResult();
    }

    public function delete(Request $request, $taskId) {
        try {
            \App\Follow::delete($taskId);

        } catch (\Exception $e) {
            return $this->_handleException($e);
        }

        return $this->_handleResult();
    }

    public function getAll(Request $request, $taskId) {

        try {

            $follows = \App\Follow::getAll($taskId);

        } catch (\Exception $e) {
            return $this->_handleException($e);
        }

        return $this->_handleResult($follows);
    }
}
