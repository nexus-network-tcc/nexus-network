<?php

/**
 * Leitor mínimo de arquivos .env (KEY=VALUE).
 */
final class Env
{
    private static array $values = [];

    public static function load(string $file): void
    {
        if (!is_file($file)) {
            return;
        }

        foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#' || !str_contains($line, '=')) {
                continue;
            }

            [$key, $value] = array_map('trim', explode('=', $line, 2));

            if ($value !== '' && ($value[0] === '"' || $value[0] === "'")) {
                $quote = $value[0];
                $end = strpos($value, $quote, 1);
                $value = $end === false ? substr($value, 1) : substr($value, 1, $end - 1);
            } else {
                $value = trim(preg_replace('/\s+#.*$/', '', $value));
            }

            self::$values[$key] = $value;
        }
    }

    public static function get(string $key, ?string $default = null): ?string
    {
        $value = self::$values[$key] ?? getenv($key);
        return ($value === false || $value === null || $value === '') ? $default : $value;
    }

    public static function bool(string $key, bool $default = false): bool
    {
        $value = self::get($key);
        return $value === null ? $default : in_array(strtolower($value), ['1', 'true', 'on', 'yes'], true);
    }
}
