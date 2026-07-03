<?php

use App\Core\Csrf;

$title = 'Rôles et permissions';

$grouped = [];
foreach ($allPermissions as $perm) {
    $grouped[$perm['category']][] = $perm;
}
?>
<h1 class="h4 mb-3">Rôles et permissions</h1>
<p class="text-muted">Les permissions sont indépendantes des rôles : créez un nouveau profil et cochez librement les permissions souhaitées, sans modification de code.</p>

<div class="card card-body mb-4" style="max-width:420px;">
    <h2 class="h6">Créer un nouveau rôle</h2>
    <form method="POST" action="/roles" class="d-flex gap-2">
        <?= Csrf::field() ?>
        <input type="text" class="form-control" name="name" placeholder="identifiant_role" required>
        <input type="text" class="form-control" name="label" placeholder="Libellé affiché" required>
        <button class="btn btn-primary">Créer</button>
    </form>
</div>

<div class="row g-3">
    <?php foreach ($roles as $role): ?>
        <div class="col-md-6 col-lg-4">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <strong><?= htmlspecialchars($role['label']) ?></strong>
                    <?php if (!$role['is_system']): ?>
                        <form method="POST" action="/roles/<?= $role['id'] ?>/delete" onsubmit="return confirm('Supprimer ce rôle ?')">
                            <?= Csrf::field() ?>
                            <button class="btn btn-sm btn-outline-danger">Supprimer</button>
                        </form>
                    <?php else: ?>
                        <span class="badge bg-secondary">Système</span>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <form method="POST" action="/roles/<?= $role['id'] ?>/permissions">
                        <?= Csrf::field() ?>
                        <?php foreach ($grouped as $category => $perms): ?>
                            <div class="mb-2">
                                <div class="text-uppercase text-muted small fw-bold"><?= htmlspecialchars($category) ?></div>
                                <?php foreach ($perms as $perm): ?>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="permission_ids[]" value="<?= $perm['id'] ?>"
                                            id="perm-<?= $role['id'] ?>-<?= $perm['id'] ?>"
                                            <?= in_array($perm['code'], $permissionsByRole[$role['id']] ?? [], true) ? 'checked' : '' ?>>
                                        <label class="form-check-label small" for="perm-<?= $role['id'] ?>-<?= $perm['id'] ?>"><?= htmlspecialchars($perm['label']) ?></label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endforeach; ?>
                        <button class="btn btn-sm btn-primary mt-2">Enregistrer les permissions</button>
                    </form>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>
