<?php

use App\Core\Csrf;

$title = 'Catégories';

// Pour le select parent_id dans le formulaire d'édition inline
$flatList = array_column($categories, null, 'id');
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 mb-0">Catégories</h1>
</div>

<div class="row g-3">
    <!-- Formulaire de création -->
    <div class="col-md-4">
        <div class="card card-body">
            <h2 class="h6">Nouvelle catégorie</h2>
            <form method="POST" action="/categories">
                <?= Csrf::field() ?>
                <div class="mb-2">
                    <label class="form-label">Nom *</label>
                    <input type="text" class="form-control" name="name" required autofocus>
                </div>
                <div class="mb-3">
                    <label class="form-label">Catégorie parente (optionnel)</label>
                    <select name="parent_id" class="form-select">
                        <option value="">— aucune —</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button class="btn btn-primary w-100">Créer</button>
            </form>
        </div>
    </div>

    <!-- Liste des catégories -->
    <div class="col-md-8">
        <?php if (!$categories): ?>
            <div class="alert alert-info">Aucune catégorie. Créez-en une à gauche.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped align-middle">
                    <thead>
                        <tr>
                            <th>Nom</th>
                            <th>Parente</th>
                            <th class="text-center">Produits</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categories as $cat): ?>
                            <tr>
                                <td>
                                    <!-- Édition inline via collapse Bootstrap -->
                                    <strong><?= htmlspecialchars($cat['name']) ?></strong>
                                    <button class="btn btn-link btn-sm p-0 ms-1"
                                            data-bs-toggle="collapse"
                                            data-bs-target="#edit-cat-<?= $cat['id'] ?>">
                                        ✏️
                                    </button>
                                    <div class="collapse mt-2" id="edit-cat-<?= $cat['id'] ?>">
                                        <form method="POST" action="/categories/<?= $cat['id'] ?>" class="d-flex gap-2 flex-wrap">
                                            <?= Csrf::field() ?>
                                            <input type="text" class="form-control form-control-sm"
                                                   name="name" value="<?= htmlspecialchars($cat['name']) ?>"
                                                   style="max-width:160px;" required>
                                            <select name="parent_id" class="form-select form-select-sm" style="max-width:150px;">
                                                <option value="">— aucune —</option>
                                                <?php foreach ($categories as $opt): ?>
                                                    <?php if ($opt['id'] === $cat['id']) continue; ?>
                                                    <option value="<?= $opt['id'] ?>"
                                                        <?= $opt['id'] == $cat['parent_id'] ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($opt['name']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <button class="btn btn-sm btn-success">Enregistrer</button>
                                        </form>
                                    </div>
                                </td>
                                <td><?= htmlspecialchars($cat['parent_name'] ?? '—') ?></td>
                                <td class="text-center">
                                    <span class="badge bg-secondary"><?= (int) $cat['product_count'] ?></span>
                                </td>
                                <td>
                                    <form method="POST" action="/categories/<?= $cat['id'] ?>/delete"
                                          onsubmit="return confirm('Supprimer « <?= htmlspecialchars($cat['name']) ?> » ?\nLes <?= (int)$cat['product_count'] ?> produit(s) liés seront sans catégorie.')">
                                        <?= Csrf::field() ?>
                                        <button class="btn btn-sm btn-outline-danger">Supprimer</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
