<?php
/**
 * Session 相关功能重写
 */

use Random\RandomException;
use Workerman\Protocols\Http\Request;
use Workerman\Protocols\Http\Session;
use Workerman\Protocols\Http\Session\FileSessionHandler;



if (!function_exists('session_commit')) {
    function session_commit(){
        session_write_close();
        // — session_write_close 的别名
    }
}

if (!function_exists('session_create_id')) {
    /**
     * @throws RandomException
     */
    function session_create_id(){
        return \bin2hex(pack('d', microtime(true)) . random_bytes(8));
    }
}


if (!function_exists('session_destroy')) {
    function session_destroy(){
        // — 销毁一个会话中的全部数据
        /* @var Request $req */
        global $req;
        $req->session->flush();
    }
}



if (!function_exists('session_gc')) {
    function session_gc(){
        /* @var Request $req */
        global $req;
        $req->session->gc();
        // — Perform session data garbage collection
    }
}

if (!function_exists('session_get_cookie_params')) {
    function session_get_cookie_params(): array
    {
        return [
            'lifetime' => Session::$lifetime,
            'path' => Session::$cookiePath,
            'domain' => Session::$domain,
            'secure' => Session::$secure,
            'httponly' => Session::$httpOnly,
            'samesite' => Session::$sameSite,
        ];
    }
}

if (!function_exists('session_id')) {
    function session_id($id = null){
        /* @var Request $req */
        global $req;
        return  $req->sessionId($id);
    }
}


if (!function_exists('session_name')) {
    function session_name($name = null){
        // — 读取/设置会话名称
        if ($name === null) {
            return ini_get('session.name');
        }else{
            ini_set('session.name', $name);
        }
        return null;
    }
}

if (!function_exists('session_regenerate_id')) {
    /**
     * @throws RandomException
     */
    function session_regenerate_id(): string
    {
        // — 使用新生成的会话 ID 更新现有会话 ID
        return session_id(session_create_id());
    }
}



if (!function_exists('session_save_path')) {
    function session_save_path(string $path = null): ?string{
        if ($path === null) {
            return ini_get('session.save_path');
        }
        FileSessionHandler::sessionSavePath($path);
        return null;
        // — 读取/设置当前会话的保存路径
    }
}

if (!function_exists('session_set_cookie_params')) {
    function session_set_cookie_params($options): void{
        // — 设置会话 cookie 参数
        if (isset($options['lifetime'])) {
           Session::$lifetime = $options['lifetime'];
        }
        if (isset($options['path'])) {
            Session::$cookiePath = $options['path'];
        }
        if (isset($options['domain'])) {
           Session::$domain = $options['domain'];
        }
        if (isset($options['secure'])) {
           Session::$secure = $options['secure'];
        }
        if (isset($options['httponly'])) {
          Session::$httpOnly = $options['httponly'];
        }
        if (isset($options['samesite'])) {
           Session::$sameSite = $options['samesite'];
        }
    }
}

if (!function_exists('session_set_save_handler')) {
    function session_set_save_handler($sessionHandler, bool $registerShutdown = true): void
    {
        // — 设置用户自定义会话存储函数
        Session::handlerClass(get_class($sessionHandler));
        // 创建一个反射类实例，用于访问和操作 Session 类
        $reflector = new ReflectionClass('Workerman\Protocols\Http\Session');

// 获取私有静态属性 $_handler 的 ReflectionProperty 对象
        $property = $reflector->getProperty('_handler');

// 设置私有属性为可访问
        $property->setAccessible(true);

// 给 $_handler 属性赋值为 $sessionHandler
        $property->setValue(null, $sessionHandler); // null 表示这是一个静态属性
    }
}

if (!function_exists('session_start')) {
    function session_start(){
        /* @var Request $req */
        global $req;
        $req->session();
    }
}

if (!function_exists('session_status')) {
    function session_status(): int
    {
        // — 返回当前会话状态
        /* @var Request $req */
        global $req;
        if ($req->session == null) {
            return PHP_SESSION_NONE;
        }else{
            return PHP_SESSION_ACTIVE;
        }
    }
}

if (!function_exists('session_unset')) {
    function session_unset(){
        // — 释放所有的会话变量
        session_destroy();
    }
}

if (!function_exists('session_write_close')) {
    function session_write_close(){
        // — Write session data and end session
        /* @var Request $req */
        global $req;
        $req->session->save();
    }
}
