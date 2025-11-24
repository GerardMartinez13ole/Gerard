<?php
// controllers/converses.php

require_once 'config.php';
require_once 'classes/Sql.php';

// Validació de sessió
if (empty($_SESSION['user'])) {
    header('Location: index.php?action=login');
    exit;
}

$config = require 'config.php';
$sql = new Sql($config);

// Normalitzar correu de l'usuari
$me = strtolower(trim($_SESSION['user']['correu'] ?? ''));

// 1. GESTIÓ D'ELIMINACIÓ (Lògica de delete_chat.php integrada aquí)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['post_action']) && $_POST['post_action'] === 'delete') {
    $routeId = isset($_POST['route_id']) ? (int)$_POST['route_id'] : 0;
    $participant = isset($_POST['participant']) ? strtolower(trim($_POST['participant'])) : '';

    if ($routeId > 0 && $participant) {
        // Eliminem els missatges on participen l'usuari actual i l'altre usuari en aquesta ruta
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
            $_SESSION['flash'] = "Error en eliminar la conversa.";
        }
    }
    // Redirecció PRG per evitar re-enviament
    header('Location: index.php?action=converses');
    exit;
}

// 2. OBTENIR CONVERSES (SQL complexa original)
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

// Executem la consulta passant el correu de l'usuari 3 vegades (pels placeholders ?)
$conversations = $sql->select($sqlQuery, [$me, $me, $me]);

// 3. CARREGAR LA VISTA
require 'views/converses.php';