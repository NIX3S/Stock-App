<?php

$title = 'Utilisateurs';
$repository = new \App\Domain\User\UserRepository();
$users = $repository->all();
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 mb-0">Utilisateurs</h1>
    <a href="/users/invitations" class="btn btn-primary"><i class="bi bi-envelope-plus"></i> Inviter un utilisateur</a>
</div>

<div class="table-responsive">
    <table class="table table-striped align-middle">
        <thead><tr><th>Nom</th><th>E-mail</th><th>Rôle</th><th>Statut</th><th>Dernière connexion</th><th></th></tr></thead>
        <tbody>
            <?php foreach ($users as $user): ?>
                <tr>
                    <td><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></td>
                    <td><?= htmlspecialchars($user['email']) ?></td>
                    <td><?= htmlspecialchars($user['role_label']) ?></td>
                    <td><span class="badge bg-<?= $user['status'] === 'active' ? 'success' : 'secondary' ?>"><?= htmlspecialchars($user['status']) ?></span></td>
                    <td><?= htmlspecialchars($user['last_login_at'] ?? 'Jamais') ?></td>
                    <td><a href="/users/<?= $user['id'] ?>" class="btn btn-sm btn-outline-primary">Gérer</a></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
