<?php
session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/classes/Sql.php';

$config = require __DIR__ . '/config.php';
$sql = new Sql($config);

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $pass = $_POST['password'] ?? '';
    $pass2 = $_POST['password_confirm'] ?? '';

    if ($name === '' || $email === '' || $pass === '' || $pass2 === '') {
        $errors[] = 'Rellena todos los campos.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Email no válido.';
    } elseif ($pass !== $pass2) {
        $errors[] = 'Las contraseñas no coinciden.';
    } else {
        // comprobar si ja existeix
        $exists = $sql->getUserByEmail($email);
        if ($exists) {
            $errors[] = 'Ya existe una cuenta con ese email.';
        } else {
            $hash = password_hash($pass, PASSWORD_DEFAULT);
            $id = $sql->createUser($name, $email, $hash);
            if ($id) {
                header('Location: login.php?registered=1');
                exit;
            } else {
                $errors[] = 'Error al crear la cuenta.';
            }
        }
    }
}
?>
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

            <form method="post" action="register.php" class="auth-form">
                <div class="mb-3">
                    <label class="form-label small">Nom complet</label>
                    <input type="text" name="name" required class="form-control form-control-lg" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label small">Email</label>
                    <input type="email" name="email" required class="form-control form-control-lg" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
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
                    <a href="login.php" class="small">Ja tinc compte</a>
                    <button class="btn btn-primary btn-lg" type="submit"><i class="bi bi-person-plus me-2"></i>Crear cuenta</button>
                </div>
            </form>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>