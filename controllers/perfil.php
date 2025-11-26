<?php
// controllers/perfil.php

// Aquest controlador gestiona la pàgina de perfil de l'usuari.
// Flux de treball:
// 1. Comprova que l'usuari estigui autenticat.
// 2. Si rep una petició POST, processa l'actualització de les dades del perfil (nom i/o contrasenya).
// 3. Si la petició és GET (o després d'un POST), obté totes les dades necessàries per a la vista:
//    - Dades actualitzades de l'usuari.
//    - Llista de rutes creades per l'usuari.
//    - Llista de rutes reservades per l'usuari.
//    - Llista de valoracions rebudes per l'usuari.
// 4. Carrega la vista 'views/perfil.php' per mostrar tota la informació.

require_once 'config.php';
require_once 'classes/Sql.php';

// Validació de sessió: l'usuari ha d'estar autenticat per accedir al seu perfil.
if (empty($_SESSION['user'])) {
    header('Location: index.php?action=login');
    exit;
}

// Carreguem la configuració i instanciem la classe per a les consultes a la base de dades.
$config = require 'config.php';
$sql = new Sql($config);

// Obtenim el correu de l'usuari de la sessió i el normalitzem a minúscules per consistència.
$user_email = strtolower(trim($_SESSION['user']['correu'] ?? ''));

// 1. PROCESSAMENT DEL FORMULARI D'ACTUALITZACIÓ (via POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Recollim les dades del formulari.
    $newName = trim($_POST['nom'] ?? '');
    $newPass = $_POST['password'] ?? '';
    $errorsUpdate = [];

    // Validació bàsica: el nom no pot ser buit.
    if ($newName === '') {
        $errorsUpdate[] = 'El nom no pot estar buit.';
    }

    if (empty($errorsUpdate)) {
        try {
            // Si s'ha introduït una nova contrasenya, la xifrem i actualitzem nom i contrasenya.
            if ($newPass !== '') {
                $hash = password_hash($newPass, PASSWORD_DEFAULT);
                $sql->execute("UPDATE usuaris SET nom = ?, contrasenya = ? WHERE LOWER(correu) = ?", [$newName, $hash, $user_email]);
            } else {
                // Si no, actualitzem només el nom.
                $sql->execute("UPDATE usuaris SET nom = ? WHERE LOWER(correu) = ?", [$newName, $user_email]);
            }
            // Actualitzem el nom a la sessió per mantenir la consistència a tota l'aplicació (p. ex., a la capçalera).
            $_SESSION['user']['nom'] = $newName;
            $_SESSION['flash'] = 'Dades del perfil actualitzades correctament.';
            
            // Redirigim a la mateixa pàgina (patró PRG) per evitar reenviament del formulari.
            header('Location: index.php?action=perfil');
            exit;
        } catch (Exception $e) {
            // En cas d'error a la base de dades, preparem un missatge d'error.
            $errorsUpdate[] = 'Error actualitzant el perfil.';
        }
    }
    
    // Si hi ha hagut errors de validació o d'execució, els guardem a la sessió i redirigim.
    if (!empty($errorsUpdate)) {
        $_SESSION['flash'] = implode(' ', $errorsUpdate);
        header('Location: index.php?action=perfil');
        exit;
    }
}

// 2. OBTENCIÓ DE DADES PER A LA VISTA (via GET o després de la redirecció POST)

// Obtenim les dades més recents de l'usuari directament de la base de dades.
$user = $sql->fetch("SELECT * FROM usuaris WHERE LOWER(correu) = ?", [$user_email]);

// Obtenim totes les rutes que l'usuari ha creat, ordenades per data descendent.
$rutesCreades = $sql->select(
    "SELECT id, origin, destination, date_time, seats, available FROM rutes WHERE LOWER(user_email) = ? ORDER BY date_time DESC",
    [$user_email]
);

// Obtenim totes les rutes que l'usuari ha reservat, fent un JOIN amb les taules de rutes i usuaris per obtenir detalls.
$rutesReservades = $sql->select(
    "SELECT r.id, r.origin, r.destination, r.date_time, r.seats, u.nom as driver
     FROM reservas res
     JOIN rutes r ON res.route_id = r.id
     JOIN usuaris u ON r.user_email = u.correu
     WHERE LOWER(res.user_email) = ?
     ORDER BY r.date_time DESC",
    [$user_email]
);

// Obtenim totes les valoracions que altres usuaris han fet a l'usuari actual.
$valoracions = $sql->select(
    "SELECT v.*, u.nom as rater_name FROM valoracions v
     JOIN usuaris u ON LOWER(u.correu) = LOWER(v.rater_email)
     WHERE LOWER(v.rated_user_email) = ?
     ORDER BY v.created_at DESC",
    [$user_email]
);

// 3. CÀRREGA DE LA VISTA
// Finalment, s'inclou el fitxer de la vista, que utilitzarà les variables $user, $rutesCreades, $rutesReservades i $valoracions.
require 'views/perfil.php';