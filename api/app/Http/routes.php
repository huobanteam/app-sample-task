<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/
$app->group(['middleware' => 'token', 'namespace' => 'App\Http\Controllers'], function($app) {

    // 项目-创建
    $app->post('/api/project', 'ProjectController@create');

    // 项目-删除
    $app->delete('/api/project/{projectId}', 'ProjectController@delete');

    // 项目-获取全部
    $app->get('/api/projects', 'ProjectController@getAll');

    // 项目-修改
    $app->put('/api/project/{projectId}', 'ProjectController@update');

    // 项目-排序
    $app->post('/api/project/order', 'ProjectController@updateOrder');

    // 任务-创建
    $app->post('/api/task', 'TaskController@create');

    // 任务-获取单个
    $app->get('/api/task/{taskId}', 'TaskController@get');

    // 任务-更新
    $app->put('/api/task/{taskId}', 'TaskController@update');

    // 任务-删除
    $app->delete('/api/task/{taskId}', 'TaskController@delete');

    // 任务-筛选
    $app->post('/api/task/find', 'TaskController@find');

    // 任务-获取某个项目下的所有任务
    $app->get('/api/tasks/project/{projectId}', 'TaskController@getAll');

    // 任务-排序
    $app->post('/api/task/project/{projectId}/order', 'TaskController@updateOrder');

    // 任务评论-创建
    $app->post('/api/comment/task/{taskId}', 'CommentController@create');

    // 任务评论-删除
    $app->delete('/api/comment/{commentId}', 'CommentController@delete');

    // 任务动态-获取
    $app->get('/api/streams/task/{taskId}', 'StreamController@getAll');

    // 任务关注-关注
    $app->post('/api/follow/task/{taskId}', 'FollowController@create');

    // 任务关注-取消关注
    $app->delete('/api/follow/task/{taskId}', 'FollowController@delete');

    // 任务关注-获取关注人信息
    $app->get('/api/follows/task/{taskId}', 'FollowController@getAll');

    // 任务关注-获取登录用户信息
    $app->get('/api/user', 'UserController@getLogged');

    // 文件-上传
    $app->post('/api/file/upload', 'FileController@upload');

});




