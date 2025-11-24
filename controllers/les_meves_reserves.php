<?php
// controllers/les_meves_reserves.php

require_once 'config.php';
require_once 'classes/Sql.php';

// Validació de sessió
if (empty($_SESSION['user'])) {
    header('Location: index.php?action=login');
    exit;
}

$config = require 'config.php';
$sql = new Sql($config);

$user_email = strtolower(trim($_SESSION['user']['correu'] ?? ''));

// 1. GESTIONAR CANCEL·LACIÓ (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['post_action']) && $_POST['post_action'] === 'cancel') {
    $route_id = isset($_POST['route_id']) ? (int)$_POST['route_id'] : 0;
    
    if ($route_id > 0) {
        // Comprovar que la reserva existeix i és de l'usuari
        $reserva = $sql->fetch("SELECT id FROM reservas WHERE route_id = ? AND LOWER(user_email) = ?", [$route_id, $user_email]);
        
        if ($reserva) {
            try {
                // 1. Esborrar reserva
                $sql->execute("DELETE FROM reservas WHERE id = ?", [$reserva['id']]);
                
                // 2. Retornar la plaça a la ruta (incrementar seats)
                // Només si la ruta encara existeix i és vàlida
                $sql->execute("UPDATE rutes SET seats = seats + 1 WHERE id = ?", [$route_id]);
                
                $_SESSION['flash'] = "Reserva cancel·lada correctament.";
            } catch (Exception $e) {
                $_SESSION['flash'] = "Error en cancel·lar la reserva.";
            }
        }
    }
    // Redirecció PRG
    header('Location: index.php?action=les_meves_reserves');
    exit;
}

// 2. OBTENIR RESERVES (GET)
$reservas = $sql->select(
    "SELECT res.id AS reserva_id, r.* FROM reservas res 
     JOIN rutes r ON res.route_id = r.id 
     WHERE LOWER(res.user_email) = ? 
     ORDER BY r.date_time DESC",
    [$user_email]
);

// 3. CARREGAR VISTA
require 'views/les_meves_reserves.php';