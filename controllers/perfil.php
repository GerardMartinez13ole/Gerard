<?php
// controllers/perfil.php

require_once 'config.php';
require_once 'classes/Sql.php';

// Validació de sessió
if (empty($_SESSION['user'])) {
    header('Location: index.php?action=login');
    exit;
}

$config = require 'config.php';
$sql = new Sql($config);

$user_email = strtolower(trim($_SESSION['user']['correu'] ?? ''));

// 1. processar POST per actualitzar perfil
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newName = trim($_POST['nom'] ?? '');
    $newPass = $_POST['password'] ?? '';
    $errorsUpdate = [];

    if ($newName === '') {
        $errorsUpdate[] = 'El nom no pot estar buit.';
    }

    if (empty($errorsUpdate)) {
        try {
            if ($newPass !== '') {
                $hash = password_hash($newPass, PASSWORD_DEFAULT);
                $sql->execute("UPDATE usuaris SET nom = ?, contrasenya = ? WHERE LOWER(correu) = ?", [$newName, $hash, $user_email]);
            } else {
                $sql->execute("UPDATE usuaris SET nom = ? WHERE LOWER(correu) = ?", [$newName, $user_email]);
            }
            // Actualitzar dades a la sessió per evitar inconsistencia
            $_SESSION['user']['nom'] = $newName;
            $_SESSION['flash'] = 'Dades del perfil actualitzades correctament.';
            
            // Redirecció MVC PRG
            header('Location: index.php?action=perfil');
            exit;
        } catch (Exception $e) {
            $errorsUpdate[] = 'Error actualitzant el perfil.';
        }
    }
    
    // Si hi ha errors
    if (!empty($errorsUpdate)) {
        $_SESSION['flash'] = implode(' ', $errorsUpdate);
        header('Location: index.php?action=perfil');
        exit;
    }
}

// 2. OBTENIR DADES PER A LA VISTA (GET)

// Obtenir dades fresques de l'usuari
$user = $sql->fetch("SELECT * FROM usuaris WHERE LOWER(correu) = ?", [$user_email]);

// Obtenir rutes creades
$rutesCreades = $sql->select(
    "SELECT id, origin, destination, date_time, seats, available FROM rutes WHERE LOWER(user_email) = ? ORDER BY date_time DESC",
    [$user_email]
);

// Obtenir rutes reservades
$rutesReservades = $sql->select(
    "SELECT r.id, r.origin, r.destination, r.date_time, r.seats, u.nom as driver
     FROM reservas res
     JOIN rutes r ON res.route_id = r.id
     JOIN usuaris u ON r.user_email = u.correu
     WHERE LOWER(res.user_email) = ?
     ORDER BY r.date_time DESC",
    [$user_email]
);

// Obtenir valoracions rebudes
$valoracions = $sql->select(
    "SELECT v.*, u.nom as rater_name FROM valoracions v
     JOIN usuaris u ON LOWER(u.correu) = LOWER(v.rater_email)
     WHERE LOWER(v.rated_user_email) = ?
     ORDER BY v.created_at DESC",
    [$user_email]
);

// 3. CARREGAR VISTA
require 'views/perfil.php';