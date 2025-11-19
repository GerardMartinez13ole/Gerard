<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$user = $_SESSION['user'] ?? null;

// Si hi ha usuari a sessió, actualitzar el saldo de tokens des de la base de dades
if (!empty($user) && !empty($user['correu'])) {
    try {
        require_once __DIR__ . '/../config.php';
        require_once __DIR__ . '/../classes/Sql.php';
        $config = require __DIR__ . '/../config.php';
        $sql = new Sql($config);

        $email = strtolower(trim($user['correu']));
        $row = $sql->fetch("SELECT tokens FROM usuaris WHERE LOWER(correu) = ?", [$email]);
        if ($row !== false && isset($row['tokens'])) {
            $_SESSION['user']['tokens'] = (int)$row['tokens'];
            $user['tokens'] = $_SESSION['user']['tokens'];
        }
    } catch (Exception $e) {
        // no fer res: mantindrem el valor existent en sessió si hi ha error
    }
}
?>
<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm site-navbar">
    <div class="container-fluid">
        <a class="navbar-brand d-flex align-items-center" href="menu.php">
            <img src="img/Logo.png" alt="Logo" class="navbar-logo">
            <span class="fw-bold ms-2">CarSharing</span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-controls="mainNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item"><a class="nav-link" href="rutes_disponibles.php">Rutes Disponibles</a></li>
                <li class="nav-item"><a class="nav-link" href="afegir_ruta.php">Afegir Ruta</a></li>
                <li class="nav-item"><a class="nav-link" href="mis_rutes.php">Les Meves Rutes</a></li>
                <li class="nav-item"><a class="nav-link" href="chat_list.php">Converses</a></li>
                <li class="nav-item"><a class="nav-link" href="mapa.php">Mapa</a></li>
                <li class="nav-item"><a class="nav-link" href="my_reservations.php">Les Meves Reserves</a></li>
                <li class="nav-item"><a class="nav-link" href="rankings.php"><i class="bi bi-trophy-fill me-1"></i>Ranking</a></li>
            </ul>

            <div class="d-flex align-items-center">
                <?php if ($user): ?>
                    <span class="me-3 text-muted small">Tokens: <strong><?= htmlspecialchars((int)($user['tokens'] ?? 0)) ?></strong></span>
                    <a class="btn btn-sm btn-outline-success me-3" href="comprar_tokens.php">Comprar tokens</a>
                    <span class="me-3 text-muted">Hola, <strong><?= htmlspecialchars($user['nom'] ?? $user['correu']) ?></strong></span>
                    <a class="btn btn-profile btn-sm me-2" href="profile.php">Vore el teu perfil</a>
                    <a class="btn btn-outline-secondary btn-sm" href="tancar_sessio.php">Tancar Sessió</a>
                <?php else: ?>
                    <a class="btn btn-outline-primary btn-sm me-2" href="login.php">Iniciar Sessió</a>
                    <a class="btn btn-primary btn-sm" href="register.php">Crear Compte</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>
