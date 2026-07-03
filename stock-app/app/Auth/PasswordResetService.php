<?php

declare(strict_types=1);

namespace App\Auth;

use App\Core\Config;
use App\Core\Database;
use App\Core\Logger;
use App\Core\Mailer;
use App\Domain\User\UserRepository;
use PDO;

final class PasswordResetService
{
    private PDO $pdo;

    public function __construct(private UserRepository $users = new UserRepository())
    {
        $this->pdo = Database::connection();
    }

    /**
     * Déclenche le workflow de réinitialisation.
     * Retourne l'URL de reset si l'e-mail n'a pas pu être envoyé
     * (sendmail absent, mode dev), null si l'e-mail est parti normalement.
     * Retourne toujours null si l'adresse est inconnue (anti-énumération).
     */
    public function requestReset(string $email): ?string
    {
        $user = $this->users->findByEmail(mb_strtolower(trim($email)));
        // Réponse identique qu'un compte existe ou non (évite l'énumération d'adresses).
        if (!$user) {
            return null;
        }

        $config     = Config::all();
        $plainToken = bin2hex(random_bytes(32));
        $tokenHash  = hash('sha256', $plainToken);

        $stmt = $this->pdo->prepare(
            'INSERT INTO password_resets (user_id, token_hash, created_at, expires_at)
             VALUES (:user_id, :token_hash, NOW(), DATE_ADD(NOW(), INTERVAL :minutes MINUTE))'
        );
        $stmt->execute([
            'user_id'    => $user['id'],
            'token_hash' => $tokenHash,
            'minutes'    => $config['password_reset']['expiry_minutes'],
        ]);

        $link = rtrim($config['app']['url'], '/') . '/password/reset/' . $plainToken;
        $body = "<p>Une réinitialisation de mot de passe a été demandée.</p>"
              . "<p><a href=\"{$link}\">Cliquez ici pour choisir un nouveau mot de passe</a></p>"
              . "<p>Ce lien expire dans {$config['password_reset']['expiry_minutes']} minutes. "
              . "Si vous n'êtes pas à l'origine de cette demande, ignorez cet e-mail.</p>";

        $unsent = Mailer::send($user['email'], 'Réinitialisation de mot de passe', $body);

        Logger::record('password.reset_requested', (int) $user['id'], 'user', (int) $user['id']);

        // Si Mailer n'a pas pu envoyer, il retourne le corps HTML.
        // On retourne alors le lien brut pour que le contrôleur l'affiche.
        return $unsent !== null ? $link : null;
    }

    public function validateToken(string $plainToken): ?array
    {
        $tokenHash = hash('sha256', $plainToken);
        $stmt = $this->pdo->prepare(
            'SELECT * FROM password_resets WHERE token_hash = :hash AND used_at IS NULL AND expires_at > NOW() LIMIT 1'
        );
        $stmt->execute(['hash' => $tokenHash]);
        $reset = $stmt->fetch();
        return $reset ?: null;
    }

    public function resetPassword(string $plainToken, string $newPassword): bool
    {
        $reset = $this->validateToken($plainToken);
        if (!$reset) {
            return false;
        }

        $this->pdo->beginTransaction();
        try {
            $this->users->updatePasswordHash((int) $reset['user_id'], password_hash($newPassword, PASSWORD_DEFAULT));

            $stmt = $this->pdo->prepare('UPDATE password_resets SET used_at = NOW() WHERE id = :id');
            $stmt->execute(['id' => $reset['id']]);

            $this->pdo->commit();
            Logger::record('password.reset_completed', (int) $reset['user_id'], 'user', (int) $reset['user_id']);
            return true;
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            error_log('[PasswordResetService] ' . $e->getMessage());
            return false;
        }
    }
}
