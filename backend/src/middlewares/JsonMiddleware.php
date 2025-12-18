<?php
declare(strict_types=1);

namespace App\Middlewares;

final class JsonMiddleware
{
    public static function applyCors(): void
    {
        // Dev only: adapte si besoin
        header('Access-Control-Allow-Origin: http://localhost:5173');
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');
        header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');

        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
            http_response_code(204);
            exit;
        }
    }

    public static function readJsonBody(): array
    {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        if (stripos($contentType, 'application/json') === false) {
            return [];
        }

        $raw = file_get_contents('php://input') ?: '';
        if ($raw === '') return [];

        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }
}
