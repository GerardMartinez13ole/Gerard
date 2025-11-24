<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Registrarse - CarSharing</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style_global.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/style_login_register.css">
</head>
<body>
    <main class="auth-page d-flex align-items-center justify-content-center">
        <div class="auth-card shadow-sm">
            <div class="text-center mb-4">
                <div class="brand-circle mb-3">CS</div>
                <h4 class="mb-0">Crear compte</h4>
                <small class="text-muted">Registra't a CarSharing</small>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $e) echo '<li>' . htmlspecialchars($e) . '</li>'; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="post" action="index.php?action=register" class="auth-form">
                <div class="mb-3">
                    <label class="form-label small">Nom complet</label>
                    <input type="text" name="name" required class="form-control form-control-lg" value="<?= htmlspecialchars($name ?? '') ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label small">Email</label>
                    <input type="email" name="email" required class="form-control form-control-lg" value="<?= htmlspecialchars($email ?? '') ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label small">Contrasenya</label>
                    <input type="password" name="password" required class="form-control form-control-lg">
                </div>

                <div class="mb-3">
                    <label class="form-label small">Confirmar contrasenya</label>
                    <input type="password" name="password_confirm" required class="form-control form-control-lg">
                </div>

                <div class="d-flex justify-content-between align-items-center mb-0">
                    <a href="index.php?action=login" class="small">Ja tinc compte</a>
                    <button class="btn btn-primary btn-lg" type="submit"><i class="bi bi-person-plus me-2"></i>Crear cuenta</button>
                </div>
            </form>
        </div>
    </main>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>