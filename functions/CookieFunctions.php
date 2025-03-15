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
 * Cookie相关功能重写
 */

// setcookie

use nova\plugin\workerman\adapter\WorkermanApp;

if (!function_exists('setcookie')) {
    function setcookie(string $name, string $value = "", int $expires_or_options = 0, string $path = "", string $domain = "", bool $secure = false, bool $httponly = false): bool
    {

        WorkermanApp::instance()->cookie($name, $value, $expires_or_options, $path, $domain, $secure, $httponly);
        return true;
    }
}
