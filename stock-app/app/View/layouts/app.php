<?php

use App\Core\Session;

$userPermissionsHelperLoaded = true;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'Gestion de Stock') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="/assets/css/app.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top">
    <div class="container-fluid">
        <a class="navbar-brand" href="/dashboard"><i class="bi bi-box-seam-fill"></i> Gestion de Stock</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item"><a class="nav-link" href="/dashboard">Tableau de bord</a></li>
                <li class="nav-item"><a class="nav-link" href="/products">Produits</a></li>
                <li class="nav-item"><a class="nav-link" href="/categories">Catégories</a></li>
                <li class="nav-item"><a class="nav-link" href="/stock-entries">Entrées</a></li>
                <li class="nav-item"><a class="nav-link" href="/stock-exits">Sorties / Scanner</a></li>
                <li class="nav-item"><a class="nav-link" href="/print">Impressions</a></li>
                <li class="nav-item"><a class="nav-link" href="/users">Utilisateurs</a></li>
                <li class="nav-item"><a class="nav-link" href="/roles">Rôles</a></li>
                <li class="nav-item"><a class="nav-link" href="/logs">Journal</a></li>
                <li class="nav-item"><a class="nav-link" href="/settings">Paramètres</a></li>
            </ul>
            <span class="navbar-text text-light me-3"><?= htmlspecialchars(Session::get('full_name', '')) ?></span>
            <a href="/logout" class="btn btn-outline-light btn-sm">Déconnexion</a>
        </div>
    </div>
</nav>

<main class="container-fluid py-4">
    <?php if ($success = Session::getFlash('success')): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($success) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if ($error = Session::getFlash('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?= $content ?>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script type="module" src="/assets/js/app.js"></script>
</body>
</html>
