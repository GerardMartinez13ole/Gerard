<?php
// controllers/rutes_disponibles.php

require_once 'config.php';
require_once 'classes/Sql.php';

// Verifiquem sessió (opcional, si vols que sigui públic treu aquest bloc)
if (empty($_SESSION['user'])) {
    header('Location: index.php?action=login');
    exit;
}

$config = require 'config.php';
$sql = new Sql($config);

// 1. Recollida de filtres
$filterOrigin = isset($_REQUEST['filter_origin']) ? trim($_REQUEST['filter_origin']) : '';
$filterDestination = isset($_REQUEST['filter_destination']) ? trim($_REQUEST['filter_destination']) : '';
$filterDate = isset($_REQUEST['filter_date']) ? trim($_REQUEST['filter_date']) : '';
$filterSeats = isset($_REQUEST['filter_seats']) ? (int)$_REQUEST['filter_seats'] : 0;

// 2. Construcció de la Query
$query = "SELECT r.id, r.origin, r.destination, r.date_time, r.seats, r.description, u.nom as username 
         FROM rutes r 
         JOIN usuaris u ON r.user_email = u.correu 
         WHERE r.available = 1";
$params = [];

if (!empty($filterOrigin)) {
    $query .= " AND r.origin LIKE ?";
    $params[] = "%$filterOrigin%";
}
if (!empty($filterDestination)) {
    $query .= " AND r.destination LIKE ?";
    $params[] = "%$filterDestination%";
}
if (!empty($filterDate)) {
    $query .= " AND DATE(r.date_time) >= ?";
    $params[] = $filterDate;
}
if ($filterSeats > 0) {
    $query .= " AND r.seats >= ?";
    $params[] = $filterSeats;
}

$query .= " ORDER BY r.date_time ASC";

// 3. Execució
$rutes = empty($params) ? $sql->select($query) : $sql->select($query, $params);

// 4. Gestió AJAX (API JSON)
if (!empty($_REQUEST['ajax'])) {
    // Netegem qualsevol sortida anterior per evitar trencar el JSON
    ob_clean(); 
    header('Content-Type: application/json');
    
    $result = [];
    foreach ($rutes as $r) {
        $result[] = [
            'id' => $r['id'],
            'origin' => htmlspecialchars($r['origin']),
            'destination' => htmlspecialchars($r['destination']),
            'date_time' => date('d/m/Y H:i', strtotime($r['date_time'])),
            'seats' => (int)$r['seats'],
            'username' => htmlspecialchars($r['username']),
            'description' => htmlspecialchars($r['description'])
        ];
    }
    echo json_encode($result);
    // Tallem l'execució aquí perquè no es carregui la vista HTML
    exit;
}

// 5. Carregar Vista
require 'views/rutes_disponibles.php';