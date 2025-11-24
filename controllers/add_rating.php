<?php
// controllers/add_rating.php

require_once 'config.php';
require_once 'classes/Sql.php';

// Validació de sessió
if (empty($_SESSION['user'])) {
    header('Location: index.php?action=login');
    exit;
}

$config = require 'config.php';
$sql = new Sql($config);

// 1. Recollida de dades
$rater = strtolower(trim($_SESSION['user']['correu'] ?? ''));
$route_id = isset($_POST['route_id']) ? (int)$_POST['route_id'] : 0;
$rated_user = isset($_POST['rated_user']) ? strtolower(trim($_POST['rated_user'])) : '';
$rating = isset($_POST['rating']) ? (int)$_POST['rating'] : 0;
$comment = isset($_POST['comment']) ? trim($_POST['comment']) : '';

// 2. Validacions inicials
if ($route_id <= 0 || !$rated_user || $rating < 1 || $rating > 5 || $rater === $rated_user) {
    $_SESSION['flash'] = 'Paràmetres invàlids.';
    // Redirecció MVC
    header("Location: index.php?action=route_details&id={$route_id}");
    exit;
}

// 3. Comprovar si l'usuari ha reservat (seguretat)
$hasReservation = $sql->fetch(
    "SELECT id FROM reservas WHERE route_id = ? AND LOWER(user_email) = ?",
    [$route_id, $rater]
);

if (!$hasReservation) {
    $_SESSION['flash'] = 'No pots valorar una ruta si no l\'has reservat.';
    header("Location: index.php?action=route_details&id={$route_id}");
    exit;
}

try {
    // 4. Gestionar Insert o Update
    $exists = $sql->fetch(
        "SELECT id FROM valoracions WHERE route_id = ? AND LOWER(rated_user_email) = ? AND LOWER(rater_email) = ?",
        [$route_id, $rated_user, $rater]
    );

    if ($exists) {
        // Actualitzar existent
        $sql->execute(
            "UPDATE valoracions SET rating = ?, comment = ?, created_at = NOW() WHERE route_id = ? AND LOWER(rated_user_email) = ? AND LOWER(rater_email) = ?",
            [$rating, $comment, $route_id, $rated_user, $rater]
        );
        $_SESSION['flash'] = 'Valoració actualitzada correctament.';
    } else {
        // Inserir nova
        $sql->insert(
            "INSERT INTO valoracions (route_id, rated_user_email, rater_email, rating, comment) VALUES (?, ?, ?, ?, ?)",
            [$route_id, $rated_user, $rater, $rating, $comment]
        );
        $_SESSION['flash'] = 'Valoració afegida correctament.';
    }

    // 5. Actualitzar la mitjana de l'usuari valorat
    // (Incrustem la lògica de la funció updateUserRating aquí directament)
    $avg = $sql->fetch(
        "SELECT AVG(rating) as avg_rating, COUNT(*) as count FROM valoracions WHERE LOWER(rated_user_email) = ?",
        [$rated_user]
    );
    
    if ($avg && $avg['count'] > 0) {
        $sql->execute(
            "UPDATE usuaris SET valoracio = ? WHERE LOWER(correu) = ?",
            [round($avg['avg_rating'], 2), $rated_user]
        );
    }

} catch (Exception $e) {
    $_SESSION['flash'] = 'Error al guardar la valoració.';
}

// 6. Redirecció final (MVC)
header("Location: index.php?action=route_details&id={$route_id}");
exit;