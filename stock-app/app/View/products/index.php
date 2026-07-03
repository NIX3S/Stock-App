<?php
$title = 'Produits';

$columns = [
    ['key' => 'name', 'label' => 'Nom', 'sortable' => true],
    ['key' => 'reference', 'label' => 'Référence', 'sortable' => true],
    ['key' => 'category_name', 'label' => 'Catégorie', 'sortable' => true],
    ['key' => 'unit', 'label' => 'Unité', 'sortable' => false],
    ['key' => 'total_stock', 'label' => 'Stock total', 'sortable' => true],
    ['key' => 'min_stock_threshold', 'label' => 'Seuil min.', 'sortable' => false],
    ['key' => 'status', 'label' => 'Statut', 'sortable' => true],
];
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 mb-0">Produits</h1>
    <a href="/products/create" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Nouveau produit</a>
</div>

<?php
echo \App\Core\Response::partial('components/datatable', [
    'tableKey' => 'products_list',
    'apiUrl' => '/api/products',
    'columns' => $columns,
]);
?>
