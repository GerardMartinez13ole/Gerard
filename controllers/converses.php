<?php
// controllers/converses.php

// Aquest controlador mostra la llista de converses (xats) de l'usuari i permet eliminar una conversa.
// Flux resumit:
// - Comprova que l'usuari estigui autenticat.
// - Gestiona una possible petició POST per eliminar una conversa concreta (mensatges entre dos usuaris per una ruta).
// - Executa una consulta per obtenir la llista de converses amb l'últim missatge, nom de l'altre usuari i dades de la ruta.
// - Carrega la vista 'views/converses.php'.

require_once 'config.php';
require_once 'classes/Sql.php';

// Validació de sessió: l'usuari ha d'estar autenticat per veure les converses.
// Si no hi ha sessió, redirigim al login.
if (empty($_SESSION['user'])) {
    header('Location: index.php?action=login');
    exit;
}

$config = require 'config.php';
$sql = new Sql($config);

// Normalitzar correu de l'usuari (ajuda a fer comparacions case-insensitive).
$me = strtolower(trim($_SESSION['user']['correu'] ?? ''));

// 1. GESTIÓ D'ELIMINACIÓ DE CONVERSA
// Si s'envia un POST amb post_action=delete, es vol eliminar la conversa entre l'usuari actual i un participant per una ruta concreta.
// Aquesta acció elimina tots els missatges en aquesta combinació (route_id, me, participant).
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['post_action']) && $_POST['post_action'] === 'delete') {
    $routeId = isset($_POST['route_id']) ? (int)$_POST['route_id'] : 0;
    $participant = isset($_POST['participant']) ? strtolower(trim($_POST['participant'])) : '';

    if ($routeId > 0 && $participant) {
        // Eliminem els missatges on participen l'usuari actual i l'altre usuari en aquesta ruta.
        $queryDelete = "DELETE FROM messages 
                        WHERE route_id = ? 
                        AND (
                            (LOWER(sender_email) = ? AND LOWER(receiver_email) = ?) 
                            OR 
                            (LOWER(sender_email) = ? AND LOWER(receiver_email) = ?)
                        )";
        try {
            $sql->execute($queryDelete, [$routeId, $me, $participant, $participant, $me]);
            $_SESSION['flash'] = "Conversa eliminada correctament.";
        } catch (Exception $e) {
            // Error genèric per al usuari; no exposar detalls de l'excepció.
            $_SESSION['flash'] = "Error en eliminar la conversa.";
        }
    }
    // Redirecció PRG per evitar re-enviament del formulari.
    header('Location: index.php?action=converses');
    exit;
}

// 2. OBTENIR CONVERSES
// Consultem per obtenir per a l'usuari: id de ruta, correu de l'altre participant, últim missatge i hora,
// nom de l'altre usuari i dades de la ruta. La subconsulta calcula l'última data per parella (route_id, other_email).
$sqlQuery = "
SELECT t.route_id, t.other_email, m.message AS last_message, m.created_at AS last_at, u.nom AS other_name, r.origin, r.destination
FROM (
    SELECT route_id,
           CASE WHEN LOWER(sender_email)=? THEN LOWER(receiver_email) ELSE LOWER(sender_email) END AS other_email,
           MAX(created_at) AS last_at
    FROM messages
    WHERE LOWER(sender_email)=? OR LOWER(receiver_email)=?
    GROUP BY route_id, other_email
) t
JOIN messages m ON m.route_id = t.route_id AND m.created_at = t.last_at
LEFT JOIN usuaris u ON LOWER(u.correu) = t.other_email
LEFT JOIN rutes r ON r.id = t.route_id
ORDER BY t.last_at DESC
";

// Executem la consulta passant el correu de l'usuari (3 vegades pels placeholders).
$conversations = $sql->select($sqlQuery, [$me, $me, $me]);

// 3. CARREGAR LA VISTA (la vista mostrarà $conversations i utilitzarà $_SESSION['flash'] si existeix).
require 'views/converses.php';