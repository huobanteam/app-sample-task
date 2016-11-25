<?php

namespace App;

use App\BaseModel;

class Notification extends BaseModel {

    const TITLE_NUMS = 60;

    public static function substr($data) {
        $total = count($data);

        $nums = self::TITLE_NUMS / $total;
        $result = array();
        foreach ($data as $key => $value) {
            $result[] = \Illuminate\Support\Str::substr($value, 0, $nums);
        }

        return $result;
    }

    public static function sendForUpdateExecutor($task, $sender, $executor, $oldExecutor = array(), $followedUserIds = array(), $parentTaskExecutor = array()) {

        $openUrl = '/item/' . $task['task_id'];

        // 发送给执行人
        if (!$executor) {
            return ;
        }

        list($task['task_title']) = self::substr(array($task['task_title']));

        $existUserIds = array();
        $existUserIds[] = $sender['user_id'];

        if (!in_array($executor['user_id'], $existUserIds)) {
            $pushMessage = $sender['name'] . ' 将“' . $task['task_title'] . '”任务分配给你';
            $title = $sender['name'] . ' 将“' . $task['task_title'] . '”任务分配给你';
            $content = '';
            self::send($executor['user_id'], $pushMessage, $title, $content, $openUrl);

            $existUserIds[] = $executor['user_id'];
        }

        // 给原执行人发送通知
        if ($oldExecutor && !in_array($oldExecutor['user_id'], $existUserIds)) {

            $pushMessage = $sender['name'] . ' 将“' . $task['task_title'] . '”任务重新分配给 ' . $executor['name'];
            $title = $sender['name'] . ' 将“' . $task['task_title'] . '”任务重新分配给 ' . $executor['name'];
            $content = '';

            self::send($oldExecutor['user_id'], $pushMessage, $title, $content, $openUrl);

            $existUserIds[] = $oldExecutor['user_id'];
        }

        // 给关注者发送通知
        if ($followedUserIds) {
            $pushMessage = $sender['name'] . ' 将“' . $task['task_title'] . '”任务分配给 ' . $executor['name'];
            $title = $sender['name'] . ' 将“' . $task['task_title'] . '”任务分配给 ' . $executor['name'];
            $content = '';

            foreach ($followedUserIds as $followedUserId) {
                if (in_array($followedUserId, $existUserIds)) {
                    continue;
                }

                self::send($followedUserId, $pushMessage, $title, $content, $openUrl);

                $existUserIds[] = $followedUserId;
            }
        }

        // 给父的执行人发送通知
        if ($parentTaskExecutor && !in_array($parentTaskExecutor['user_id'], $existUserIds)) {
            $pushMessage = $sender['name'] . ' 将“' . $task['task_title'] . '”任务分配给 ' . $executor['name'];
            $title = $sender['name'] . ' 将“' . $task['task_title'] . '”任务分配给 ' . $executor['name'];
            $content = '';

            self::send($parentTaskExecutor['user_id'], $pushMessage, $title, $content, $openUrl);
        }
    }

