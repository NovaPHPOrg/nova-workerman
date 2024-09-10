<?php

namespace nova\plugin\workerman;

use Adapter;
use Workerman\Worker;

require_once __DIR__ . '/../../../vendor/autoload.php';
$config = require __DIR__ . '/../../../workerman.php';
// #### http worker ####
$http_worker = new Worker("http://{$config['ip']}:{$config['port']}");

$http_worker->name = 'Nova WorkerMan';
// 获取CPU核心
$http_worker->count = $config['workers'];

// Emitted when data received
$http_worker->onMessage = function ($connection, $request) {
    global $config;
    ob_start();
    include_once  __DIR__ ."/start.php";
    Adapter::Init($request,$config);
    include_once __DIR__."/../../../public/index.php";
    $connection->send(ob_get_clean());
};

// Run all workers
Worker::runAll();