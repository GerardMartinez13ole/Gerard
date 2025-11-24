<!doctype html>
<html lang="ca">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Les Meves Converses - CarSharing</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style_global.css">
</head>
<body class="bg-light">
    <?php require_once 'includes/header.php'; ?>
    <main class="container py-5">
        <div class="mb-4">
            <h1 class="mb-1">Les meves converses</h1>
            <p class="text-muted">Xats recents amb conductors i passatgers</p>
        </div>

        <?php if (!empty($_SESSION['flash'])): ?>
            <div class="alert alert-info alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($_SESSION['flash']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['flash']); ?>
        <?php endif; ?>

        <?php if (empty($conversations)): ?>
            <div class="alert alert-info">No tens converses actives.</div>
        <?php else: ?>
            <div class="list-group">
                <?php foreach ($conversations as $c): 
                    $other = $c['other_email'] ?? '';
                    $otherName = $c['other_name'] ?? $other;
                    $routeLabel = (!empty($c['origin']) && !empty($c['destination'])) ? htmlspecialchars($c['origin'] . ' → ' . $c['destination']) : 'Ruta #' . (int)$c['route_id'];
                    
                    // CONSTRUCCIÓ DE L'ENLLAÇ MVC
                    // abans: chat.php?route_id=...
                    $chatUrl = 'index.php?action=chat&route_id=' . urlencode($c['route_id']) . '&participant=' . urlencode($other);
                ?>
                    <div class="list-group-item d-flex justify-content-between align-items-start p-3" style="border-radius:8px; margin-bottom:0.75rem; box-shadow:0 2px 8px rgba(0,0,0,0.05);">
                        <div style="flex:1;">
                            <div class="fw-bold"><?= htmlspecialchars($otherName) ?></div>
                            <div class="small text-muted"><?= $routeLabel ?></div>
                            <div class="small text-truncate mt-1" style="max-width:300px;"><?= htmlspecialchars($c['last_message']) ?></div>
                        </div>
                        <div class="text-end ms-2">
                            <div class="small text-muted"><?= date('d/m H:i', strtotime($c['last_at'])) ?></div>
                            <div class="mt-2 d-flex gap-2">
                                <a href="<?= htmlspecialchars($chatUrl) ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-chat-dots"></i></a>
                                
                                <form method="post" action="index.php?action=converses" style="display:inline;" onsubmit="return confirm('Segur que vols eliminar aquesta conversa? Totes els missatges s\'esborraran.');">
                                    <input type="hidden" name="post_action" value="delete">
                                    <input type="hidden" name="route_id" value="<?= (int)$c['route_id'] ?>">
                                    <input type="hidden" name="participant" value="<?= htmlspecialchars($other) ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
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