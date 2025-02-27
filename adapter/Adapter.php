<?php

declare(strict_types=1);

/*
 * Copyright (c) 2025. Lorem ipsum dolor sit amet, consectetur adipiscing elit.
 * Morbi non lorem porttitor neque feugiat blandit. Ut vitae ipsum eget quam lacinia accumsan.
 * Etiam sed turpis ac ipsum condimentum fringilla. Maecenas magna.
 * Proin dapibus sapien vel ante. Aliquam erat volutpat. Pellentesque sagittis ligula eget metus.
 * Vestibulum commodo. Ut rhoncus gravida arcu.
 */

namespace adapter;

use Workerman\Protocols\Http\Request;

class Adapter
{
    public static function loadFunctions(): void
    {
        require_once __DIR__ . "/../functions/CommonFunctions.php";
        require_once __DIR__ . "/../functions/SessionFunctions.php";
        require_once __DIR__ . "/../functions/CookieFunctions.php";
        require_once __DIR__ . "/../functions/HttpFunctions.php";

    }

    public static function InitServerVar(Request $request): void
    {
        $dir  = dirname(__DIR__, 3);
        $hostname = gethostname();
        $_SERVER = [
            "PHP_SELF" =>  "$dir/public/index.php",
            "SCRIPT_NAME" => "/index.php",
            "GATEWAY_INTERFACE" => "CGI/1.1",
            "SERVER_ADDR" => gethostbyname($hostname),
            "SERVER_NAME" => $hostname,
            "SERVER_SOFTWARE" => "workerman",
            "SERVER_PROTOCOL" => $request->protocolVersion(),
            "REQUEST_METHOD" => $request->method(),
            "REQUEST_TIME" => time(),
            "REQUEST_TIME_FLOAT" => microtime(true),
            "QUERY_STRING" => parse_url($request->uri(), PHP_URL_QUERY),
            "DOCUMENT_ROOT" => "$dir/public",
            "HTTPS" => str_starts_with($request->uri(), 'https') ? 'on' : '',
            "REMOTE_ADDR" => $request->connection->getRemoteAddress(),
            "REMOTE_HOST" => $request->connection->getRemoteIp(),
            "REMOTE_PORT" => $request->connection->getRemotePort(),
            "REMOTE_USER" => "",
            "REDIRECT_REMOTE_USER" => "",
            "SCRIPT_FILENAME" => __DIR__ . "$dir/public/index.php",
            "SERVER_ADMIN" => "",
            "SERVER_PORT" => $request->connection->getLocalPort(),
            "SERVER_SIGNATURE" => "",
            "PATH_TRANSLATED" => "",
            "REQUEST_URI" => $request->uri(),
            "PATH_INFO" => parse_url($request->uri(), PHP_URL_PATH),
        ];

        // 处理http
        foreach ($request->header() as $key => $value) {
            $_SERVER["HTTP_".str_replace("-", "_", strtoupper($key))] = $value;
        }
        $_COOKIE = [];
        // 处理cookie
        foreach ($request->cookie() as $key => $value) {
            $_COOKIE[$key] = $value;
        }
        $_GET = [];
        // 处理get
        foreach ($request->get() as $key => $value) {
            $_GET[$key] = $value;
        }
        $_POST = [];
        // 处理post
        foreach ($request->post() as $key => $value) {
            $_POST[$key] = $value;
        }
        $_FILES = [];
        // 处理files
        foreach ($request->file() as $key => $value) {
            $_FILES[$key] = $value;
        }
        $_REQUEST = array_merge($_GET, $_POST);
        $_SESSION = [];

        if (session_status() == PHP_SESSION_ACTIVE) {
            try {
                $_SESSION = $request->session()->all();
            } catch (\Exception $e) {
                $_SESSION = [];
            }
        }

        //$_ENV = [];
        $_SERVER['CONTENT_LENGTH'] = $request->header('content-length') ?? 0;
        $_SERVER['CONTENT_TYPE'] = $request->header('content-type') ?? "";
    }
}
