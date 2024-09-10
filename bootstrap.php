<?php

namespace nova\plugin\workerman;

use Adapter;
use Workerman\Protocols\Http\Response;
use Workerman\Worker;

require_once __DIR__ . '/../../../vendor/autoload.php';
$config = require __DIR__ . '/../../../workerman.php';
// #### http worker ####
$http_worker = new Worker("http://{$config['ip']}:{$config['port']}");

$http_worker->name = 'Nova WorkerMan';
// 获取CPU核心
$http_worker->count = $config['workers'];

$http_worker->onWorkerStart = function ($worker) {
    global $config;
    echo "Worker started at {$config['ip']}:{$config['port']}\n";
};

$http_connections = [];

function getHttpConnection()
{
    // 获取当前进程的id
    $id = getmypid();

    global $http_connections;
    if (isset($http_connections[$id])) {
        return $http_connections[$id];
    }
    return [null,null];
}

// Emitted when data received
$http_worker->onMessage = function ($connection, $request) {
    global $config,$http_connections;
    ob_start();
    include_once  __DIR__ ."/start.php";

    $rep = new Response(200);
    $req = $request;
    $id = getmypid();

    $http_connections[$id] = [$req,$rep];

    Adapter::Init($request,$config);
    include_once __DIR__."/../../../public/index.php";
    $connection->send($rep->withBody(ob_get_clean()));
    unset($http_connections[$id]);
};

// Run all workers
Worker::runAll();