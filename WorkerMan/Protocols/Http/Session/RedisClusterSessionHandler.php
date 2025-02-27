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
use RedisClusterException;
use RedisException;

class RedisClusterSessionHandler extends RedisSessionHandler
{
    /**
     * @param                        $config
     * @throws RedisClusterException
     * @throws RedisException
     */
    public function __construct($config)
    {
        $timeout = $config['timeout'] ?? 2;
        $readTimeout = $config['read_timeout'] ?? $timeout;
        $persistent = $config['persistent'] ?? false;
        $auth = $config['auth'] ?? '';
        $args = [null, $config['host'], $timeout, $readTimeout, $persistent];
        if ($auth) {
            $args[] = $auth;
        }
        $this->redis = new RedisCluster(...$args);
        if (empty($config['prefix'])) {
            $config['prefix'] = 'redis_session_';
        }
        $this->redis->setOption(Redis::OPT_PREFIX, $config['prefix']);
    }

    /**
     * {@inheritdoc}
     */
    public function read(string $sessionId): string|false
    {
        return $this->redis->get($sessionId);
    }
}
