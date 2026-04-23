<?php

declare(strict_types=1);

namespace nova\plugin\workerman\adapter;

use nova\plugin\cookie\Cookie;
use SessionHandlerInterface;

/**
 * WorkermanSession 类
 * 用于在 Workerman 环境下管理会话，支持自定义 SessionHandler
 */
class WorkermanSession
{
    /** @var string 会话ID */
    private string $sessionId;

    /** @var int 会话过期时间（秒） */
    private int $lifetime;

    /** @var string 会话cookie名称 */
    private string $sessionName = 'NOVA_SESSION_ID';

    /** @var SessionHandlerInterface|null 会话处理器 */
    private ?SessionHandlerInterface $handler = null;

    /** @var string 会话存储路径 */
    private string $savePath;

    /** @var bool 会话是否已启动 */
    private bool $started = false;

    /** @var array 会话配置 */
    private array $options = [
        'lifetime' => 86400,
        'path' => '/',
        'domain' => '',
        'secure' => false,
        'httponly' => true,
        'gc_probability' => 1,
        'gc_divisor' => 100,
        'gc_maxlifetime' => 86400,
    ];

    /**
     * 构造函数
     */
    public function __construct()
    {
        $this->lifetime = $this->options['lifetime'];
        $this->savePath = sys_get_temp_dir() . '/nova_sessions';

        // 确保会话存储目录存在
        if (!is_dir($this->savePath)) {
            mkdir($this->savePath, 0777, true);
        }

    }

    private bool $newSession = false;

    public function start(): void
    {
        if ($this->started) {
            return;
        }

        $sessionId = Cookie::getInstance()->get($this->sessionName);

        if (empty($sessionId)) {
            $this->newSession = true;
            $sessionId = $this->generateSessionId();
        }
        // 获取或创建会话ID
        $this->sessionId = $sessionId;

        // 执行垃圾回收
        $this->gc();

        // 加载会话数据
        $this->loadSession();

        if ($this->newSession) {
            setcookie(
                $this->sessionName,
                $this->sessionId,
                time() + $this->options['lifetime'],
                $this->options['path'],
                $this->options['domain'],
                $this->options['secure'],
                $this->options['httponly']
            );
        }

        $this->started = true;
    }

    /**
     * 检查会话是否已启动
     * @return bool
     */
    public function isStarted(): bool
    {
        return $this->started;
    }

    /**
     * 设置会话处理器
     * @param  SessionHandlerInterface $handler
     * @return bool
     */
    public function setHandler(SessionHandlerInterface $handler): bool
    {
        $this->handler = $handler;
        $this->handler->open($this->savePath, $this->sessionName);
        return true;
    }

    /**
     * 获取会话处理器
     * @return SessionHandlerInterface|null
     */
    public function getHandler(): ?SessionHandlerInterface
    {
        return $this->handler;
    }

    /**
     * 设置会话配置选项
     * @param array $options
     */
    public function setOptions($name, array $options): void
    {
        $this->options = array_merge($this->options, $options);
        $this->lifetime = $this->options['lifetime'];
        $this->sessionName = $name;
    }

    /**
     * 获取会话配置选项
     * @return array
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * 执行垃圾回收
     */
    public function gc(): void
    {
        if (mt_rand(1, $this->options['gc_divisor']) <= $this->options['gc_probability']) {
            if ($this->handler) {
                $this->handler->gc($this->options['gc_maxlifetime']);
            } else {
                $this->defaultGc();
            }
        }
    }

    /**
     * 默认的垃圾回收实现
     */
    private function defaultGc(): void
    {
        $files = glob($this->savePath . '/sess_*');
        if (!is_array($files)) {
            return;
        }
        foreach ($files as $file) {
            if (is_file($file) && filemtime($file) + $this->options['gc_maxlifetime'] < time()) {
                @unlink($file);
            }
        }
    }

    /**
     * 生成唯一的会话ID
     * @return string
     */
    private function generateSessionId(): string
    {
        return bin2hex(random_bytes(16));
    }

