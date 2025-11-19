<?php
session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/classes/Sql.php';

if (empty($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$config = require __DIR__ . '/config.php';
$sql = new Sql($config);

$user_email = strtolower(trim($_SESSION['user']['correu'] ?? ''));

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = (int)($_POST['amount'] ?? 0);
    if ($amount <= 0) $errors[] = 'Tria una quantitat vàlida.';
    if (empty($errors)) {
        try {
            $sql->execute("UPDATE usuaris SET tokens = tokens + ? WHERE LOWER(correu) = ?", [$amount, $user_email]);
            $row = $sql->fetch("SELECT tokens FROM usuaris WHERE LOWER(correu) = ?", [$user_email]);
            $_SESSION['user']['tokens'] = (int)($row['tokens'] ?? 0);
            $_SESSION['flash'] = 'Compra de tokens realitzada: +' . $amount . ' tokens';
            header('Location: profile.php');
            exit;
        } catch (Exception $e) {
            $errors[] = 'Error actualitzant tokens.';
        }
    }
}
?>
<!doctype html>
<html lang="ca">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Les meves reserves - CarSharing</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style_global.css">
</head>
<body class="bg-light">
    <?php require_once __DIR__ . '/includes/header.php'; ?>
    <main class="container py-5">
        <div class="row justify-content-center">
            <div class="col-12 col-md-6">
                <h3>Comprar Tokens</h3>
                <p class="text-muted">Tria la quantitat de tokens que vols afegir (simulat).</p>
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $e) echo '<li>' . htmlspecialchars($e) . '</li>'; ?></ul></div>
                <?php endif; ?>
                <form method="post" action="comprar_tokens.php">
                    <div class="mb-3">
                        <label class="form-label">Quantitat de tokens</label>
                        <input type="number" name="amount" class="form-control" min="1" value="10" required>
                    </div>
                    <div class="d-flex gap-2">
                        <button class="btn btn-success">Comprar (simulat)</button>
                        <a href="profile.php" class="btn btn-outline-secondary">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </main>
    <?php require_once __DIR__ . '/includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
