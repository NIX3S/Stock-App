<?php

declare(strict_types=1);

namespace App\Domain\Role;

use App\Core\Database;
use PDO;

final class RoleRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::connection();
    }

    public function all(): array
    {
        return $this->pdo->query('SELECT * FROM roles ORDER BY id')->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM roles WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function create(string $name, string $label): int
    {
        $stmt = $this->pdo->prepare('INSERT INTO roles (name, label, is_system, created_at) VALUES (:name, :label, 0, NOW())');
        $stmt->execute(['name' => $name, 'label' => $label]);
        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, string $label): void
    {
        $this->pdo->prepare('UPDATE roles SET label = :label WHERE id = :id AND is_system = 0')
            ->execute(['label' => $label, 'id' => $id]);
    }

    public function delete(int $id): bool
    {
        $role = $this->findById($id);
        if (!$role || (int) $role['is_system'] === 1) {
            return false;
        }
        $this->pdo->prepare('DELETE FROM roles WHERE id = :id')->execute(['id' => $id]);
        return true;
    }
}
