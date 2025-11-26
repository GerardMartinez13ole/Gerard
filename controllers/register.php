<?php
// controllers/register.php

// Aquest controlador gestiona el procés de registre d'un nou usuari.
// Flux de treball:
// 1. Si la petició és GET, simplement mostra el formulari de registre.
// 2. Si la petició és POST, processa les dades del formulari:
//    a. Recull i neteja les dades (nom, correu, contrasenyes).
//    b. Realitza validacions: camps buits, format de correu, coincidència de contrasenyes.
//    c. Comprova si el correu electrònic ja existeix a la base de dades.
//    d. Si tot és correcte, xifra la contrasenya i crea el nou usuari.
//    e. Redirigeix a la pàgina de login amb un missatge d'èxit.
//    f. Si hi ha errors, els acumula per mostrar-los a la vista.
// 3. Carrega la vista 'views/register.php', passant-li els errors i les dades introduïdes.

require_once 'config.php';
require_once 'classes/Sql.php';

// Inicialitzem variables per a la vista. Això evita errors de "variable no definida"
// i permet repoblar el formulari en cas d'error per millorar l'experiència d'usuari.
$errors = [];
$name = '';
$email = '';

// Instanciem la classe per a les consultes a la base de dades.
$config = require 'config.php';
$sql = new Sql($config);

// Comprovem si la petició és de tipus POST, la qual cosa indica que s'està enviant el formulari de registre.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Recollim i netegem les dades del formulari.
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $pass = $_POST['password'] ?? '';
    $pass2 = $_POST['password_confirm'] ?? '';

    // Realitzem una sèrie de validacions sobre les dades rebudes.
    if ($name === '' || $email === '' || $pass === '' || $pass2 === '') {
        $errors[] = 'Rellena todos los campos.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Email no válido.';
    } elseif ($pass !== $pass2) {
        $errors[] = 'Las contraseñas no coinciden.';
    } else {
        // Si les validacions bàsiques són correctes, comprovem si el correu ja està registrat.
        $exists = $sql->getUserByEmail($email);
        if ($exists) {
            $errors[] = 'Ya existe una cuenta con ese email.';
        } else {
            // Si el correu no existeix, procedim a crear l'usuari.
            // Xifrem la contrasenya per seguretat abans de guardar-la. Mai guardis contrasenyes en text pla.
            $hash = password_hash($pass, PASSWORD_DEFAULT);
            $id = $sql->createUser($name, $email, $hash);
            
            if ($id) {
                // Si la creació ha tingut èxit, redirigim a la pàgina de login.
                // El paràmetre 'registered=1' permetrà a la vista de login mostrar un missatge d'èxit.
                header('Location: index.php?action=login&registered=1');
                exit;
            } else {
                $errors[] = 'Error al crear la cuenta.';
            }
        }
    }
}

// Finalment, carreguem la vista. La vista 'views/register.php' utilitzarà les variables
// $errors, $name i $email per mostrar missatges d'error o mantenir els valors al formulari.
require 'views/register.php';