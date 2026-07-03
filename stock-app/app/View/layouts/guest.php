<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'Gestion de Stock') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/assets/css/app.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container">
    <div class="row justify-content-center align-items-center" style="min-height: 100vh;">
        <div class="col-12 col-md-6 col-lg-4">
            <div class="text-center mb-4">
                <i class="bi bi-box-seam-fill fs-1"></i>
                <h1 class="h4 mt-2">Gestion de Stock</h1>
            </div>
            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <?= $content ?>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
