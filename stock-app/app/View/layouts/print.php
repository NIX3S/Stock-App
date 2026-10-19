<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($title ?? 'Impression') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/assets/css/print.css" rel="stylesheet">
</head>
<body>
<div class="no-print text-end p-2">
    <button class="btn btn-primary" onclick="window.print()">Imprimer</button>
    <button class="btn btn-outline-secondary" onclick="window.close()">✕ Fermer l'onglet</button>
</div>
<div class="print-content">
    <?= $content ?>
</div>
</body>
</html>
