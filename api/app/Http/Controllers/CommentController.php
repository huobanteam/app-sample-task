<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Exceptions\APIException;

class CommentController extends Controller
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

    public function create(Request $request, $taskId) {

        try {
            $loggedUser = \App\User::getLoggedUser();

            $params = $request->all();
            $content = isset($params['content']) ? trim($params['content']) : '';
            $parentCommentId = isset($params['parent_comment_id']) ? intval($params['parent_comment_id']) : 0;
            // 使用文件上传接口得到file_id
            $fileIds = isset($params['file_ids']) ? intval($params['file_ids']) : 0;

            if ($content === '') {
                throw new APIException('评论内容不能为空');
            }

            // 需要给评论中at到的人发送通知 所以这里单独取出来
            preg_match_all('/<a.*?user_id="(\d+)".*?>/', $content, $matches);
            if ($matches && $matches[1]) {
                $atReceiverIds = array_unique($matches[1]);
            } else {
                $atReceiverIds = array();
            }

            $commentReceiverIds = array();

            // 给任务执行人和父任务执行人发送通知
            $task = \App\Task::get($taskId);
            if ($task['task_executor']) {
                $commentReceiverIds[] = $task['task_executor']['user_id'];
            }

            if ($task['task_parent_task']) {
                $parentTask = \App\Task::get($task['task_parent_task']['task_id']);
                if ($parentTask && $parentTask['task_executor']) {
                    $commentReceiverIds[] = $parentTask['task_executor']['task_id'];
                }
            }

            // 给关注者发送通知
            $followedUserIds = \App\Follow::getFollowedUserIds($task['task_id']);

            $commentReceiverIds = array_merge($commentReceiverIds, $followedUserIds);

            // 去重
            $commentReceiverIds = array_unique(array_diff($commentReceiverIds, $atReceiverIds));

            // 调用接口 发表评论
            $comment = \App\Comment::create($taskId, $content, $parentCommentId, $fileIds);

            // 发通知
            if ($atReceiverIds) {
                \App\Notification::sendForCommentAt($task, $loggedUser, $atReceiverIds, $comment['content']);
            }

            if ($commentReceiverIds) {
                \App\Notification::sendForCommentCreated($task, $loggedUser, $commentReceiverIds, $comment['content']);
            }

        } catch (\Exception $e) {
            return $this->_handleException($e);
        }

        return $this->_handleResult($comment);
    }

    public function delete($commentId) {
        try {
            $loggedUser = \App\User::getLoggedUser();

            \App\Comment::delete($commentId);

        } catch (\Exception $e) {
            return $this->_handleException($e);
        }

        return $this->_handleResult();
    }
}
