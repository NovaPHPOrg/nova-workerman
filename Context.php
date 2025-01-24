<?php
namespace nova\plugin\workerman;
use Workerman\Protocols\Http\Request;
use Workerman\Protocols\Http\Response;

/**
 * Web应用的上下文
 */
class Context
{
    protected Request $request;
    protected Response $response;
    protected array $config;

    public function __construct(Request $request,array $config)
    {
        $this->request = $request;
        $this->config = $config;
        $this->response = new Response();
    }

    public function config($key)
    {
        return $this->config[$key] ?? null;
    }

    public function getRequest(): Request
    {
        return $this->request;
    }

    public function getResponse(): Response
    {
        return $this->response;
    }
}