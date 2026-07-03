<?php

declare(strict_types=1);

namespace App\Auth;

use App\Core\Logger;
use App\Core\RateLimiter;
use App\Core\Session;
use App\Domain\User\UserRepository;

final class AuthService
{
    public function __construct(private UserRepository $users = new UserRepository())
    {
    }

    /**
     * @return array{success: bool, message?: string}
     */
    public function attempt(string $email, string $password): array
    {
        $email = mb_strtolower(trim($email));

        if (RateLimiter::tooManyAttempts($email)) {
            return ['success' => false, 'message' => 'Trop de tentatives. Réessayez dans ' . RateLimiter::minutesUntilRetry() . ' minutes.'];
        }

        $user = $this->users->findByEmail($email);

        if (!$user || $user['status'] !== 'active' || !password_verify($password, $user['password_hash'])) {
            RateLimiter::recordAttempt($email, false);
            Logger::record('login.failed', null, 'user', null, ['email' => $email]);
            return ['success' => false, 'message' => 'Identifiants invalides.'];
        }

        RateLimiter::recordAttempt($email, true);

        // Rehash transparent si l'algorithme par défaut a évolué entre-temps
        if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
            $this->users->updatePasswordHash((int) $user['id'], password_hash($password, PASSWORD_DEFAULT));
        }

        Session::regenerate();
        Session::set('user_id', (int) $user['id']);
        Session::set('role_id', (int) $user['role_id']);
        Session::set('full_name', $user['first_name'] . ' ' . $user['last_name']);
        Session::set('must_change_password', (bool) $user['must_change_password']);

        $this->users->touchLastLogin((int) $user['id']);
        Logger::record('login.success', (int) $user['id'], 'user', (int) $user['id']);

        return ['success' => true];
    }

    public function logout(): void
    {
        $userId = Session::get('user_id');
        if ($userId) {
            Logger::record('logout', (int) $userId, 'user', (int) $userId);
        }
        Session::destroy();
    }

    public function currentUserId(): ?int
    {
        $id = Session::get('user_id');
        return $id ? (int) $id : null;
    }
}
