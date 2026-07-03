<?php

use App\Core\Csrf;
use App\Core\Session;

$title    = 'Invitations';
$config   = \App\Core\Config::all();
$isDev    = ($config['app']['env'] ?? 'production') !== 'production';
$devLink  = Session::getFlash('dev_invitation_link');
?>
<h1 class="h4 mb-3">Invitations</h1>

<?php if ($isDev): ?>
    <div class="alert alert-info small">
        <strong>Mode développement :</strong>
        les e-mails d'invitation ne sont pas envoyés.
        Les liens générés sont dans <code>storage/logs/mail.log</code>.
    </div>
<?php endif; ?>

<?php if ($devLink): ?>
    <div class="alert alert-warning">
        <strong>Lien d'invitation (sendmail non disponible) :</strong><br>
        <a href="<?= htmlspecialchars($devLink) ?>" class="alert-link text-break">
            <?= htmlspecialchars($devLink) ?>
        </a>
        <div class="small text-muted mt-1">Communiquez ce lien à l'utilisateur invité.</div>
    </div>
<?php endif; ?>

<div class="row g-3">
    <div class="col-md-4">
        <div class="card card-body">
            <h2 class="h6">Inviter un nouvel utilisateur</h2>
            <form method="POST" action="/users/invitations">
                <?= Csrf::field() ?>
                <div class="mb-2">
                    <label class="form-label">Adresse e-mail</label>
                    <input type="email" class="form-control" name="email" required>
                </div>
                <div class="mb-2">
                    <label class="form-label">Rôle</label>
                    <select name="role_id" class="form-select" required>
                        <?php foreach ($roles as $role): ?>
                            <option value="<?= $role['id'] ?>"><?= htmlspecialchars($role['label']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button class="btn btn-primary w-100">Envoyer l'invitation</button>
            </form>
        </div>
    </div>

    <div class="col-md-8">
        <div class="table-responsive">
            <table class="table table-striped align-middle">
                <thead><tr><th>E-mail</th><th>Rôle</th><th>Créée par</th><th>Expire le</th><th>Statut</th><th></th></tr></thead>
                <tbody>
                    <?php foreach ($invitations as $inv): ?>
                        <tr>
                            <td><?= htmlspecialchars($inv['email']) ?></td>
                            <td><?= htmlspecialchars($inv['role_label']) ?></td>
                            <td><?= htmlspecialchars($inv['first_name'] . ' ' . $inv['last_name']) ?></td>
                            <td><?= htmlspecialchars($inv['expires_at']) ?></td>
                            <td>
                                <span class="badge bg-<?= ['pending' => 'info', 'used' => 'success', 'expired' => 'secondary', 'revoked' => 'danger'][$inv['status']] ?>">
                                    <?= htmlspecialchars($inv['status']) ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($inv['status'] === 'pending'): ?>
                                    <form method="POST" action="/users/invitations/<?= $inv['id'] ?>/revoke" onsubmit="return confirm('Révoquer cette invitation ?')">
                                        <?= Csrf::field() ?>
                                        <button class="btn btn-sm btn-outline-danger">Révoquer</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$invitations): ?><tr><td colspan="6" class="text-muted text-center">Aucune invitation.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
