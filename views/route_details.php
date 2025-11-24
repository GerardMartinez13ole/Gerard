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
    <style>
    .rating-input {
        display: flex;
        flex-direction: row-reverse;
        justify-content: flex-end;
        gap: 0.5rem;
    }
    .rating-input input { display: none; }
    .star-label {
        font-size: 1.5rem;
        color: #ddd;
        cursor: pointer;
        transition: color 0.2s;
    }
    .rating-input input:checked ~ label,
    .rating-input label:hover,
    .rating-input label:hover ~ label { color: #ffc107; }
    </style>
</head>
<body class="bg-light">
    <?php require_once 'includes/header.php'; ?>

    <main class="container py-5">
        <div class="mb-5">
            <h1 class="h4">Detalls de la ruta</h1>
            <p class="text-muted">Informació completa del viatge</p>
        </div>

        <?php if (empty($ruta)): ?>
            <div class="alert alert-warning alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle me-2"></i>
                <strong>Ruta no trobada</strong>
                <a href="index.php?action=rutes_disponibles" class="alert-link ms-2">Tornar a rutes disponibles</a>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php else: ?>
            <div class="route-header">
                <h2><i class="bi bi-geo-alt-fill"></i> Ruta disponible</h2>
                <div class="route-locations">
                    <div class="location-box">
                        <div class="location-label">Sortida</div>
                        <div class="location-name"><?= htmlspecialchars($ruta['origin']) ?></div>
                    </div>
                    <div class="route-arrow"><i class="bi bi-arrow-right"></i></div>
                    <div class="location-box">
                        <div class="location-label">Destí</div>
                        <div class="location-name"><?= htmlspecialchars($ruta['destination']) ?></div>
                    </div>
                </div>
            </div>

            <div class="route-details-grid">
                <div class="detail-card date">
                    <div class="detail-icon"><i class="bi bi-calendar-event"></i></div>
                    <div class="detail-label">Data i hora</div>
                    <div class="detail-value"><?= date('d/m/Y H:i', strtotime($ruta['date_time'])) ?></div>
                </div>
                <div class="detail-card seats">
                    <div class="detail-icon"><i class="bi bi-people"></i></div>
                    <div class="detail-label">Places disponibles</div>
                    <div class="detail-value"><?= (int)$ruta['seats'] ?> <?= $ruta['seats'] == 1 ? 'plaça' : 'places' ?></div>
                </div>
                <div class="detail-card created">
                    <div class="detail-icon"><i class="bi bi-clock-history"></i></div>
                    <div class="detail-label">Publicada</div>
                    <div class="detail-value"><?= date('d/m/Y', strtotime($ruta['created_at'])) ?></div>
                </div>
            </div>

            <?php if (!empty($ruta['description'])): ?>
                <div class="description-section">
                    <h4><i class="bi bi-info-circle"></i> Descripció del viatge</h4>
                    <div class="description-text"><?= htmlspecialchars($ruta['description']) ?></div>
                </div>
            <?php endif; ?>

            <div class="driver-card">
                <div class="driver-header">
                    <div class="driver-avatar"><?= strtoupper(substr($ruta['driver_name'] ?? '?', 0, 1)) ?></div>
                    <div class="driver-info">
                        <h5><?= htmlspecialchars($ruta['driver_name'] ?? '—') ?></h5>
                        <div class="rating">
                            <?php 
                            $ratingVal = (float)($ruta['driver_valoracio'] ?? 0);
                            $stars = round($ratingVal);
                            for ($i = 0; $i < 5; $i++): 
                                echo ($i < $stars) ? '<i class="bi bi-star-fill"></i>' : '<i class="bi bi-star"></i>';
                            endfor;
                            ?>
                            <span class="ms-2"><?= number_format($ratingVal, 1) ?>/5.0</span>
                        </div>
                    </div>
                </div>

                <div class="driver-email">
                    <strong><i class="bi bi-envelope me-2"></i>Contacte:</strong><br>
                    <a href="mailto:<?= htmlspecialchars($ruta['user_email']) ?>"><?= htmlspecialchars($ruta['user_email']) ?></a>
                </div>

                <div class="route-actions">
                    <?php 
                    // Preparem URLs MVC
                    $chatUrl = 'index.php?action=chat&route_id=' . urlencode($ruta['id']) . '&participant=' . urlencode($ruta['driver_email']);
                    $mapUrl = 'index.php?action=mapa&focus_route=' . urlencode($ruta['id']);
                    $editUrl = 'index.php?action=editar_ruta&id=' . $ruta['id'];
                    ?>
                    
                    <?php if (!empty($_SESSION['user']) && $_SESSION['user']['correu'] === ($ruta['driver_email'] ?? null)): ?>
                        <a href="<?= $editUrl ?>" class="btn-action btn-edit"><i class="bi bi-pencil"></i>Editar Ruta</a>
                        
                        <form method="post" action="index.php?action=mis_rutes" style="display:contents;" onsubmit="return confirm('Segur que vols eliminar aquesta ruta?');">
                            <input type="hidden" name="id" value="<?= $ruta['id'] ?>">
                            <input type="hidden" name="action_type" value="delete"> <button type="submit" class="btn-action btn-delete"><i class="bi bi-trash"></i>Eliminar</button>
                        </form>
                    <?php else: ?>
                        <?php if (!empty($_SESSION['user'])): ?>
                            <a href="<?= $chatUrl ?>" class="btn-action btn-chat"><i class="bi bi-chat-dots"></i>Xatejar</a>
                        <?php endif; ?>
                    <?php endif; ?>
                    
                    <a href="<?= $mapUrl ?>" class="btn-action btn-details"><i class="bi bi-map"></i>Veure al mapa</a>

                    <?php if (!empty($_SESSION['user']) && strtolower($_SESSION['user']['correu']) !== strtolower($ruta['driver_email'] ?? '')): 
                        if (!$hasReservation): 
                            $seatsAvailable = (int)($ruta['seats'] ?? 0) > 0;
                    ?>
                        <form method="post" action="index.php?action=route_details&id=<?= (int)$ruta['id'] ?>" class="d-inline-block ms-2" onsubmit="return confirm('Confirmes la reserva?');">
                            <input type="hidden" name="post_action" value="reserve">
                            <input type="hidden" name="route_id" value="<?= (int)$ruta['id'] ?>">
                            <button type="submit" class="btn btn-success btn-action" <?= $seatsAvailable ? '' : 'disabled' ?>>
                                <i class="bi bi-check2-circle"></i> <?= $seatsAvailable ? 'Reservar plaça' : 'Sense places' ?>
                            </button>
                        </form>
                    <?php endif; endif; ?>
                </div>
            </div>

            <?php if (!empty($_SESSION['flash'])): ?>
                <div class="mt-3 alert alert-info"><?= htmlspecialchars($_SESSION['flash']) ?></div>
                <?php unset($_SESSION['flash']); ?>
            <?php endif; ?>

            <?php if ($hasReservation && !empty($_SESSION['user']) && strtolower($_SESSION['user']['correu']) !== strtolower($ruta['driver_email'] ?? '')): ?>
                <div class="card mb-4" style="border-left: 5px solid #ffc107;">
                    <div class="card-body">
                        <h5 class="mb-3">
                            <i class="bi bi-star-fill text-warning me-2"></i>
                            <?= $currentRating ? 'Actualitza la teva valoració' : 'Valora aquest viatge' ?>
                        </h5>
                        <form method="post" action="index.php?action=add_rating">
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
                                <textarea name="comment" class="form-control" rows="3"><?= htmlspecialchars($currentRating['comment'] ?? '') ?></textarea>
                            </div>
                            <button type="submit" class="btn btn-warning"><i class="bi bi-star-fill me-2"></i>Enviar</button>
                        </form>
                    </div>
                </div>
            <?php elseif (!$hasReservation && !empty($_SESSION['user']) && strtolower($_SESSION['user']['correu']) !== strtolower($ruta['driver_email'] ?? '')): ?>
                <div class="alert alert-info mb-4">Per valorar aquesta ruta, primer has de fer una reserva.</div>
            <?php endif; ?>

            <div class="text-center mt-5">
                <a href="index.php?action=rutes_disponibles" class="btn btn-outline-secondary btn-lg">
                    <i class="bi bi-arrow-left me-2"></i>Tornar a rutes disponibles
                </a>
            </div>
        <?php endif; ?>
    </main>
    <?php require_once 'includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>