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

/**
 * libevent eventloop
 */
final class Event implements EventInterface
{
    /**
     * Event base.
     *
     * @var \EventBase
     */
    private \EventBase $eventBase;

    /**
     * All listeners for read event.
     *
     * @var array<int, \Event>
     */
    private array $readEvents = [];

    /**
     * All listeners for write event.
     *
     * @var array<int, \Event>
     */
    private array $writeEvents = [];

    /**
     * Event listeners of signal.
     *
     * @var array<int, \Event>
     */
    private array $eventSignal = [];

    /**
     * All timer event listeners.
     *
     * @var array<int, \Event>
     */
    private array $eventTimer = [];

    /**
     * Timer id.
     *
     * @var int
     */
    private int $timerId = 0;

    /**
     * Event class name.
     *
     * @var string
     */
    private string $eventClassName = '';

    /**
     * @var ?callable
     */
    private $errorHandler = null;

    /**
     * Construct.
     */
    public function __construct()
    {
        if (\class_exists('\\\\Event', false)) {
            $className = '\\\\Event';
        } else {
            $className = '\Event';
        }
        $this->eventClassName = $className;
        if (\class_exists('\\\\EventBase', false)) {
            $className = '\\\\EventBase';
        } else {
            $className = '\EventBase';
        }
        $this->eventBase = new $className();
    }

    /**
     * {@inheritdoc}
     */
    public function delay(float $delay, callable $func, array $args = []): int
    {
        $className = $this->eventClassName;
        $timerId = $this->timerId++;
        $event = new $className($this->eventBase, -1, $className::TIMEOUT, function () use ($func, $args, $timerId) {
            unset($this->eventTimer[$timerId]);
            $this->safeCall($func, $args);
        });
        if (!$event->addTimer($delay)) {
            throw new \RuntimeException("Event::addTimer($delay) failed");
        }
        $this->eventTimer[$timerId] = $event;
        return $timerId;
    }

    /**
     * {@inheritdoc}
     */
    public function offDelay(int $timerId): bool
    {
        if (isset($this->eventTimer[$timerId])) {
            $this->eventTimer[$timerId]->del();
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
        $className = $this->eventClassName;
        $timerId = $this->timerId++;
        $event = new $className($this->eventBase, -1, $className::TIMEOUT | $className::PERSIST, $func);
        if (!$event->addTimer($interval)) {
            throw new \RuntimeException("Event::addTimer($interval) failed");
        }
        $this->eventTimer[$timerId] = $event;
        return $timerId;
    }

    /**
     * {@inheritdoc}
     */
    public function onReadable($stream, callable $func): void
    {
        $className = $this->eventClassName;
        $fdKey = (int)$stream;
        $event = new $className($this->eventBase, $stream, $className::READ | $className::PERSIST, $func);
        if ($event->add()) {
            $this->readEvents[$fdKey] = $event;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function offReadable($stream): bool
    {
        $fdKey = (int)$stream;
        if (isset($this->readEvents[$fdKey])) {
            $this->readEvents[$fdKey]->del();
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
        $className = $this->eventClassName;
        $fdKey = (int)$stream;
        $event = new $className($this->eventBase, $stream, $className::WRITE | $className::PERSIST, $func);
        if ($event->add()) {
            $this->writeEvents[$fdKey] = $event;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function offWritable($stream): bool
    {
        $fdKey = (int)$stream;
        if (isset($this->writeEvents[$fdKey])) {
            $this->writeEvents[$fdKey]->del();
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
        $className = $this->eventClassName;
        $fdKey = $signal;
        $event = $className::signal($this->eventBase, $signal, fn () => $this->safeCall($func, [$signal]));
        if ($event->add()) {
            $this->eventSignal[$fdKey] = $event;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function offSignal(int $signal): bool
    {
        $fdKey = $signal;
        if (isset($this->eventSignal[$fdKey])) {
            $this->eventSignal[$fdKey]->del();
            unset($this->eventSignal[$fdKey]);
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
            $event->del();
        }
        $this->eventTimer = [];
    }

    /**
     * {@inheritdoc}
     */
    public function run(): void
    {
        $this->eventBase->loop();
    }

    /**
     * {@inheritdoc}
     */
    public function stop(): void
    {
        $this->eventBase->exit();
    }

    /**
     * {@inheritdoc}
     */
    public function getTimerCount(): int
    {
        return \count($this->eventTimer);
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
        } catch (\Throwable $e) {
            if ($this->errorHandler === null) {
                echo $e;
            } else {
                ($this->errorHandler)($e);
            }
        }
    }
}
