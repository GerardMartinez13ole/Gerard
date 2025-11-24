<!doctype html>
<html lang="ca">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Les meves reserves - CarSharing</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style_global.css">
    <link rel="stylesheet" href="css/style_mis_rutes_professional.css">
</head>
<body class="bg-light">
    <?php require_once 'includes/header.php'; ?>
    <main class="container py-5">
        <div class="mb-4">
            <h1 class="mb-1">Les meves reserves</h1>
            <p class="text-muted">Gestiona les teves reserves</p>
        </div>

        <?php if (!empty($_SESSION['flash'])): ?>
            <div class="alert alert-info alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($_SESSION['flash']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['flash']); ?>
        <?php endif; ?>

        <?php if (empty($reservas)): ?>
            <div class="alert alert-info">No tens reserves actives.</div>
        <?php else: ?>
            <div class="rutes-list">
                <?php foreach ($reservas as $r): ?>
                    <div class="rute-card">
                        <div class="rute-card-body">
                            <div class="rute-header">
                                <div class="rute-route">
                                    <i class="bi bi-geo-alt-fill rute-route-icon"></i>
                                    <div class="rute-route-info">
                                        <h5><?= htmlspecialchars($r['origin']) ?> <span class="route-arrow mx-2">→</span> <?= htmlspecialchars($r['destination']) ?></h5>
                                    </div>
                                </div>
                                <div class="rute-status">
                                    <span class="status-badge active"><i class="bi bi-check-circle me-1"></i>Reservada</span>
                                </div>
                            </div>

                            <div class="rute-details">
                                <div class="rute-detail">
                                    <div class="rute-detail-icon"><i class="bi bi-calendar-event"></i></div>
                                    <div class="rute-detail-info">
                                        <div class="rute-detail-label">Data i hora</div>
                                        <div class="rute-detail-value"><?= date('d/m/Y H:i', strtotime($r['date_time'])) ?></div>
                                    </div>
                                </div>
                                <div class="rute-detail">
                                    <div class="rute-detail-icon"><i class="bi bi-people"></i></div>
                                    <div class="rute-detail-info">
                                        <div class="rute-detail-label">Places restants</div>
                                        <div class="rute-detail-value"><?= (int)$r['seats'] ?></div>
                                    </div>
                                </div>
                            </div>

                            <div class="rute-actions">
                                <a href="index.php?action=route_details&id=<?= $r['id'] ?>" class="btn-rute-action btn-details"><i class="bi bi-eye"></i>Detalls</a>

                                <form method="post" action="index.php?action=les_meves_reserves" style="display:contents;" onsubmit="return confirm('Segur que vols cancel·lar aquesta reserva?');">
                                    <input type="hidden" name="route_id" value="<?= $r['id'] ?>">
                                    <input type="hidden" name="post_action" value="cancel">
                                    <button type="submit" class="btn-rute-action btn-delete"><i class="bi bi-x-circle"></i>Cancel·lar</button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="mt-4">
            <a href="index.php?action=menu" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-2"></i>Tornar al menú</a>
        </div>
    </main>
    <?php require_once 'includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>