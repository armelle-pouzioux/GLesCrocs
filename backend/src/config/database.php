<?php
declare(strict_types=1);

namespace App\Config;

use PDO;
use PDOException;

final class Database
{
    private static ?PDO $pdo = null;

    public static function pdo(): PDO
    {
        if (self::$pdo) return self::$pdo;

        $env = self::loadEnv(dirname(__DIR__, 2) . '/.env');

        $host = $env['DB_HOST'] ?? 'localhost';
        $db   = $env['DB_NAME'] ?? '';
        $user = $env['DB_USER'] ?? '';
        $pass = $env['DB_PASS'] ?? '';

        if ($db === '' || $user === '') {
            throw new \RuntimeException('DB_NAME et DB_USER doivent être définis dans backend/.env');
        }

        $dsn = "mysql:host={$host};dbname={$db};charset=utf8mb4";

        try {
            self::$pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        } catch (PDOException $e) {
            throw new \RuntimeException('Connexion DB impossible: ' . $e->getMessage());
        }

        return self::$pdo;
    }

    private static function loadEnv(string $path): array
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
            $val = trim($parts[1]);

            // enlève guillemets éventuels
            $val = trim($val, "\"'");

            $env[$key] = $val;
        }

        return $env;
    }
}
