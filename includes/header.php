<?php
// includes/header.php

// Comprovar sessió de forma segura (per si ja està iniciada a l'index)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$user = $_SESSION['user'] ?? null;

// Lògica per refrescar tokens (es manté igual, només assegurant rutes)
if (!empty($user) && !empty($user['correu'])) {
    try {
        // Utilitzem __DIR__ per assegurar que troba els fitxers independentment d'on s'inclogui
        require_once __DIR__ . '/../config.php';
        require_once __DIR__ . '/../classes/Sql.php';
        
        $config = require __DIR__ . '/../config.php';
        $sql = new Sql($config);

        $email = strtolower(trim($user['correu']));
        $row = $sql->fetch("SELECT tokens FROM usuaris WHERE LOWER(correu) = ?", [$email]);
        if ($row !== false && isset($row['tokens'])) {
            $_SESSION['user']['tokens'] = (int)$row['tokens'];
            $user['tokens'] = $_SESSION['user']['tokens']; // Actualitzem la variable local també
        }
    } catch (Exception $e) {
        // Silenci en cas d'error per no trencar la vista
    }
}
?>
<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm site-navbar">
    <div class="container-fluid">
        <a class="navbar-brand d-flex align-items-center" href="index.php?action=menu">
            <img src="img/Logo.png" alt="Logo" class="navbar-logo">
            <span class="fw-bold ms-2">CarSharing</span>
        </a>
        
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-controls="mainNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item"><a class="nav-link" href="index.php?action=rutes_disponibles">Rutes Disponibles</a></li>
                <li class="nav-item"><a class="nav-link" href="index.php?action=afegir_ruta">Afegir Ruta</a></li>
                <li class="nav-item"><a class="nav-link" href="index.php?action=mis_rutes">Les Meves Rutes</a></li>
                <li class="nav-item"><a class="nav-link" href="index.php?action=converses">Converses</a></li>
                <li class="nav-item"><a class="nav-link" href="index.php?action=mapa">Mapa</a></li>
                <li class="nav-item"><a class="nav-link" href="index.php?action=les_meves_reserves">Les Meves Reserves</a></li>
                <li class="nav-item"><a class="nav-link" href="index.php?action=rankings"><i class="bi bi-trophy-fill me-1"></i>Ranking</a></li>
            </ul>

            <div class="d-flex align-items-center">
                <?php if ($user): ?>
                    <span class="me-3 text-muted small">Tokens: <strong><?= htmlspecialchars((int)($user['tokens'] ?? 0)) ?></strong></span>
                    <a class="btn btn-sm btn-outline-success me-3" href="index.php?action=comprar_tokens">Comprar tokens</a>
                    
                    <span class="me-3 text-muted">Hola, <strong><?= htmlspecialchars($user['nom'] ?? $user['correu']) ?></strong></span>
                    
                    <a class="btn btn-profile btn-sm me-2" href="index.php?action=perfil">Vore el teu perfil</a>
                    <a class="btn btn-outline-secondary btn-sm" href="index.php?action=logout">Tancar Sessió</a>
                <?php else: ?>
                    <a class="btn btn-outline-primary btn-sm me-2" href="index.php?action=login">Iniciar Sessió</a>
                    <a class="btn btn-primary btn-sm" href="index.php?action=register">Crear Compte</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>