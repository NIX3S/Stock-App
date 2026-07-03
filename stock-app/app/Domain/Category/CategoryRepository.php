<?php

declare(strict_types=1);

namespace App\Domain\Category;

use App\Core\Database;
use PDO;

final class CategoryRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::connection();
    }

    public function all(): array
    {
        return $this->pdo->query(
            'SELECT c.*, p.name AS parent_name,
                    (SELECT COUNT(*) FROM products WHERE category_id = c.id) AS product_count
             FROM categories c
             LEFT JOIN categories p ON p.id = c.parent_id
             ORDER BY p.name, c.name'
        )->fetchAll();
    }

    public function allFlat(): array
    {
        return $this->pdo->query('SELECT id, name, parent_id FROM categories ORDER BY name')->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM categories WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function create(string $name, ?int $parentId): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO categories (name, parent_id, created_at) VALUES (:name, :parent_id, NOW())'
        );
        $stmt->execute(['name' => trim($name), 'parent_id' => $parentId]);
        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, string $name, ?int $parentId): void
    {
        // Évite les références circulaires (une catégorie ne peut pas être son propre parent)
        if ($parentId === $id) {
            $parentId = null;
        }
        $this->pdo->prepare(
            'UPDATE categories SET name = :name, parent_id = :parent_id WHERE id = :id'
        )->execute(['name' => trim($name), 'parent_id' => $parentId, 'id' => $id]);
    }

    public function delete(int $id): void
    {
        // Les produits liés auront category_id mis à NULL (ON DELETE SET NULL dans la migration)
        // Les sous-catégories auront parent_id mis à NULL
        $this->pdo->prepare('DELETE FROM categories WHERE id = :id')->execute(['id' => $id]);
    }
}
