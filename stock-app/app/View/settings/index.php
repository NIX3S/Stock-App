<?php

use App\Core\Csrf;

$title = 'Paramètres';

function renderCustomFieldList(array $fields, string $section): void {
?>
<ul class="list-group">
    <?php foreach ($fields as $f): ?>
        <li class="list-group-item d-flex justify-content-between align-items-center flex-wrap gap-2">
            <span>
                <?= htmlspecialchars($f['label']) ?>
                <span class="badge bg-light text-dark ms-1"><?= htmlspecialchars($f['field_type']) ?></span>
                <?php if (!$f['is_active']): ?>
                    <span class="badge bg-secondary ms-1">Désactivé</span>
                <?php endif; ?>
            </span>
            <div class="d-flex gap-1">
                <?php if ($f['is_active']): ?>
                    <form method="POST" action="/settings/custom-fields/<?= $f['id'] ?>/remove">
                        <?= Csrf::field() ?>
                        <button class="btn btn-sm btn-outline-warning" title="Masquer sans supprimer">Désactiver</button>
                    </form>
                <?php endif; ?>
                <form method="POST" action="/settings/custom-fields/<?= $f['id'] ?>/delete"
                      onsubmit="return confirm('Supprimer définitivement « <?= htmlspecialchars($f['label']) ?> » et toutes ses valeurs ?')">
                    <?= Csrf::field() ?>
                    <button class="btn btn-sm btn-outline-danger">Supprimer</button>
                </form>
            </div>
        </li>
    <?php endforeach; ?>
    <?php if (!$fields): ?>
        <li class="list-group-item text-muted">Aucun champ personnalisé.</li>
    <?php endif; ?>
</ul>
<?php
}
?>

<h1 class="h4 mb-3">Paramètres</h1>

<div class="row g-3">
    <!-- Ajout de champ personnalisé -->
    <div class="col-md-7">
        <div class="card card-body mb-3">
            <h2 class="h6">Ajouter un champ personnalisé</h2>
            <p class="text-muted small">
                Ajoutez un champ (Personne responsable, Température, Emplacement, N° lot interne…)
                à une entrée de stock ou à un produit, sans modification de code.
            </p>
            <form method="POST" action="/settings/custom-fields" class="row g-2">
                <?= Csrf::field() ?>
                <div class="col-md-4">
                    <select name="entity" class="form-select" required>
                        <option value="stock_entry">Entrée de stock</option>
                        <option value="product">Produit</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <input type="text" class="form-control" name="label" placeholder="Libellé (ex: Température)" required>
                </div>
                <div class="col-md-4">
                    <select name="field_type" class="form-select" required>
                        <option value="text">Texte</option>
                        <option value="number">Nombre</option>
                        <option value="date">Date</option>
                        <option value="select">Liste déroulante</option>
                        <option value="boolean">Oui / Non</option>
                    </select>
                </div>
                <div class="col-md-8">
                    <input type="text" class="form-control" name="options"
                           placeholder="Options pour liste déroulante, séparées par des virgules">
                </div>
                <div class="col-md-4 d-flex align-items-center gap-2">
                    <div class="form-check mb-0">
                        <input type="checkbox" class="form-check-input" name="is_required" value="1" id="cf-required">
                        <label class="form-check-label" for="cf-required">Obligatoire</label>
                    </div>
                    <button class="btn btn-primary">Ajouter</button>
                </div>
            </form>
        </div>

        <div class="card card-body mb-3">
            <h2 class="h6">Champs — Entrées de stock</h2>
            <?php renderCustomFieldList($stockEntryFields, 'stock_entry'); ?>
        </div>

        <div class="card card-body">
            <h2 class="h6">Champs — Produits</h2>
            <?php renderCustomFieldList($productFields, 'product'); ?>
        </div>
    </div>

    <!-- Sauvegardes -->
    <div class="col-md-5">
        <div class="card card-body">
            <h2 class="h6">Sauvegardes</h2>
            <form method="POST" action="/settings/backup" class="mb-3">
                <?= Csrf::field() ?>
                <button class="btn btn-success">Lancer une sauvegarde maintenant</button>
            </form>
            <ul class="list-group">
                <?php foreach ($backups as $b): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span>
                            <?= htmlspecialchars($b['name']) ?>
                            <span class="text-muted small">(<?= number_format($b['size'] / 1024, 0) ?> Ko)</span>
                        </span>
                        <a href="/settings/backup/<?= htmlspecialchars($b['name']) ?>"
                           class="btn btn-sm btn-outline-primary">Télécharger</a>
                    </li>
                <?php endforeach; ?>
                <?php if (!$backups): ?>
                    <li class="list-group-item text-muted">Aucune sauvegarde.</li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</div>
