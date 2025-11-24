<?php
// controllers/comprar_tokens.php

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
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = (int)($_POST['amount'] ?? 0);
    
    if ($amount <= 0) {
        $errors[] = 'Tria una quantitat vàlida.';
    }

    if (empty($errors)) {
        try {
            // 1. Actualitzar saldo a la BD
            $sql->execute("UPDATE usuaris SET tokens = tokens + ? WHERE LOWER(correu) = ?", [$amount, $user_email]);
            
            // 2. Llegir el nou saldo per actualitzar la sessió
            $row = $sql->fetch("SELECT tokens FROM usuaris WHERE LOWER(correu) = ?", [$user_email]);
            
            // 3. Actualitzar sessió (perquè es vegi al header)
            $_SESSION['user']['tokens'] = (int)($row['tokens'] ?? 0);
            
            $_SESSION['flash'] = 'Compra realitzada correctament: +' . $amount . ' tokens.';
            
            // 4. Redirigir al perfil
            header('Location: index.php?action=perfil');
            exit;
        } catch (Exception $e) {
            $errors[] = 'Error actualitzant tokens. Torna-ho a intentar.';
        }
    }
}

// Carregar vista
require 'views/comprar_tokens.php';