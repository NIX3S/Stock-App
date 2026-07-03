<?php $title = 'Tableau de bord'; ?>

<!-- KPIs -->
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="card text-bg-primary h-100">
            <div class="card-body">
                <div class="text-uppercase small fw-semibold opacity-75">Produits actifs</div>
                <div class="display-6 fw-bold"><?= (int) $productCount ?></div>
                <a href="/products" class="stretched-link text-white text-decoration-none"></a>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card text-bg-success h-100">
            <div class="card-body">
                <div class="text-uppercase small fw-semibold opacity-75">Quantité totale en stock</div>
                <div class="display-6 fw-bold"><?= (int) $totalStock ?></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <a href="/products?low_stock=1" class="text-decoration-none">
            <div class="card text-bg-warning h-100">
                <div class="card-body">
                    <div class="text-uppercase small fw-semibold opacity-75">Sous le stock minimum</div>
                    <div class="display-6 fw-bold"><?= count($belowMinimum) ?></div>
                </div>
            </div>
        </a>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card text-bg-danger h-100">
            <div class="card-body">
                <div class="text-uppercase small fw-semibold opacity-75">DDM/DLC ≤ 7 jours</div>
                <div class="display-6 fw-bold"><?= count($expiringSoon) ?></div>
            </div>
        </div>
    </div>
</div>

<!-- Tableau DDM/DLC proches + Graphiques -->
<div class="row g-3 mb-4">
    <div class="col-12 col-xl-7">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-exclamation-triangle-fill text-danger me-1"></i> DDM / DLC proches (≤ 7 jours)</span>
                <a href="/print/expiring" target="_blank" class="btn btn-sm btn-outline-secondary">🖨️ Imprimer</a>
            </div>
            <?php if ($expiringSoon): ?>
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Produit</th>
                                <th>Type</th>
                                <th>Date</th>
                                <th class="text-center">J restants</th>
                                <th class="text-end">Restant</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($expiringSoon as $e):
                                $days = (int) ceil((strtotime($e['expiry_date']) - time()) / 86400);
                                $cls  = $days < 0 ? 'danger' : ($days <= 3 ? 'warning' : 'secondary');
                            ?>
                                <tr>
                                    <td>
                                        <a href="/products/<?= $e['product_id'] ?>" class="text-decoration-none fw-semibold">
                                            <?= htmlspecialchars($e['product_name']) ?>
                                        </a>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border"><?= htmlspecialchars($e['expiry_type'] ?? '—') ?></span>
                                    </td>
                                    <td><?= htmlspecialchars($e['expiry_date']) ?></td>
                                    <td class="text-center">
                                        <span class="badge bg-<?= $cls ?>">
                                            <?= $days < 0 ? 'Périmé' : ($days === 0 ? "Aujourd'hui" : $days . 'j') ?>
                                        </span>
                                    </td>
                                    <td class="text-end"><?= (int) $e['remaining_quantity'] ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="card-body text-muted">
                    <i class="bi bi-check-circle text-success"></i> Aucun produit n'expire dans les 7 prochains jours.
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="col-12 col-xl-5">
        <div class="card h-100">
            <div class="card-header">Répartition du stock par catégorie</div>
            <div class="card-body d-flex align-items-center justify-content-center">
                <canvas id="stockChart" style="max-height:220px;"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Dernières entrées / sorties / connexions -->
<div class="row g-3">
    <div class="col-12 col-lg-4">
        <div class="card h-100">
            <div class="card-header">Dernières entrées</div>
            <ul class="list-group list-group-flush">
                <?php foreach ($recentEntries as $m): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span class="text-truncate me-2"><?= htmlspecialchars($m['product_name']) ?></span>
                        <span class="badge bg-success flex-shrink-0">+<?= (int) $m['quantity'] ?></span>
                    </li>
                <?php endforeach; ?>
                <?php if (!$recentEntries): ?>
                    <li class="list-group-item text-muted">Aucune entrée récente.</li>
                <?php endif; ?>
            </ul>
        </div>
    </div>

    <div class="col-12 col-lg-4">
        <div class="card h-100">
            <div class="card-header">Dernières sorties</div>
            <ul class="list-group list-group-flush">
                <?php foreach ($recentExits as $m): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span class="text-truncate me-2"><?= htmlspecialchars($m['product_name']) ?></span>
                        <span class="badge bg-danger flex-shrink-0">−<?= (int) $m['quantity'] ?></span>
                    </li>
                <?php endforeach; ?>
                <?php if (!$recentExits): ?>
                    <li class="list-group-item text-muted">Aucune sortie récente.</li>
                <?php endif; ?>
            </ul>
        </div>
    </div>

    <div class="col-12 col-lg-4">
        <div class="card h-100">
            <div class="card-header">Dernières connexions</div>
            <ul class="list-group list-group-flush">
                <?php foreach ($recentLogins as $log): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span class="text-truncate me-2">
                            <?= htmlspecialchars(trim(($log['first_name'] ?? '') . ' ' . ($log['last_name'] ?? '')) ?: 'Système') ?>
                        </span>
                        <span class="text-muted small flex-shrink-0"><?= htmlspecialchars(substr($log['created_at'], 0, 16)) ?></span>
                    </li>
                <?php endforeach; ?>
                <?php if (!$recentLogins): ?>
                    <li class="list-group-item text-muted">Aucune activité récente.</li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script type="module" src="/assets/js/modules/dashboard-charts.js"></script>
