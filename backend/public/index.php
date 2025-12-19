<?php
declare(strict_types=1);


spl_autoload_register(function (string $class) {
    $prefix = 'App\\';
    if (strpos($class, $prefix) !== 0) return;

    $relative = substr($class, strlen($prefix));
    $relativePath = str_replace('\\', DIRECTORY_SEPARATOR, $relative) . '.php';

    $baseDir = dirname(__DIR__) . '/src/';
    $file = $baseDir . $relativePath;

    if (file_exists($file)) require $file;
});

use App\Middlewares\JsonMiddleware;
use App\Utils\Response;
use App\Controllers\OrderController;

JsonMiddleware::applyCors();

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';

try {
    if ($method === 'GET' && $path === '/api/orders') {
    OrderController::list();
    }

    if ($method === 'POST' && $path === '/api/orders') {
    $body = JsonMiddleware::readJsonBody();
    OrderController::create($body);
    }

    if ($method === 'PATCH' && preg_match('#^/api/orders/(\d+)/(preparing|paid|ready|completed|cancel)$#', $path, $m)) {
    $orderId = (int)$m[1];
    $action = $m[2];
    OrderController::changeStatus($orderId, $action);
}



    Response::error('NOT_FOUND', 'Route non trouvée', 404, [
        'method' => $method,
        'path' => $path
    ]);
} catch (DomainException $e) {
    Response::error('BUSINESS_RULE', $e->getMessage(), 409);
} catch (Throwable $e) {
    Response::error('SERVER_ERROR', $e->getMessage(), 500);
}
?>
