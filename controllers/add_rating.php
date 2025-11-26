<?php
// controllers/add_rating.php

// Aquest controlador processa el formulari de valoracions d'una ruta.
// Flux resumit:
// - Comprova que l'usuari estigui autenticat.
// - Llegeix les dades POST (id de ruta, usuari valorat, valoració, comentari).
// - Valida paràmetres bàsics i que l'usuari hagi reservat la ruta.
// - Inserta o actualitza la valoració a la taula 'valoracions'.
// - Recalcula la mitjana de valoracions de l'usuari valorat i l'actualitza a 'usuaris'.
// - Assigna missatges flash i redirigeix a la pàgina de detalls de la ruta.

require_once 'config.php';
require_once 'classes/Sql.php';

// Validació de sessió: l'usuari ha d'estar loguejat per valorar.
// Si no hi ha sessió, redirigim al login.
if (empty($_SESSION['user'])) {
    header('Location: index.php?action=login');
    exit;
}

$config = require 'config.php';
$sql = new Sql($config);

// 1. Recollida de dades
// - rater: correu electrònic de qui valora (obtingut de la sessió).
// - route_id: id de la ruta que es valora.
// - rated_user: correu de l'usuari que rep la valoració.
// - rating: valor numèric (1..5).
// - comment: text opcional.
$rater = strtolower(trim($_SESSION['user']['correu'] ?? ''));
$route_id = isset($_POST['route_id']) ? (int)$_POST['route_id'] : 0;
$rated_user = isset($_POST['rated_user']) ? strtolower(trim($_POST['rated_user'])) : '';
$rating = isset($_POST['rating']) ? (int)$_POST['rating'] : 0;
$comment = isset($_POST['comment']) ? trim($_POST['comment']) : '';

// 2. Validacions inicials
// - route_id ha de ser positiu.
// - hi ha d'haver un usuari valorat.
// - rating dins l'interval correcte.
// - no permetre que un usuari es valori a si mateix.
if ($route_id <= 0 || !$rated_user || $rating < 1 || $rating > 5 || $rater === $rated_user) {
    $_SESSION['flash'] = 'Paràmetres invàlids.';
    // Redirecció MVC cap als detalls de la ruta amb missatge d'error.
    header("Location: index.php?action=route_details&id={$route_id}");
    exit;
}

// 3. Comprovar si l'usuari ha reservat (seguretat)
// Només es permet valorar si qui vota ha fet realment una reserva d'aquesta ruta.
// Es fa una consulta a la taula 'reservas' buscant (route_id, user_email).
$hasReservation = $sql->fetch(
    "SELECT id FROM reservas WHERE route_id = ? AND LOWER(user_email) = ?",
    [$route_id, $rater]
);

if (!$hasReservation) {
    // Si no hi ha reserva, bloquegem l'acció i informem a l'usuari.
    $_SESSION['flash'] = 'No pots valorar una ruta si no l\'has reservat.';
    header("Location: index.php?action=route_details&id={$route_id}");
    exit;
}

try {
    // 4. Gestionar Insert o Update
    // Comprovem si ja existeix una valoració d'aquest usuari per aquesta ruta i usuari valorat.
    $exists = $sql->fetch(
        "SELECT id FROM valoracions WHERE route_id = ? AND LOWER(rated_user_email) = ? AND LOWER(rater_email) = ?",
        [$route_id, $rated_user, $rater]
    );

    if ($exists) {
        // Si existeix, actualitzem la fila amb la nova valoració i comentari.
        $sql->execute(
            "UPDATE valoracions SET rating = ?, comment = ?, created_at = NOW() WHERE route_id = ? AND LOWER(rated_user_email) = ? AND LOWER(rater_email) = ?",
            [$rating, $comment, $route_id, $rated_user, $rater]
        );
        $_SESSION['flash'] = 'Valoració actualitzada correctament.';
    } else {
        // Si no existeix, inserim una nova valoració.
        $sql->insert(
            "INSERT INTO valoracions (route_id, rated_user_email, rater_email, rating, comment) VALUES (?, ?, ?, ?, ?)",
            [$route_id, $rated_user, $rater, $rating, $comment]
        );
        $_SESSION['flash'] = 'Valoració afegida correctament.';
    }

    // 5. Actualitzar la mitjana de l'usuari valorat
    // Recalculem la mitjana i el comptador de valoracions per l'usuari valorat
    // i actualitzem el camp 'valoracio' a la taula 'usuaris'.
    $avg = $sql->fetch(
        "SELECT AVG(rating) as avg_rating, COUNT(*) as count FROM valoracions WHERE LOWER(rated_user_email) = ?",
        [$rated_user]
    );
    
    if ($avg && $avg['count'] > 0) {
        // Es fa round a 2 decimals abans d'emmagatzemar la mitjana.
        $sql->execute(
            "UPDATE usuaris SET valoracio = ? WHERE LOWER(correu) = ?",
            [round($avg['avg_rating'], 2), $rated_user]
        );
    }

} catch (Exception $e) {
    // En cas d'error en la base de dades o en l'execució, establir missatge d'error genèric.
    // Detalls de l'excepció no s'exposen al usuari per seguretat.
    $_SESSION['flash'] = 'Error al guardar la valoració.';
}

// 6. Redirecció final cap als detalls de la ruta (comportament MVC)
header("Location: index.php?action=route_details&id={$route_id}");
exit;