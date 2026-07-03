<?php $title = 'Journal d\'audit'; ?>
<h1 class="h4 mb-3">Journal d'audit</h1>
<p class="text-muted small">Ce journal est en lecture seule : aucune entrée ne peut être modifiée ou supprimée.</p>

<div class="table-responsive">
    <table class="table table-striped table-sm align-middle">
        <thead><tr><th>Date</th><th>Utilisateur</th><th>Action</th><th>Entité</th><th>IP</th></tr></thead>
        <tbody>
            <?php foreach ($logs as $log): ?>
                <tr>
                    <td><?= htmlspecialchars($log['created_at']) ?></td>
                    <td><?= htmlspecialchars(trim(($log['first_name'] ?? '') . ' ' . ($log['last_name'] ?? '')) ?: 'Système') ?></td>
                    <td><code><?= htmlspecialchars($log['action_code']) ?></code></td>
                    <td><?= htmlspecialchars(trim(($log['entity_type'] ?? '') . ' #' . ($log['entity_id'] ?? ''))) ?></td>
                    <td><?= htmlspecialchars($log['ip_address'] ?? '—') ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$logs): ?><tr><td colspan="5" class="text-muted text-center">Aucune entrée.</td></tr><?php endif; ?>
        </tbody>
    </table>
</div>

<nav>
    <ul class="pagination">
        <?php for ($p = 1; $p <= $totalPages; $p++): ?>
            <li class="page-item <?= $p === $page ? 'active' : '' ?>"><a class="page-link" href="/logs?page=<?= $p ?>"><?= $p ?></a></li>
        <?php endfor; ?>
    </ul>
</nav>
