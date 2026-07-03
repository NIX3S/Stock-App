<?php

use App\Core\Csrf;

$title = 'Changement de mot de passe requis';
?>
<p class="text-muted small">Votre mot de passe a été réinitialisé par un administrateur. Vous devez en choisir un nouveau avant de continuer.</p>
<form method="POST" action="/password/change-required">
    <?= Csrf::field() ?>
    <div class="mb-3">
        <label class="form-label">Nouveau mot de passe</label>
        <input type="password" class="form-control" name="password" required minlength="10">
    </div>
    <button type="submit" class="btn btn-primary w-100">Valider</button>
</form>
