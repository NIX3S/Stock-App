<?php

declare(strict_types=1);

namespace App\Auth;

use App\Core\Config;
use App\Core\Database;
use App\Core\Logger;
use App\Core\Mailer;
use App\Core\Session;
use App\Domain\User\UserRepository;
use PDO;

final class InvitationService
{
    public function __construct(
        private ?PDO $pdo = null,
        private UserRepository $users = new UserRepository()
    ) {
        $this->pdo = $pdo ?? Database::connection();
    }

    public function create(string $email, int $roleId, int $createdBy, ?int $expiryDays = null): string
    {
        $config     = Config::all();
        $expiryDays = $expiryDays ?? $config['invitation']['default_expiry_days'];
        $uuid       = $this->generateUuid();

        $stmt = $this->pdo->prepare(
            'INSERT INTO invitations (uuid, email, role_id, created_by, created_at, expires_at, max_uses, uses_count, status)
             VALUES (:uuid, :email, :role_id, :created_by, NOW(), DATE_ADD(NOW(), INTERVAL :days DAY), 1, 0, "pending")'
        );
        $stmt->execute([
            'uuid'       => $uuid,
            'email'      => mb_strtolower(trim($email)),
            'role_id'    => $roleId,
            'created_by' => $createdBy,
            'days'       => $expiryDays,
        ]);

        Logger::record('invitation.create', $createdBy, 'invitation', (int) $this->pdo->lastInsertId(), ['email' => $email]);

        $devLink = $this->sendInvitationEmail($email, $uuid);

        // Si le mail n'a pas pu partir, on stocke le lien en session flash
        // pour que le contrôleur puisse l'afficher sur la page.
        if ($devLink !== null) {
            Session::flash('dev_invitation_link', $devLink);
        }

        return $uuid;
    }

    private function sendInvitationEmail(string $email, string $uuid): ?string
    {
        $config = Config::all();
        $link   = rtrim($config['app']['url'], '/') . '/invitation/' . $uuid;
        $body   = "<p>Vous avez été invité(e) à rejoindre {$config['app']['name']}.</p>"
                . "<p><a href=\"{$link}\">Cliquez ici pour créer votre compte</a></p>"
                . "<p>Ce lien expire dans quelques jours et ne peut être utilisé qu'une seule fois.</p>";

        $unsent = Mailer::send($email, 'Invitation - ' . $config['app']['name'], $body);
        return $unsent !== null ? $link : null;
    }

    /**
     * @return array|null L'invitation si valide et utilisable, null sinon.
     */
    public function validate(string $uuid): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM invitations WHERE uuid = :uuid LIMIT 1');
        $stmt->execute(['uuid' => $uuid]);
        $invitation = $stmt->fetch();

        if (!$invitation) {
            return null;
        }

        if ($invitation['status'] !== 'pending') {
            return null;
        }

        if (strtotime($invitation['expires_at']) < time()) {
            $this->markExpired((int) $invitation['id']);
            return null;
        }

        if ((int) $invitation['uses_count'] >= (int) $invitation['max_uses']) {
            return null;
        }

        return $invitation;
    }

    public function acceptAndCreateAccount(string $uuid, string $firstName, string $lastName, string $password): bool
    {
        $invitation = $this->validate($uuid);
        if (!$invitation) {
            return false;
        }

        $this->pdo->beginTransaction();
        try {
            $userId = $this->users->create(
                $invitation['email'],
                password_hash($password, PASSWORD_DEFAULT),
                $firstName,
                $lastName,
                (int) $invitation['role_id']
            );

            $stmt = $this->pdo->prepare(
                'UPDATE invitations SET uses_count = uses_count + 1,
                 status = CASE WHEN uses_count + 1 >= max_uses THEN "used" ELSE status END
                 WHERE id = :id'
            );
            $stmt->execute(['id' => $invitation['id']]);

            $this->pdo->commit();
            Logger::record('invitation.accepted', $userId, 'user', $userId, ['invitation_uuid' => $uuid]);
            return true;
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            error_log('[InvitationService] ' . $e->getMessage());
            return false;
        }
    }

    public function revoke(int $invitationId, int $byUserId): void
    {
        $stmt = $this->pdo->prepare('UPDATE invitations SET status = "revoked" WHERE id = :id');
        $stmt->execute(['id' => $invitationId]);
        Logger::record('invitation.revoke', $byUserId, 'invitation', $invitationId);
    }

    private function markExpired(int $invitationId): void
    {
        $stmt = $this->pdo->prepare('UPDATE invitations SET status = "expired" WHERE id = :id');
        $stmt->execute(['id' => $invitationId]);
    }

    /** Invalide automatiquement toutes les invitations dont la date d'expiration est dépassée. */
    public function expireOutdated(): int
    {
        $stmt = $this->pdo->prepare(
            'UPDATE invitations SET status = "expired" WHERE status = "pending" AND expires_at < NOW()'
        );
        $stmt->execute();
        return $stmt->rowCount();
    }

    public function listAll(): array
    {
        $stmt = $this->pdo->query(
            'SELECT i.*, r.label AS role_label, u.first_name, u.last_name
             FROM invitations i
             JOIN roles r ON r.id = i.role_id
             JOIN users u ON u.id = i.created_by
             ORDER BY i.created_at DESC'
        );
        return $stmt->fetchAll();
    }

    private function generateUuid(): string
    {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
