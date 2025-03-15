<?php

/*
 * Copyright (c) 2025. Lorem ipsum dolor sit amet, consectetur adipiscing elit.
 * Morbi non lorem porttitor neque feugiat blandit. Ut vitae ipsum eget quam lacinia accumsan.
 * Etiam sed turpis ac ipsum condimentum fringilla. Maecenas magna.
 * Proin dapibus sapien vel ante. Aliquam erat volutpat. Pellentesque sagittis ligula eget metus.
 * Vestibulum commodo. Ut rhoncus gravida arcu.
 */

declare(strict_types=1);

namespace adapter;

use nova\framework\App;
use nova\framework\core\Context;
use nova\framework\core\Loader;
use RuntimeException;
use Workerman\Connection\TcpConnection;
use Workerman\Protocols\Http\Request;
use Workerman\Protocols\Http\Response;
use Workerman\Protocols\Http\ServerSentEvents;
use Workerman\Protocols\Http\Session;

/**
 * WorkermanApp 类
 * 用于处理 Workerman 环境下的 HTTP 请求和响应
 */
class WorkermanApp
{
    /** @var Response HTTP 响应对象 */
    protected Response $response;

    /** @var Request HTTP 请求对象 */
    protected Request $request;

    protected TcpConnection $connection;

    /**
     * 获取 WorkermanApp 实例
     * @return WorkermanApp
     * @throws RuntimeException 当实例未初始化时抛出异常
     */
    public static function instance(): WorkermanApp
    {
        global $workermanApp;
        if (!isset($workermanApp)) {
            throw new RuntimeException("WorkermanApp instance not found");
        }
        return $workermanApp;
    }

    /**
     * 构造函数
     * 初始化应用程序环境，包括错误报告、自动加载器和上下文环境
     * @param Request $request HTTP 请求对象
     */
    public function __construct(Request $request, TcpConnection $connection)
    {
        $this->request = $request;

        $this->connection = $connection;
        /**
         * 设置错误报告
         * 开发环境下显示所有错误
         */
        error_reporting(E_ALL);
        ini_set('display_errors', '1');

        /**
         * 框架初始化流程
         * 1. 加载自动加载器
         * 2. 初始化上下文环境
         * 3. 加载助手函数
         * 4. 启动应用程序
         */

        $dir = dirname(__DIR__, 4);
        // 加载框架核心的自动加载器
        require_once "$dir/nova/framework/core/Loader.php";
        $loader = new Loader();
        global $context;
        // 初始化应用程序上下文
        $context = new Context($loader);

        $context->setResponseClass(WorkermanResponse::class);
        // 加载助手函数
        require_once "$dir/nova/framework/helper.php";
        //创建响应
        $this->response = new Response();
    }

    /**
     * 析构函数
     * 清理全局上下文
     */
    public function __destruct()
    {
        global $context;
        $context->destroy();
        $context = null;
    }

    /**
     * 运行应用程序
     * @return Response 返回处理后的 HTTP 响应
     */
    public function run(): Response
    {
        ob_start();
        App::getInstance()->start();
        return $this->response->withBody(ob_get_clean() ?: "");
    }

    /**
     * 获取响应对象
     * @return Response
     */
    public function response(): Response
    {
        return $this->response;
    }

    /**
     * 设置 Cookie
     * @param string $name               Cookie 名称
     * @param string $value              Cookie 值
     * @param int    $expires_or_options 过期时间或选项
     * @param string $path               Cookie 路径
     * @param string $domain             Cookie 域名
     * @param bool   $secure             是否仅通过 HTTPS 传输
     * @param bool   $httponly           是否仅允许 HTTP 访问
     */
    public function cookie(string $name, string $value, int $expires_or_options, string $path, string $domain, bool $secure, bool $httponly): void
    {
        $this->response->cookie($name, $value, $expires_or_options, $path, $domain, $secure, $httponly);
    }

    /**
     * 设置响应头
     * @param mixed $key   头部字段名
     * @param mixed $value 头部字段值
     */
    public function header(mixed $key, mixed $value): void
    {
        $this->response->header($key, $value);
    }

    /**
     * 获取所有响应头
     * @return array 响应头数组
     */
    public function getHeaders(): array
    {
        return $this->response->getHeaders();
    }

    /**
     * 设置响应状态码
     * @param int $response_code HTTP 状态码
     */
    public function setResponseCode(int $response_code): void
    {
        $this->response->withStatus($response_code);
    }

    /**
     * 获取请求对象
     * @return Request
     */
    public function request(): Request
    {
        return $this->request;
    }

    public function connection(): TcpConnection
    {
        return $this->connection;
    }

    /**
     * 获取会话对象
     * @return Session|null 返回会话对象，如果创建失败则返回 null
     */
    public function session(): ?Session
    {
        try {
            return $this->request->session();
        } catch (\Exception $e) {
            return null;
        }
    }

    public function sendResponse(): void
    {
        $this->connection->send($this->response->withBody(ob_get_contents() ?: ""));
    }

    public function sendSSE(array $data): void
    {
        if (connection_aborted()) {
            return;
        }
        $this->connection->send(new ServerSentEvents($data));
    }
}
