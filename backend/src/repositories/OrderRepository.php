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

    public function createOrder(array $items): array
    {
    $this->pdo->beginTransaction();

    try {
        $menu = $this->getTodayMenu();
        $ticket = $this->getNextTicketNumber();
        $calc = $this->calculatePrepTime($items);

        // Insert order
        $stmt = $this->pdo->prepare("
            INSERT INTO orders (
                service_date, ticket_number, status,
                menu_id, total_cents, estimated_prep_sec, validated_at
            ) VALUES (
                CURDATE(), :ticket, 'VALIDATED',
                :menu_id, :total, :prep, NOW()
            )
        ");

        $stmt->execute([
            ':ticket' => $ticket,
            ':menu_id' => $menu['id'],
            ':total' => $calc['total_cents'],
            ':prep' => $calc['estimated_prep_sec']
        ]);

        $orderId = (int)$this->pdo->lastInsertId();

        // Insert order items
        foreach ($calc['items'] as $item) {
            $stmtItem = $this->pdo->prepare("
                INSERT INTO order_items (
                    order_id, menu_item_id, label_snapshot, qty, unit_price_cents
                ) VALUES (
                    :order_id, :menu_item_id, :label, :qty, :price
                )
            ");

            $stmtItem->execute([
                ':order_id' => $orderId,
                ':menu_item_id' => $item['id'],
                ':label' => $item['name'],
                ':qty' => $item['qty'],
                ':price' => $item['price_cents']
            ]);
        }

        $this->pdo->commit();

        return [
            'id' => $orderId,
            'service_date' => date('Y-m-d'),
            'ticket_number' => $ticket,
            'status' => 'VALIDATED',
            'estimated_prep_sec' => $calc['estimated_prep_sec']
        ];
    } catch (\Throwable $e) {
        $this->pdo->rollBack();
        throw $e;
    }
    }

    private function getTodayMenu(): array
    {
    $stmt = $this->pdo->query("
        SELECT * FROM menus
        WHERE service_date = CURDATE() AND is_active = 1
        LIMIT 1
    ");

    $menu = $stmt->fetch();
    if (!$menu) {
        throw new \RuntimeException('Aucun menu actif pour aujourd’hui');
    }

    return $menu;
    }
    
    private function getNextTicketNumber(): int
    {
    $stmt = $this->pdo->query("
        SELECT MAX(ticket_number) AS max_ticket
        FROM orders
        WHERE service_date = CURDATE()
    ");

    $result = $stmt->fetch();
    $maxTicket = $result['max_ticket'] ?? 0;
    return ((int)$maxTicket) + 1;
    }

    private function calculatePrepTime(array $items): array
    {
    $estimated = 0;
    $total = 0;
    $resultItems = [];

    foreach ($items as $item) {
        if (!isset($item['menu_item_id'], $item['qty'])) {
            throw new \RuntimeException('Item invalide');
        }

        $stmt = $this->pdo->prepare("
            SELECT id, name, price_cents, prep_time_sec
            FROM menu_items
            WHERE id = :id AND available = 1
        ");

        $stmt->execute([':id' => $item['menu_item_id']]);
        $dbItem = $stmt->fetch();

        if (!$dbItem) {
            throw new \RuntimeException('Item introuvable ou indisponible');
        }

        $qty = max(1, (int)$item['qty']);

        $estimated += ($dbItem['prep_time_sec'] ?? 0) * $qty;
        $total += ($dbItem['price_cents'] ?? 0) * $qty;

        $resultItems[] = [
            'id' => $dbItem['id'],
            'name' => $dbItem['name'],
            'qty' => $qty,
            'price_cents' => $dbItem['price_cents']
        ];
    }

    return [
        'estimated_prep_sec' => $estimated,
        'total_cents' => $total,
        'items' => $resultItems
    ];
    }

    public function setStatusByAction(int $orderId, string $action): array
    {
    $map = [
        'preparing' => ['PREPARING', 'preparing_at'],
        'paid'      => ['PAID', 'paid_at'],
        'ready'     => ['READY', 'ready_at'],
        'completed' => ['COMPLETED', 'completed_at'],
        'cancel'    => ['CANCELLED', 'cancelled_at'],
    ];

    if (!isset($map[$action])) {
        throw new \RuntimeException('Action invalide');
    }

    [$nextStatus, $tsColumn] = $map[$action];

    $current = $this->getCurrentStatus($orderId);

    // transitions autorisées
    $allowed = [
        'VALIDATED'  => ['PREPARING', 'CANCELLED'],
        'PREPARING'  => ['PAID', 'CANCELLED'],
        'PAID'       => ['READY', 'CANCELLED'],
        'READY'      => ['COMPLETED'],
        'COMPLETED'  => [],
        'CANCELLED'  => [],
    ];

    if (!isset($allowed[$current]) || !in_array($nextStatus, $allowed[$current], true)) {
        // erreur métier = conflict
        throw new \DomainException("Transition interdite: $current -> $nextStatus");
    }

    $stmt = $this->pdo->prepare("
        UPDATE orders
        SET status = :status,
            {$tsColumn} = NOW()
        WHERE id = :id
    ");
    $stmt->execute([':status' => $nextStatus, ':id' => $orderId]);

    return $this->getById($orderId);
    }


    public function getById(int $id): array
    {
    $stmt = $this->pdo->prepare("SELECT * FROM orders WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $order = $stmt->fetch();
    if (!$order) {
        throw new \RuntimeException('Commande introuvable');
    }
    return $order;
    }

    private function getCurrentStatus(int $orderId): string
    {
    $stmt = $this->pdo->prepare("SELECT status FROM orders WHERE id = :id");
    $stmt->execute([':id' => $orderId]);
    $row = $stmt->fetch();
    if (!$row) {
        throw new \RuntimeException('Commande introuvable');
    }
    return (string)$row['status'];
    }
}