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

use FilesystemIterator;
use nova\plugin\workerman\adapter\Adapter;
use nova\plugin\workerman\adapter\WorkermanApp;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Workerman\Connection\TcpConnection;
use Workerman\Protocols\Http\Request;
use Workerman\Timer;
use Workerman\Worker;

date_default_timezone_set('Asia/Shanghai');
require_once  __DIR__ ."/start.php";
$configFile = __DIR__ . '/../../../config.php';
if (file_exists('/.dockerenv') && file_exists(__DIR__ . '/../../../docker.config.php')) {
    $configFile = __DIR__ . '/../../../docker.config.php';
}
$config = file_exists($configFile) ? require_once $configFile : [];
if (!is_array($config)) {
    $config = [];
}
Adapter::loadFunctions();
// 全局初始化一次 Loader，避免每次请求重复注册 autoloader 导致内存泄露
require_once dirname(__DIR__, 3) . "/nova/framework/core/Loader.php";
global $globalLoader;
$globalLoader = new \nova\framework\core\Loader();

// #### http worker ####
$ip = $config['ip'] ?? '0.0.0.0';
$port = $config['port'] ?? 9528;
Worker::$pidFile = sys_get_temp_dir() . '/workerman_' . md5(__DIR__) . '.pid';
Worker::$logFile = sys_get_temp_dir() . '/workerman.log';

$http_worker = new Worker("http://{$ip}:{$port}");

$http_worker->name = 'Nova WorkerMan';
// 获取CPU核心数量，根据实际情况设置
$cpuCount = cpu_count();
$http_worker->count = $cpuCount * 2;  // 一般建议设置为CPU核心数的1-2倍
$http_worker->reloadable = false;
$http_worker->onWorkerStart = function ($worker) use ($config) {
    if (!Worker::$daemonize
        && $worker->id === 0
    ) {
        // 记录每个文件的修改时间
        global $file_mtime_map;
        $file_mtime_map = [];
        // 每秒检查一次文件修改时间
        Timer::add(1, function () use (&$file_mtime_map) {
            $monitor_dir = dirname(__DIR__, 3) . "/";
            // 递归遍历目录
            $dir_iterator = new RecursiveDirectoryIterator($monitor_dir, FilesystemIterator::SKIP_DOTS);
            $iterator = new RecursiveIteratorIterator($dir_iterator);

            foreach ($iterator as $file) {
                // 确保 $file 是文件，而不是目录
                if (!$file->isFile()) {
                    continue;
                }

                // 只检查 PHP 文件
                if (pathinfo($file->getFilename(), PATHINFO_EXTENSION) !== 'php') {
                    continue;
                }

                $file_path = $file->getRealPath(); // 获取完整路径
                $file_mtime = $file->getMTime();   // 获取文件修改时间

                // 如果文件不在 map 中，初始化它
                if (!isset($file_mtime_map[$file_path])) {
                    $file_mtime_map[$file_path] = $file_mtime;
                    continue;
                }

                // 只有当当前文件的修改时间比记录的时间大时，才触发重载
                if ($file_mtime > $file_mtime_map[$file_path]) {
                    echo "[".date('Y-m-d H:i:s')."] $file_path updated , reloading...\n";

                    if (function_exists('opcache_reset')) {
                        opcache_reset();
                    }

                    // Windows 和 UNIX/Linux 系统使用不同的重载方式
                    if (DIRECTORY_SEPARATOR === '\\') {
                        // Windows 系统
                        Worker::$globalEvent->deleteAllTimer();
                        Worker::stopAll();
                    } else {
                        // UNIX/Linux 系统
                        posix_kill(posix_getppid(), SIGUSR1);
                    }

                    // 更新该文件的修改时间
                    $file_mtime_map[$file_path] = $file_mtime;
                }
            }
        });
    }

};

// Emitted when data received
$http_worker->onMessage = function (TcpConnection $connection, Request $request) {

    try {
        Adapter::InitServerVar($request);
        global $workermanApp;
        $workermanApp = new WorkermanApp($request, $connection);
        $response = $workermanApp->run();
        $connection->send($response);
    } catch (\Throwable|\Error $e) {
        global $config;
        if ($config['debug'] ?? false) {
            // 调试模式下输出详细错误信息
            $traces = [];
            foreach ($e->getTrace() as $i => $trace) {
                $args = isset($trace['args']) ? array_map(function ($arg) {
                    if (is_object($arg)) {
                        return get_class($arg);
                    } elseif (is_array($arg)) {
                        return 'array(' . count($arg) . ')';
                    } else {
                        return gettype($arg);
                    }
                }, $trace['args']) : [];

                $traces[] = sprintf(
                    "#%d %s(%d): %s%s%s(%s)",
                    $i,
                    $trace['file'] ?? 'unknown file',
                    $trace['line'] ?? 0,
                    $trace['class'] ?? '',
                    $trace['type'] ?? '',
                    $trace['function'] ?? '',
                    implode(', ', $args)
                );
            }

            // 获取已加载的扩展信息
            $extensions = get_loaded_extensions();
            sort($extensions); // 按字母顺序排序
            $extensionInfo = sprintf(
                "\n\n系统信息:\nPHP版本: %s\n操作系统: %s\nPHP配置文件(php.ini): %s\n扫描目录(scan): %s\n已加载扩展 (%d):\n%s",
                PHP_VERSION,
                PHP_OS,
                php_ini_loaded_file() ?: '未加载配置文件',
                php_ini_scanned_files() ?: '无额外配置目录',
                count($extensions),
                implode("\n ", array_map(function ($ext) {
                    $version = phpversion($ext);
                    return $version ? "$ext(v$version)" : $ext;
                }, $extensions))
            );

            $error = sprintf(
                "严重错误!\n\n错误类型: [%s]\n错误信息: %s\n文件位置: %s\n错误行号: %d\n\n完整堆栈信息:\n%s%s",
                get_class($e),
                $e->getMessage(),
                $e->getFile(),
                $e->getLine(),
                implode("\n", $traces),
                $extensionInfo
            );

            $style = "
                <style>
                    .error-container { 
                        background: #fff1f0; 
                        color: #d85030; 
                        padding: 15px; 
                        margin: 20px; 
                        border: 1px solid #ffd7d7;
                        font-family: monospace;
                        white-space: pre-wrap;
                        word-wrap: break-word;
                    }
                </style>
            ";

            $connection->send($style . '<div class="error-container">' . htmlspecialchars($error) . '</div>');
        } else {
            $connection->send("Internal Server Error");
        }
    } finally {
        $workermanApp = null;
    }
};

// Run all workers
Worker::runAll();
