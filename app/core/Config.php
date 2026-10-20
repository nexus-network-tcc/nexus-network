<?php

final class Config
{
    private static array $items = [];

    public static function load(array $items): void
    {
        self::$items = $items;
    }

    /** Lê um valor usando notação de ponto, ex.: Config::get('db.host'). */
    public static function get(string $key, mixed $default = null): mixed
    {
        $value = self::$items;
        foreach (explode('.', $key) as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }
        return $value;
    }
}
