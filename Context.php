<?php

class Context
{
    private static array $context = [];

    public static function set(string $key, $value): void
    {
        self::$context[$key] = $value;
    }

    public static function get(string $key)
    {
        return self::$context[$key] ?? null;
    }

    public static function has(string $key): bool
    {
        return isset(self::$context[$key]);
    }

    public static function remove(string $key): void
    {
        unset(self::$context[$key]);
    }
}