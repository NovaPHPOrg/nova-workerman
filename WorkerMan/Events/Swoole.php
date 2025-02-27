<?php

/*
 * Copyright (c) 2025. Lorem ipsum dolor sit amet, consectetur adipiscing elit.
 * Morbi non lorem porttitor neque feugiat blandit. Ut vitae ipsum eget quam lacinia accumsan.
 * Etiam sed turpis ac ipsum condimentum fringilla. Maecenas magna.
 * Proin dapibus sapien vel ante. Aliquam erat volutpat. Pellentesque sagittis ligula eget metus.
 * Vestibulum commodo. Ut rhoncus gravida arcu.
 */

declare(strict_types=1);

namespace Workerman\Events;

use Swoole\Coroutine;
use Swoole\Event;
use Swoole\Process;
use Swoole\Timer;

final class Swoole implements EventInterface
{
    /**
     * All listeners for read timer
     *
     * @var array<int, int>
     */
    private array $eventTimer = [];

    /**
     * All listeners for read event.
     *
     * @var array<int, array>
     */
    private array $readEvents = [];

    /**
     * All listeners for write event.
     *
     * @var array<int, array>
     */
    private array $writeEvents = [];

    /**
     * @var ?callable
     */
    private $errorHandler = null;

    /**
     * Constructor.
     */
    public function __construct()
    {
        Coroutine::set(['hook_flags' => SWOOLE_HOOK_ALL]);
    }

    /**
     * {@inheritdoc}
     */
    public function delay(float $delay, callable $func, array $args = []): int
    {
        $t = (int)($delay * 1000);
        $t = max($t, 1);
        $timerId = Timer::after($t, function () use ($func, $args, &$timerId) {
            unset($this->eventTimer[$timerId]);
            $this->safeCall($func, $args);
        });
        $this->eventTimer[$timerId] = $timerId;
        return $timerId;
    }

    /**
     * {@inheritdoc}
     */
    public function offDelay(int $timerId): bool
    {
        if (isset($this->eventTimer[$timerId])) {
            Timer::clear($timerId);
            unset($this->eventTimer[$timerId]);
            return true;
        }
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function offRepeat(int $timerId): bool
    {
        return $this->offDelay($timerId);
    }

    /**
     * {@inheritdoc}
     */
    public function repeat(float $interval, callable $func, array $args = []): int
    {
        $t = (int)($interval * 1000);
        $t = max($t, 1);
        $timerId = Timer::tick($t, function () use ($func, $args) {
            $this->safeCall($func, $args);
        });
        $this->eventTimer[$timerId] = $timerId;
        return $timerId;
    }

    /**
     * {@inheritdoc}
     */
    public function onReadable($stream, callable $func): void
    {
        $fd = (int)$stream;
        if (!isset($this->readEvents[$fd]) && !isset($this->writeEvents[$fd])) {
            Event::add($stream, fn () => $this->callRead($fd), null, SWOOLE_EVENT_READ);
        } elseif (isset($this->writeEvents[$fd])) {
            Event::set($stream, fn () => $this->callRead($fd), null, SWOOLE_EVENT_READ | SWOOLE_EVENT_WRITE);
        } else {
            Event::set($stream, fn () => $this->callRead($fd), null, SWOOLE_EVENT_READ);
        }

        $this->readEvents[$fd] = [$func, [$stream]];
    }

    /**
     * {@inheritdoc}
     */
    public function offReadable($stream): bool
    {
        $fd = (int)$stream;
        if (!isset($this->readEvents[$fd])) {
            return false;
        }
        unset($this->readEvents[$fd]);
        if (!isset($this->writeEvents[$fd])) {
            Event::del($stream);
            return true;
        }
        Event::set($stream, null, null, SWOOLE_EVENT_WRITE);
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function onWritable($stream, callable $func): void
    {
        $fd = (int)$stream;
        if (!isset($this->readEvents[$fd]) && !isset($this->writeEvents[$fd])) {
            Event::add($stream, null, fn () => $this->callWrite($fd), SWOOLE_EVENT_WRITE);
        } elseif (isset($this->readEvents[$fd])) {
            Event::set($stream, null, fn () => $this->callWrite($fd), SWOOLE_EVENT_WRITE | SWOOLE_EVENT_READ);
        } else {
            Event::set($stream, null, fn () => $this->callWrite($fd), SWOOLE_EVENT_WRITE);
        }

        $this->writeEvents[$fd] = [$func, [$stream]];
    }

    /**
     * {@inheritdoc}
     */
    public function offWritable($stream): bool
    {
        $fd = (int)$stream;
        if (!isset($this->writeEvents[$fd])) {
            return false;
        }
        unset($this->writeEvents[$fd]);
        if (!isset($this->readEvents[$fd])) {
            Event::del($stream);
            return true;
        }
        Event::set($stream, null, null, SWOOLE_EVENT_READ);
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function onSignal(int $signal, callable $func): void
    {
        Process::signal($signal, fn () => $this->safeCall($func, [$signal]));
    }

    /**
     * Please see https://wiki.swoole.com/#/process/process?id=signal
     * {@inheritdoc}
     */
    public function offSignal(int $signal): bool
    {
        return Process::signal($signal, null);
    }

    /**
     * {@inheritdoc}
     */
    public function deleteAllTimer(): void
    {
        foreach ($this->eventTimer as $timerId) {
            Timer::clear($timerId);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function run(): void
    {
        // Avoid process exit due to no listening
        Timer::tick(100000000, static fn () => null);
        Event::wait();
    }

    /**
     * Destroy loop.
     *
     * @return void
     */
    public function stop(): void
    {
        // Cancel all coroutines before Event::exit
        foreach (Coroutine::listCoroutines() as $coroutine) {
            Coroutine::cancel($coroutine);
        }
        // Wait for coroutines to exit
        usleep(200000);
        Event::exit();
    }

    /**
     * Get timer count.
     *
     * @return integer
     */
    public function getTimerCount(): int
    {
        return count($this->eventTimer);
    }

    /**
     * {@inheritdoc}
     */
    public function setErrorHandler(callable $errorHandler): void
    {
        $this->errorHandler = $errorHandler;
    }

    /**
     * @param       $fd
     * @return void
     */
    private function callRead($fd)
    {
        if (isset($this->readEvents[$fd])) {
            $this->safeCall($this->readEvents[$fd][0], $this->readEvents[$fd][1]);
        }
    }

    /**
     * @param       $fd
     * @return void
     */
    private function callWrite($fd)
    {
        if (isset($this->writeEvents[$fd])) {
            $this->safeCall($this->writeEvents[$fd][0], $this->writeEvents[$fd][1]);
        }
    }

    /**
     * @param  callable $func
     * @param  array    $args
     * @return void
     */
    private function safeCall(callable $func, array $args = []): void
    {
        try {
            $func(...$args);
        } catch (\Throwable $e) {
            if ($this->errorHandler === null) {
                echo $e;
            } else {
                ($this->errorHandler)($e);
            }
        }
    }
}
