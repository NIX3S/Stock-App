<?php

declare(strict_types=1);

namespace App\Domain\Stock;

use App\Core\Database;
use PDO;

final class StockEntryRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::connection();
    }

    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO stock_entries (product_id, quantity, remaining_quantity, entry_date, expiry_date, expiry_type, origin, comment, created_by, created_at)
             VALUES (:product_id, :quantity, :remaining_quantity, :entry_date, :expiry_date, :expiry_type, :origin, :comment, :created_by, NOW())'
        );
        $stmt->execute([
            'product_id'         => $data['product_id'],
            'quantity'           => $data['quantity'],
            'remaining_quantity' => $data['quantity'], // au départ égal à quantity, décrémenté par les sorties
            'entry_date'         => $data['entry_date'],
            'expiry_date'        => $data['expiry_date'] ?? null,
            'expiry_type'        => $data['expiry_type'] ?? null,
            'origin'             => $data['origin'] ?? null,
            'comment'            => $data['comment'] ?? null,
            'created_by'         => $data['created_by'],
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Mise à jour des champs éditables d'une entrée.
     * La quantité initiale n'est jamais modifiable ici (elle fait partie de l'historique).
     * On peut corriger : DDM/DLC, type, origine, commentaire.
     * Si on corrige aussi remaining_quantity (ex: saisie initiale erronée), on l'autorise.
     */
    public function update(int $id, array $data): void
    {
        $allowed = ['expiry_date', 'expiry_type', 'origin', 'comment', 'entry_date'];
        // Cas spécial : correction de la quantité restante (erreur de saisie)
        if (isset($data['remaining_quantity'])) {
            $allowed[] = 'remaining_quantity';
        }

        $set    = [];
        $params = ['id' => $id];
        foreach ($data as $key => $value) {
            if (in_array($key, $allowed, true)) {
                $set[]        = "{$key} = :{$key}";
                $params[$key] = ($value === '') ? null : $value;
            }
        }
        if (!$set) {
            return;
        }
        $this->pdo->prepare('UPDATE stock_entries SET ' . implode(', ', $set) . ' WHERE id = :id')
                  ->execute($params);
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM stock_entries WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    /** Entrées d'un produit ayant encore du stock, triées par échéance la plus proche. */
    public function availableForProduct(int $productId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM stock_entries
             WHERE product_id = :product_id AND remaining_quantity > 0
             ORDER BY (expiry_date IS NULL), expiry_date ASC'
        );
        $stmt->execute(['product_id' => $productId]);
        return $stmt->fetchAll();
    }

    /** Retourne l'entrée du produit ayant l'échéance la plus proche, hors entrée exclue. */
    public function earliestExpiryExcluding(int $productId, int $excludeEntryId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM stock_entries
             WHERE product_id = :product_id AND remaining_quantity > 0 AND id <> :exclude_id AND expiry_date IS NOT NULL
             ORDER BY expiry_date ASC LIMIT 1'
        );
        $stmt->execute(['product_id' => $productId, 'exclude_id' => $excludeEntryId]);
        return $stmt->fetch() ?: null;
    }

    public function decrementRemaining(int $entryId, int $quantity): void
    {
        // PDO named params ne peuvent pas être réutilisés dans la même requête :
        // :qty et :qty_check portent la même valeur mais des noms distincts.
        $stmt = $this->pdo->prepare(
            'UPDATE stock_entries
             SET remaining_quantity = remaining_quantity - :qty
             WHERE id = :id
               AND remaining_quantity >= :qty_check'
        );
        $stmt->execute([
            'qty'       => $quantity,
            'id'        => $entryId,
            'qty_check' => $quantity,
        ]);
    }

    public function paginate(int $page, int $perPage, array $filters, string $sortColumn, string $sortDir): array
    {
        $allowedSort = ['entry_date', 'expiry_date', 'product_name', 'quantity', 'remaining_quantity'];
        if (!in_array($sortColumn, $allowedSort, true)) {
            $sortColumn = 'entry_date';
        }
        $sortDir = strtolower($sortDir) === 'asc' ? 'ASC' : 'DESC';

        $where = [];
        $params = [];
        if (!empty($filters['product_id'])) {
            $where[] = 'se.product_id = :product_id';
            $params['product_id'] = $filters['product_id'];
        }
        if (!empty($filters['expiring_soon_days'])) {
            $where[] = 'se.expiry_date IS NOT NULL AND se.expiry_date <= DATE_ADD(CURDATE(), INTERVAL :days DAY)';
            $params['days'] = (int) $filters['expiring_soon_days'];
        }
        $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        $offset = ($page - 1) * $perPage;

        $countStmt = $this->pdo->prepare("SELECT COUNT(*) FROM stock_entries se {$whereSql}");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $sql = "SELECT se.*, p.name AS product_name, p.unit
                FROM stock_entries se
                JOIN products p ON p.id = se.product_id
                {$whereSql}
                ORDER BY {$sortColumn} {$sortDir}
                LIMIT :limit OFFSET :offset";
        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue('limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return ['data' => $stmt->fetchAll(), 'total' => $total];
    }

    public function expiringSoon(int $days = 7, int $limit = 10): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT se.*, p.name AS product_name FROM stock_entries se
             JOIN products p ON p.id = se.product_id
             WHERE se.remaining_quantity > 0 AND se.expiry_date IS NOT NULL
               AND se.expiry_date <= DATE_ADD(CURDATE(), INTERVAL :days DAY)
             ORDER BY se.expiry_date ASC LIMIT :limit'
        );
        $stmt->bindValue('days', $days, PDO::PARAM_INT);
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
