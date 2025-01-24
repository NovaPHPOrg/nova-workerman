<?php

namespace nova\plugin\workerman;

use Workerman\Connection\TcpConnection;
use Workerman\Worker;

require __DIR__ . '/start.php';
$config = require __DIR__ . '/../../../config.php';
// #### http worker ####
$http_worker = new Worker("http://{$config['ip']}:{$config['port']}");

$http_worker->name = 'Nova WorkerMan';
// 获取CPU核心
$http_worker->count = $config['workers'];

$http_worker->onWorkerStart = function ($worker) {

};

Adapter::import();
// Emitted when data received
$http_worker->onMessage = function (TcpConnection $connection, $request) {
    global $config;
    $context = new Context($request,$config);
    $adapter = new Adapter($context);
    $id = $connection->worker->id;
    $adapter->initServerVar();
    include_once __DIR__."/../../../public/index.php";
    $adapter->destroy();
    $connection->send($context->getResponse());
    $connection->destroy();
};

// Run all workers
Worker::runAll();