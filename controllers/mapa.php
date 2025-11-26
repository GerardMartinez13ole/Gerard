<?php
// controllers/mapa.php

// Aquest controlador obté les rutes disponibles per mostrar-les en un mapa o llista.
// - construeix una consulta per recuperar informació bàsica de cada ruta i el conductor
// - envia el resultat a views/mapa.php per al renderitzat

require_once 'config.php';
require_once 'classes/Sql.php';

// Obtenim la configuració (p. ex. dades de connexió)
$config = require 'config.php';

// Instanciem la classe que fa consultes a la BD
$sql = new Sql($config);

// obtenir rutes disponibles amb dades bàsiques
// La consulta retorna:
// - r.id: identificador de la ruta
// - r.origin, r.destination: punts d'origen i destinació
// - r.date_time: data i hora de la ruta
// - r.seats: places disponibles
// - r.description: descripció opcional
// - u.nom i u.correu: dades del conductor
// Filtre: només rutes amb available = 1 (actives) i ordenades per data ascendent.
$rutes = $sql->select(
    "SELECT r.id, r.origin, r.destination, r.date_time, r.seats, r.description, u.nom as driver, u.correu as driver_email
     FROM rutes r
     JOIN usuaris u ON r.user_email = u.correu
     WHERE r.available = 1
     ORDER BY r.date_time ASC"
);

// Si no hi ha rutes, assegurem que la variable sigui un array buit per evitar errors a la vista
if (empty($rutes)) {
    $rutes = [];
}

// La vista 'views/mapa.php' rebrà $rutes i s'encarregarà de mostrar-les.
// Exemple d'estructura d'un element de $rutes:
// [
//   'id' => 1,
//   'origin' => 'Ciutat A',
//   'destination' => 'Ciutat B',
//   'date_time' => '2025-01-01 09:00:00',
//   'seats' => 3,
//   'description' => 'Punt de trobada: ...',
//   'driver' => 'Nom Conductor',
//   'driver_email' => 'conductor@example.com'
// ]
require 'views/mapa.php';