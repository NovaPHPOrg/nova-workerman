<?php
/**
 * http相关功能重写
 */
// header,header_remove,headers_sent,headers_list,http_response_code

use Workerman\Protocols\Http\Request;
use Workerman\Protocols\Http\Response;
use function nova\plugin\workerman\getHttpConnection;

if (!function_exists('header')) {
    function header($string, bool $replace = true, int $http_response_code = null): void
    {
        /* @var Response $rep */
        [$req,$rep] = getHttpConnection();
        [$key, $value] = explode(':', $string, 2);
        $rep->header($key, $value);
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
        /* @var Response $rep */
        [$req,$rep] = getHttpConnection();
        return $rep->getHeaders();
    }
}

if (!function_exists('http_response_code')) {
    function http_response_code(int $response_code = null): int
    {
        /* @var Response $rep */
        [$req,$rep] = getHttpConnection();
         $rep->withStatus($response_code);
         return $response_code;
    }
}