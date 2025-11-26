<?php
// controllers/login.php

// Aquest fitxer gestiona la lògica del formulari de login:
// - rep les dades POST del formulari
// - valida els camps
// - crida a la classe Sql per verificar l'usuari
// - en cas d'èxit guarda l'usuari a la sessió i redirecciona al menú
// - en cas d'error prepara missatges per a la vista

// Incloem la configuració i la classe que fa les consultes SQL
require_once 'config.php';
require_once 'classes/Sql.php';

// Carreguem la configuració; $config contindrà, per exemple, dades de connexió a la BD
$config = require 'config.php';

// Instanciem l'objecte que exposa mètodes per consultar la base de dades
$sql = new Sql($config);

// Array per acumular missatges d'error que s'enviaran a la vista
$errors = [];

// Comprovem si venim d'un registre exitós per mostrar un missatge informatiu a la vista
$registered = isset($_GET['registered']);

// PROCESSAMENT DEL FORMULARI
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Netejem espais al correu i obtenim la contrasenya tal qual (no la modifiquem aquí)
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Validació bàsica: camp buit
    if ($email === '' || $password === '') {
        // Afegim un missatge d'error que la vista podrà mostrar
        $errors[] = 'Rellena todos los campos.';
    } else {
        // Aquí cridem el mètode que comprova si l'usuari existeix i si la contrasenya és correcta.
        // Assumpció: verifyUser retorna la fila d'usuari (array/object) en cas d'èxit o false/null en cas contrari.
        $user = $sql->verifyUser($email, $password);
        if ($user) {
            // IMPORTANT: la sessió s'inicia a index.php segons l'estructura del projecte
            // Guardem les dades mínimes de l'usuari a la sessió perquè altres pàgines puguin saber qui està autenticat
            $_SESSION['user'] = $user;
            
            // Redirecció MVC cap al menú principal (evita re-enviament del formulari amb refresh)
            header('Location: index.php?action=menu');
            exit;
        } else {
            // Credencials incorrectes: afegim missatge perquè la vista l'informi a l'usuari
            $errors[] = 'Email o contraseña incorrectos.';
        }
    }
}

// Carreguem la vista del formulari de login que utilitzarà $errors i $registered
require 'views/login.php';