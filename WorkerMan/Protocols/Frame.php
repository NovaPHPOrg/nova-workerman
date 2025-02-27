<?php

/*
 * Copyright (c) 2025. Lorem ipsum dolor sit amet, consectetur adipiscing elit.
 * Morbi non lorem porttitor neque feugiat blandit. Ut vitae ipsum eget quam lacinia accumsan.
 * Etiam sed turpis ac ipsum condimentum fringilla. Maecenas magna.
 * Proin dapibus sapien vel ante. Aliquam erat volutpat. Pellentesque sagittis ligula eget metus.
 * Vestibulum commodo. Ut rhoncus gravida arcu.
 */

declare(strict_types=1);

namespace Workerman\Protocols;

use function pack;
use function strlen;
use function substr;
use function unpack;

/**
 * Frame Protocol.
 */
class Frame
{
    /**
     * Check the integrity of the package.
     *
     * @param  string $buffer
     * @return int
     */
    public static function input(string $buffer): int
    {
        if (strlen($buffer) < 4) {
            return 0;
        }
        $unpackData = unpack('Ntotal_length', $buffer);
        return $unpackData['total_length'];
    }

    /**
     * Decode.
     *
     * @param  string $buffer
     * @return string
     */
    public static function decode(string $buffer): string
    {
        return substr($buffer, 4);
    }

    /**
     * Encode.
     *
     * @param  string $data
     * @return string
     */
    public static function encode(string $data): string
    {
        $totalLength = 4 + strlen($data);
        return pack('N', $totalLength) . $data;
    }
}
