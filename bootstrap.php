<?php

declare(strict_types=1);

/*
 * Copyright (c) 2025. Lorem ipsum dolor sit amet, consectetur adipiscing elit.
 * Morbi non lorem porttitor neque feugiat blandit. Ut vitae ipsum eget quam lacinia accumsan.
 * Etiam sed turpis ac ipsum condimentum fringilla. Maecenas magna.
 * Proin dapibus sapien vel ante. Aliquam erat volutpat. Pellentesque sagittis ligula eget metus.
 * Vestibulum commodo. Ut rhoncus gravida arcu.
 */

namespace nova\plugin\workerman;

use adapter\Adapter;
use adapter\WorkermanApp;
use Workerman\Connection\TcpConnection;
use Workerman\Protocols\Http\Request;
use Workerman\Worker;

require_once  __DIR__ ."/start.php";
$config = require_once __DIR__ . '/../../../config.php';
Adapter::loadFunctions();
// #### http worker ####
$http_worker = new Worker("http://{$config['ip']}:{$config['port']}");

$http_worker->name = 'Nova WorkerMan';
// 获取CPU核心数量，根据实际情况设置
$cpuCount = cpu_count();
$http_worker->count = $cpuCount * 2;  // 一般建议设置为CPU核心数的1-2倍

$http_worker->onWorkerStart = function ($worker) use ($config) {
    $pid = getmypid();
    echo "Worker started at {$config['ip']}:{$config['port']},pid:{$pid}\n";
};

// Emitted when data received
$http_worker->onMessage = function (TcpConnection $connection, Request $request) {
    static $count;
    if ($count === null) {
        $count = 0;
    }
    $count++;

    try {
        Adapter::InitServerVar($request);
        global $workermanApp;
        $workermanApp = new WorkermanApp($request, $connection);
        $response = $workermanApp->run();
        $connection->send($response);
    } catch (\Throwable|\Error $e) {
        // 错误处理
        $connection->send("Internal Server Error: " . $e->getMessage());
    } finally {
        $workermanApp = null;
    }

    // 优化内存管理策略
    if ($count % 1000 === 0) {  // 调整为更合理的频率
        gc_collect_cycles();
        if (memory_get_usage() > 128 * 1024 * 1024) {  // 设置内存上限，例如128MB
            exit(0);
        }
    }
};

// Run all workers
Worker::runAll();
