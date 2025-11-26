<?php
// controllers/rankings.php

// Aquest controlador s'encarrega de generar i mostrar una pàgina de rànquings d'usuaris.
// Flux de treball:
// 1. Comprova que l'usuari estigui autenticat per accedir a aquesta secció.
// 2. Executa una consulta complexa a la base de dades per obtenir una llista classificada d'usuaris.
//    - La classificació es basa principalment en la valoració mitjana dels usuaris.
//    - Com a criteri de desempat, s'utilitza el nombre total de valoracions rebudes.
// 3. Passa les dades obtingudes a la vista 'views/rankings.php' perquè les mostri en format de taula.

require_once 'config.php';
require_once 'classes/Sql.php';

// Validació de sessió: l'usuari ha d'estar autenticat per veure els rànquings.
if (empty($_SESSION['user'])) {
    header('Location: index.php?action=login');
    exit;
}

// Carreguem la configuració i instanciem la classe per a les consultes a la base de dades.
$config = require 'config.php';
$sql = new Sql($config);

// Obtenim el rànquing de conductors.
// La consulta selecciona usuaris que tinguin una valoració superior a 0 i els ordena de la següent manera:
// 1. Per valoració mitjana (de més alta a més baixa).
// 2. En cas d'empat en la valoració, per nombre de valoracions rebudes (de més a menys).
// També s'obté el nombre total de rutes creades per cada usuari.
$rankings = $sql->select(
    "SELECT u.correu, u.nom, u.valoracio, 
            COUNT(v.id) as num_valoracions, -- Compta quantes valoracions ha rebut l'usuari.
            (SELECT COUNT(*) FROM rutes WHERE user_email = u.correu) as num_rutes -- Subconsulta per comptar les rutes creades.
     FROM usuaris u
     LEFT JOIN valoracions v ON LOWER(v.rated_user_email) = LOWER(u.correu) -- Unim amb valoracions per poder comptar-les.
     WHERE u.valoracio > 0 -- Només incloem usuaris que ja han estat valorats.
     GROUP BY u.correu, u.nom, u.valoracio -- Agrupem per usuari per poder fer els recomptes.
     ORDER BY u.valoracio DESC, num_valoracions DESC -- Criteri d'ordenació principal i de desempat.
     LIMIT 50" // Limitem el resultat als 50 millors usuaris.
);

// Carreguem la vista, que s'encarregarà de recórrer la variable $rankings i mostrar les dades.
require 'views/rankings.php';