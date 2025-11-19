<?php
session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/classes/Sql.php';

$config = require __DIR__ . '/config.php';
$sql = new Sql($config);

// AFEGIT: manejador de reserva (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reserve') {
    // Comprovar usuari
    if (empty($_SESSION['user'])) {
        $_SESSION['flash'] = 'Cal iniciar sessió per fer una reserva.';
        header('Location: route_details.php?id=' . urlencode($_POST['route_id'] ?? $id));
        exit;
    }
    $user_email = strtolower(trim($_SESSION['user']['correu'] ?? ''));
    $route_id = isset($_POST['route_id']) ? (int)$_POST['route_id'] : 0;

    // Recarregar dades ruta per a validacions
    $routeCheck = $sql->fetch("SELECT id, user_email, seats FROM rutes WHERE id = ?", [$route_id]);
    if (empty($routeCheck)) {
        $_SESSION['flash'] = 'Ruta no trobada.';
        header('Location: rutes_disponibles.php');
        exit;
    }

    // No pot reservar el mateix conductor
    if (strtolower($routeCheck['user_email'] ?? '') === $user_email) {
        $_SESSION['flash'] = 'No pots reservar la teva pròpia ruta.';
        header('Location: route_details.php?id=' . $route_id);
        exit;
    }

    // Comprovar si ja té reserva
    $existing = $sql->fetch("SELECT id FROM reservas WHERE route_id = ? AND LOWER(user_email) = ?", [$route_id, $user_email]);
    if (!empty($existing)) {
        $_SESSION['flash'] = 'Ja tens una reserva per aquesta ruta.';
        header('Location: route_details.php?id=' . $route_id);
        exit;
    }

    // Comprovar places disponibles
    if ((int)($routeCheck['seats'] ?? 0) <= 0) {
        $_SESSION['flash'] = 'No hi ha places disponibles per aquesta ruta.';
        header('Location: route_details.php?id=' . $route_id);
        exit;
    }

    // Inserir reserva i decrementar places (amb transacció si el wrapper ho permet)
    try {
        // Intentar executar; si el teu Sql té un mètode diferent, canvia execute per l'adequat
        $sql->execute("INSERT INTO reservas (route_id, user_email, created_at) VALUES (?, ?, NOW())", [$route_id, $user_email]);
        $sql->execute("UPDATE rutes SET seats = seats - 1 WHERE id = ? AND seats > 0", [$route_id]);

        $_SESSION['flash'] = 'Reserva realitzada correctament.';
    } catch (Exception $e) {
        $_SESSION['flash'] = 'Error en realitzar la reserva. Torna-ho a intentar.';
    }

    header('Location: route_details.php?id=' . $route_id);
    exit;
}

// obtenir id
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header('Location: rutes_disponibles.php');
    exit;
}

// buscar ruta amb dades del conductor
$ruta = $sql->fetch(
    "SELECT r.*, u.nom AS driver_name, u.valoracio AS driver_valoracio, u.correu AS driver_email 
     FROM rutes r 
     JOIN usuaris u ON r.user_email = u.correu 
     WHERE r.id = ?",
    [$id]
);

// Comprovar si l'usuari actual ha reservat aquesta ruta
$hasReservation = false;
$currentRating = null;
if (!empty($_SESSION['user'])) {
    $user_email = strtolower(trim($_SESSION['user']['correu'] ?? ''));
    
    // Comprovar si té reserva
    $reservation = $sql->fetch(
        "SELECT id FROM reservas WHERE route_id = ? AND LOWER(user_email) = ?",
        [$id, $user_email]
    );
    $hasReservation = !empty($reservation);
    
    // Comprovar si ja ha fet una valoració
    $rating = $sql->fetch(
        "SELECT id, rating, comment FROM valoracions 
         WHERE route_id = ? AND LOWER(rater_email) = ? AND LOWER(rated_user_email) = ?",
        [$id, $user_email, strtolower($ruta['driver_email'] ?? '')]
    );
    $currentRating = $rating;
}

?>
<!doctype html>
<html lang="ca">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Detalls de la ruta - CarSharing</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style_global.css">
    <link rel="stylesheet" href="css/style_route_details_professional.css">
