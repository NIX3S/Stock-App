<?php
$title = 'Entrées de stock';

$columns = [
    ['key' => 'product_name', 'label' => 'Produit', 'sortable' => true],
    ['key' => 'entry_date', 'label' => 'Date d\'entrée', 'sortable' => true],
    ['key' => 'expiry_date', 'label' => 'DDM/DLC', 'sortable' => true],
    ['key' => 'quantity', 'label' => 'Quantité initiale', 'sortable' => true],
    ['key' => 'remaining_quantity', 'label' => 'Restant', 'sortable' => true],
    ['key' => 'origin', 'label' => 'Origine', 'sortable' => false],
];
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 mb-0">Entrées de stock</h1>
    <a href="/stock-entries/create" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Nouvelle entrée</a>
</div>

<?php
echo \App\Core\Response::partial('components/datatable', [
    'tableKey' => 'stock_entries_list',
    'apiUrl' => '/api/stock-entries',
    'columns' => $columns,
]);
?>
