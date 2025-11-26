<?php
// controllers/mis_rutes.php

// Aquest controlador gestiona la pàgina "Les meves rutes", on un usuari pot veure i eliminar les rutes que ha creat.
// Flux de treball:
// 1. Comprova que l'usuari estigui autenticat.
// 2. Si rep una petició POST per eliminar una ruta, realitza una eliminació lògica (soft delete).
// 3. Obté de la base de dades totes les rutes actives creades per l'usuari.
// 4. Carrega la vista 'views/mis_rutes.php' per mostrar la llista de rutes.

require_once 'config.php';
require_once 'classes/Sql.php';

// Verificació de seguretat: l'usuari ha d'haver iniciat sessió per accedir a aquesta pàgina.
if (empty($_SESSION['user'])) {
    // Si no hi ha sessió, es redirigeix a la pàgina de login.
    header('Location: index.php?action=login');
    exit;
}

// Carreguem la configuració i instanciem la classe per a les consultes a la base de dades.
$config = require 'config.php';
$sql = new Sql($config);

// Obtenim el correu de l'usuari de la sessió per identificar les seves rutes.
$user_email = $_SESSION['user']['correu'] ?? null;
if (!$user_email) {
    // Mesura de seguretat addicional: si no hi ha correu a la sessió, es redirigeix al login.
    header('Location: index.php?action=login&error=no_user_email');
    exit;
}

// 1. GESTIÓ DE L'ELIMINACIÓ DE RUTES (via POST)
// Comprovem si la petició és de tipus POST i si conté l'acció 'delete' i un ID de ruta.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['post_action']) && $_POST['post_action'] === 'delete' && !empty($_POST['id'])) {
    // Obtenim l'ID de la ruta a eliminar i el convertim a enter per seguretat.
    $id = (int)$_POST['id'];
    
    // S'executa una eliminació lògica (soft delete): en lloc d'esborrar el registre,
    // es marca la ruta com a no disponible (available = 0).
    // La condició 'user_email = ?' assegura que un usuari només pot eliminar les seves pròpies rutes.
    $sql->execute("UPDATE rutes SET available = 0 WHERE id = ? AND user_email = ?", [$id, $user_email]);
    
    // Redirigim a la mateixa pàgina per evitar que el formulari es torni a enviar si l'usuari refresca (Patró PRG: Post-Redirect-Get).
    header('Location: index.php?action=mis_rutes');
    exit;
}

// 2. OBTENCIÓ DE LES RUTES DE L'USUARI (via GET)
// Es fa una consulta per obtenir totes les rutes de l'usuari actual que estiguin actives (available = 1).
// S'ordenen per data de manera descendent per mostrar les més recents primer.
$rutes = $sql->select("SELECT * FROM rutes WHERE user_email = ? AND available = 1 ORDER BY date_time DESC", [$user_email]);

// 3. CÀRREGA DE LA VISTA
// Finalment, s'inclou el fitxer de la vista, que s'encarregarà de mostrar les dades contingudes a la variable $rutes.
require 'views/mis_rutes.php';