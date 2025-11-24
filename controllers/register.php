<?php
// controllers/register.php

require_once 'config.php';
require_once 'classes/Sql.php';

// Inicialitzem variables per a la vista
$errors = [];
$name = '';
$email = '';

// Instanciem la connexió (el Model)
$config = require 'config.php';
$sql = new Sql($config);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Recollim dades
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $pass = $_POST['password'] ?? '';
    $pass2 = $_POST['password_confirm'] ?? '';

    // Validacions
    if ($name === '' || $email === '' || $pass === '' || $pass2 === '') {
        $errors[] = 'Rellena todos los campos.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Email no válido.';
    } elseif ($pass !== $pass2) {
        $errors[] = 'Las contraseñas no coinciden.';
    } else {
        // Comprovar si ja existeix (Model)
        $exists = $sql->getUserByEmail($email);
        if ($exists) {
            $errors[] = 'Ya existe una cuenta con ese email.';
        } else {
            // Crear usuari
            $hash = password_hash($pass, PASSWORD_DEFAULT);
            $id = $sql->createUser($name, $email, $hash);
            
            if ($id) {
                // Redirecció en format MVC
                header('Location: index.php?action=login&registered=1');
                exit;
            } else {
                $errors[] = 'Error al crear la cuenta.';
            }
        }
    }
}

// Finalment, carreguem la vista
require 'views/register.php';