    public static function sendForRemoveExecutor($task, $sender, $oldExecutor, $followedUserIds = array(), $parentTaskExecutor = array()) {

        $openUrl = '/item/' . $task['task_id'];

        $existUserIds = array();
        $existUserIds[] = $sender['user_id'];

        list($task['task_title']) = self::substr(array($task['task_title']));

        if (!in_array($oldExecutor['user_id'], $existUserIds)) {
            $pushMessage = $sender['name'] . ' 取消了“' . $task['task_title'] . '”任务对你的分配';
            $title = $sender['name'] . ' 取消了“' . $task['task_title'] . '”任务对你的分配';
            $content = '';
            self::send($oldExecutor['user_id'], $pushMessage, $title, $content, $openUrl);

            $existUserIds[] = $oldExecutor['user_id'];
        }

        // 给关注者发送通知
        if ($followedUserIds) {
            $pushMessage = $sender['name'] . ' 取消了“' . $task['task_title'] . '”任务对' . $oldExecutor['name'] . '的分配';
            $title = $sender['name'] . ' 取消了“' . $task['task_title'] . '”任务对' . $oldExecutor['name'] . '的分配';
            $content = '';

            foreach ($followedUserIds as $followedUserId) {
                if (in_array($followedUserId, $existUserIds)) {
                    continue;
                }

                self::send($followedUserId, $pushMessage, $title, $content, $openUrl);

                $existUserIds[] = $followedUserId;
            }
        }

        // 给父“' . $task['task_title'] . '”任务的执行人发送通知
        if ($parentTaskExecutor && !in_array($parentTaskExecutor['user_id'], $existUserIds)) {
            $pushMessage = $sender['name'] . ' 取消了“' . $task['task_title'] . '”任务对' . $oldExecutor['name'] . '的分配';
            $title = $sender['name'] . ' 取消了“' . $task['task_title'] . '”任务对' . $oldExecutor['name'] . '的分配';
            $content = '';

            self::send($parentTaskExecutor['user_id'], $pushMessage, $title, $content, $openUrl);
        }
    }

    public static function sendForUpdateDueOn($task, $sender, $dueOn, $executor = array(), $followedUserIds = array(), $parentTaskExecutor = array()) {

        list($task['task_title']) = self::substr(array($task['task_title']));

        $data = array(
            'open_url' => '/item/' . $task['task_id'],
            'push_message' => $sender['name'] . ' 修改“' . $task['task_title'] . '”任务的到期时间为' . $dueOn,
            'title' => $sender['name'] . ' 修改“' . $task['task_title'] . '”任务的到期时间为' . $dueOn,
            'content' => '',
        );

        self::__commonSend($sender, $data, $executor, $followedUserIds, $parentTaskExecutor);
    }

    public static function sendForRemoveDueOn($task, $sender, $dueOn, $executor = array(), $followedUserIds = array(), $parentTaskExecutor = array()) {

        list($task['task_title']) = self::substr(array($task['task_title']));

        $data = array(
            'open_url' => '/item/' . $task['task_id'],
            'push_message' => $sender['name'] . ' 清空了“' . $task['task_title'] . '”任务的到期时间',
            'title' => $sender['name'] . ' 清空了“' . $task['task_title'] . '”任务的到期时间',
            'content' => '',
        );
        self::__commonSend($sender, $data, $executor, $followedUserIds, $parentTaskExecutor);

    }

    public static function sendForComplete($task, $sender, $executor = array(), $followedUserIds = array(), $parentTaskExecutor = array()) {

        list($task['task_title']) = self::substr(array($task['task_title']));

        $data = array(
            'open_url' => '/item/' . $task['task_id'],
            'push_message' => $sender['name'] . ' 完成了“' . $task['task_title'] . '”任务',
            'title' => $sender['name'] . ' 完成了“' . $task['task_title'] . '”任务',
            'content' => '',
        );
        self::__commonSend($sender, $data, $executor, $followedUserIds, $parentTaskExecutor);

    }

    public static function sendForUncomplete($task, $sender, $executor = array(), $followedUserIds = array(), $parentTaskExecutor = array()) {

        list($task['task_title']) = self::substr(array($task['task_title']));

        $data = array(
            'open_url' => '/item/' . $task['task_id'],
            'push_message' => $sender['name'] . ' 将“' . $task['task_title'] . '”任务调整为未完成',
            'title' => $sender['name'] . ' 将“' . $task['task_title'] . '”任务调整为未完成',
            'content' => '',
        );
        self::__commonSend($sender, $data, $executor, $followedUserIds, $parentTaskExecutor);

    }

    public static function sendForDelete($task, $sender, $executor = array(), $followedUserIds = array(), $parentTaskExecutor = array()) {

        list($task['task_title']) = self::substr(array($task['task_title']));

        $data = array(
            'open_url' => '',
            'push_message' => $sender['name'] . ' 删除了“' . $task['task_title'] . '”任务',
            'title' => $sender['name'] . ' 删除了“' . $task['task_title'] . '”任务',
            'content' => '',
        );
        self::__commonSend($sender, $data, $executor, $followedUserIds, $parentTaskExecutor);

    }

