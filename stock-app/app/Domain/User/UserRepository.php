<?php

declare(strict_types=1);

namespace App\Domain\User;

use App\Core\Database;
use PDO;

final class UserRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::connection();
    }

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);
        return $stmt->fetch() ?: null;
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT u.*, r.name AS role_name, r.label AS role_label
             FROM users u JOIN roles r ON r.id = u.role_id
             WHERE u.id = :id LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function create(string $email, string $passwordHash, string $firstName, string $lastName, int $roleId): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO users (email, password_hash, first_name, last_name, status, role_id, must_change_password, created_at, updated_at)
             VALUES (:email, :password_hash, :first_name, :last_name, "active", :role_id, 0, NOW(), NOW())'
        );
        $stmt->execute([
            'email' => $email,
            'password_hash' => $passwordHash,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'role_id' => $roleId,
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, array $fields): void
    {
        $allowed = ['first_name', 'last_name', 'email', 'role_id', 'status'];
        $set = [];
        $params = ['id' => $id];
        foreach ($fields as $key => $value) {
            if (in_array($key, $allowed, true)) {
                $set[] = "{$key} = :{$key}";
                $params[$key] = $value;
            }
        }
        if (!$set) {
            return;
        }
        $sql = 'UPDATE users SET ' . implode(', ', $set) . ', updated_at = NOW() WHERE id = :id';
        $this->pdo->prepare($sql)->execute($params);
    }

    public function updatePasswordHash(int $id, string $hash): void
    {
        $stmt = $this->pdo->prepare('UPDATE users SET password_hash = :hash, must_change_password = 0, updated_at = NOW() WHERE id = :id');
        $stmt->execute(['hash' => $hash, 'id' => $id]);
    }

    public function forcePasswordReset(int $id, string $temporaryHash): void
    {
        $stmt = $this->pdo->prepare('UPDATE users SET password_hash = :hash, must_change_password = 1, updated_at = NOW() WHERE id = :id');
        $stmt->execute(['hash' => $temporaryHash, 'id' => $id]);
    }

    public function suspend(int $id): void
    {
        $this->pdo->prepare('UPDATE users SET status = "suspended", updated_at = NOW() WHERE id = :id')->execute(['id' => $id]);
    }

    public function reactivate(int $id): void
    {
        $this->pdo->prepare('UPDATE users SET status = "active", updated_at = NOW() WHERE id = :id')->execute(['id' => $id]);
    }

    public function delete(int $id): void
    {
        $this->pdo->prepare('DELETE FROM users WHERE id = :id')->execute(['id' => $id]);
    }

    public function touchLastLogin(int $id): void
    {
        $this->pdo->prepare('UPDATE users SET last_login_at = NOW() WHERE id = :id')->execute(['id' => $id]);
    }

    public function paginate(int $page, int $perPage, array $filters = []): array
    {
        $where = [];
        $params = [];

        if (!empty($filters['search'])) {
            $where[] = '(u.first_name LIKE :search OR u.last_name LIKE :search OR u.email LIKE :search)';
            $params['search'] = '%' . $filters['search'] . '%';
        }
        if (!empty($filters['status'])) {
            $where[] = 'u.status = :status';
            $params['status'] = $filters['status'];
        }
        if (!empty($filters['role_id'])) {
            $where[] = 'u.role_id = :role_id';
            $params['role_id'] = $filters['role_id'];
        }

        $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        $offset = ($page - 1) * $perPage;

        $countStmt = $this->pdo->prepare("SELECT COUNT(*) FROM users u {$whereSql}");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $stmt = $this->pdo->prepare(
            "SELECT u.*, r.label AS role_label FROM users u
             JOIN roles r ON r.id = u.role_id
             {$whereSql}
             ORDER BY u.created_at DESC
             LIMIT :limit OFFSET :offset"
        );
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue('limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return ['data' => $stmt->fetchAll(), 'total' => $total];
    }

    public function all(): array
    {
        return $this->pdo->query('SELECT u.*, r.label AS role_label FROM users u JOIN roles r ON r.id = u.role_id ORDER BY u.last_name')->fetchAll();
    }
}
