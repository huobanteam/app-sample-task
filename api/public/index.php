<?php

/*
|--------------------------------------------------------------------------
| Create The Application
|--------------------------------------------------------------------------
|
| First we need to get an application instance. This creates an instance
| of the application / container and bootstraps the application so it
| is ready to receive HTTP / Console requests from the environment.
|
*/

$app = require __DIR__.'/../bootstrap/app.php';

$headers = array();

if (isset($_SERVER['HTTP_ORIGIN']) && $_SERVER['HTTP_ORIGIN']) {
    $headers['Access-Control-Allow-Methods'] = 'GET, POST, PUT, DELETE, OPTIONS';
    $headers['Access-Control-Allow-Origin'] = $_SERVER['HTTP_ORIGIN'];
    $headers['Access-Control-Allow-Credentials'] = 'true';
    $headers['Access-Control-Max-Age'] = 2592000;
}

// 设置跨域请求的头 以便前端进行跨域请求
if (strtoupper($_SERVER['REQUEST_METHOD']) == 'OPTIONS') {

	$headers['Access-Control-Allow-Headers'] = 'Content-Type, Authorization, X-Requested-With, X-Task-Table-Id, X-Task-App-Id, X-Task-Ticket';

	$response = response('', 200, $headers);
	$response->send();
	exit;
}

/*
|--------------------------------------------------------------------------
| Run The Application
|--------------------------------------------------------------------------
|
| Once we have the application, we can handle the incoming request
| through the kernel, and send the associated response back to
| the client's browser allowing them to enjoy the creative
| and wonderful application we have prepared for them.
|
*/

$app->run(null, $headers);
