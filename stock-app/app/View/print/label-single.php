<?php $title = 'Étiquette — ' . htmlspecialchars($product['name']); ?>
<div class="no-print alert alert-success mb-3">
    <strong>Entrée enregistrée.</strong>
    Imprimez l'étiquette ci-dessous, puis
    <a href="/stock-entries/create">nouvelle entrée</a> ou
    <a href="/products/<?= $product['id'] ?>">retour à la fiche produit</a>.
</div>

<?= $labelsHtml ?>
