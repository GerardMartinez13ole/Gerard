<?php
// controllers/comprar_tokens.php

// Aquest controlador processa la compra de tokens per part d'un usuari.
// Flux resumit:
// - Comprova que l'usuari estigui autenticat.
// - Llegeix la quantitat enviada via POST.
// - Valida la quantitat i actualitza el saldo a la taula 'usuaris'.
// - Llegeix el nou saldo i l'actualitza a la sessió (per mostrar-lo al header).
// - Assigna un missatge flash i redirigeix al perfil.

// Carreguem configuració i classe d'accés a BD
require_once 'config.php';
require_once 'classes/Sql.php';

// Validació de sessió: l'usuari ha d'estar autenticat per comprar tokens.
// Si no hi ha sessió, redirigim al login.
if (empty($_SESSION['user'])) {
    header('Location: index.php?action=login');
    exit;
}

$config = require 'config.php';
$sql = new Sql($config);

// Normalitzem el correu de l'usuari des de la sessió.
// S'utilitza LOWER al fer consultes per evitar problemes de majúscules/minúscules.
$user_email = strtolower(trim($_SESSION['user']['correu'] ?? ''));
$errors = [];

// Si rebem POST, processem la compra.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Llegim la quantitat sol·licitada (cast a int per seguretat).
    $amount = (int)($_POST['amount'] ?? 0);
    
    // Validació simple: la quantitat ha de ser positiva.
    if ($amount <= 0) {
        $errors[] = 'Tria una quantitat vàlida.';
    }

    if (empty($errors)) {
        try {
            // 1. Actualitzar saldo a la BD: increments tokens existents.
            // S'executa una UPDATE amb placeholder per evitar injeccions.
            $sql->execute("UPDATE usuaris SET tokens = tokens + ? WHERE LOWER(correu) = ?", [$amount, $user_email]);
            
            // 2. Llegir el nou saldo per actualitzar la sessió i mostrar-ho immediatament.
            $row = $sql->fetch("SELECT tokens FROM usuaris WHERE LOWER(correu) = ?", [$user_email]);
            
            // 3. Actualitzar sessió (perquè el nou saldo es mostri al header/UX).
            $_SESSION['user']['tokens'] = (int)($row['tokens'] ?? 0);
            
            // Missatge informatiu de l'operació.
            $_SESSION['flash'] = 'Compra realitzada correctament: +' . $amount . ' tokens.';
            
            // 4. Redirigir al perfil de l'usuari després de l'èxit.
            header('Location: index.php?action=perfil');
            exit;
        } catch (Exception $e) {
            // En cas d'error (BD, connexió, etc.), afegim un error genèric.
            // No s'exposen detalls de l'excepció per motius de seguretat.
            $errors[] = 'Error actualitzant tokens. Torna-ho a intentar.';
        }
    }
}

// Finalment, carreguem la vista que conté el formulari per comprar tokens.
// La vista utilitza la variable $errors per mostrar missatges a l'usuari.
require 'views/comprar_tokens.php';