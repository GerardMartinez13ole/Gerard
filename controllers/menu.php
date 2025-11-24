<?php
// controllers/menu.php

// Verificació de seguretat: Si no està loguejat, fora.
if (empty($_SESSION['user'])) {
    header('Location: index.php?action=login');
    exit;
}

// Opcionalment pots recarregar dades de l'usuari aquí si fos necessari
// $user = $_SESSION['user']; 

// Carreguem la vista
require 'views/menu.php';