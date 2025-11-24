<?php
// index.php - FRONT CONTROLLER

// 1. Iniciem sessió (necessari per a tota l'app)
session_start();

// 2. Enrutador bàsic: Mirem què vol fer l'usuari
// Si no hi ha cap acció definida, per defecte anem a 'inici'
$action = $_GET['action'] ?? 'inici';

// 3. Switch per decidir quin controlador o vista carregar
switch ($action) {
    case 'inici':
        // Com que la pàgina d'inici és estàtica, podem carregar la vista directament
        // o cridar a un controlador si necessitessim dades.
        require 'views/inici.php';
        break;

    case 'login':
        // Aquí cridarem al controlador de login (ho farem quan em passis l'arxiu iniciar_sessio.php)
        // Per ara, posem un placeholder:
        require 'controllers/login.php';
        break;

    case 'register':
        // Aquí cridarem al controlador de registre
        require 'controllers/register.php';
        break;

    case 'menu':
        require 'controllers/menu.php';
        break;

    case 'rutes_disponibles':
        require 'controllers/rutes_disponibles.php';
        break;

    case 'route_details':
        require 'controllers/route_details.php';
        break;

    case 'mis_rutes':
        require 'controllers/mis_rutes.php';
        break;

    case 'afegir_ruta':
        require 'controllers/afegir_ruta.php';
        break;

    case 'editar_ruta':
        require 'controllers/editar_ruta.php';
        break;

    case 'add_rating':
        require 'controllers/add_rating.php';
        break;

    case 'converses':
        require 'controllers/converses.php';
        break;

    case 'chat':
        require 'controllers/chat.php';
        break;

    case 'perfil':
        require 'controllers/perfil.php';
        break;

    case 'les_meves_reserves':
        require 'controllers/les_meves_reserves.php';
        break;

    case 'mapa':
        require 'controllers/mapa.php';
        break;

    case 'rankings':
        require 'controllers/rankings.php';
        break;

    case 'comprar_tokens':
        require 'controllers/comprar_tokens.php';
        break;

    case 'logout':
        // Podem fer la lògica aquí directament o crear un arxiu controllers/logout.php
        // Com que és molt curt, ho podem posar aquí:
        session_destroy();
        header('Location: index.php?action=login');
        exit;
        break;
        
    default:
        // Pàgina 404 si l'acció no existeix
        echo "Pàgina no trobada";
        break;
}