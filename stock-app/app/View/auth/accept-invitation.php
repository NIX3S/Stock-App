<?php

use App\Core\Csrf;

$title = 'Créer votre compte';
?>
<p class="text-muted small">Compte associé à : <strong><?= htmlspecialchars($email) ?></strong></p>
<form method="POST" action="/invitation/<?= htmlspecialchars($uuid) ?>">
    <?= Csrf::field() ?>
    <div class="row">
        <div class="col-6 mb-3">
            <label class="form-label">Prénom</label>
            <input type="text" class="form-control" name="first_name" required>
        </div>
        <div class="col-6 mb-3">
            <label class="form-label">Nom</label>
            <input type="text" class="form-control" name="last_name" required>
        </div>
    </div>
    <div class="mb-3">
        <label class="form-label">Mot de passe</label>
        <input type="password" class="form-control" name="password" required minlength="10">
        <div class="form-text">Au moins 10 caractères, avec majuscules, minuscules et chiffres.</div>
    </div>
    <button type="submit" class="btn btn-primary w-100">Créer mon compte</button>
</form>
