<?php $title = 'Inventaire complet'; ?>
<h1>Inventaire complet — <?= date('d/m/Y') ?></h1>
<table>
    <thead><tr><th>Nom</th><th>Référence</th><th>Code-barres</th><th>Catégorie</th><th>Stock total</th><th>Unité</th></tr></thead>
    <tbody>
        <?php foreach ($products as $p): ?>
            <tr>
                <td><?= htmlspecialchars($p['name']) ?></td>
                <td><?= htmlspecialchars($p['reference'] ?? '') ?></td>
                <td><?= htmlspecialchars($p['barcode'] ?? '') ?></td>
                <td><?= htmlspecialchars($p['category_name'] ?? '') ?></td>
                <td><?= (int) $p['total_stock'] ?></td>
                <td><?= htmlspecialchars($p['unit']) ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
