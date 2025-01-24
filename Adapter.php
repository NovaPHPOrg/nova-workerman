<?php
namespace nova\plugin\workerman;
use nova\framework\App;

class Adapter
{
    protected Context $context;
    public function __construct(Context $context)
    {
        $this->context = $context;
    }

    static function import(): void
    {
        require_once __DIR__ . "/functions/CommonFunctions.php";
        require_once __DIR__ . "/functions/SessionFunctions.php";
        require_once __DIR__ . "/functions/CookieFunctions.php";
        require_once __DIR__ . "/functions/HttpFunctions.php";
    }









    function initServerVar(): void
    {
        $hostname = gethostname();
        $request = $this->context->getRequest();
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
            "SERVER_PORT"=>$this->context->config('port'),
            "SERVER_SIGNATURE"=>"",
            "PATH_TRANSLATED"=>"",
            "REQUEST_URI" => $request->uri(),
            "PATH_INFO"=>parse_url($request->uri(), PHP_URL_PATH),
        ];

        // 处理http
        foreach ($request->header() as $key => $value) {
            $_SERVER["HTTP_".str_replace("-","_",strtoupper($key))] = $value;
        }
        // 处理cookie
        $_COOKIE = array_map(function ($value) {
            return $value;
        }, $request->cookie());
        // 处理get
        $_GET = array_map(function ($value) {
            return $value;
        }, $request->get());
        // 处理post
        $_POST = array_map(function ($value) {
            return $value;
        }, $request->post());
        // 处理files
        $_FILES = array_map(function ($value) {
            return $value;
        }, $request->file());
        $_REQUEST = array_merge($_GET,$_POST);
        $_SESSION = [];

        if (session_status() == PHP_SESSION_ACTIVE){
            $_SESSION = $request->session->all();
        }

        $_ENV = [];
        $_SERVER['CONTENT_LENGTH'] = $request->header('content-length') ?? 0;
        $_SERVER['CONTENT_TYPE'] = $request->header('content-type') ?? "";
    }

    function destroy(): void
    {
        $instance = App::getInstance();
        unset($instance);
    }
}