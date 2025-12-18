<?php
declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class OrderRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function listTodayQueue(): array
    {
        // File du jour: on exclut COMPLETED et CANCELLED
        $sql = "
            SELECT
                id,
                service_date,
                ticket_number,
                status,
                total_cents,
                estimated_prep_sec,
                validated_at,
                preparing_at,
                paid_at,
                ready_at,
                completed_at,
                cancelled_at
            FROM orders
            WHERE service_date = CURDATE()
              AND status NOT IN ('COMPLETED', 'CANCELLED')
            ORDER BY ticket_number ASC
        ";

        return $this->pdo->query($sql)->fetchAll();
    }
}
