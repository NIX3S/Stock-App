<?php

declare(strict_types=1);

namespace App\Core;

use PDO;

final class RateLimiter
{
    private const MAX_ATTEMPTS = 5;
    private const WINDOW_MINUTES = 15;

    public static function tooManyAttempts(string $identifier): bool
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare(
            'SELECT COUNT(*) FROM login_attempts
             WHERE identifier = :identifier
               AND success = 0
               AND attempted_at > (NOW() - INTERVAL :window MINUTE)'
        );
        $stmt->bindValue('identifier', $identifier);
        $stmt->bindValue('window', self::WINDOW_MINUTES, PDO::PARAM_INT);
        $stmt->execute();
        return (int) $stmt->fetchColumn() >= self::MAX_ATTEMPTS;
    }

    public static function recordAttempt(string $identifier, bool $success): void
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare(
            'INSERT INTO login_attempts (identifier, attempted_at, success) VALUES (:identifier, NOW(), :success)'
        );
        $stmt->execute(['identifier' => $identifier, 'success' => $success ? 1 : 0]);
    }

    public static function minutesUntilRetry(): int
    {
        return self::WINDOW_MINUTES;
    }
}