    public static function sendForAddFile($task, $sender, $executor = array(), $followedUserIds = array(), $parentTaskExecutor = array(), $file = array()) {

        list($task['task_title'], $file['name']) = self::substr(array($task['task_title'], $file['name']));

        $data = array(
            'open_url' => '/item/' . $task['task_id'],
            'push_message' => $sender['name'] . ' 在“' . $task['task_title'] . '”任务中添加了附件 ' . $file['name'],
            'title' => $sender['name'] . ' 在“' . $task['task_title'] . '”任务中添加了附件 ' . $file['name'],
            'content' => '',
        );
        self::__commonSend($sender, $data, $executor, $followedUserIds, $parentTaskExecutor);

    }

    public static function sendForRemoveFile($task, $sender, $executor = array(), $followedUserIds = array(), $parentTaskExecutor = array(), $file = array()) {

        list($task['task_title'], $file['name']) = self::substr(array($task['task_title'], $file['name']));

        $data = array(
            'open_url' => '/item/' . $task['task_id'],
            'push_message' => $sender['name'] . ' 在“' . $task['task_title'] . '”任务中删除了附件 ' . $file['name'],
            'title' => $sender['name'] . ' 在“' . $task['task_title'] . '”任务中删除了附件 ' . $file['name'],
            'content' => '',
        );
        self::__commonSend($sender, $data, $executor, $followedUserIds, $parentTaskExecutor);

    }

    public static function sendForUpdateTitle($task, $sender, $executor = array(), $followedUserIds = array(), $parentTaskExecutor = array()) {

        list($task['task_title']) = self::substr(array($task['task_title']));

        $data = array(
            'open_url' => '/item/' . $task['task_id'],
            'push_message' => $sender['name'] . ' 修改了“' . $task['task_title'] . '”任务的标题',
            'title' => $sender['name'] . ' 修改了“' . $task['task_title'] . '”任务的标题',
            'content' => '',
        );

        self::__commonSend($sender, $data, $executor, $followedUserIds, $parentTaskExecutor);

    }

    public static function sendForUpdateDescription($task, $sender, $executor = array(), $followedUserIds = array(), $parentTaskExecutor = array()) {

        list($task['task_title']) = self::substr(array($task['task_title']));

        $data = array(
            'open_url' => '/item/' . $task['task_id'],
            'push_message' => $sender['name'] . ' 修改了“' . $task['task_title'] . '”任务的描述',
            'title' => $sender['name'] . ' 修改了“' . $task['task_title'] . '”任务的描述',
            'content' => '',
        );
        self::__commonSend($sender, $data, $executor, $followedUserIds, $parentTaskExecutor);

    }

    public static function sendForRemoveDescription($task, $sender, $executor = array(), $followedUserIds = array(), $parentTaskExecutor = array()) {

        list($task['task_title']) = self::substr(array($task['task_title']));

        $data = array(
            'open_url' => '/item/' . $task['task_id'],
            'push_message' => $sender['name'] . ' 清空了“' . $task['task_title'] . '”任务的描述',
            'title' => $sender['name'] . ' 清空了“' . $task['task_title'] . '”任务的描述',
            'content' => '',
        );
        self::__commonSend($sender, $data, $executor, $followedUserIds, $parentTaskExecutor);

    }

    public static function sendForAddChildTask($task, $sender, $executor = array(), $followedUserIds = array(), $parentTaskExecutor = array(), $subTask = array()) {

        list($task['task_title'], $subTask['task_title']) = self::substr(array($task['task_title'], $subTask['task_title']));

        $data = array(
            'open_url' => '/item/' . $task['task_id'],
            'push_message' => $sender['name'] . ' 在“' . $task['task_title'] . '”任务中添加了子任务 “' . $subTask['task_title'] . '”',
            'title' => $sender['name'] . ' 在“' . $task['task_title'] . '”任务中添加了子任务 “' . $subTask['task_title'] . '”',
            'content' => '',
        );
        self::__commonSend($sender, $data, $executor, $followedUserIds, $parentTaskExecutor);

    }

