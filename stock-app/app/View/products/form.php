<?php

use App\Core\Csrf;

$isEdit = $product !== null;
$title  = $isEdit ? 'Modifier le produit' : 'Nouveau produit';
?>
<h1 class="h4 mb-3"><?= htmlspecialchars($title) ?></h1>

<form method="POST"
      action="<?= $isEdit ? '/products/' . $product['id'] : '/products' ?>"
      enctype="multipart/form-data"
      class="card card-body">
    <?= Csrf::field() ?>

    <div class="row">
        <!-- Colonne principale -->
        <div class="col-md-8">
            <div class="mb-3">
                <label class="form-label">Nom *</label>
                <input type="text" class="form-control" name="name"
                       value="<?= htmlspecialchars($product['name'] ?? '') ?>" required>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Référence</label>
                    <input type="text" class="form-control" name="reference"
                           value="<?= htmlspecialchars($product['reference'] ?? '') ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Code-barres
                        <span class="text-muted small">(vide = code interne auto-généré)</span>
                    </label>
                    <div class="input-group">
                        <input type="text" class="form-control" name="barcode" id="barcode-input"
                               value="<?= htmlspecialchars($product['barcode'] ?? '') ?>"
                               placeholder="Scanner ou saisir manuellement">
                        <button type="button" class="btn btn-outline-secondary" id="barcode-scan-btn">
                            📷 Scanner
                        </button>
                    </div>
                    <?php if (!empty($product['barcode_type'])): ?>
                        <div class="form-text">
                            Type actuel :
                            <span class="badge bg-<?= $product['barcode_type'] === 'internal' ? 'secondary' : 'info' ?>">
                                <?= $product['barcode_type'] === 'internal' ? 'Code interne' : 'Code fabricant' ?>
                            </span>
                        </div>
                    <?php endif; ?>

                    <!-- Fenêtre caméra inline -->
                    <div class="scanner-wrapper d-none mt-2">
                        <video id="barcode-video" class="w-100 rounded bg-dark"
                               autoplay muted playsinline style="max-height:200px;"></video>
                    </div>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Description</label>
                <textarea class="form-control" name="description" rows="3"><?= htmlspecialchars($product['description'] ?? '') ?></textarea>
            </div>

            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Unité</label>
                    <input type="text" class="form-control" name="unit"
                           value="<?= htmlspecialchars($product['unit'] ?? 'unité') ?>">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Seuil minimum</label>
                    <input type="number" class="form-control" name="min_stock_threshold"
                           value="<?= (int)($product['min_stock_threshold'] ?? 0) ?>" min="0">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Catégorie</label>
                    <?php
                    $categories = (new \App\Domain\Category\CategoryRepository())->allFlat();
                    ?>
                    <select name="category_id" class="form-select">
                        <option value="">— aucune —</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>"
                                <?= (isset($product['category_id']) && $product['category_id'] == $cat['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <!-- Photo -->
        <div class="col-md-4">
            <div class="mb-3">
                <label class="form-label">Photo</label>
                <input type="file" class="form-control" name="photo" accept="image/*">
                <?php if (!empty($product['photo_path'])): ?>
                    <img src="/<?= htmlspecialchars($product['photo_path']) ?>"
                         class="img-fluid mt-2 rounded" alt="">
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Champs personnalisés -->
    <?php if (!empty($customFields)): ?>
        <hr>
        <h2 class="h6">Champs personnalisés</h2>
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

    <div class="mt-3">
        <button type="submit" class="btn btn-primary">Enregistrer</button>
        <a href="/products" class="btn btn-outline-secondary">Annuler</a>
    </div>
</form>

<script type="module">
import { attachScanButton } from '/assets/js/modules/scanner.js';
attachScanButton('#barcode-input', '#barcode-scan-btn', '#barcode-video');
</script>
