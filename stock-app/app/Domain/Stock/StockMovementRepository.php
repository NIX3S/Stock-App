<?php

declare(strict_types=1);

namespace App\Domain\Stock;

use App\Core\Database;
use PDO;

final class StockMovementRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::connection();
    }

    public function insert(string $type, int $productId, int $stockEntryId, int $quantity, int $performedBy, ?string $comment = null): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO stock_movements (type, product_id, stock_entry_id, quantity, performed_by, performed_at, comment)
             VALUES (:type, :product_id, :stock_entry_id, :quantity, :performed_by, NOW(), :comment)'
        );
        $stmt->execute([
            'type' => $type,
            'product_id' => $productId,
            'stock_entry_id' => $stockEntryId,
            'quantity' => $quantity,
            'performed_by' => $performedBy,
            'comment' => $comment,
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function recent(string $type, int $limit = 10): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT sm.*, p.name AS product_name, u.first_name, u.last_name
             FROM stock_movements sm
             JOIN products p ON p.id = sm.product_id
             JOIN users u ON u.id = sm.performed_by
             WHERE sm.type = :type
             ORDER BY sm.performed_at DESC LIMIT :limit'
        );
        $stmt->bindValue('type', $type);
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
