<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Exceptions\APIException;

class StreamController extends Controller
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

    // 获取某个任务的动态
    public function getAll(Request $request, $taskId) {

        try {
            $loggedUser = \App\User::getLoggedUser();

            $params = $request->all();
            $limit = $params['limit'] ? $params['limit'] : 20;
            $lastStreamId = $params['last_stream_id'] ? $params['last_stream_id'] : 0;

            $streams = \App\Stream::getAll($taskId, $limit, $lastStreamId);

            $loadMore = false;
            if (count($streams) == $limit) {
                $loadMore = true;
            }

            $fields = \App\Table::getFields();

            $streamsResult = array();
            foreach ($streams as $key => $stream) {
                $formatResult = \App\Stream::format($stream, $fields);
                if ($formatResult === false) {
                    continue;
                }

                $streamsResult[] = $formatResult;
            }

            $result = array(
                'load_more' => $loadMore,
                'streams' => $streamsResult,
            );

        } catch (\Exception $e) {
            return $this->_handleException($e);
        }

        return $this->_handleResult($result);
    }
}
