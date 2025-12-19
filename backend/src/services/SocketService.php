<?php
declare(strict_types=1);

namespace App\Services;

final class SocketService
{
    private string $baseUrl;
    private string $token;

    public function __construct(array $env)
    {
        $this->baseUrl = rtrim((string)($env['SOCKET_SERVER_URL'] ?? 'http://localhost:3001'), '/');
        $this->token = (string)($env['SOCKET_TOKEN'] ?? '');
    }

    public function emitQueueUpdated(): void
    {
        $this->post('/emit/queue-updated', []);
    }

    public function emitOrderReady(int $orderId, int $ticketNumber): void
    {
        $this->post('/emit/order-ready', [
            'orderId' => $orderId,
            'ticketNumber' => $ticketNumber
        ]);
    }

    private function post(string $path, array $payload): void
    {
        $url = $this->baseUrl . $path;

        $headers = "Content-Type: application/json\r\n";
        if ($this->token !== '') {
            $headers .= "x-emit-token: {$this->token}\r\n";
        }

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => $headers,
                'content' => json_encode($payload),
                'timeout' => 2,
            ]
        ]);

        // Important: si le socket-server est down, on ne casse pas l’API
        @file_get_contents($url, false, $context);
    }
}
