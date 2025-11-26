<?php
// controllers/route_details.php

// Aquest controlador gestiona la pàgina de detalls d'una ruta específica.
// Té una doble responsabilitat:
// 1. Si la petició és GET, mostra els detalls de la ruta, el conductor i si l'usuari actual ja té una reserva.
// 2. Si la petició és POST, processa una sol·licitud de reserva per a aquesta ruta.

require_once 'config.php';
require_once 'classes/Sql.php';

// Carreguem la configuració i instanciem la classe per a les consultes a la base de dades.
$config = require 'config.php';
$sql = new Sql($config);

// 1. OBTENCIÓ DE L'ID DE LA RUTA
// L'ID pot venir per GET (quan es carrega la pàgina) o per POST (quan s'envia el formulari de reserva).
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0 && (!isset($_POST['route_id']))) {
    // Si no tenim ID ni per GET ni per POST, no podem continuar. Redirigim a la llista de rutes.
    header('Location: index.php?action=rutes_disponibles');
    exit;
}
// Si l'ID no ve per GET, el busquem al POST (fallback per al formulari).
if ($id == 0 && isset($_POST['route_id'])) {
    $id = (int)$_POST['route_id'];
}

// 2. LÒGICA DE RESERVA (quan s'envia un formulari via POST)
// Comprovem si la petició és POST i si conté l'acció específica 'reserve'.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['post_action']) && $_POST['post_action'] === 'reserve') {
    
    // Validació 1: L'usuari ha d'haver iniciat sessió per reservar.
    if (empty($_SESSION['user'])) {
        // Guardem un missatge flash a la sessió per mostrar-lo a la vista.
        $_SESSION['flash'] = 'Cal iniciar sessió per fer una reserva.';
        header('Location: index.php?action=route_details&id=' . $id);
        exit;
    }

    // Obtenim el correu de l'usuari que vol reservar.
    $user_email = strtolower(trim($_SESSION['user']['correu'] ?? ''));
    
    // Validació 2: Obtenim les dades més recents de la ruta per assegurar-nos que encara és vàlida.
    $routeCheck = $sql->fetch("SELECT id, user_email, seats FROM rutes WHERE id = ?", [$id]);
    
    if (empty($routeCheck)) {
        // Si la ruta ja no existeix, redirigim.
        $_SESSION['flash'] = 'Ruta no trobada.';
        header('Location: index.php?action=rutes_disponibles');
        exit;
    }

    // Validacions de negoci (regles de l'aplicació):
    // a) Un usuari no pot reservar la seva pròpia ruta.
    if (strtolower($routeCheck['user_email'] ?? '') === $user_email) {
        $_SESSION['flash'] = 'No pots reservar la teva pròpia ruta.';
    // b) La ruta ha de tenir places disponibles.
    } elseif ((int)($routeCheck['seats'] ?? 0) <= 0) {
        $_SESSION['flash'] = 'No hi ha places disponibles.';
    } else {
        // c) Comprovem que l'usuari no tingui ja una reserva per a aquesta ruta.
        $existing = $sql->fetch("SELECT id FROM reservas WHERE route_id = ? AND LOWER(user_email) = ?", [$id, $user_email]);
        if (!empty($existing)) {
            $_SESSION['flash'] = 'Ja tens una reserva per aquesta ruta.';
        } else {
            // Si totes les validacions són correctes, executem la reserva.
            try {
                // 1. Inserim la nova reserva a la taula 'reservas'.
                $sql->execute("INSERT INTO reservas (route_id, user_email, created_at) VALUES (?, ?, NOW())", [$id, $user_email]);
                // 2. Restem una plaça a la ruta. La condició 'seats > 0' és una seguretat addicional contra condicions de cursa.
                $sql->execute("UPDATE rutes SET seats = seats - 1 WHERE id = ? AND seats > 0", [$id]);
                $_SESSION['flash'] = 'Reserva realitzada correctament.';
            } catch (Exception $e) {
                // En cas d'error a la base de dades, informem l'usuari.
                $_SESSION['flash'] = 'Error en realitzar la reserva.';
            }
        }
    }

    // Redirigim a la mateixa pàgina per mostrar el resultat (èxit o error) i evitar que el formulari es torni a enviar si es refresca la pàgina (Patró PRG).
    header('Location: index.php?action=route_details&id=' . $id);
    exit;
}

// 3. OBTENCIÓ DE DADES PER A LA VISTA (GET)

// Obtenim les dades completes de la ruta, incloent informació del conductor (nom, valoració, correu).
$ruta = $sql->fetch(
    "SELECT r.*, u.nom AS driver_name, u.valoracio AS driver_valoracio, u.correu AS driver_email 
     FROM rutes r 
     JOIN usuaris u ON r.user_email = u.correu 
     WHERE r.id = ?",
    [$id]
);

// Inicialitzem variables per a la vista.
$hasReservation = false;
$currentRating = null;

// Si l'usuari ha iniciat sessió, fem comprovacions addicionals.
if (!empty($_SESSION['user'])) {
    $user_email = strtolower(trim($_SESSION['user']['correu'] ?? ''));
    
    // Comprovem si l'usuari actual ja té una reserva per a aquesta ruta.
    $reservation = $sql->fetch(
        "SELECT id FROM reservas WHERE route_id = ? AND LOWER(user_email) = ?",
        [$id, $user_email]
    );
    $hasReservation = !empty($reservation);
    
    // Comprovem si l'usuari actual ja ha valorat el conductor en aquesta ruta.
    // Això servirà per mostrar el formulari de valoració o la valoració ja existent.
    $currentRating = $sql->fetch(
        "SELECT id, rating, comment FROM valoracions 
         WHERE route_id = ? AND LOWER(rater_email) = ? AND LOWER(rated_user_email) = ?",
        [$id, $user_email, strtolower($ruta['driver_email'] ?? '')]
    );
}

// 4. CÀRREGA DE LA VISTA
// Finalment, carreguem la vista, que utilitzarà les variables $ruta, $hasReservation i $currentRating per mostrar la informació.
require 'views/route_details.php';