    /**
     * 获取会话文件路径
     * @return string
     */
    private function getSessionFile(): string
    {
        return $this->savePath . '/sess_' . $this->sessionId;
    }

    /**
     * 加载会话数据
     */
    private function loadSession(): void
    {
        if ($this->handler) {
            $data = $this->handler->read($this->sessionId);
            $_SESSION = $data ? unserialize($data) : [];
        } else {
            $this->loadDefaultSession();
        }

    }

    /**
     * 默认的会话加载实现
     */
    private function loadDefaultSession(): void
    {
        $file = $this->getSessionFile();
        if (file_exists($file)) {
            $content = file_get_contents($file);
            if ($content !== false) {
                $data = unserialize($content);
                if (is_array($data) && isset($data['expires']) && $data['expires'] > time()) {
                    $_SESSION = $data['data'] ?? [];
                    return;
                }
            }
            // 如果会话已过期或无效，删除文件
            @unlink($file);
        }
        $_SESSION = [];
    }

    /**
     * 保存会话数据
     */
    public function save(): void
    {

        if (!$this->started) {
            return;
        }

        if ($this->handler) {
            $this->handler->write($this->sessionId, serialize($_SESSION));
        } else {
            $this->saveDefaultSession();
        }
    }

    /**
     * 默认的会话保存实现
     */
    private function saveDefaultSession(): void
    {
        $data = [
            'expires' => time() + $this->lifetime,
            'data' => $_SESSION
        ];
        file_put_contents($this->getSessionFile(), serialize($data), LOCK_EX);
    }

    /**
     * 获取会话值
     * @param  string $key     键名
     * @param  mixed  $default 默认值
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        if (!$this->started) {
            return $default;
        }
        return  $_SESSION[$key] ?? $default;
    }

    /**
     * 设置会话值
     * @param string $key   键名
     * @param mixed  $value 值
     */
    public function set(string $key, mixed $value): void
    {
        if (!$this->started) {
            return;
        }
        $_SESSION[$key] = $value;
        $this->save();
    }

    /**
     * 魔术方法：获取会话值
     * @param  string $name
     * @return mixed
     */
    public function __get(string $name): mixed
    {
        return $this->get($name);
    }

    /**
     * 魔术方法：设置会话值
     * @param string $name
     * @param mixed  $value
     */
    public function __set(string $name, mixed $value): void
    {
        $this->set($name, $value);
    }

    /**
     * 魔术方法：检查会话值是否存在
     * @param  string $name
     * @return bool
     */
    public function __isset(string $name): bool
    {
        return $this->has($name);
    }

    /**
     * 魔术方法：删除会话值
     * @param string $name
     */
    public function __unset(string $name): void
    {
        $this->delete($name);
    }

    /**
     * 删除会话值
     * @param string $key 键名
     */
    public function delete(string $key): void
    {
        if (!$this->started) {
            return;
        }
        unset($_SESSION[$key]);
        $this->save();
    }

    /**
     * 清除所有会话数据
     */
    public function clear(): void
    {
        if (!$this->started) {
            return;
        }
        $_SESSION = [];
        if ($this->handler) {
            $this->handler->destroy($this->sessionId);
        } else {
            @unlink($this->getSessionFile());
        }
    }

    /**
     * 获取会话ID
     * @return string
     */
    public function getId(): string
    {
        return $this->sessionId;
    }

    /**
     * 获取所有会话数据
     * @return array
     */
    public function all(): array
    {
        if (!$this->started) {
            return [];
        }
        return  $_SESSION;
    }

    /**
     * 检查会话键是否存在
     * @param  string $key 键名
     * @return bool
     */
    public function has(string $key): bool
    {
        if (!$this->started) {
            return false;
        }
        return isset($_SESSION[$key]);
    }

    /**
     * 析构函数，确保会话数据被保存并关闭处理器
     */
    public function __destruct()
    {
        $this->save();
        $this->handler?->close();

    }

    public function id(?string $id = null): string
    {
        if (!$this->started) {
            $this->start();
        }
        return $id ?? $this->sessionId;
    }

}