</head>
<body class="bg-light">
    <?php require_once __DIR__ . '/includes/header.php'; ?>

    <main class="container py-5">
        <div class="mb-5">
            <h1 class="h4">Detalls de la ruta</h1>
            <p class="text-muted">Informació completa del viatge</p>
        </div>

        <?php if (empty($ruta)): ?>
            <div class="alert alert-warning alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle me-2"></i>
                <strong>Ruta no trobada</strong>
                <a href="rutes_disponibles.php" class="alert-link ms-2">Tornar a rutes disponibles</a>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php else: ?>
            <!-- ROUTE HEADER -->
            <div class="route-header">
                <h2>
                    <i class="bi bi-geo-alt-fill"></i>
                    Ruta disponible
                </h2>
                <div class="route-locations">
                    <div class="location-box">
                        <div class="location-label">Sortida</div>
                        <div class="location-name"><?= htmlspecialchars($ruta['origin']) ?></div>
                    </div>
                    <div class="route-arrow">
                        <i class="bi bi-arrow-right"></i>
                    </div>
                    <div class="location-box">
                        <div class="location-label">Destí</div>
                        <div class="location-name"><?= htmlspecialchars($ruta['destination']) ?></div>
                    </div>
                </div>
            </div>

            <!-- ROUTE DETAILS GRID -->
            <div class="route-details-grid">
                <div class="detail-card date">
                    <div class="detail-icon">
                        <i class="bi bi-calendar-event"></i>
                    </div>
                    <div class="detail-label">Data i hora</div>
                    <div class="detail-value"><?= date('d/m/Y H:i', strtotime($ruta['date_time'])) ?></div>
                </div>

                <div class="detail-card seats">
                    <div class="detail-icon">
                        <i class="bi bi-people"></i>
                    </div>
                    <div class="detail-label">Places disponibles</div>
                    <div class="detail-value"><?= (int)$ruta['seats'] ?> <?= $ruta['seats'] == 1 ? 'plaça' : 'places' ?></div>
                </div>

                <div class="detail-card created">
                    <div class="detail-icon">
                        <i class="bi bi-clock-history"></i>
                    </div>
                    <div class="detail-label">Publicada</div>
                    <div class="detail-value"><?= date('d/m/Y', strtotime($ruta['created_at'])) ?></div>
                </div>
            </div>

            <!-- DESCRIPTION SECTION -->
            <?php if (!empty($ruta['description'])): ?>
                <div class="description-section">
                    <h4>
                        <i class="bi bi-info-circle"></i>
                        Descripció del viatge
                    </h4>
                    <div class="description-text"><?= htmlspecialchars($ruta['description']) ?></div>
                </div>
            <?php endif; ?>

            <!-- DRIVER INFO CARD -->
            <div class="driver-card">
                <div class="driver-header">
                    <div class="driver-avatar">
                        <?= strtoupper(substr($ruta['driver_name'] ?? '?', 0, 1)) ?>
                    </div>
                    <div class="driver-info">
                        <h5><?= htmlspecialchars($ruta['driver_name'] ?? '—') ?></h5>
                        <div class="rating">
                            <?php 
                            $rating = (float)($ruta['driver_valoracio'] ?? 0);
                            $stars = round($rating);
                            for ($i = 0; $i < 5; $i++): 
                                if ($i < $stars): ?>
                                    <i class="bi bi-star-fill"></i>
                                <?php else: ?>
                                    <i class="bi bi-star"></i>
                                <?php endif;
                            endfor;
                            ?>
                            <span class="ms-2"><?= number_format($rating, 1) ?>/5.0</span>
                        </div>
                    </div>
                </div>

                <div class="driver-email">
                    <strong><i class="bi bi-envelope me-2"></i>Contacte:</strong><br>
                    <a href="mailto:<?= htmlspecialchars($ruta['user_email']) ?>">
                        <?= htmlspecialchars($ruta['user_email']) ?>
                    </a>
                </div>

                <!-- ACTION BUTTONS -->
                <div class="route-actions">
                    <?php 
                    $chatUrl = 'chat.php?route_id=' . urlencode($ruta['id']) . '&participant=' . urlencode($ruta['driver_email']);
                    $mapUrl = 'mapa.php?focus_route=' . urlencode($ruta['id']);
                    $tokenCost = (int)($ruta['token_cost'] ?? 0);
                    ?>
                    
                    <?php if (!empty($_SESSION['user']) && $_SESSION['user']['correu'] === ($ruta['driver_email'] ?? null)): ?>
                        <!-- PROPIETARIO DE LA RUTA -->
                        <a href="editar_ruta.php?id=<?= $ruta['id'] ?>" class="btn-action btn-edit">
                            <i class="bi bi-pencil"></i>Editar Ruta
                        </a>
                        <form method="post" action="mis_rutes.php" style="display:contents;" onsubmit="return confirm('Segur que vols eliminar aquesta ruta?');">
                            <input type="hidden" name="id" value="<?= $ruta['id'] ?>">
                            <input type="hidden" name="action" value="delete">
                            <button type="submit" class="btn-action btn-delete">
                                <i class="bi bi-trash"></i>Eliminar
                            </button>
                        </form>
                    <?php else: ?>
                        <!-- ALTRES USUARIS -->
                        <?php if (!empty($_SESSION['user'])): ?>
                            <a href="<?= htmlspecialchars($chatUrl) ?>" class="btn-action btn-chat">
                                <i class="bi bi-chat-dots"></i>Xatejar amb el conductor
                            </a>
                        <?php endif; ?>
                    <?php endif; ?>
                    <!-- AFEGIT: botó per obrir la ruta al mapa -->
                    <a href="<?= htmlspecialchars($mapUrl) ?>" class="btn-action btn-details">
                        <i class="bi bi-map"></i>Veure al mapa
                    </a>

                    <!-- AFEGIT: formulari de reserva per a usuaris (només si no són conductor i no tenen reserva i hi ha places) -->
                    <?php if (!empty($_SESSION['user']) && strtolower($_SESSION['user']['correu']) !== strtolower($ruta['driver_email'] ?? '')): 
                        // comprovar si l'usuari ja té reserva (ja calculat abans a $hasReservation)
                        if (!$hasReservation): 
                            $seatsAvailable = (int)($ruta['seats'] ?? 0) > 0;
                    ?>
                        <form method="post" class="d-inline-block ms-2" onsubmit="return confirm('Confirmes la reserva d\\'aquesta plaça?');">
                            <input type="hidden" name="action" value="reserve">
                            <input type="hidden" name="route_id" value="<?= (int)$ruta['id'] ?>">
                            <button type="submit" class="btn btn-success btn-action" <?= $seatsAvailable ? '' : 'disabled' ?>>
                                <i class="bi bi-check2-circle"></i>
                                <?= $seatsAvailable ? 'Reservar plaça' : 'Sense places' ?>
                            </button>
                        </form>
                    <?php endif; endif; ?>
                </div>

    <!-- abans del final del <main> afegir missatge flash si existeix -->
    <?php if (!empty($_SESSION['flash'])): ?>
        <div class="mt-3">
            <div class="alert alert-info"><?= htmlspecialchars($_SESSION['flash']) ?></div>
        </div>
        <?php unset($_SESSION['flash']); ?>
    <?php endif; ?>

            <!-- RATING SECTION - només per usuaris que han reservat i no són el conductor -->
            <?php if ($hasReservation && !empty($_SESSION['user']) && strtolower($_SESSION['user']['correu']) !== strtolower($ruta['driver_email'] ?? '')): ?>
                <div class="card mb-4" style="border-left: 5px solid #ffc107;">
                    <div class="card-body">
                        <h5 class="mb-3">
                            <i class="bi bi-star-fill text-warning me-2"></i>
                            <?php if ($currentRating): ?>
                                Actualitza la teva valoració
                            <?php else: ?>
                                Valora aquest viatge
                            <?php endif; ?>
                        </h5>
                        <form method="post" action="add_rating.php">
                            <input type="hidden" name="route_id" value="<?= (int)$ruta['id'] ?>">
                            <input type="hidden" name="rated_user" value="<?= htmlspecialchars($ruta['driver_email']) ?>">
                            
                            <div class="mb-3">
                                <label class="form-label">Valoració (1-5 estrelles)</label>
                                <div class="rating-input">
                                    <?php for ($i = 5; $i >= 1; $i--): 
                                        $checked = $currentRating && $currentRating['rating'] == $i ? 'checked' : '';
                                    ?>
                                        <input type="radio" id="star<?= $i ?>" name="rating" value="<?= $i ?>" <?= $checked ?> required>
                                        <label for="star<?= $i ?>" class="star-label"><i class="bi bi-star-fill"></i></label>
                                    <?php endfor; ?>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Comentari (opcional)</label>
                                <textarea name="comment" class="form-control" rows="3" placeholder="Comparteix la teva experiència..."><?php if ($currentRating) echo htmlspecialchars($currentRating['comment']); ?></textarea>
                            </div>

                            <button type="submit" class="btn btn-warning">
                                <i class="bi bi-star-fill me-2"></i>
                                <?php if ($currentRating): ?>
                                    Actualitzar valoració
                                <?php else: ?>
                                    Enviar valoració
                                <?php endif; ?>
                            </button>
                        </form>
                    </div>
                </div>
            <?php elseif (!$hasReservation && !empty($_SESSION['user']) && strtolower($_SESSION['user']['correu']) !== strtolower($ruta['driver_email'] ?? '')): ?>
                <!-- Missatge si no ha reservat -->
                <div class="alert alert-info mb-4">
                    <i class="bi bi-info-circle me-2"></i>
                    <strong>Per valorar aquesta ruta</strong>, primer has de fer una reserva.
                </div>
            <?php endif; ?>

            <!-- BACK BUTTON -->
            <div class="text-center mt-5">
                <a href="rutes_disponibles.php" class="btn btn-outline-secondary btn-lg">
                    <i class="bi bi-arrow-left me-2"></i>Tornar a rutes disponibles
                </a>
            </div>
        



</main> 
    <?php require_once __DIR__ . '/includes/footer.php'; ?>           <?php endif; ?>
    <style>
    .rating-input {
        display: flex;
        flex-direction: row-reverse;
        justify-content: flex-end;
        gap: 0.5rem;
    }

    .rating-input input {
        display: none;
    }

    .star-label {
        font-size: 1.5rem;
        color: #ddd;
        cursor: pointer;
        transition: color 0.2s;
    }

    .rating-input input:checked ~ label,
    .rating-input label:hover,
    .rating-input label:hover ~ label {
        color: #ffc107;
    }
    </style>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>