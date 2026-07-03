<?php

use App\Core\Csrf;

$title = $product['name'];
?>
<div class="d-flex justify-content-between align-items-start mb-3">
    <div>
        <h1 class="h4 mb-1"><?= htmlspecialchars($product['name']) ?></h1>
        <span class="badge bg-<?= $product['status'] === 'active' ? 'success' : 'secondary' ?>"><?= htmlspecialchars($product['status']) ?></span>
        <span class="badge bg-light text-dark"><?= htmlspecialchars($product['barcode_type'] === 'internal' ? 'Code interne' : 'Code fabricant') ?></span>
    </div>
    <div>
        <a href="/products/<?= $product['id'] ?>/edit" class="btn btn-outline-primary btn-sm">Modifier</a>
        <a href="/stock-entries/create?product_id=<?= $product['id'] ?>" class="btn btn-success btn-sm">Entrée de stock</a>
        <?php if ($product['status'] === 'active'): ?>
            <form method="POST" action="/products/<?= $product['id'] ?>/archive" class="d-inline">
                <?= Csrf::field() ?>
                <button class="btn btn-outline-secondary btn-sm" onclick="return confirm('Archiver ce produit ?')">Archiver</button>
            </form>
        <?php else: ?>
            <form method="POST" action="/products/<?= $product['id'] ?>/reactivate" class="d-inline">
                <?= Csrf::field() ?>
                <button class="btn btn-outline-success btn-sm">Réactiver</button>
            </form>
        <?php endif; ?>
    </div>
</div>

<div class="row g-3">
    <div class="col-md-4">
        <?php if (!empty($product['photo_path'])): ?>
            <img src="/<?= htmlspecialchars($product['photo_path']) ?>" class="img-fluid rounded mb-3" alt="">
        <?php endif; ?>
        <table class="table table-sm">
            <tr><th>Référence</th><td><?= htmlspecialchars($product['reference'] ?? '—') ?></td></tr>
            <tr><th>Code-barres</th><td><?= htmlspecialchars($product['barcode'] ?? '—') ?></td></tr>
            <tr><th>Catégorie</th><td><?= htmlspecialchars($product['category_name'] ?? '—') ?></td></tr>
            <tr><th>Unité</th><td><?= htmlspecialchars($product['unit']) ?></td></tr>
            <tr><th>Stock total</th><td><strong><?= (int) $product['total_stock'] ?></strong></td></tr>
            <tr><th>Seuil minimum</th><td><?= (int) $product['min_stock_threshold'] ?></td></tr>
            <?php foreach ($customFieldValues as $cf): ?>
                <tr><th><?= htmlspecialchars($cf['label']) ?></th><td><?= htmlspecialchars($cf['value_text'] ?? $cf['value_number'] ?? $cf['value_date'] ?? '—') ?></td></tr>
            <?php endforeach; ?>
        </table>
        <?php if (!empty($product['description'])): ?>
            <p class="text-muted"><?= nl2br(htmlspecialchars($product['description'])) ?></p>
        <?php endif; ?>
    </div>
    <div class="col-md-8">
        <h2 class="h6">Entrées de stock disponibles</h2>
        <table class="table table-sm table-striped">
            <thead><tr><th>Date d'entrée</th><th>Échéance</th><th>Restant</th><th>Origine</th></tr></thead>
            <tbody>
                <?php foreach ($stockEntries as $entry): ?>
                    <tr>
                        <td><?= htmlspecialchars($entry['entry_date']) ?></td>
                        <td><?= htmlspecialchars($entry['expiry_date'] ?? '—') ?> <?= $entry['expiry_type'] ? '(' . htmlspecialchars($entry['expiry_type']) . ')' : '' ?></td>
                        <td><?= (int) $entry['remaining_quantity'] ?></td>
                        <td><?= htmlspecialchars($entry['origin'] ?? '—') ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$stockEntries): ?>
                    <tr><td colspan="4" class="text-muted text-center">Aucune entrée disponible.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
