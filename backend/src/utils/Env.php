<?php
declare(strict_types=1);

namespace App\Utils;

final class Env
{
    public static function load(string $path): array
    {
        if (!file_exists($path)) return [];

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
        $env = [];

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || strpos($line, '#') === 0) continue;

            $parts = explode('=', $line, 2);
            if (count($parts) !== 2) continue;

            $key = trim($parts[0]);
            $val = trim(trim($parts[1]), "\"'");
            $env[$key] = $val;
        }

        return $env;
    }
}
