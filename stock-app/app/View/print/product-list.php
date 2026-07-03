<?php $title = 'Liste des produits'; ?>
<h1>Liste des produits — <?= date('d/m/Y') ?></h1>
<table>
    <thead><tr><th>Nom</th><th>Référence</th><th>Catégorie</th><th>Statut</th></tr></thead>
    <tbody>
        <?php foreach ($products as $p): ?>
            <tr>
                <td><?= htmlspecialchars($p['name']) ?></td>
                <td><?= htmlspecialchars($p['reference'] ?? '') ?></td>
                <td><?= htmlspecialchars($p['category_name'] ?? '') ?></td>
                <td><?= htmlspecialchars($p['status']) ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
