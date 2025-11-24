<?php
// controllers/route_details.php

require_once 'config.php';
require_once 'classes/Sql.php';

$config = require 'config.php';
$sql = new Sql($config);

// 1. Obtenir ID
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0 && (!isset($_POST['route_id']))) {
    // Si no tenim ID ni per GET ni per POST, tornem a la llista
    header('Location: index.php?action=rutes_disponibles');
    exit;
}
if ($id == 0 && isset($_POST['route_id'])) {
    $id = (int)$_POST['route_id'];
}

// 2. LÒGICA DE RESERVA (POST)
// Utilitzem 'post_action' per diferenciar del 'action' de l'enrutador
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['post_action']) && $_POST['post_action'] === 'reserve') {
    
    // Validacions inicials
    if (empty($_SESSION['user'])) {
        $_SESSION['flash'] = 'Cal iniciar sessió per fer una reserva.';
        header('Location: index.php?action=route_details&id=' . $id);
        exit;
    }

    $user_email = strtolower(trim($_SESSION['user']['correu'] ?? ''));
    
    // Obtenir dades fresques per validar
    $routeCheck = $sql->fetch("SELECT id, user_email, seats FROM rutes WHERE id = ?", [$id]);
    
    if (empty($routeCheck)) {
        $_SESSION['flash'] = 'Ruta no trobada.';
        header('Location: index.php?action=rutes_disponibles');
        exit;
    }

    // Validacions de negoci
    if (strtolower($routeCheck['user_email'] ?? '') === $user_email) {
        $_SESSION['flash'] = 'No pots reservar la teva pròpia ruta.';
    } elseif ((int)($routeCheck['seats'] ?? 0) <= 0) {
        $_SESSION['flash'] = 'No hi ha places disponibles.';
    } else {
        // Comprovar duplicats
        $existing = $sql->fetch("SELECT id FROM reservas WHERE route_id = ? AND LOWER(user_email) = ?", [$id, $user_email]);
        if (!empty($existing)) {
            $_SESSION['flash'] = 'Ja tens una reserva per aquesta ruta.';
        } else {
            // EXECUTAR RESERVA
            try {
                $sql->execute("INSERT INTO reservas (route_id, user_email, created_at) VALUES (?, ?, NOW())", [$id, $user_email]);
                $sql->execute("UPDATE rutes SET seats = seats - 1 WHERE id = ? AND seats > 0", [$id]);
                $_SESSION['flash'] = 'Reserva realitzada correctament.';
            } catch (Exception $e) {
                $_SESSION['flash'] = 'Error en realitzar la reserva.';
            }
        }
    }

    // Redirecció per evitar re-enviament del formulari (PRG Pattern)
    header('Location: index.php?action=route_details&id=' . $id);
    exit;
}

// 3. OBTENCIÓ DE DADES PER A LA VISTA (GET)

// Dades de la ruta i conductor
$ruta = $sql->fetch(
    "SELECT r.*, u.nom AS driver_name, u.valoracio AS driver_valoracio, u.correu AS driver_email 
     FROM rutes r 
     JOIN usuaris u ON r.user_email = u.correu 
     WHERE r.id = ?",
    [$id]
);

// Comprovacions addicionals per l'usuari actual
$hasReservation = false;
$currentRating = null;

if (!empty($_SESSION['user'])) {
    $user_email = strtolower(trim($_SESSION['user']['correu'] ?? ''));
    
    // Té reserva?
    $reservation = $sql->fetch(
        "SELECT id FROM reservas WHERE route_id = ? AND LOWER(user_email) = ?",
        [$id, $user_email]
    );
    $hasReservation = !empty($reservation);
    
    // Ha valorat?
    $currentRating = $sql->fetch(
        "SELECT id, rating, comment FROM valoracions 
         WHERE route_id = ? AND LOWER(rater_email) = ? AND LOWER(rated_user_email) = ?",
        [$id, $user_email, strtolower($ruta['driver_email'] ?? '')]
    );
}

// 4. Carregar Vista
require 'views/route_details.php';