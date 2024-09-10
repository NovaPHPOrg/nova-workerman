<?php

use Workerman\Protocols\Http\Request;

class Adapter
{
    static function Init(Request $request,$config)
    {


        self::loadFunctions();

        self::InitServerVar($request,$config);
    }

    static function loadFunctions(): void
    {
        require_once __DIR__ . "/functions/CommonFunctions.php";
        require_once __DIR__ . "/functions/SessionFunctions.php";
        require_once __DIR__ . "/functions/CookieFunctions.php";
        require_once __DIR__ . "/functions/HttpFunctions.php";

    }



    static function InitServerVar(Request $request,$config): void
    {
        $hostname = gethostname();
        $_SERVER = [
            "PHP_SELF"=> __DIR__ . "/../../../public/index.php",
            "SCRIPT_NAME"=> "/index.php",
            "GATEWAY_INTERFACE"=>"CGI/1.1",
            "SERVER_ADDR"=>gethostbyname($hostname),
            "SERVER_NAME"=>$hostname,
            "SERVER_SOFTWARE"=>"workerman",
            "SERVER_PROTOCOL"=> $request->protocolVersion(),
            "REQUEST_METHOD"=>$request->method(),
            "REQUEST_TIME"=>time(),
            "REQUEST_TIME_FLOAT"=>microtime(true),
            "QUERY_STRING"=>parse_url($request->uri(), PHP_URL_QUERY),
            "DOCUMENT_ROOT"=>__DIR__ . "/../../../public",
            "HTTPS"=>str_starts_with($request->uri(), 'https') ? 'on' : '',
            "REMOTE_ADDR"=>$request->header('x-real-ip') ?? $request->header('x-forwarded-for') ?? $request->header('remote_addr') ?? $request->header('remote_addr'),
            "REMOTE_HOST"=>$request->header('remote_host') ?? "",
            "REMOTE_PORT"=>$request->header('remote_port') ?? "",
            "REMOTE_USER"=>"",
            "REDIRECT_REMOTE_USER"=>"",
            "SCRIPT_FILENAME"=>__DIR__ . "/../../../public/index.php",
            "SERVER_ADMIN"=>"",
            "SERVER_PORT"=>$config['port'],
            "SERVER_SIGNATURE"=>"",
            "PATH_TRANSLATED"=>"",
            "REQUEST_URI" => $request->uri(),
            "PATH_INFO"=>parse_url($request->uri(), PHP_URL_PATH),
        ];

        // 处理http
        foreach ($request->header() as $key => $value) {
            $_SERVER["HTTP_".str_replace("-","_",strtoupper($key))] = $value;
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
        $_REQUEST = array_merge($_GET,$_POST);
        $_SESSION = [];

        if (session_status() == PHP_SESSION_ACTIVE){
            $_SESSION = $request->session->all();
        }

        $_ENV = [];
        $_SERVER['CONTENT_LENGTH'] = $request->header('content-length') ?? 0;
        $_SERVER['CONTENT_TYPE'] = $request->header('content-type') ?? "";
    }
}