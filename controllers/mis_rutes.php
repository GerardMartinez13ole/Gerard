<?php
// controllers/mis_rutes.php

require_once 'config.php';
require_once 'classes/Sql.php';

// Verificació de seguretat
if (empty($_SESSION['user'])) {
    header('Location: index.php?action=login');
    exit;
}

$config = require 'config.php';
$sql = new Sql($config);

$user_email = $_SESSION['user']['correu'] ?? null;
if (!$user_email) {
    // Si per algun motiu la sessió no té correu, fora
    header('Location: index.php?action=login&error=no_user_email');
    exit;
}

// 1. GESTIONAR ELIMINACIÓ (Soft Delete)
// Busquem 'post_action' perquè és el que hem posat al formulari de la vista
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['post_action']) && $_POST['post_action'] === 'delete' && !empty($_POST['id'])) {
    $id = (int)$_POST['id'];
    
    // Executar eliminació lògica
    $sql->execute("UPDATE rutes SET available = 0 WHERE id = ? AND user_email = ?", [$id, $user_email]);
    
    // Recarregar la pàgina (patró PRG)
    header('Location: index.php?action=mis_rutes');
    exit;
}

// 2. OBTENIR RUTES (Només les actives)
$rutes = $sql->select("SELECT * FROM rutes WHERE user_email = ? AND available = 1 ORDER BY date_time DESC", [$user_email]);

// 3. CARREGAR VISTA
require 'views/mis_rutes.php';