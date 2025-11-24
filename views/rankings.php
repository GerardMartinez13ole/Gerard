<!doctype html>
<html lang="ca">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Ranking de conductors - CarSharing</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style_global.css">
</head>
<body class="bg-light">
    <?php require_once 'includes/header.php'; ?>

    <main class="container py-5">
        <div class="mb-5">
            <h1 class="mb-1"><i class="bi bi-trophy me-2 text-warning"></i>Ranking de conductors</h1>
            <p class="text-muted">Els millors conductors de la comunitat CarSharing</p>
        </div>

        <?php if (!empty($_SESSION['flash'])): ?>
            <div class="alert alert-info alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($_SESSION['flash']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['flash']); ?>
        <?php endif; ?>

        <div class="row">
            <div class="col-12 col-lg-10 offset-lg-1">
                <?php if (empty($rankings)): ?>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>No hi ha conductors valorats encara.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th style="width:5%">Posició</th>
                                    <th style="width:40%">Conductor</th>
                                    <th style="width:20%">Valoració</th>
                                    <th style="width:15%">Resenyes</th>
                                    <th style="width:20%">Rutes</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($rankings as $i => $r): 
                                    $stars = round($r['valoracio']);
                                ?>
                                    <tr>
                                        <td>
                                            <?php if ($i == 0): ?>
                                                <span class="badge bg-warning"><i class="bi bi-trophy-fill"></i> #1</span>
                                            <?php elseif ($i == 1): ?>
                                                <span class="badge bg-secondary"><i class="bi bi-award"></i> #2</span>
                                            <?php elseif ($i == 2): ?>
                                                <span class="badge bg-danger"><i class="bi bi-gem"></i> #3</span>
                                            <?php else: ?>
                                                <strong>#<?= $i + 1 ?></strong>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="avatar me-2" style="width:40px; height:40px; border-radius:50%; background:linear-gradient(135deg,#0d6efd,#0b5ed7); display:flex; align-items:center; justify-content:center; color:white; font-weight:700;">
                                                    <?= strtoupper(substr($r['nom'], 0, 1)) ?>
                                                </div>
                                                <strong><?= htmlspecialchars($r['nom']) ?></strong>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <?php for ($j = 0; $j < 5; $j++): ?>
                                                    <i class="bi <?= $j < $stars ? 'bi-star-fill' : 'bi-star' ?>" style="color:<?= $j < $stars ? '#ffc107' : '#ddd' ?>; font-size:0.9rem;"></i>
                                                <?php endfor; ?>
                                                <span class="ms-2 fw-bold"><?= number_format($r['valoracio'], 2) ?>/5</span>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark"><?= (int)$r['num_valoracions'] ?></span>
                                        </td>
                                        <td>
                                            <span class="badge bg-info"><?= (int)$r['num_rutes'] ?></span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>

                <div class="mt-4 text-center">
                    <a href="index.php?action=menu" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-2"></i>Tornar al menú
                    </a>
                </div>
            </div>
        </div>
    </main>

    <?php require_once 'includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>