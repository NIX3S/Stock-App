<?php

declare(strict_types=1);

namespace App\Domain\Product;

use App\Core\Database;
use PDO;

final class ProductRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::connection();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT p.*, c.name AS category_name,
                    COALESCE((SELECT SUM(remaining_quantity) FROM stock_entries se WHERE se.product_id = p.id), 0) AS total_stock
             FROM products p
             LEFT JOIN categories c ON c.id = p.category_id
             WHERE p.id = :id'
        );
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function findByBarcode(string $barcode): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM products WHERE barcode = :barcode LIMIT 1');
        $stmt->execute(['barcode' => $barcode]);
        return $stmt->fetch() ?: null;
    }

    public function create(array $data): int
    {
        // Normalise les champs nullable : chaîne vide → NULL
        foreach (self::NULLABLE_FIELDS as $f) {
            if (array_key_exists($f, $data)) {
                $data[$f] = $this->nullify($f, $data[$f]);
            }
        }

        $stmt = $this->pdo->prepare(
            'INSERT INTO products
                (name, reference, barcode, barcode_type, category_id, description,
                 photo_path, unit, min_stock_threshold, status, created_at, updated_at)
             VALUES
                (:name, :reference, :barcode, :barcode_type, :category_id, :description,
                 :photo_path, :unit, :min_stock_threshold, "active", NOW(), NOW())'
        );
        $stmt->execute([
            'name'                => $data['name'],
            'reference'           => $data['reference'] ?? null,
            'barcode'             => $data['barcode'] ?? null,
            'barcode_type'        => $data['barcode_type'] ?? 'manufacturer',
            'category_id'         => $data['category_id'] ?? null,
            'description'         => $data['description'] ?? null,
            'photo_path'          => $data['photo_path'] ?? null,
            'unit'                => $data['unit'] ?? 'unité',
            'min_stock_threshold' => $data['min_stock_threshold'] ?? 0,
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    /** Colonnes FK nullable : toute valeur vide/nulle → NULL (évite les violations FK). */
    private const NULLABLE_FIELDS = ['category_id', 'barcode', 'reference', 'photo_path', 'expiry_type'];

    /** Retourne null si la valeur est vide, "0" ou 0 pour une colonne FK nullable. */
    private function nullify(string $key, mixed $value): mixed
    {
        if (!in_array($key, self::NULLABLE_FIELDS, true)) {
            return $value;
        }
        // "" | "0" | 0 | null → NULL en base
        if ($value === null || $value === '' || $value === 0 || $value === '0') {
            return null;
        }
        return $value;
    }

    public function update(int $id, array $data): void
    {
        $allowed = ['name', 'reference', 'barcode', 'barcode_type', 'category_id',
                    'description', 'photo_path', 'unit', 'min_stock_threshold', 'status'];
        $set    = [];
        $params = ['id' => $id];
        foreach ($data as $key => $value) {
            if (in_array($key, $allowed, true)) {
                $set[]        = "{$key} = :{$key}";
                $params[$key] = $this->nullify($key, $value);
            }
        }
        if (!$set) {
            return;
        }
        $sql = 'UPDATE products SET ' . implode(', ', $set) . ', updated_at = NOW() WHERE id = :id';
        $this->pdo->prepare($sql)->execute($params);
    }

    public function archive(int $id): void
    {
        $this->pdo->prepare('UPDATE products SET status = "archived", updated_at = NOW() WHERE id = :id')->execute(['id' => $id]);
    }

    public function reactivate(int $id): void
    {
        $this->pdo->prepare('UPDATE products SET status = "active", updated_at = NOW() WHERE id = :id')->execute(['id' => $id]);
    }

    /**
     * Tableau paginé avec tri/filtre/recherche — utilisé par l'API du datatable.
     */
    public function paginate(int $page, int $perPage, array $filters, string $sortColumn, string $sortDir): array
    {
        $allowedSort = ['name', 'reference', 'category_name', 'total_stock', 'status', 'created_at'];
        if (!in_array($sortColumn, $allowedSort, true)) {
            $sortColumn = 'name';
        }
        $sortDir = strtolower($sortDir) === 'desc' ? 'DESC' : 'ASC';

        $where = [];
        $params = [];

        if (!empty($filters['search'])) {
            $where[] = 'MATCH(p.name, p.reference, p.description) AGAINST(:search IN BOOLEAN MODE)';
            $params['search'] = $filters['search'] . '*';
        }
        if (!empty($filters['status'])) {
            $where[] = 'p.status = :status';
            $params['status'] = $filters['status'];
        }
        if (!empty($filters['category_id'])) {
            $where[] = 'p.category_id = :category_id';
            $params['category_id'] = $filters['category_id'];
        }
        if (!empty($filters['low_stock'])) {
            $where[] = '(SELECT COALESCE(SUM(remaining_quantity),0) FROM stock_entries se WHERE se.product_id = p.id) <= p.min_stock_threshold';
        }

        $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        $offset = ($page - 1) * $perPage;

        $countStmt = $this->pdo->prepare("SELECT COUNT(*) FROM products p {$whereSql}");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $sql = "SELECT p.*, c.name AS category_name,
                       COALESCE((SELECT SUM(remaining_quantity) FROM stock_entries se WHERE se.product_id = p.id), 0) AS total_stock
                FROM products p
                LEFT JOIN categories c ON c.id = p.category_id
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

    public function countActive(): int
    {
        return (int) $this->pdo->query('SELECT COUNT(*) FROM products WHERE status = "active"')->fetchColumn();
    }

    public function totalStockQuantity(): int
    {
        return (int) $this->pdo->query('SELECT COALESCE(SUM(remaining_quantity),0) FROM stock_entries')->fetchColumn();
    }

    public function belowMinimum(int $limit = 10): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT p.id, p.name, p.min_stock_threshold,
                    COALESCE((SELECT SUM(remaining_quantity) FROM stock_entries se WHERE se.product_id = p.id), 0) AS total_stock
             FROM products p
             WHERE p.status = "active"
             HAVING total_stock <= p.min_stock_threshold
             ORDER BY total_stock ASC
             LIMIT :limit'
        );
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
