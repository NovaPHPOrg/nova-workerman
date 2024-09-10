<?php
/**
 * 常规函数
 */
// set_time_limit
if (!function_exists('set_time_limit')) {
    function set_time_limit(int $seconds): void
    {
        // — 设置脚本最大执行时间
        ini_set('max_execution_time', $seconds);
    }
}