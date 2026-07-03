<?php $title = 'Produits bientôt périmés'; ?>
<h1>Produits bientôt périmés (30 jours) — <?= date('d/m/Y') ?></h1>
<table>
    <thead><tr><th>Produit</th><th>Échéance</th><th>Type</th><th>Quantité restante</th></tr></thead>
    <tbody>
        <?php foreach ($entries as $e): ?>
            <tr>
                <td><?= htmlspecialchars($e['product_name']) ?></td>
                <td><?= htmlspecialchars($e['expiry_date']) ?></td>
                <td><?= htmlspecialchars($e['expiry_type'] ?? '') ?></td>
                <td><?= (int) $e['remaining_quantity'] ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
