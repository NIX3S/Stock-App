<?php

declare(strict_types=1);

namespace App\Core;

use PDO;

/**
 * Écrit dans audit_logs. N'expose volontairement aucune méthode de
 * modification ou de suppression : le journal est append-only par design.
 */
final class Logger
{
    public static function record(
        string $actionCode,
        ?int $userId = null,
        ?string $entityType = null,
        ?int $entityId = null,
        array $details = []
    ): void {
        $pdo = Database::connection();
        $stmt = $pdo->prepare(
            'INSERT INTO audit_logs (user_id, action_code, entity_type, entity_id, details_json, ip_address, created_at)
             VALUES (:user_id, :action_code, :entity_type, :entity_id, :details_json, :ip_address, NOW())'
        );
        $stmt->execute([
            'user_id' => $userId,
            'action_code' => $actionCode,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'details_json' => $details ? json_encode($details, JSON_UNESCAPED_UNICODE) : null,
            'ip_address' => $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? null,
        ]);
    }

    public static function recent(int $limit = 20): array
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare(
            'SELECT al.*, u.first_name, u.last_name
             FROM audit_logs al
             LEFT JOIN users u ON u.id = al.user_id
             ORDER BY al.created_at DESC
             LIMIT :limit'
        );
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
