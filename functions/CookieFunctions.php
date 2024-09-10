<?php
/**
 * Cookie相关功能重写
 */

// setcookie
use Workerman\Protocols\Http\Response;

if (!function_exists('setcookie')) {
    function setcookie(string $name, string $value = "", int $expires_or_options = 0, string $path = "", string $domain = "", bool $secure = false, bool $httponly = false ): bool {
        /* @var Response $rep */
        global $rep;
        $rep->cookie($name, $value, $expires_or_options, $path, $domain, $secure, $httponly);
        return true;
    }
}