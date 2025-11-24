<!doctype html>
<html lang="ca">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Comprar Tokens - CarSharing</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style_global.css">
</head>
<body class="bg-light">
    <?php require_once 'includes/header.php'; ?>
    <main class="container py-5">
        <div class="row justify-content-center">
            <div class="col-12 col-md-6">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h3 class="card-title mb-3">Comprar Tokens</h3>
                        <p class="text-muted">Tria la quantitat de tokens que vols afegir al teu compte (simulació).</p>
                        
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    <?php foreach ($errors as $e) echo '<li>' . htmlspecialchars($e) . '</li>'; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <form method="post" action="index.php?action=comprar_tokens">
                            <div class="mb-3">
                                <label class="form-label">Quantitat de tokens</label>
                                <input type="number" name="amount" class="form-control" min="1" value="10" required>
                            </div>
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-success"><i class="bi bi-cart-plus me-2"></i>Comprar (simulat)</button>
                                <a href="index.php?action=perfil" class="btn btn-outline-secondary">Cancel·lar</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>
    <?php require_once 'includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>