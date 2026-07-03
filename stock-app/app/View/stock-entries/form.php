<?php

use App\Core\Csrf;

$title = 'Nouvelle entrée de stock';

// Chargement produit pré-sélectionné si paramètre URL
$presetProduct = null;
if (!empty($presetProductId)) {
    $presetProduct = (new \App\Domain\Product\ProductRepository())->findById((int) $presetProductId);
}
?>
<h1 class="h4 mb-3">Nouvelle entrée de stock</h1>
<meta name="csrf-token" content="<?= htmlspecialchars(\App\Core\Csrf::token()) ?>">

<div class="row g-3">
    <!-- Scanner / recherche produit -->
    <div class="col-lg-4">
        <div class="card card-body h-100">
            <h2 class="h6 mb-3">1. Identifier le produit</h2>

            <!-- Bouton scan caméra -->
            <button type="button" id="entry-scan-btn" class="btn btn-primary mb-2">
                📷 Scanner le code-barres
            </button>
            <div class="scanner-wrapper d-none mb-2">
                <video id="entry-video" class="w-100 rounded bg-dark"
                       autoplay muted playsinline style="max-height:220px;"></video>
            </div>

            <!-- Saisie manuelle -->
            <div class="input-group mb-3">
                <input type="text" id="manual-barcode" class="form-control"
                       placeholder="Saisie manuelle du code-barres">
                <button type="button" id="manual-lookup-btn" class="btn btn-outline-secondary">Chercher</button>
            </div>

            <!-- Résultat produit trouvé -->
            <div id="product-found" class="alert alert-success d-none">
                <strong id="found-name"></strong><br>
                <span class="small" id="found-meta"></span>
            </div>
            <div id="product-not-found" class="alert alert-danger d-none">
                Produit introuvable pour ce code-barres.
            </div>
        </div>
    </div>

    <!-- Formulaire d'entrée -->
    <div class="col-lg-8">
        <form method="POST" action="/stock-entries" id="entry-form" class="card card-body">
            <?= Csrf::field() ?>

            <!-- Champ caché : product_id (rempli par le scan ou l'URL) -->
            <input type="hidden" name="product_id" id="product-id-field"
                   value="<?= htmlspecialchars((string)($presetProductId ?? '')) ?>">

            <?php if ($presetProduct): ?>
                <div class="alert alert-info mb-3">
                    Produit : <strong><?= htmlspecialchars($presetProduct['name']) ?></strong>
                    (<?= htmlspecialchars($presetProduct['barcode'] ?? '—') ?>)
                </div>
            <?php endif; ?>

            <h2 class="h6 mb-3">2. Détails de l'entrée</h2>

            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Quantité *</label>
                    <input type="number" class="form-control" name="quantity" id="qty-field"
                           min="1" value="1" required>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Date d'entrée *</label>
                    <input type="date" class="form-control" name="entry_date"
                           value="<?= date('Y-m-d') ?>" required>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Origine</label>
                    <input type="text" class="form-control" name="origin"
                           placeholder="Don, achat, collecte…">
                </div>
            </div>

            <div class="row">
                <div class="col-md-5 mb-3">
                    <label class="form-label">DDM / DLC
                        <span class="text-muted small">(plusieurs entrées du même produit peuvent avoir des dates différentes)</span>
                    </label>
                    <input type="date" class="form-control" name="expiry_date">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Type</label>
                    <select class="form-select" name="expiry_type">
                        <option value="">—</option>
                        <option value="DDM">DDM</option>
                        <option value="DLC">DLC</option>
                    </select>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Commentaire</label>
                <textarea class="form-control" name="comment" rows="2"></textarea>
            </div>

            <!-- Champs personnalisés dynamiques -->
            <?php if (!empty($customFields)): ?>
                <hr>
                <h2 class="h6">Champs personnalisés
                    <a href="/settings" class="ms-2 small text-muted">(configurer)</a>
                </h2>
                <div class="row">
                    <?php foreach ($customFields as $field): ?>
                        <div class="col-md-4 mb-3">
                            <label class="form-label"><?= htmlspecialchars($field->label) ?></label>
                            <?php if ($field->fieldType === 'select'): ?>
                                <select class="form-select"
                                        name="custom_fields[<?= htmlspecialchars($field->fieldKey) ?>]">
                                    <?php foreach ($field->options ?? [] as $opt): ?>
                                        <option value="<?= htmlspecialchars($opt) ?>"><?= htmlspecialchars($opt) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            <?php elseif ($field->fieldType === 'boolean'): ?>
                                <select class="form-select"
                                        name="custom_fields[<?= htmlspecialchars($field->fieldKey) ?>]">
                                    <option value="1">Oui</option>
                                    <option value="0">Non</option>
                                </select>
                            <?php else: ?>
                                <input type="<?= $field->fieldType === 'number' ? 'number' : ($field->fieldType === 'date' ? 'date' : 'text') ?>"
                                       class="form-control"
                                       name="custom_fields[<?= htmlspecialchars($field->fieldKey) ?>]"
                                       <?= $field->isRequired ? 'required' : '' ?>>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="d-flex gap-2 mt-3">
                <button type="submit" name="action" value="save" class="btn btn-primary">
                    Enregistrer
                </button>
                <button type="submit" name="action" value="save_and_print" class="btn btn-success">
                    🖨️ Enregistrer et imprimer l'étiquette
                </button>
                <a href="/stock-entries" class="btn btn-outline-secondary">Annuler</a>
            </div>
        </form>
    </div>
</div>

<script type="module">
import { BarcodeScanner, attachScanButton } from '/assets/js/modules/scanner.js';

// ---------- Scanner dédié pour l'entrée de stock ----------
let entryScanner = null;
const scanBtn    = document.getElementById('entry-scan-btn');
const videoEl    = document.getElementById('entry-video');
const wrapperEl  = videoEl.closest('.scanner-wrapper');

async function lookupBarcode(code) {
    const res = await fetch(`/scanner/lookup?barcode=${encodeURIComponent(code)}`);
    if (!res.ok) {
        document.getElementById('product-found').classList.add('d-none');
        document.getElementById('product-not-found').classList.remove('d-none');
        return;
    }
    const data = await res.json();
    document.getElementById('product-id-field').value = data.product.id;
    document.getElementById('found-name').textContent  = data.product.name;
    document.getElementById('found-meta').textContent  =
        `Réf: ${data.product.reference ?? '—'}  |  Code: ${data.product.barcode}`;
    document.getElementById('product-found').classList.remove('d-none');
    document.getElementById('product-not-found').classList.add('d-none');
    // Focus sur la quantité pour une saisie rapide
    document.getElementById('qty-field').focus();
}

scanBtn.addEventListener('click', async () => {
    if (entryScanner) {
        entryScanner.stop();
        entryScanner = null;
        wrapperEl.classList.add('d-none');
        scanBtn.textContent = '📷 Scanner le code-barres';
        return;
    }
    wrapperEl.classList.remove('d-none');
    scanBtn.textContent = '⏹ Arrêter le scan';
    entryScanner = new BarcodeScanner(videoEl, async (code) => {
        entryScanner.stop();
        entryScanner = null;
        wrapperEl.classList.add('d-none');
        scanBtn.textContent = '📷 Scanner le code-barres';
        await lookupBarcode(code);
    });
    try {
        await entryScanner.start();
    } catch (e) {
        alert(e.message);
        entryScanner = null;
        wrapperEl.classList.add('d-none');
        scanBtn.textContent = '📷 Scanner le code-barres';
    }
});

document.getElementById('manual-lookup-btn').addEventListener('click', () => {
    const code = document.getElementById('manual-barcode').value.trim();
    if (code) lookupBarcode(code);
});

document.getElementById('manual-barcode').addEventListener('keypress', (e) => {
    if (e.key === 'Enter') {
        e.preventDefault();
        document.getElementById('manual-lookup-btn').click();
    }
});
</script>
