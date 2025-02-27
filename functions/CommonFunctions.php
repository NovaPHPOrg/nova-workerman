<?php

declare(strict_types=1);

/*
 * Copyright (c) 2025. Lorem ipsum dolor sit amet, consectetur adipiscing elit.
 * Morbi non lorem porttitor neque feugiat blandit. Ut vitae ipsum eget quam lacinia accumsan.
 * Etiam sed turpis ac ipsum condimentum fringilla. Maecenas magna.
 * Proin dapibus sapien vel ante. Aliquam erat volutpat. Pellentesque sagittis ligula eget metus.
 * Vestibulum commodo. Ut rhoncus gravida arcu.
 */

/**
 * 常规函数
 */

// set_time_limit
use adapter\WorkermanApp;
use Workerman\Connection\TcpConnection;

if (!function_exists('set_time_limit')) {
    function set_time_limit(int $seconds): bool
    {
        // Disable set_time_limit to not stop the worker
        // by default CLI sapi use 0 (unlimited)
        return true;
    }
}

if (!function_exists('connection_aborted')) {
    function connection_aborted(): bool
    {
        return WorkermanApp::instance()->connection()->getStatus() !== TcpConnection::STATUS_ESTABLISHED;

    }
}
function cpu_count(): int
{
    // Windows does not support the number of processes setting.
    if (\DIRECTORY_SEPARATOR === '\\') {
        return 1;
    }
    $count = 4;
    if (\is_callable('shell_exec')) {
        if (\strtolower(PHP_OS) === 'darwin') {
            $count = (int)\shell_exec('sysctl -n machdep.cpu.core_count');
        } else {
            $count = (int)\shell_exec('nproc');
        }
    }
    return $count > 0 ? $count : 2;
}
