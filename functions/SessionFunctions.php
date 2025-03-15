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
 * Session 相关功能重写
 */

use nova\framework\core\Context;
use nova\plugin\workerman\adapter\WorkermanApp;
use Random\RandomException;
use Workerman\Protocols\Http\Request;

if (!function_exists('session_commit')) {
    function session_commit(): void
    {
        session_write_close();
        // — session_write_close 的别名
    }
}

if (!function_exists('session_create_id')) {
    /**
     * @throws RandomException|Exception
     */
    function session_create_id(): string
    {
        return \bin2hex(pack('d', microtime(true)) . random_bytes(8));
    }
}

if (!function_exists('session_destroy')) {
    function session_destroy(): bool
    {
        $_SESSION = [];
        return WorkermanApp::instance()->session()?->clear() ?? false;
    }
}

if (!function_exists('session_gc')) {
    function session_gc(): void
    {
        WorkermanApp::instance()->session()?->gc();
        // — Perform session data garbage collection
    }
}

if (!function_exists('session_get_cookie_params')) {
    function session_get_cookie_params(): array
    {
        $context = Context::instance();
        return [
           // 会话生命周期（秒），默认24小时
        'lifetime' => $context->get('session_lifetime', 86400),
        
        // Cookie路径，默认网站根目录
        'path' => $context->get('session_path', '/'),
        
        // Cookie域名，默认空（当前域名）
        'domain' => $context->get('session_domain', ''),
        
        // 是否仅通过HTTPS传输，默认自动检测
        'secure' => $context->get('session_secure', isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        
        // 是否仅允许HTTP访问，默认true以提高安全性
        'httponly' => $context->get('session_httponly', true),
        
        // SameSite属性，默认Lax（更好的安全性和兼容性平衡）
        'samesite' => $context->get('session_samesite', 'Lax'),
        ];
    }
}

if (!function_exists('session_id')) {
    function session_id($id = null): string
    {
        /* @var Request $req */
        try {
            return WorkermanApp::instance()->session()->id($id);
        } catch (Exception $e) {
            return '';
        }
    }
}

if (!function_exists('session_name')) {
    function session_name($name = null)
    {
        // — 读取/设置会话名称
        if ($name === null) {
            return Context::instance()->get('session_name',"NovaSession");
        } else {
            Context::instance()->set('session_name',$name);
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
    function session_save_path(string $path = null): ?string
    {
        if ($path === null) {
            return Context::instance()->get('session_save_path', sys_get_temp_dir());
        }
        Context::instance()->set('session_save_path', sys_get_temp_dir());
        return null;
        // — 读取/设置当前会话的保存路径
    }
}

if (!function_exists('session_set_cookie_params')) {
    function session_set_cookie_params($options): void
    {
        // — 设置会话 cookie 参数
        if (isset($options['lifetime'])) {
            Context::instance()->set('session_lifetime', $options['lifetime']);
        }
        if (isset($options['path'])) {
           Context::instance()->set('session_path', $options['path']);
        }
        if (isset($options['domain'])) {
           Context::instance()->set('session_domain', $options['domain']);
        }
        if (isset($options['secure'])) {
           Context::instance()->set('session_secure', $options['secure']);
        }
        if (isset($options['httponly'])) {
            Context::instance()->set('session_httponly', $options['httponly']);
        }
        if (isset($options['samesite'])) {
            Context::instance()->set('session_samesite', $options['samesite']);
        }
    }
}

if (!function_exists('session_set_save_handler')) {
    function session_set_save_handler(SessionHandlerInterface $sessionHandler): void
    {
        WorkermanApp::instance()->session()->setHandler($sessionHandler);
    }
}

if (!function_exists('session_start')) {
    function session_start(): void
    {
        $context = Context::instance();
        if (!isset($_SESSION)) {
            $_SESSION = [];
        }
        $app = WorkermanApp::instance();
        $session = $app->session();
        if ($session) {
            $session->setOptions(
                $context->get('session_name',"NovaSession"),[
                'lifetime' => $context->get('session_lifetime', 86400),
                'path' => $context->get('session_path', '/'),
                'domain' => $context->get('session_domain', ''),
                'secure' => $context->get('session_secure', isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
                'httponly' => $context->get('session_httponly', true),
                'samesite' => $context->get('session_samesite', 'Lax'),
            ]);
            $session->start();
        }
    }
}

if (!function_exists('session_status')) {
    function session_status(): int
    {
        if (WorkermanApp::instance()->session(false) === null) {
            return PHP_SESSION_NONE;
        }
        if (WorkermanApp::instance()->session(false)->isStarted()) {
            return PHP_SESSION_ACTIVE;
        }
        // — 返回当前会话状态
        return PHP_SESSION_NONE;
    }
}

if (!function_exists('session_unset')) {
    function session_unset(): bool
    {
        if (isset($_SESSION)) {
            $_SESSION = [];
            return true;
        }
        return false;
    }
}

if (!function_exists('session_write_close')) {
    function session_write_close(): bool
    {
        $session = WorkermanApp::instance()->session(false);
        if ($session) {
            $session->save();
            return true;
        }
        return false;
    }
}
