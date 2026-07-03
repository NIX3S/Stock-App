<?php

declare(strict_types=1);

namespace App\Domain\User;

use App\Core\Logger;

final class UserService
{
    public function __construct(private UserRepository $repository = new UserRepository())
    {
    }

    public function changeRole(int $userId, int $newRoleId, int $byUserId): void
    {
        $this->repository->update($userId, ['role_id' => $newRoleId]);
        Logger::record('user.role_changed', $byUserId, 'user', $userId, ['new_role_id' => $newRoleId]);
    }

    public function suspend(int $userId, int $byUserId): void
    {
        $this->repository->suspend($userId);
        Logger::record('user.suspended', $byUserId, 'user', $userId);
    }

    public function reactivate(int $userId, int $byUserId): void
    {
        $this->repository->reactivate($userId);
        Logger::record('user.reactivated', $byUserId, 'user', $userId);
    }

    public function update(int $userId, array $fields, int $byUserId): void
    {
        $this->repository->update($userId, $fields);
        Logger::record('user.updated', $byUserId, 'user', $userId, ['fields' => array_keys($fields)]);
    }

    public function delete(int $userId, int $byUserId): void
    {
        Logger::record('user.deleted', $byUserId, 'user', $userId);
        $this->repository->delete($userId);
    }

    /** Génère un mot de passe temporaire et force son changement à la prochaine connexion. */
    public function resetPasswordByAdmin(int $userId, int $byUserId): string
    {
        $temporary = bin2hex(random_bytes(6));
        $this->repository->forcePasswordReset($userId, password_hash($temporary, PASSWORD_DEFAULT));
        Logger::record('user.password_reset_by_admin', $byUserId, 'user', $userId);
        return $temporary;
    }
}
