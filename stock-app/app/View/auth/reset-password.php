<?php

use App\Core\Csrf;

$title = 'Nouveau mot de passe';
?>
<form method="POST" action="/password/reset/<?= htmlspecialchars($token) ?>">
    <?= Csrf::field() ?>
    <div class="mb-3">
        <label class="form-label">Nouveau mot de passe</label>
        <input type="password" class="form-control" name="password" required minlength="10">
        <div class="form-text">Au moins 10 caractères, avec majuscules, minuscules et chiffres.</div>
    </div>
    <button type="submit" class="btn btn-primary w-100">Réinitialiser</button>
</form>
