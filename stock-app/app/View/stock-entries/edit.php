<?php

use App\Core\Csrf;

$title = 'Modifier l\'entrée de stock';
?>
<h1 class="h4 mb-3">Modifier une entrée de stock</h1>

<div class="alert alert-info small">
    <strong>Produit :</strong> <?= htmlspecialchars($product['name'] ?? '—') ?>
    &nbsp;|&nbsp;
    <strong>Quantité initiale :</strong> <?= (int) $entry['quantity'] ?>
    &nbsp;|&nbsp;
    <strong>Restant actuel :</strong> <?= (int) $entry['remaining_quantity'] ?>
</div>

<form method="POST" action="/stock-entries/<?= $entry['id'] ?>/edit" class="card card-body" style="max-width:640px;">
    <?= Csrf::field() ?>

    <div class="row">
        <div class="col-md-6 mb-3">
            <label class="form-label">Date d'entrée</label>
            <input type="date" class="form-control" name="entry_date"
                   value="<?= htmlspecialchars($entry['entry_date']) ?>">
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label">Quantité restante
                <span class="text-muted small">(correction de saisie uniquement)</span>
            </label>
            <input type="number" class="form-control" name="remaining_quantity"
                   min="0" value="<?= (int) $entry['remaining_quantity'] ?>">
        </div>
    </div>

    <div class="row">
        <div class="col-md-6 mb-3">
            <label class="form-label">DDM / DLC</label>
            <input type="date" class="form-control" name="expiry_date"
                   value="<?= htmlspecialchars($entry['expiry_date'] ?? '') ?>">
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label">Type d'échéance</label>
            <select class="form-select" name="expiry_type">
                <option value="">—</option>
                <option value="DDM" <?= ($entry['expiry_type'] ?? '') === 'DDM' ? 'selected' : '' ?>>DDM</option>
                <option value="DLC" <?= ($entry['expiry_type'] ?? '') === 'DLC' ? 'selected' : '' ?>>DLC</option>
            </select>
        </div>
    </div>

    <div class="mb-3">
        <label class="form-label">Origine</label>
        <input type="text" class="form-control" name="origin"
               value="<?= htmlspecialchars($entry['origin'] ?? '') ?>">
    </div>

    <div class="mb-3">
        <label class="form-label">Commentaire</label>
        <textarea class="form-control" name="comment" rows="2"><?= htmlspecialchars($entry['comment'] ?? '') ?></textarea>
    </div>

    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary">Enregistrer les modifications</button>
        <a href="/stock-entries" class="btn btn-outline-secondary">Annuler</a>
    </div>
</form>
