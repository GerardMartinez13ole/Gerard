<?php
// controllers/mapa.php

require_once 'config.php';
require_once 'classes/Sql.php';

$config = require 'config.php';
$sql = new Sql($config);

// obtenir rutes disponibles amb dades bàsiques
$rutes = $sql->select(
    "SELECT r.id, r.origin, r.destination, r.date_time, r.seats, r.description, u.nom as driver, u.correu as driver_email
     FROM rutes r
     JOIN usuaris u ON r.user_email = u.correu
     WHERE r.available = 1
     ORDER BY r.date_time ASC"
);

// Debug: si no hi ha rutes
if (empty($rutes)) {
    $rutes = [];
}

// Carregar vista
require 'views/mapa.php';