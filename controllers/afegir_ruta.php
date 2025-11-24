<?php
// controllers/afegir_ruta.php

require_once 'config.php';
require_once 'classes/Sql.php';

// Validació de sessió
if (empty($_SESSION['user'])) {
    header('Location: index.php?action=login');
    exit;
}

$user_email = $_SESSION['user']['correu'] ?? null;
if (!$user_email) {
    header('Location: index.php?action=login&error=no_user_email');
    exit;
}

$config = require 'config.php';
$sql = new Sql($config);

// Inicialitzem variables per evitar errors "Undefined variable" a la vista
$errors = [];
$origin = '';
$destination = '';
$date = '';
$time = '';
$seats = 1;
$description = '';
$token_cost = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Recollim dades
    $origin = trim($_POST['origin'] ?? '');
    $destination = trim($_POST['destination'] ?? '');
    $date = trim($_POST['date'] ?? '');
    $time = trim($_POST['time'] ?? '');
    $seats = (int)($_POST['seats'] ?? 1);
    $description = trim($_POST['description'] ?? '');
    $token_cost = (int)($_POST['token_cost'] ?? 0);

    // Validacions bàsiques
    if (!$origin || !$destination || !$date || !$time) {
        $errors[] = 'Tots els camps són obligatoris excepte la descripció.';
    }

    if (empty($errors)) {
        $date_time = date('Y-m-d H:i:s', strtotime("$date $time"));
        
        try {
            $sql->insert(
                "INSERT INTO rutes (user_email, origin, destination, date_time, seats, description, token_cost, available) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, 1)",
                [$user_email, $origin, $destination, $date_time, $seats, $description, $token_cost]
            );
            
            // ÈXIT: Redirigim a "Les meves rutes" per veure la creació
            header('Location: index.php?action=mis_rutes');
            exit;
        } catch (Exception $e) {
            $errors[] = 'Error al guardar la ruta.';
        }
    }
}

// Carreguem la vista
require 'views/afegir_ruta.php';