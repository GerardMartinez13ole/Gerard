<?php
// controllers/les_meves_reserves.php

require_once 'config.php';
require_once 'classes/Sql.php';

// Aquest controlador mostra les reserves de l'usuari i permet cancel·lar-les.
// Flux:
// 1) Comprovar sessió.
// 2) Si arriba POST amb post_action=cancel, validar i eliminar la reserva (PRG).
// 3) Recuperar totes les reserves de l'usuari i carregar la vista.

// Validació de sessió: l'usuari ha d'estar autenticat.
if (empty($_SESSION['user'])) {
    header('Location: index.php?action=login');
    exit;
}

$config = require 'config.php';
$sql = new Sql($config);

// Normalitzar correu de l'usuari per fer consultes case-insensitive.
$user_email = strtolower(trim($_SESSION['user']['correu'] ?? ''));

// 1. GESTIONAR CANCEL·LACIÓ (POST)
// Si l'usuari demana cancel·lar una reserva, es comprova que la reserva existeix i pertany a l'usuari,
// després s'elimina i s'incrementa el nombre de places a la ruta (si aplica).
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['post_action']) && $_POST['post_action'] === 'cancel') {
    $route_id = isset($_POST['route_id']) ? (int)$_POST['route_id'] : 0;
    
    if ($route_id > 0) {
        // Comprovar que la reserva existeix i és de l'usuari actual.
        $reserva = $sql->fetch("SELECT id FROM reservas WHERE route_id = ? AND LOWER(user_email) = ?", [$route_id, $user_email]);
        
        if ($reserva) {
            try {
                // 1. Esborrar la reserva identificada per id.
                $sql->execute("DELETE FROM reservas WHERE id = ?", [$reserva['id']]);
                
                // 2. Retornar la plaça a la ruta (incrementar seats). Si la ruta ja no existeix, l'UPDATE no tindrà efecte.
                $sql->execute("UPDATE rutes SET seats = seats + 1 WHERE id = ?", [$route_id]);
                
                $_SESSION['flash'] = "Reserva cancel·lada correctament.";
            } catch (Exception $e) {
                // Missatge genèric d'error per a l'usuari; no exposem detalls interns.
                $_SESSION['flash'] = "Error en cancel·lar la reserva.";
            }
        }
    }
    // Redirecció PRG per evitar re-enviament del formulari.
    header('Location: index.php?action=les_meves_reserves');
    exit;
}

// 2. OBTENIR RESERVES (GET)
// Recuperem totes les reserves de l'usuari amb dades de la ruta per mostrar-les a la vista.
$reservas = $sql->select(
    "SELECT res.id AS reserva_id, r.* FROM reservas res 
     JOIN rutes r ON res.route_id = r.id 
     WHERE LOWER(res.user_email) = ? 
     ORDER BY r.date_time DESC",
    [$user_email]
);

// 3. CARREGAR VISTA
require 'views/les_meves_reserves.php';