<?php

namespace App\Http\Middleware;

use Closure;

class Token
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        // 处理ticket、tableId、appId等信息
        $tableId = intval($request->headers->get('X-Task-Table-Id'));
        $appId = intval($request->headers->get('X-Task-App-Id'));
        $ticket = $request->headers->get('X-Task-Ticket');
        $ticket = trim($ticket);

        define('TABLE_ID', $tableId);
        define('APP_ID', $appId);

        // todo 是否为测试环境
        if (strpos($_SERVER['HTTP_HOST'], '.dev.huoban.com') !== false) {
            $isTest = true;
        } else {
            $isTest = false;
        }

        $isTest = false;

        define('IS_TEST', $isTest);
        \App\Http\Request\Huoban::setup($ticket);
        return $next($request);
    }
}
