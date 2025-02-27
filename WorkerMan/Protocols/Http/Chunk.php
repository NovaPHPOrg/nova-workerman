<?php

/*
 * Copyright (c) 2025. Lorem ipsum dolor sit amet, consectetur adipiscing elit.
 * Morbi non lorem porttitor neque feugiat blandit. Ut vitae ipsum eget quam lacinia accumsan.
 * Etiam sed turpis ac ipsum condimentum fringilla. Maecenas magna.
 * Proin dapibus sapien vel ante. Aliquam erat volutpat. Pellentesque sagittis ligula eget metus.
 * Vestibulum commodo. Ut rhoncus gravida arcu.
 */

declare(strict_types=1);

namespace Workerman\Protocols\Http;

use function dechex;

use Stringable;

use function strlen;

/**
 * Class Chunk
 * @package Workerman\Protocols\Http
 */
class Chunk implements Stringable
{
    public function __construct(protected string $buffer)
    {
    }

    public function __toString(): string
    {
        return dechex(strlen($this->buffer)) . "\r\n$this->buffer\r\n";
    }
}
