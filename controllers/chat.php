<?php
// controllers/chat.php

require_once 'config.php';
require_once 'classes/Sql.php';

// Validació de sessió
if (empty($_SESSION['user'])) {
    header('Location: index.php?action=login');
    exit;
}

$config = require 'config.php';
$sql = new Sql($config);

// Normalitzar correu propi
$me = strtolower(trim($_SESSION['user']['correu'] ?? ''));

// Obtenir ID ruta
$route_id = isset($_GET['route_id']) ? (int)$_GET['route_id'] : 0;
if ($route_id <= 0) {
    header('Location: index.php?action=rutes_disponibles');
    exit;
}

// Obtenir dades ruta i conductor
$route = $sql->fetch("SELECT r.*, u.nom AS driver_name, u.correu AS driver_email FROM rutes r JOIN usuaris u ON r.user_email = u.correu WHERE r.id = ?", [$route_id]);
if (!$route) {
    header('Location: index.php?action=rutes_disponibles');
    exit;
}

// Normalitzar correu del driver
$driver_email = strtolower(trim($route['driver_email'] ?? ''));
$driver_name = $route['driver_name'] ?? '—';

// Participant des de GET
$participant = isset($_GET['participant']) ? strtolower(trim($_GET['participant'])) : null;

// Lògica de selecció de participant:
// 1. Si NO sóc el conductor, el meu "participant" (amb qui parlo) és el conductor.
if ($me !== $driver_email && !$participant) {
    $participant = $driver_email;
}

// 2. Si sóc el conductor i NO he triat participant, he de mostrar la llista.
$participants = [];
if ($me === $driver_email && !$participant) {
    $rows = $sql->select(
        "SELECT DISTINCT LOWER(email) AS participant FROM (
            SELECT sender_email AS email FROM messages WHERE route_id = ?
            UNION
            SELECT receiver_email AS email FROM messages WHERE route_id = ?
        ) t WHERE LOWER(email) != ?",
        [$route_id, $route_id, $driver_email]
    );
    foreach ($rows as $r) {
        if (!empty($r['participant'])) $participants[] = $r['participant'];
    }
}

// PROCESAMENT D'ENVIAMENT DE MISSATGE (POST)
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message'])) {
    $msg = trim($_POST['message']);
    $receiver = strtolower(trim($_POST['receiver'] ?? ''));

    if ($msg === '') {
        $errors[] = 'El mensaje no puede estar vacío.';
    } elseif ($receiver === '' ) {
        $errors[] = 'Destinatari invàlid.';
    } else {
        try {
            // Inserir
            $sql->insert("INSERT INTO messages (route_id, sender_email, receiver_email, message) VALUES (?, ?, ?, ?)", [$route_id, $me, $receiver, $msg]);
            
            // Redirecció MVC per evitar reenviament
            $qs = "route_id={$route_id}";
            if (!empty($participant)) $qs .= "&participant=" . urlencode($participant);
            
            header("Location: index.php?action=chat&{$qs}");
            exit;
        } catch (Exception $e) {
            $errors[] = 'Error al enviar el mensaje.';
        }
    }
}

// CARREGAR HISTORIAL DE MISSATGES
$messages = [];
if (!empty($participant)) {
    $other = $participant;
    $messages = $sql->select(
        "SELECT * FROM messages 
         WHERE route_id = ? 
           AND (
             (LOWER(sender_email) = ? AND LOWER(receiver_email) = ?)
             OR
             (LOWER(sender_email) = ? AND LOWER(receiver_email) = ?)
           )
         ORDER BY created_at ASC",
        [$route_id, $me, $other, $other, $me]
    );
}

// CARREGAR VISTA
require 'views/chat.php';