<?php $title = 'Lien invalide'; ?>
<div class="text-center">
    <i class="bi bi-exclamation-triangle-fill fs-1 text-warning"></i>
    <p class="mt-3"><?= htmlspecialchars($message ?? 'Ce lien d\'invitation est invalide, expiré ou a déjà été utilisé.') ?></p>
    <a href="/login" class="btn btn-primary">Retour à la connexion</a>
</div>
