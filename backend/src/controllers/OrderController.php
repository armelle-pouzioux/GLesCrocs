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
}
