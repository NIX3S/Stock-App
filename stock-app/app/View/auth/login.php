<?php

use App\Core\Csrf;

$title = 'Connexion';
?>
<form method="POST" action="/login">
    <?= Csrf::field() ?>
    <div class="mb-3">
        <label for="email" class="form-label">Adresse e-mail</label>
        <input type="email" class="form-control" id="email" name="email" required autofocus>
    </div>
    <div class="mb-3">
        <label for="password" class="form-label">Mot de passe</label>
        <input type="password" class="form-control" id="password" name="password" required>
    </div>
    <button type="submit" class="btn btn-primary w-100">Se connecter</button>
    <div class="text-center mt-3">
        <a href="/password/forgot" class="small">Mot de passe oublié ?</a>
    </div>
</form>
<p class="text-muted small text-center mt-4 mb-0">
    L'accès se fait uniquement par invitation d'un administrateur.
</p>
