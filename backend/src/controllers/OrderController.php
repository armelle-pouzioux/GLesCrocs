<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Config\Database;
use App\Repositories\OrderRepository;
use App\Utils\Response;

final class OrderController
{
    public static function list(): void
    {
        $repo = new OrderRepository(Database::pdo());
        $orders = $repo->listTodayQueue();

        Response::success([
            'service_date' => date('Y-m-d'),
            'orders' => $orders
        ]);
    }

    public static function create(array $body): void
    {
    if (!isset($body['items']) || !is_array($body['items']) || count($body['items']) === 0) {
        Response::error('VALIDATION_ERROR', 'Items manquants ou invalides', 422);
    }

    $repo = new OrderRepository(Database::pdo());

    $order = $repo->createOrder($body['items']);

    Response::success([
        'order' => $order
    ], 201);
    }

    public static function changeStatus(int $orderId, string $action): void
    {
    $repo = new OrderRepository(Database::pdo());
    $order = $repo->setStatusByAction($orderId, $action);

    Response::success(['order' => $order]);
    }


}
