<!doctype html>
<html lang="ca">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Les meves rutes - CarSharing</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style_global.css">
    <link rel="stylesheet" href="css/style_mis_rutes_professional.css">
</head>
<body class="bg-light">
    <?php require_once 'includes/header.php'; ?>

    <main class="container py-5">
        <div class="mb-5">
            <h1 class="mb-1">Les meves rutes</h1>
            <p class="text-muted">Gestiona les rutes que has creat</p>
        </div>

        <?php if (empty($rutes)): ?>
            <div class="empty-state">
                <div class="empty-state-icon">
                    <i class="bi bi-inbox"></i>
                </div>
                <h3 class="empty-state-title">No tens rutes publicades</h3>
                <p class="empty-state-text">Comença a compartir viatges creant una nova ruta</p>
                <a href="index.php?action=afegir_ruta" class="add-route-btn">
                    <i class="bi bi-plus-circle"></i>
                    Afegir nova ruta
                </a>
            </div>
        <?php else: ?>
            <div class="rutes-list">
                <?php foreach ($rutes as $rute): ?>
                    <div class="rute-card mb-4">
                        <div class="rute-card-body p-3 border rounded bg-white">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <h5 class="mb-1">
                                        <i class="bi bi-geo-alt-fill me-2"></i>
                                        <?= htmlspecialchars($rute['origin']) ?> <span class="mx-2">→</span> <?= htmlspecialchars($rute['destination']) ?>
                                    </h5>
                                </div>
                                <div class="text-end">
                                    <span class="status-badge <?= $rute['available'] ? 'active' : 'inactive' ?>">
                                        <i class="bi <?= $rute['available'] ? 'bi-check-circle' : 'bi-x-circle' ?> me-1"></i>
                                        <?= $rute['available'] ? 'Activa' : 'Inactiva' ?>
                                    </span>
                                </div>
                            </div>

                            <div class="d-flex flex-wrap mb-3 gap-3 text-muted small">
                                <div><i class="bi bi-calendar-event me-1"></i><?= date('d/m/Y H:i', strtotime($rute['date_time'])) ?></div>
                                <div><i class="bi bi-people me-1"></i><?= (int)$rute['seats'] ?> places</div>
                                <div><i class="bi bi-coin me-1"></i><?= (int)($rute['token_cost'] ?? 0) ?> tokens</div>
                                <div><i class="bi bi-clock-history me-1"></i>Publicada: <?= date('d/m/Y', strtotime($rute['created_at'])) ?></div>
                            </div>

                            <?php if (!empty($rute['description'])): ?>
                                <div class="rute-description mb-3 text-muted">
                                    <strong>Descripció:</strong> <?= nl2br(htmlspecialchars($rute['description'])) ?>
                                </div>
                            <?php endif; ?>

                            <div class="d-flex gap-2">
                                <a href="index.php?action=editar_ruta&id=<?= $rute['id'] ?>" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-pencil me-1"></i>Editar
                                </a>

                                <form method="post" action="index.php?action=mis_rutes" style="display:inline;" onsubmit="return confirm('Segur que vols eliminar aquesta ruta?');">
                                    <input type="hidden" name="id" value="<?= $rute['id'] ?>">
                                    <input type="hidden" name="post_action" value="delete"> <button type="submit" class="btn btn-sm btn-outline-danger">
                                        <i class="bi bi-trash me-1"></i>Eliminar
                                    </button>
                                </form>

                                <a href="index.php?action=route_details&id=<?= $rute['id'] ?>" class="btn btn-sm btn-outline-secondary ms-auto">
                                    <i class="bi bi-eye me-1"></i>Detalls
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="mt-4 text-center">
                <a href="index.php?action=afegir_ruta" class="btn btn-primary">
                    <i class="bi bi-plus-circle me-1"></i>Afegir nova ruta
                </a>
            </div>
        <?php endif; ?>

        <div class="mt-4 text-center">
            <a href="index.php?action=menu" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-2"></i>Tornar al menú
            </a>
        </div>
    </main>

    <?php require_once 'includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>