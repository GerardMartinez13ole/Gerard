<?php
session_start();
// Página principal després de iniciar sessió
if (empty($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$user = $_SESSION['user'];
?>
<!doctype html>
<html lang="ca">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Menú Principal - CarSharing</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style_global.css">
</head>

<body>
    <?php require_once __DIR__ . '/includes/header.php'; ?>

    <main class="container py-5">
        <div class="mb-5">
            <h1 class="mb-1">Menú Principal</h1>
            <p class="text-muted">Gestiona els teus viatges i connexions</p>
        </div>

        <div class="row g-3">
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 text-center">
                    <div class="card-body">
                        <i class="bi bi-geo-alt text-primary" style="font-size: 2rem;"></i>
                        <h5 class="card-title mt-3">Rutes Disponibles</h5>
                        <p class="card-text text-muted">Explora totes les rutes disponibles</p>
                        <a href="rutes_disponibles.php" class="btn btn-primary mt-3">Veure Rutes</a>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 text-center">
                    <div class="card-body">
                        <i class="bi bi-plus-circle text-success" style="font-size: 2rem;"></i>
                        <h5 class="card-title mt-3">Afegir Ruta</h5>
                        <p class="card-text text-muted">Crea una nova ruta de viatge</p>
                        <a href="afegir_ruta.php" class="btn btn-primary mt-3">Crear Ruta</a>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-lg-4">
                <div class="card h-100 text-center">
                    <div class="card-body">
                        <i class="bi bi-list-check text-info" style="font-size: 2rem;"></i>
                        <h5 class="card-title mt-3">Les Meves Rutes</h5>
                        <p class="card-text text-muted">Gestiona les teves rutes publicades</p>
                        <a href="mis_rutes.php" class="btn btn-primary mt-3">Les Meves Rutes</a>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-lg-4">
                <div class="card h-100 text-center">
                    <div class="card-body">
                        <i class="bi bi-chat-dots text-warning" style="font-size: 2rem;"></i>
                        <h5 class="card-title mt-3">Converses</h5>
                        <p class="card-text text-muted">Xateja amb altres usuaris</p>
                        <a href="converses.php" class="btn btn-primary mt-3">Converses</a>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-lg-4">
                <div class="card h-100 text-center">
                    <div class="card-body">
                        <i class="bi bi-map text-danger" style="font-size: 2rem;"></i>
                        <h5 class="card-title mt-3">Mapa</h5>
                        <p class="card-text text-muted">Visualitza les rutes en un mapa</p>
                        <a href="mapa.php" class="btn btn-primary mt-3">Veure Mapa</a>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-lg-4">
                <div class="card h-100 text-center">
                    <div class="card-body">
                        <i class="bi bi-ticket-perforated text-success" style="font-size: 2rem;"></i>
                        <h5 class="card-title mt-3">Les Meves Reserves</h5>
                        <p class="card-text text-muted">Gestiona i cancel·la les teves reserves</p>
                        <a href="les_meves_reserves.php" class="btn btn-primary mt-3">Veure Reserves</a>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-lg-4">
                <div class="card h-100 text-center">
                    <div class="card-body">
                        <i class="bi bi-person-circle text-info" style="font-size: 2rem;"></i>
                        <h5 class="card-title mt-3">El meu perfil</h5>
                        <p class="card-text text-muted">Gestiona les teves dades i historial</p>
                        <a href="perfil.php" class="btn btn-primary mt-3">Veure perfil</a>>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-lg-4">
                <div class="card h-100 text-center">
                    <div class="card-body">
                        <i class="bi bi-trophy-fill text-warning" style="font-size: 2rem;"></i>
                        <h5 class="card-title mt-3">Ranking</h5>
                        <p class="card-text text-muted">Veu els millors conductors</p>
                        <a href="rankings.php" class="btn btn-primary mt-3">Veure Ranking</a>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-lg-4">
                <div class="card h-100 text-center">
                    <div class="card-body">
                        <i class="bi bi-box-arrow-right text-secondary" style="font-size: 2rem;"></i>
                        <h5 class="card-title mt-3">Tancar Sessió</h5>
                        <p class="card-text text-muted">Tanca la teva sessió actual</p>
                        <a href="tancar_sessio.php" class="btn btn-outline-secondary mt-3">Sortir</a>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php require_once __DIR__ . '/includes/footer.php'; ?>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>