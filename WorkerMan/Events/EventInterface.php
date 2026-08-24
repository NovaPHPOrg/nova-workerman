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

use Throwable;

interface EventInterface
{
    /**
     * Delay the execution of a callback.
     *
     * @param  float                    $delay
     * @param  callable(mixed...): void $func
     * @param  array                    $args
     * @return int
     */
    public function delay(float $delay, callable $func, array $args = []): int;

    /**
     * Delete a delay timer.
     *
     * @param  int  $timerId
     * @return bool
     */
    public function offDelay(int $timerId): bool;

    /**
     * Repeatedly execute a callback.
     *
     * @param  float                    $interval
     * @param  callable(mixed...): void $func
     * @param  array                    $args
     * @return int
     */
    public function repeat(float $interval, callable $func, array $args = []): int;

    /**
     * Delete a repeat timer.
     *
     * @param  int  $timerId
     * @return bool
     */
    public function offRepeat(int $timerId): bool;

    /**
     * Execute a callback when a stream resource becomes readable or is closed for reading.
     *
     * @param  resource                 $stream
     * @param  callable(resource): void $func
     * @return void
     */
    public function onReadable($stream, callable $func): void;

    /**
     * Cancel a callback of stream readable.
     *
     * @param  resource $stream
     * @return bool
     */
    public function offReadable($stream): bool;

    /**
     * Execute a callback when a stream resource becomes writable or is closed for writing.
     *
     * @param  resource                 $stream
     * @param  callable(resource): void $func
     * @return void
     */
    public function onWritable($stream, callable $func): void;

    /**
     * Cancel a callback of stream writable.
     *
     * @param  resource $stream
     * @return bool
     */
    public function offWritable($stream): bool;

    /**
     * Execute a callback when a signal is received.
     *
     * @param  int                 $signal
     * @param  callable(int): void $func
     * @return void
     */
    public function onSignal(int $signal, callable $func): void;

    /**
     * Cancel a callback of signal.
     *
     * @param  int  $signal
     * @return bool
     */
    public function offSignal(int $signal): bool;

    /**
     * Delete all timer.
     *
     * @return void
     */
    public function deleteAllTimer(): void;

    /**
     * Run the event loop.
     *
     * @return void
     */
    public function run(): void;

    /**
     * Stop event loop.
     *
     * @return void
     */
    public function stop(): void;

    /**
     * Get Timer count.
     *
     * @return int
     */
    public function getTimerCount(): int;

    /**
     * Set error handler.
     *
     * @param callable(Throwable): void $errorHandler
     * @return void
     */
    public function setErrorHandler(callable $errorHandler): void;
}
