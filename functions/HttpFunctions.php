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
 * http相关功能重写
 */
// header,header_remove,headers_sent,headers_list,http_response_code

use nova\plugin\workerman\adapter\WorkermanApp;

if (!function_exists('header')) {
    function header($string, bool $replace = true, int $http_response_code = null): void
    {
        // 处理 HTTP 状态码的情况
        if (preg_match('/^HTTP\/\d\.\d\s+(\d+)(?:\s+(.+))?$/i', $string, $matches)) {
            http_response_code((int)$matches[1]);
            return;
        }

        // 处理普通 header
        $parts = explode(':', $string, 2);
        if (count($parts) !== 2) {
            throw new \InvalidArgumentException('Invalid header format');
        }
        [$key, $value] = $parts;
        WorkermanApp::instance()->header(trim($key), trim($value));
        if ($http_response_code !== null) {
            http_response_code($http_response_code);
        }
    }
}

if (!function_exists('header_remove')) {
    function header_remove($name = null): void
    {
        throw new \RuntimeException('header_remove() not support');
    }
}

if (!function_exists('headers_sent')) {
    function headers_sent(&$file = null, &$line = null): bool
    {
        return false;
    }
}

if (!function_exists('headers_list')) {
    function headers_list(): array
    {
        return WorkermanApp::instance()->getHeaders();
    }
}

if (!function_exists('http_response_code')) {
    function http_response_code(int $response_code = 200): int
    {
        WorkermanApp::instance()->setResponseCode($response_code);
        return $response_code;
    }
}

if (! function_exists('getallheaders')) { // It's declared in a dev lib
    /**
     * Fetch all HTTP request headers
     *
     * @return array<string,string>
     * @link https://www.php.net/manual/en/function.getallheaders.php
     */
    function getallheaders(): array
    {
        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($key, 5)))))] = $value;
            }
        }

        return $headers;
    }
}
