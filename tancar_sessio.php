<?php
// Simple logout: destruir sessió i redirigir a la pàgina d'inici de sessió
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Opcional: preparar un missatge curt per mostrar després
$_SESSION['flash'] = 'Sessió tancada correctament.';

// Netejar variables de sessió
$_SESSION = [];

// Esborrar cookie de sessió
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destruir sessió
session_destroy();

// Redirigir a la pàgina d'inici de sessió (en català)
header('Location: login.php');
exit;
?>
