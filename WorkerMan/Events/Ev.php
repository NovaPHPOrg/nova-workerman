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

use EvIo;
use EvSignal;
use EvTimer;
use Throwable;

/**
 * Ev eventloop
 */
final class Ev implements EventInterface
{
    /**
     * All listeners for read event.
     *
     * @var array<int, EvIo>
     */
    private array $readEvents = [];

    /**
     * All listeners for write event.
     *
     * @var array<int, EvIo>
     */
    private array $writeEvents = [];

    /**
     * Event listeners of signal.
     *
     * @var array<int, EvSignal>
     */
    private array $eventSignal = [];

    /**
     * All timer event listeners.
     *
     * @var array<int, EvTimer>
     */
    private array $eventTimer = [];

    /**
     * @var ?callable
     */
    private $errorHandler = null;

    /**
     * Timer id.
     *
     * @var int
     */
    private static int $timerId = 1;

    /**
     * {@inheritdoc}
     */
    public function delay(float $delay, callable $func, array $args = []): int
    {
        $timerId = self::$timerId;
        $event = new EvTimer($delay, 0, function () use ($func, $args, $timerId) {
            unset($this->eventTimer[$timerId]);
            $this->safeCall($func, $args);
        });
        $this->eventTimer[self::$timerId] = $event;
        return self::$timerId++;
    }

    /**
     * {@inheritdoc}
     */
    public function offDelay(int $timerId): bool
    {
        if (isset($this->eventTimer[$timerId])) {
            $this->eventTimer[$timerId]->stop();
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
        $event = new EvTimer($interval, $interval, fn() => $this->safeCall($func, $args));
        $this->eventTimer[self::$timerId] = $event;
        return self::$timerId++;
    }

    /**
     * {@inheritdoc}
     */
    public function onReadable($stream, callable $func): void
    {
        $fdKey = (int)$stream;
        $event = new EvIo($stream, \Ev::READ, fn() => $this->safeCall($func, [$stream]));
        $this->readEvents[$fdKey] = $event;
    }

    /**
     * {@inheritdoc}
     */
    public function offReadable($stream): bool
    {
        $fdKey = (int)$stream;
        if (isset($this->readEvents[$fdKey])) {
            $this->readEvents[$fdKey]->stop();
            unset($this->readEvents[$fdKey]);
            return true;
        }
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function onWritable($stream, callable $func): void
    {
        $fdKey = (int)$stream;
        $event = new EvIo($stream, \Ev::WRITE, fn() => $this->safeCall($func, [$stream]));
        $this->writeEvents[$fdKey] = $event;
    }

    /**
     * {@inheritdoc}
     */
    public function offWritable($stream): bool
    {
        $fdKey = (int)$stream;
        if (isset($this->writeEvents[$fdKey])) {
            $this->writeEvents[$fdKey]->stop();
            unset($this->writeEvents[$fdKey]);
            return true;
        }
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function onSignal(int $signal, callable $func): void
    {
        $event = new EvSignal($signal, fn() => $this->safeCall($func, [$signal]));
        $this->eventSignal[$signal] = $event;
    }

    /**
     * {@inheritdoc}
     */
    public function offSignal(int $signal): bool
    {
        if (isset($this->eventSignal[$signal])) {
            $this->eventSignal[$signal]->stop();
            unset($this->eventSignal[$signal]);
            return true;
        }
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteAllTimer(): void
    {
        foreach ($this->eventTimer as $event) {
            $event->stop();
        }
        $this->eventTimer = [];
    }

    /**
     * {@inheritdoc}
     */
    public function run(): void
    {
        \Ev::run();
    }

    /**
     * {@inheritdoc}
     */
    public function stop(): void
    {
        \Ev::stop();
    }

    /**
     * {@inheritdoc}
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
     * @param  callable $func
     * @param  array    $args
     * @return void
     */
    private function safeCall(callable $func, array $args = []): void
    {
        try {
            $func(...$args);
        } catch (Throwable $e) {
            if ($this->errorHandler === null) {
                echo $e;
            } else {
                ($this->errorHandler)($e);
            }
        }
    }
}
