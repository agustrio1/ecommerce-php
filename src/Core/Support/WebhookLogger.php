<?php

declare(strict_types=1);

namespace App\Core\Support;

use PDO;

class WebhookLogger
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = db();
    }

    public function log(string $source, ?string $event, ?string $reference, array $payload): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO webhook_logs (source, event, reference, payload, status, created_at, updated_at)
             VALUES (:source, :event, :reference, :payload, "received", NOW(), NOW())'
        );
        $stmt->execute([
            'source'    => $source,
            'event'     => $event,
            'reference' => $reference,
            'payload'   => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function markProcessed(int $logId): void
    {
        $stmt = $this->pdo->prepare('UPDATE webhook_logs SET status = "processed", updated_at = NOW() WHERE id = :id');
        $stmt->execute(['id' => $logId]);
    }

    public function markFailed(int $logId, string $errorMessage): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE webhook_logs SET status = "failed", error_message = :error, updated_at = NOW() WHERE id = :id'
        );
        $stmt->execute(['id' => $logId, 'error' => $errorMessage]);
    }

    /**
     * Cek apakah reference ini sudah pernah berhasil diproses (idempotency check).
     * Mencegah double-processing kalau webhook provider kirim ulang event yang sama.
     */
    public function alreadyProcessed(string $source, string $reference): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) FROM webhook_logs WHERE source = :source AND reference = :reference AND status = "processed"'
        );
        $stmt->execute(['source' => $source, 'reference' => $reference]);

        return (int) $stmt->fetchColumn() > 0;
    }
}