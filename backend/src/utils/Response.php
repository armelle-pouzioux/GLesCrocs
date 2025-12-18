<?php
declare(strict_types=1);

namespace App\Utils;

final class Response
{
    public static function json(array $payload, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    public static function success(array $data = [], int $status = 200): void
    {
        self::json(['success' => true, 'data' => $data], $status);
    }

    public static function error(string $code, string $message, int $status = 400, array $meta = []): void
    {
        self::json([
            'success' => false,
            'error' => [
                'code' => $code,
                'message' => $message,
                'meta' => $meta
            ]
        ], $status);
    }
}
