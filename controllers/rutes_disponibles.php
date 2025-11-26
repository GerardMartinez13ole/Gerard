<?php
// controllers/rutes_disponibles.php

// Aquest controlador s'encarrega de mostrar una llista de rutes disponibles, amb opcions de filtratge.
// Té una doble funcionalitat:
// 1. Si es crida normalment, carrega una vista HTML amb la llista de rutes.
// 2. Si es crida amb el paràmetre 'ajax=1', retorna les dades en format JSON, ideal per a mapes interactius o actualitzacions dinàmiques.

require_once 'config.php';
require_once 'classes/Sql.php';

// Verificació de sessió: l'usuari ha d'estar autenticat per veure les rutes.
// Si es vol que aquesta pàgina sigui pública, es pot eliminar aquest bloc.
if (empty($_SESSION['user'])) {
    header('Location: index.php?action=login');
    exit;
}

// Carreguem la configuració i instanciem la classe per a les consultes a la base de dades.
$config = require 'config.php';
$sql = new Sql($config);

// 1. RECOLLIDA DE FILTRES
// Obtenim els paràmetres de filtratge de la petició (pot ser GET o POST, per això s'usa $_REQUEST).
// Si un filtre no s'especifica, la seva variable quedarà buida o amb el valor per defecte.
$filterOrigin = isset($_REQUEST['filter_origin']) ? trim($_REQUEST['filter_origin']) : '';
$filterDestination = isset($_REQUEST['filter_destination']) ? trim($_REQUEST['filter_destination']) : '';
$filterDate = isset($_REQUEST['filter_date']) ? trim($_REQUEST['filter_date']) : '';
$filterSeats = isset($_REQUEST['filter_seats']) ? (int)$_REQUEST['filter_seats'] : 0;

// 2. CONSTRUCCIÓ DINÀMICA DE LA CONSULTA SQL
// Comencem amb una consulta base que selecciona les rutes actives i les uneix amb la taula d'usuaris per obtenir el nom del conductor.
$query = "SELECT r.id, r.origin, r.destination, r.date_time, r.seats, r.description, u.nom as username 
         FROM rutes r 
         JOIN usuaris u ON r.user_email = u.correu 
         WHERE r.available = 1";
// Aquest array emmagatzemarà els valors dels filtres per a la consulta preparada, evitant injeccions SQL.
$params = [];

// Afegim condicions a la consulta només si s'han proporcionat els filtres corresponents.
if (!empty($filterOrigin)) {
    $query .= " AND r.origin LIKE ?"; // LIKE permet cerques parcials (p. ex., "Barce" trobarà "Barcelona").
    $params[] = "%$filterOrigin%";
}
if (!empty($filterDestination)) {
    $query .= " AND r.destination LIKE ?";
    $params[] = "%$filterDestination%";
}
if (!empty($filterDate)) {
    $query .= " AND DATE(r.date_time) >= ?"; // Compara només la part de la data, ignorant l'hora.
    $params[] = $filterDate;
}
if ($filterSeats > 0) {
    $query .= " AND r.seats >= ?"; // Busca rutes amb un nombre de seients igual o superior al sol·licitat.
    $params[] = $filterSeats;
}

// Finalment, ordenem els resultats per data de manera ascendent.
$query .= " ORDER BY r.date_time ASC";

// 3. EXECUCIÓ DE LA CONSULTA
// Executem la consulta amb els paràmetres si n'hi ha, o sense si no s'ha aplicat cap filtre.
$rutes = empty($params) ? $sql->select($query) : $sql->select($query, $params);

// 4. GESTIÓ DE PETICIONS AJAX (API en format JSON)
// Si la petició inclou el paràmetre 'ajax', no mostrem la pàgina HTML. En lloc d'això, retornem un JSON.
if (!empty($_REQUEST['ajax'])) {
    // Netegem qualsevol sortida que s'hagi pogut generar abans (p. ex., espais en blanc) per garantir un JSON vàlid.
    ob_clean(); 
    // Indiquem al navegador que la resposta és de tipus JSON.
    header('Content-Type: application/json');
    
    $result = [];
    // Recorrem les rutes obtingudes i les preparem per a la sortida JSON.
    // És una bona pràctica formatar les dades i aplicar 'htmlspecialchars' per seguretat.
    foreach ($rutes as $r) {
        $result[] = [
            'id' => $r['id'],
            'origin' => htmlspecialchars($r['origin']), // Evita problemes de XSS si les dades es mostren en HTML.
            'destination' => htmlspecialchars($r['destination']), 
            'date_time' => date('d/m/Y H:i', strtotime($r['date_time'])),
            'seats' => (int)$r['seats'],
            'username' => htmlspecialchars($r['username']),
            'description' => htmlspecialchars($r['description'])
        ];
    }
    echo json_encode($result); // Convertim l'array a format JSON i l'enviem com a resposta.
    // Aturem l'execució del script aquí per no carregar la vista HTML de sota.
    exit;
}

// 5. CÀRREGA DE LA VISTA HTML (per a peticions no-AJAX)
// Si no era una petició AJAX, carreguem la vista normal, que utilitzarà la variable $rutes per mostrar la llista.
require 'views/rutes_disponibles.php';