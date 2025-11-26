<?php
// controllers/afegir_ruta.php

// Aquest controlador processa el formulari per afegir una nova ruta.
// Flux resumit:
// - Comprova que l'usuari estigui autenticat.
// - Recull dades del formulari (POST).
// - Valida camps obligatoris i prepara la data/hora.
// - Inserta la ruta a la taula 'rutes' o mostra errors.
// - Redirigeix a la llista de rutes de l'usuari en cas d'èxit.

// Carreguem configuració i classe d'accés a BD
require_once 'config.php';
require_once 'classes/Sql.php';

// Validació de sessió: l'usuari ha d'estar autenticat per crear una ruta.
// Si no hi ha sessió, redirigim al login.
if (empty($_SESSION['user'])) {
    header('Location: index.php?action=login');
    exit;
}

// Obtenim el correu de l'usuari des de la sessió.
// Si no existeix, considerem que hi ha un problema i redirigim al login amb error.
$user_email = $_SESSION['user']['correu'] ?? null;
if (!$user_email) {
    header('Location: index.php?action=login&error=no_user_email');
    exit;
}

$config = require 'config.php';
$sql = new Sql($config);

// Inicialitzem variables per evitar avisos a la vista (Undefined variable).
$errors = [];
$origin = '';
$destination = '';
$date = '';
$time = '';
$seats = 1;
$description = '';
$token_cost = 0;

// Si rebem una sol·licitud POST, processem el formulari.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Recollim dades del formulari, aplicant trim i cast necessari.
    $origin = trim($_POST['origin'] ?? '');
    $destination = trim($_POST['destination'] ?? '');
    $date = trim($_POST['date'] ?? '');
    $time = trim($_POST['time'] ?? '');
    $seats = (int)($_POST['seats'] ?? 1);
    $description = trim($_POST['description'] ?? '');
    $token_cost = (int)($_POST['token_cost'] ?? 0);

    // Validacions bàsiques:
    // - origin, destination, date i time són obligatoris.
    if (!$origin || !$destination || !$date || !$time) {
        $errors[] = 'Tots els camps són obligatoris excepte la descripció.';
    }

    // Si no hi ha errors, preparem la data/hora en format MySQL i fem la inserció.
    if (empty($errors)) {
        // Convertim la data i hora a format 'Y-m-d H:i:s' per emmagatzemar-ho en BD.
        $date_time = date('Y-m-d H:i:s', strtotime("$date $time"));
        
        try {
            // Inserim la nova ruta a la taula 'rutes'.
            $sql->insert(
                "INSERT INTO rutes (user_email, origin, destination, date_time, seats, description, token_cost, available) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, 1)",
                [$user_email, $origin, $destination, $date_time, $seats, $description, $token_cost]
            );
            
            // ÈXIT: Redirigim a "Les meves rutes" per veure la creació.
            header('Location: index.php?action=mis_rutes');
            exit;
        } catch (Exception $e) {
            // En cas d'error d'inserció, afegim un missatge genèric.
            // No exposem detalls de l'excepció per motius de seguretat.
            $errors[] = 'Error al guardar la ruta.';
        }
    }
}

// Finalment, carreguem la vista que mostrarà el formulari i possibles errors.
// La vista utilitza les variables inicialitzades més amunt.
require 'views/afegir_ruta.php';