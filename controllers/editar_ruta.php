<?php
// controllers/editar_ruta.php

// Aquest controlador permet l'edició d'una ruta per part del seu propietari.
// Passos principals:
// - Comprovar sessió i obtenir l'id de la ruta (GET id).
// - Carregar la ruta assegurant que l'usuari autenticat en sigui el propietari.
// - Si arriba POST, validar camps, convertir data+hora i actualitzar la BD.
// - En cas d'èxit redirigir a "mis_rutes", en cas contrari mostrar errors.

require_once 'config.php';
require_once 'classes/Sql.php';

// Validació de sessió
if (empty($_SESSION['user'])) {
    header('Location: index.php?action=login');
    exit;
}

$config = require 'config.php';
$sql = new Sql($config);
$user_email = $_SESSION['user']['correu'] ?? null;

// Obtenir ID de la URL
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header('Location: index.php?action=mis_rutes');
    exit;
}

// Carregar la ruta i comprovar propietari
// Aquesta query fa les dues coses: busca la ruta I assegura que l'usuari sigui l'amo
$rute = $sql->fetch("SELECT * FROM rutes WHERE id = ? AND user_email = ?", [$id, $user_email]);

if (!$rute) {
    // Si no troba resultat, o no existeix o no ets el propietari
    header('Location: index.php?action=mis_rutes');
    exit;
}

$errors = [];

// Processar formulari
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Recollir dades i normalitzar-les
    $origin = trim($_POST['origin'] ?? '');
    $destination = trim($_POST['destination'] ?? '');
    $date = trim($_POST['date'] ?? '');
    $time = trim($_POST['time'] ?? '');
    $seats = (int)($_POST['seats'] ?? 1);
    $description = trim($_POST['description'] ?? '');
    $available = isset($_POST['available']) ? 1 : 0;

    // Validacions bàsiques: camps obligatoris
    if (!$origin || !$destination || !$date || !$time) {
        $errors[] = 'Tots els camps són obligatoris excepte la descripció.';
    }

    if (empty($errors)) {
        $date_time = date('Y-m-d H:i:s', strtotime("$date $time"));
        
        try {
            // Actualitzar la ruta només si l'id i el propietari coincideixen
            $sql->execute(
                "UPDATE rutes SET origin = ?, destination = ?, date_time = ?, seats = ?, description = ?, available = ? WHERE id = ? AND user_email = ?",
                [$origin, $destination, $date_time, $seats, $description, $available, $id, $user_email]
            );
            
            // ÈXIT: Tornar a la llista de les meves rutes
            header('Location: index.php?action=mis_rutes');
            exit;
        } catch (Exception $e) {
            // Missatge genèric per a l'usuari; no exposem l'error intern
            $errors[] = 'Error en actualitzar la ruta.';
        }
    }
}

// Carregar la vista d'edició (la vista mostra $rute i $errors)
require 'views/editar_ruta.php';