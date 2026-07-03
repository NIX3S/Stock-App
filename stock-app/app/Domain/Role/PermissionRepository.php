<?php

declare(strict_types=1);

namespace App\Domain\Role;

use App\Core\Database;
use PDO;

final class PermissionRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::connection();
    }

    public function roleHasPermission(int $roleId, string $permissionCode): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT 1 FROM role_permissions rp
             JOIN permissions p ON p.id = rp.permission_id
             WHERE rp.role_id = :role_id AND p.code = :code LIMIT 1'
        );
        $stmt->execute(['role_id' => $roleId, 'code' => $permissionCode]);
        return (bool) $stmt->fetchColumn();
    }

    public function permissionsForRole(int $roleId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT p.code FROM role_permissions rp
             JOIN permissions p ON p.id = rp.permission_id
             WHERE rp.role_id = :role_id'
        );
        $stmt->execute(['role_id' => $roleId]);
        return array_column($stmt->fetchAll(), 'code');
    }

    public function allPermissions(): array
    {
        return $this->pdo->query('SELECT * FROM permissions ORDER BY category, label')->fetchAll();
    }

    public function setRolePermissions(int $roleId, array $permissionIds): void
    {
        $this->pdo->beginTransaction();
        try {
            $this->pdo->prepare('DELETE FROM role_permissions WHERE role_id = :role_id')->execute(['role_id' => $roleId]);
            $stmt = $this->pdo->prepare('INSERT INTO role_permissions (role_id, permission_id) VALUES (:role_id, :permission_id)');
            foreach ($permissionIds as $permissionId) {
                $stmt->execute(['role_id' => $roleId, 'permission_id' => (int) $permissionId]);
            }
            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }
}
