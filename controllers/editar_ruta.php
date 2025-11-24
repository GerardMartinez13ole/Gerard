<?php
// controllers/editar_ruta.php

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
    $origin = trim($_POST['origin'] ?? '');
    $destination = trim($_POST['destination'] ?? '');
    $date = trim($_POST['date'] ?? '');
    $time = trim($_POST['time'] ?? '');
    $seats = (int)($_POST['seats'] ?? 1);
    $description = trim($_POST['description'] ?? '');
    // El checkbox envia valor només si està marcat
    $available = isset($_POST['available']) ? 1 : 0;

    if (!$origin || !$destination || !$date || !$time) {
        $errors[] = 'Todos los campos son obligatorios excepto la descripción.';
    }

    if (empty($errors)) {
        $date_time = date('Y-m-d H:i:s', strtotime("$date $time"));
        
        try {
            $sql->execute(
                "UPDATE rutes SET origin = ?, destination = ?, date_time = ?, seats = ?, description = ?, available = ? WHERE id = ? AND user_email = ?",
                [$origin, $destination, $date_time, $seats, $description, $available, $id, $user_email]
            );
            
            // ÈXIT: Tornar a la llista
            header('Location: index.php?action=mis_rutes');
            exit;
        } catch (Exception $e) {
            $errors[] = 'Error al actualizar la ruta.';
        }
    }
}

// Carregar la vista
require 'views/editar_ruta.php';