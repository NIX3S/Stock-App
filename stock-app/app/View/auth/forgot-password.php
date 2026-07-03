<?php

use App\Core\Csrf;
use App\Core\Session;

$title  = 'Mot de passe oublié';
$config = \App\Core\Config::all();
$isDev  = ($config['app']['env'] ?? 'production') !== 'production';

$devLink = Session::getFlash('dev_reset_link');
?>

<?php if ($isDev): ?>
    <div class="alert alert-info small">
        <strong>Mode développement :</strong>
        les e-mails sont enregistrés dans <code>storage/logs/mail.log</code> uniquement.
    </div>
<?php endif; ?>

<?php if (!empty($message)): ?>
    <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>

<?php if ($devLink): ?>
    <div class="alert alert-warning">
        <strong>Lien de réinitialisation (sendmail non disponible) :</strong><br>
        <a href="<?= htmlspecialchars($devLink) ?>" class="alert-link text-break">
            <?= htmlspecialchars($devLink) ?>
        </a>
        <div class="mt-2 small text-muted">Cliquez sur ce lien pour choisir un nouveau mot de passe.</div>
    </div>
<?php endif; ?>

<form method="POST" action="/password/forgot">
    <?= Csrf::field() ?>
    <div class="mb-3">
        <label class="form-label">Adresse e-mail</label>
        <input type="email" class="form-control" name="email" required autofocus>
    </div>
    <button type="submit" class="btn btn-primary w-100">
        Envoyer le lien de réinitialisation
    </button>
    <div class="text-center mt-3">
        <a href="/login" class="small">Retour à la connexion</a>
    </div>
</form>