    public static function sendForRemoveChildTask($task, $sender, $executor = array(), $followedUserIds = array(), $parentTaskExecutor = array(), $subTask = array()) {

        list($task['task_title'], $subTask['task_title']) = self::substr(array($task['task_title'], $subTask['task_title']));

        $data = array(
            'open_url' => '/item/' . $task['task_id'],
            'push_message' => $sender['name'] . ' 删除了“' . $task['task_title'] . '”任务中的子任务 “' . $subTask['task_title'] . '”',
            'title' => $sender['name'] . ' 删除了“' . $task['task_title'] . '”任务中的子任务 “' . $subTask['task_title'] . '”',
            'content' => '',
        );
        self::__commonSend($sender, $data, $executor, $followedUserIds, $parentTaskExecutor);

    }

    private static function __commonSend($sender, $data, $executor, $followedUserIds, $parentTaskExecutor) {

        $existUserIds = array();
        $existUserIds[] = $sender['user_id'];

        $pushMessage = $data['push_message'];
        $title = $data['title'];
        $content = $data['content'];
        $openUrl = $data['open_url'];

        // 发送给执行人
        if ($executor && !in_array($executor['user_id'], $existUserIds)) {
            self::send($executor['user_id'], $pushMessage, $title, $content, $openUrl);
            $existUserIds[] = $executor['user_id'];
        }

        // 给关注者发送通知
        if ($followedUserIds) {

            foreach ($followedUserIds as $followedUserId) {
                if (in_array($followedUserId, $existUserIds)) {
                    continue;
                }

                self::send($followedUserId, $pushMessage, $title, $content, $openUrl);

                $existUserIds[] = $followedUserId;
            }
        }

        // 给父任务的执行人发送通知
        if ($parentTaskExecutor && !in_array($parentTaskExecutor['user_id'], $existUserIds)) {

            self::send($parentTaskExecutor['user_id'], $pushMessage, $title, $content, $openUrl);
        }
    }

    public static function sendForCommentAt($task, $sender, $atReceiverIds, $content) {

        list($task['task_title']) = self::substr(array($task['task_title']));

        $content = str_replace('<br>', ' ', $content);
        $content = strip_tags($content);

        $openUrl = '/item/' . $task['task_id'];
        $pushMessage = $sender['name'] . ' 在“' . $task['task_title'] . '”任务中@了你';
        $title = $sender['name'] . ' 在“' . $task['task_title'] . '”任务中@了你';

        foreach ($atReceiverIds as $key => $userId) {
            if ($userId == $sender['user_id']) {
                continue;
            }
            self::send($userId, $pushMessage, $title, $content, $openUrl);
        }
    }

    public static function sendForCommentCreated($task, $sender, $commentReceiverIds, $content) {

        list($task['task_title']) = self::substr(array($task['task_title']));

        $content = str_replace('<br>', ' ', $content);
        $content = strip_tags($content);

        $openUrl = '/item/' . $task['task_id'];
        $pushMessage = $sender['name'] . ' 评论了“' . $task['task_title'] . '”任务';
        $title = $sender['name'] . ' 评论了“' . $task['task_title'] . '”任务';

        foreach ($commentReceiverIds as $key => $userId) {
            if ($userId == $sender['user_id']) {
                continue;
            }

            self::send($userId, $pushMessage, $title, $content, $openUrl);
        }
    }

    public static function send($receiverId, $pushMessage, $title, $content, $openUrl = '', $senderId = 0, $options = array()) {

        $params = array(
            'receiver_id' => $receiverId,
            'push_message' => $pushMessage,
            'title' => $title,
            'content' => $content,
            'open_url' => $openUrl,
        );

        if ($senderId) {
            $params['created_by_id'] = $senderId;
        }

        \App\Http\Request\Huoban::post('/v2/notification', $params, $options);
    }
}
