<?php
/**
 * Composant de tableau générique, piloté par modules/datatable.js.
 * Variables attendues :
 * - $tableKey (string) : clé unique servant aux préférences utilisateur et à l'endpoint API
 * - $apiUrl (string) : endpoint JSON server-side processing
 * - $columns (array) : [['key' => 'name', 'label' => 'Nom', 'sortable' => true], ...]
 * - $rowActionsView (string|null) : vue partielle optionnelle pour les actions par ligne
 */
?>
<div class="datatable-component" data-table-key="<?= htmlspecialchars($tableKey) ?>" data-api-url="<?= htmlspecialchars($apiUrl) ?>">
    <div class="d-flex flex-wrap gap-2 mb-3 align-items-center">
        <input type="search" class="form-control dt-search" placeholder="Recherche instantanée..." style="max-width: 280px;">
        <div class="dropdown ms-auto">
            <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                <i class="bi bi-eye"></i> Colonnes
            </button>
            <div class="dropdown-menu p-2 dt-column-toggle" style="min-width: 220px;"></div>
        </div>
        <button class="btn btn-outline-secondary btn-sm dt-export-csv"><i class="bi bi-filetype-csv"></i> CSV</button>
        <button class="btn btn-outline-secondary btn-sm dt-export-xlsx"><i class="bi bi-file-earmark-excel"></i> Excel</button>
        <button class="btn btn-outline-secondary btn-sm dt-print"><i class="bi bi-printer"></i> Imprimer</button>
    </div>

    <div class="table-responsive">
        <table class="table table-hover table-striped align-middle dt-table">
            <thead><tr class="dt-header-row"></tr></thead>
            <tbody class="dt-body">
                <tr><td class="text-center text-muted py-4">Chargement...</td></tr>
            </tbody>
        </table>
    </div>

    <div class="d-flex justify-content-between align-items-center">
        <div class="text-muted small dt-summary"></div>
        <nav><ul class="pagination pagination-sm dt-pagination mb-0"></ul></nav>
    </div>

    <script type="application/json" class="dt-columns-config"><?= json_encode($columns, JSON_UNESCAPED_UNICODE) ?></script>
</div>
