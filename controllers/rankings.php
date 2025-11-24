<?php
// controllers/rankings.php

require_once 'config.php';
require_once 'classes/Sql.php';

// Validació de sessió (per seguretat i coherència)
if (empty($_SESSION['user'])) {
    header('Location: index.php?action=login');
    exit;
}

$config = require 'config.php';
$sql = new Sql($config);

// Obtenir ranking de conductors
// Seleccionem usuaris amb valoració > 0, ordenats per valoració i nombre de vots
$rankings = $sql->select(
    "SELECT u.correu, u.nom, u.valoracio, 
            COUNT(v.id) as num_valoracions,
            (SELECT COUNT(*) FROM rutes WHERE user_email = u.correu) as num_rutes
     FROM usuaris u
     LEFT JOIN valoracions v ON LOWER(v.rated_user_email) = LOWER(u.correu)
     WHERE u.valoracio > 0
     GROUP BY u.correu, u.nom, u.valoracio
     ORDER BY u.valoracio DESC, num_valoracions DESC
     LIMIT 50"
);

// Carregar vista
require 'views/rankings.php';