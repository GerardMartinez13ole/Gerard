<?php
// controllers/login.php

require_once 'config.php';
require_once 'classes/Sql.php';

$config = require 'config.php';
$sql = new Sql($config);

$errors = [];

// Comprovem si venim d'un registre exitós
$registered = isset($_GET['registered']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if ($email === '' || $password === '') {
        $errors[] = 'Rellena todos los campos.';
    } else {
        $user = $sql->verifyUser($email, $password);
        if ($user) {
            // Guardar en session (la sessió ja està iniciada a index.php)
            $_SESSION['user'] = $user;
            
            // Redirecció MVC cap al menú principal
            header('Location: index.php?action=menu');
            exit;
        } else {
            $errors[] = 'Email o contraseña incorrectos.';
        }
    }
}

// Carreguem la vista
require 'views/login.php';