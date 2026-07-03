<?php

use App\Core\Csrf;

$title = 'Utilisateur : ' . $user['first_name'];
?>
<h1 class="h4 mb-3"><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></h1>

<div class="row g-3">
    <div class="col-md-6">
        <div class="card card-body">
            <h2 class="h6">Informations</h2>
            <form method="POST" action="/users/<?= $user['id'] ?>">
                <?= Csrf::field() ?>
                <div class="mb-2"><label class="form-label">Prénom</label><input type="text" class="form-control" name="first_name" value="<?= htmlspecialchars($user['first_name']) ?>"></div>
                <div class="mb-2"><label class="form-label">Nom</label><input type="text" class="form-control" name="last_name" value="<?= htmlspecialchars($user['last_name']) ?>"></div>
                <div class="mb-2"><label class="form-label">E-mail</label><input type="email" class="form-control" name="email" value="<?= htmlspecialchars($user['email']) ?>"></div>
                <button class="btn btn-primary btn-sm mt-2">Enregistrer</button>
            </form>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card card-body mb-3">
            <h2 class="h6">Rôle</h2>
            <form method="POST" action="/users/<?= $user['id'] ?>/role" class="d-flex gap-2">
                <?= Csrf::field() ?>
                <select name="role_id" class="form-select">
                    <?php foreach ($roles as $role): ?>
                        <option value="<?= $role['id'] ?>" <?= $role['id'] == $user['role_id'] ? 'selected' : '' ?>><?= htmlspecialchars($role['label']) ?></option>
                    <?php endforeach; ?>
                </select>
                <button class="btn btn-outline-primary">Changer</button>
            </form>
        </div>

        <div class="card card-body mb-3">
            <h2 class="h6">Compte</h2>
            <div class="d-flex gap-2 flex-wrap">
                <?php if ($user['status'] === 'active'): ?>
                    <form method="POST" action="/users/<?= $user['id'] ?>/suspend"><?= Csrf::field() ?><button class="btn btn-warning btn-sm">Suspendre</button></form>
                <?php else: ?>
                    <form method="POST" action="/users/<?= $user['id'] ?>/reactivate"><?= Csrf::field() ?><button class="btn btn-success btn-sm">Réactiver</button></form>
                <?php endif; ?>
                <form method="POST" action="/users/<?= $user['id'] ?>/reset-password" onsubmit="return confirm('Réinitialiser le mot de passe ?')"><?= Csrf::field() ?><button class="btn btn-outline-secondary btn-sm">Réinitialiser le mot de passe</button></form>
                <form method="POST" action="/users/<?= $user['id'] ?>/delete" onsubmit="return confirm('Supprimer définitivement cet utilisateur ?')"><?= Csrf::field() ?><button class="btn btn-outline-danger btn-sm">Supprimer</button></form>
            </div>
        </div>
    </div>
</div>
