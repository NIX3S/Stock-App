<?php

use App\Core\Csrf;
use App\Domain\Product\ProductRepository;

$title = 'Impressions';
$products = (new ProductRepository())->paginate(1, 1000, ['status' => 'active'], 'name', 'asc')['data'];

// Produit pré-sélectionné (après "Enregistrer + Imprimer" depuis le formulaire d'entrée)
$presetProductId = (int) ($_GET['preset_product'] ?? 0);
?>
<h1 class="h4 mb-3">Impressions</h1>

<?php if ($presetProductId): ?>
    <div class="alert alert-success">
        Entrée enregistrée. Configurez et imprimez l'étiquette ci-dessous, ou
        <a href="/stock-entries" class="alert-link">retournez aux entrées de stock</a>.
    </div>
<?php endif; ?>

<div class="row g-3">
    <div class="col-md-4">
        <div class="card card-body h-100">
            <h2 class="h6">Documents</h2>
            <a href="/print/inventory" target="_blank" class="btn btn-outline-primary w-100 mb-2">
                <i class="bi bi-list-ul"></i> Inventaire complet
            </a>
            <a href="/print/products" target="_blank" class="btn btn-outline-primary w-100 mb-2">
                <i class="bi bi-box"></i> Liste des produits
            </a>
            <a href="/print/expiring" target="_blank" class="btn btn-outline-primary w-100">
                <i class="bi bi-calendar-x"></i> Produits bientôt périmés
            </a>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card card-body">
            <h2 class="h6">Générateur d'étiquettes</h2>
            <form id="label-form" method="POST" action="/print/labels" target="_blank">
                <?= Csrf::field() ?>
                <div class="row mb-3">
                    <div class="col-4">
                        <label class="form-label">Copies par produit</label>
                        <input type="number" class="form-control" name="copies" value="1" min="1">
                    </div>
                    <div class="col-4">
                        <label class="form-label">Colonnes (A4)</label>
                        <input type="number" class="form-control" name="columns" value="3" min="1">
                    </div>
                    <div class="col-4">
                        <label class="form-label">Lignes (A4)</label>
                        <input type="number" class="form-control" name="rows" value="8" min="1">
                    </div>
                </div>

                <label class="form-label">Produits</label>
                <div class="input-group mb-2">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" id="product-filter" class="form-control"
                           placeholder="Filtrer la liste...">
                    <button type="button" id="select-all" class="btn btn-outline-secondary">Tout</button>
                    <button type="button" id="deselect-all" class="btn btn-outline-secondary">Aucun</button>
                </div>

                <div class="border rounded p-2 mb-2" style="max-height:260px; overflow-y:auto;"
                     id="product-list">
                    <?php foreach ($products as $p): ?>
                        <div class="form-check product-item">
                            <input class="form-check-input" type="checkbox"
                                   name="product_ids[]" value="<?= $p['id'] ?>"
                                   id="label-prod-<?= $p['id'] ?>"
                                   <?= ($presetProductId && $presetProductId === (int)$p['id']) ? 'checked' : '' ?>>
                            <label class="form-check-label d-flex justify-content-between"
                                   for="label-prod-<?= $p['id'] ?>">
                                <span><?= htmlspecialchars($p['name']) ?></span>
                                <span class="text-muted small"><?= htmlspecialchars($p['barcode'] ?? 'sans code') ?></span>
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>

                <p id="label-preview-count" class="text-muted small mb-2"></p>
                <button class="btn btn-primary">
                    <i class="bi bi-printer"></i> Générer les étiquettes
                </button>
            </form>
        </div>
    </div>
</div>

<script type="module">
import { initLabelForm } from '/assets/js/modules/label-printer.js';
initLabelForm();

// Filtre en temps réel dans la liste des produits
document.getElementById('product-filter').addEventListener('input', (e) => {
    const q = e.target.value.toLowerCase();
    document.querySelectorAll('.product-item').forEach(item => {
        item.style.display = item.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
});

document.getElementById('select-all').addEventListener('click', () =>
    document.querySelectorAll('#product-list input[type=checkbox]').forEach(c => c.checked = true)
);
document.getElementById('deselect-all').addEventListener('click', () =>
    document.querySelectorAll('#product-list input[type=checkbox]').forEach(c => c.checked = false)
);

// Scroll jusqu'au produit pré-sélectionné
const presetId = <?= $presetProductId ?: 'null' ?>;
if (presetId) {
    const el = document.getElementById(`label-prod-${presetId}`);
    el?.closest('.product-item')?.scrollIntoView({ block: 'center' });
}
</script>
