<?php

/*
 * Copyright (c) 2025. Lorem ipsum dolor sit amet, consectetur adipiscing elit.
 * Morbi non lorem porttitor neque feugiat blandit. Ut vitae ipsum eget quam lacinia accumsan.
 * Etiam sed turpis ac ipsum condimentum fringilla. Maecenas magna.
 * Proin dapibus sapien vel ante. Aliquam erat volutpat. Pellentesque sagittis ligula eget metus.
 * Vestibulum commodo. Ut rhoncus gravida arcu.
 */

declare(strict_types=1);

namespace Workerman\Protocols\Http\Session;

use Redis;
use RedisCluster;
use RedisException;
use RuntimeException;
use Throwable;
use Workerman\Protocols\Http\Session;
use Workerman\Timer;

/**
 * Class RedisSessionHandler
 * @package Workerman\Protocols\Http\Session
 */
class RedisSessionHandler implements SessionHandlerInterface
{
    /**
     * @var Redis|RedisCluster
     */
    protected Redis|RedisCluster $redis;

    /**
     * @var array
     */
    protected array $config;

    /**
     * RedisSessionHandler constructor.
     * @param  array          $config = [
     *                                'host'     => '127.0.0.1',
     *                                'port'     => 6379,
     *                                'timeout'  => 2,
     *                                'auth'     => '******',
     *                                'database' => 2,
     *                                'prefix'   => 'redis_session_',
     *                                'ping'     => 55,
     *                                ]
     * @throws RedisException
     */
    public function __construct(array $config)
    {
        if (false === extension_loaded('redis')) {
            throw new RuntimeException('Please install redis extension.');
        }

        $config['timeout'] ??= 2;
        $this->config = $config;
        $this->connect();

        Timer::add($config['ping'] ?? 55, function () {
            $this->redis->get('ping');
        });
    }

    /**
     * @throws RedisException
     */
    public function connect()
    {
        $config = $this->config;

        $this->redis = new Redis();
        if (false === $this->redis->connect($config['host'], $config['port'], $config['timeout'])) {
            throw new RuntimeException("Redis connect {$config['host']}:{$config['port']} fail.");
        }
        if (!empty($config['auth'])) {
            $this->redis->auth($config['auth']);
        }
        if (!empty($config['database'])) {
            $this->redis->select($config['database']);
        }
        if (empty($config['prefix'])) {
            $config['prefix'] = 'redis_session_';
        }
        $this->redis->setOption(Redis::OPT_PREFIX, $config['prefix']);
    }

    /**
     * {@inheritdoc}
     */
    public function open(string $savePath, string $name): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     * @param  string         $sessionId
     * @return string|false
     * @throws RedisException
     * @throws Throwable
     */
    public function read(string $sessionId): string|false
    {
        try {
            return $this->redis->get($sessionId);
        } catch (Throwable $e) {
            $msg = strtolower($e->getMessage());
            if ($msg === 'connection lost' || strpos($msg, 'went away')) {
                $this->connect();
                return $this->redis->get($sessionId);
            }
            throw $e;
        }
    }

    /**
     * {@inheritdoc}
     * @throws RedisException
     */
    public function write(string $sessionId, string $sessionData): bool
    {
        return true === $this->redis->setex($sessionId, Session::$lifetime, $sessionData);
    }

    /**
     * {@inheritdoc}
     * @throws RedisException
     */
    public function updateTimestamp(string $sessionId, string $data = ""): bool
    {
        return true === $this->redis->expire($sessionId, Session::$lifetime);
    }

    /**
     * {@inheritdoc}
     * @throws RedisException
     */
    public function destroy(string $sessionId): bool
    {
        $this->redis->del($sessionId);
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function close(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function gc(int $maxLifetime): bool
    {
        return true;
    }
}
