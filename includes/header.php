<?php
// includes/header.php

// Aquest fitxer defineix la capçalera i la barra de navegació principal del lloc web.
// És un component reutilitzable que s'inclou a la majoria de vistes.
// Conté lògica per:
// 1. Gestionar la sessió de l'usuari.
// 2. Refrescar i mostrar el saldo de tokens de l'usuari.
// 3. Mostrar enllaços de navegació diferents si l'usuari està autenticat o no.

// Comprovem si ja hi ha una sessió iniciada per evitar errors. Si no n'hi ha, la iniciem.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Obtenim les dades de l'usuari de la sessió. Si no hi ha sessió, $user serà null.
$user = $_SESSION['user'] ?? null;

// LÒGICA PER REFRESCAR TOKENS
// Si hi ha un usuari autenticat, fem una consulta a la base de dades per obtenir el seu saldo de tokens més recent.
// Això assegura que el valor mostrat a la capçalera sempre estigui actualitzat, fins i tot després d'una compra o despesa.
if (!empty($user) && !empty($user['correu'])) {
    try {
        // Utilitzem la constant màgica `__DIR__` per construir rutes absolutes als fitxers.
        // Això garanteix que els `require_once` funcionin correctament, independentment des d'on s'inclogui aquest header.
        require_once __DIR__ . '/../config.php';
        require_once __DIR__ . '/../classes/Sql.php';
        
        $config = require __DIR__ . '/../config.php';
        $sql = new Sql($config);

        // Obtenim el saldo de tokens més recent de la base de dades.
        $email = strtolower(trim($user['correu']));
        $row = $sql->fetch("SELECT tokens FROM usuaris WHERE LOWER(correu) = ?", [$email]);
        if ($row !== false && isset($row['tokens'])) {
            // Actualitzem el valor tant a la sessió com a la variable local $user.
            $_SESSION['user']['tokens'] = (int)$row['tokens'];
            $user['tokens'] = $_SESSION['user']['tokens'];
        }
    } catch (Exception $e) {
        // En cas d'error (p. ex., la base de dades no està disponible), no fem res per evitar que la pàgina es trenqui.
        // Simplement es mostrarà l'últim valor de tokens guardat a la sessió.
    }
}
?>
<!-- Barra de navegació de Bootstrap 5 -->
<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm site-navbar">
    <div class="container-fluid">
        <!-- Logotip i nom de la marca, que enllacen al menú principal -->
        <a class="navbar-brand d-flex align-items-center" href="index.php?action=menu">
            <img src="img/Logo.png" alt="Logo" class="navbar-logo">
            <span class="fw-bold ms-2">CarSharing</span>
        </a>
        
        <!-- Botó "hamburguesa" per a dispositius mòbils -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-controls="mainNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Contingut de la barra de navegació que es col·lapsa en mòbil -->
        <div class="collapse navbar-collapse" id="mainNav">
            <!-- Enllaços de navegació principals a l'esquerra -->
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item"><a class="nav-link" href="index.php?action=rutes_disponibles">Rutes Disponibles</a></li>
                <li class="nav-item"><a class="nav-link" href="index.php?action=afegir_ruta">Afegir Ruta</a></li>
                <li class="nav-item"><a class="nav-link" href="index.php?action=mis_rutes">Les Meves Rutes</a></li>
                <li class="nav-item"><a class="nav-link" href="index.php?action=converses">Converses</a></li>
                <li class="nav-item"><a class="nav-link" href="index.php?action=mapa">Mapa</a></li>
                <li class="nav-item"><a class="nav-link" href="index.php?action=les_meves_reserves">Les Meves Reserves</a></li>
                <li class="nav-item"><a class="nav-link" href="index.php?action=rankings"><i class="bi bi-trophy-fill me-1"></i>Rànquing</a></li>
            </ul>

            <!-- Secció dreta de la barra de navegació -->
            <div class="d-flex align-items-center">
                <?php if ($user): // Si l'usuari està autenticat, mostrem informació del seu compte ?>
                    <span class="me-3 text-muted small">Tokens: <strong><?= htmlspecialchars((int)($user['tokens'] ?? 0)) ?></strong></span> <!-- Mostrem el saldo de tokens -->
                    <a class="btn btn-sm btn-outline-success me-3" href="index.php?action=comprar_tokens">Comprar tokens</a>
                    
                    <span class="me-3 text-muted">Hola, <strong><?= htmlspecialchars($user['nom'] ?? $user['correu']) ?></strong></span> <!-- Missatge de benvinguda -->
                    
                    <a class="btn btn-profile btn-sm me-2" href="index.php?action=perfil">Vore el teu perfil</a>
                    <a class="btn btn-outline-secondary btn-sm" href="index.php?action=logout">Tancar Sessió</a>
                <?php else: // Si l'usuari no està autenticat, mostrem els botons de login i registre ?>
                    <a class="btn btn-outline-primary btn-sm me-2" href="index.php?action=login">Iniciar Sessió</a>
                    <a class="btn btn-primary btn-sm" href="index.php?action=register">Crear Compte</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>