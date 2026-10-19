<?php

use App\Core\Csrf;

$title = 'Modifier l\'entrée de stock';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 mb-0">Modifier une entrée de stock</h1>
    <a href="/stock-entries" class="btn btn-outline-secondary btn-sm">← Retour</a>
</div>

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

    <!-- Alerte doublon (affichée dynamiquement à la modification) -->
    <div id="duplicate-alert" class="alert alert-warning d-none mt-3">
        <strong>⚠️ Doublon potentiel détecté</strong><br>
        <span id="duplicate-info"></span><br>
        <div class="mt-2 d-flex gap-2 flex-wrap">
            <button type="button" id="btn-merge" class="btn btn-warning btn-sm">
                ➕ Fusionner avec l'entrée existante
            </button>
            <button type="button" id="btn-ignore-duplicate" class="btn btn-outline-secondary btn-sm">
                Continuer quand même la modification
            </button>
        </div>
    </div>

    <div class="d-flex gap-2 mt-3">
        <button type="submit" id="btn-save" class="btn btn-primary">Enregistrer les modifications</button>
        <a href="/stock-entries" class="btn btn-outline-secondary">Annuler</a>
    </div>
</form>

<!-- Formulaire de fusion (hors du formulaire principal) -->
<form id="merge-form-edit" method="POST" action="" class="d-none">
    <?= \App\Core\Csrf::field() ?>
    <input type="hidden" name="quantity" id="merge-qty">
    <input type="hidden" name="action" id="merge-action" value="save">
    <input type="hidden" name="source_id" value="<?= (int) $entry['id'] ?>">
</form>

<script>
(function () {
    const PRODUCT_ID  = <?= (int) $entry['product_id'] ?>;
    const mergeForm   = document.getElementById('merge-form-edit');
    const EXCLUDE_ID  = <?= (int) $entry['id'] ?>;
    const CSRF_TOKEN  = document.querySelector('meta[name="csrf-token"]')?.content || '';

    let duplicateWarning  = false;
    let duplicateTargetId = null;
    let checkTimeout      = null;

    async function checkDuplicate() {
        const expiryDate = document.querySelector('[name="expiry_date"]').value;
        const origin     = document.querySelector('[name="origin"]').value.trim();

        const params = new URLSearchParams({ product_id: PRODUCT_ID, exclude_id: EXCLUDE_ID });
        if (expiryDate) params.set('expiry_date', expiryDate);
        if (origin)     params.set('origin', origin);

        try {
            const res  = await fetch('/api/stock-entries/check-duplicate?' + params);
            const data = await res.json();
            const alertEl = document.getElementById('duplicate-alert');

            if (data.duplicate) {
                duplicateWarning  = true;
                duplicateTargetId = data.entry_id;
                const expLabel = data.expiry_date ? ' — Expiration : ' + data.expiry_date : '';
                document.getElementById('duplicate-info').innerHTML =
                    'Une autre entrée existe déjà pour <strong>' + data.product_name + '</strong>' +
                    expLabel + ', entrée du ' + data.entry_date +
                    (data.origin ? ' — Origine : ' + data.origin : '') +
                    ' (Stock : <strong>' + data.current_quantity + '</strong> unités, ID #' + data.entry_id + ').<br>' +
                    'Modifier cette entrée créerait un doublon.';
                alertEl.classList.remove('d-none');
            } else {
                duplicateWarning  = false;
                duplicateTargetId = null;
                alertEl.classList.add('d-none');
            }
        } catch (e) { /* silencieux */ }
    }

    ['expiry_date', 'origin'].forEach(name => {
        document.querySelector('[name="' + name + '"]').addEventListener('change', () => {
            clearTimeout(checkTimeout);
            checkTimeout = setTimeout(checkDuplicate, 400);
        });
    });

    document.getElementById('btn-ignore-duplicate').addEventListener('click', () => {
        duplicateWarning = false;
        document.getElementById('duplicate-alert').classList.add('d-none');
    });

    document.getElementById('btn-merge').addEventListener('click', () => {
        if (!duplicateTargetId) return;
        // Lire la valeur actuelle du champ (l'utilisateur a pu la modifier)
        const remaining = parseInt(document.querySelector('[name="remaining_quantity"]').value, 10) || 0;
        const msg = remaining > 0
            ? `Fusionner les ${remaining} unités restantes de cette entrée dans l'entrée #${duplicateTargetId} ?\n\nCette entrée (#${EXCLUDE_ID}) sera supprimée.`
            : `Cette entrée n'a plus de stock restant (0 unités).\nFusionner quand même (cela n'ajoutera rien au stock de l'entrée #${duplicateTargetId}) ?`;
        if (!confirm(msg)) return;
        mergeForm.action = `/stock-entries/${duplicateTargetId}/merge`;
        document.getElementById('merge-qty').value    = remaining;
        document.getElementById('merge-action').value = 'save';
        mergeForm.submit();
    });
})();
</script>
