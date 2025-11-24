<!doctype html>
<html lang="ca">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>El meu perfil - CarSharing</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style_global.css">
</head>
<body class="bg-light">
    <?php require_once 'includes/header.php'; ?>

    <main class="container py-5">
        <div class="mb-5">
            <h1 class="mb-1"><i class="bi bi-person-circle me-2"></i>El meu perfil</h1>
            <p class="text-muted">Gestiona les teves dades i historial</p>
        </div>

        <?php if (!empty($_SESSION['flash'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($_SESSION['flash']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['flash']); ?>
        <?php endif; ?>

        <div class="row">
            <div class="col-12 col-lg-3">
                <div class="card mb-4 text-center">
                    <div class="card-body">
                        <div style="width:80px; height:80px; margin:0 auto 1rem; border-radius:50%; background:linear-gradient(135deg,#0d6efd,#0b5ed7); display:flex; align-items:center; justify-content:center; color:white; font-size:2rem; font-weight:700;">
                            <?= strtoupper(substr($user['nom'] ?? '', 0, 1)) ?>
                        </div>
                        <h5><?= htmlspecialchars($user['nom']) ?></h5>
                        <p class="text-muted small"><?= htmlspecialchars($user['correu']) ?></p>
                        <div class="mb-3">
                            <?php 
                            $stars = round($user['valoracio'] ?? 0);
                            for ($i = 0; $i < 5; $i++): 
                            ?>
                                <i class="bi <?= $i < $stars ? 'bi-star-fill' : 'bi-star' ?>" style="color:<?= $i < $stars ? '#ffc107' : '#ddd' ?>"></i>
                            <?php endfor; ?>
                            <span class="ms-2 fw-bold"><?= number_format($user['valoracio'] ?? 0, 2) ?>/5</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-9">
                <ul class="nav nav-tabs mb-4" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="dades-tab" data-bs-toggle="tab" data-bs-target="#dades" type="button" role="tab">
                            <i class="bi bi-pencil me-2"></i>Dades
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="rutes-tab" data-bs-toggle="tab" data-bs-target="#rutes" type="button" role="tab">
                            <i class="bi bi-map me-2"></i>Les meves rutes (<?= count($rutesCreades) ?>)
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="reserves-tab" data-bs-toggle="tab" data-bs-target="#reserves" type="button" role="tab">
                            <i class="bi bi-ticket me-2"></i>Les meves reserves (<?= count($rutesReservades) ?>)
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="valoracions-tab" data-bs-toggle="tab" data-bs-target="#valoracions" type="button" role="tab">
                            <i class="bi bi-star me-2"></i>Valoracions (<?= count($valoracions) ?>)
                        </button>
                    </li>
                </ul>

                <div class="tab-content">
                    <div class="tab-pane fade show active" id="dades" role="tabpanel">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="mb-3">Editar les meves dades</h5>
                                <form method="post" action="index.php?action=perfil">
                                    <div class="mb-3">
                                        <label class="form-label">Nom</label>
                                        <input type="text" class="form-control" name="nom" value="<?= htmlspecialchars($user['nom']) ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Correu</label>
                                        <input type="email" class="form-control" value="<?= htmlspecialchars($user['correu']) ?>" disabled>
                                        <small class="text-muted">El correu no es pot canviar</small>
                                    </div>
                                    <div class="mb-4">
                                        <label class="form-label">Nova contrasenya (deixa en blanc per no canviar)</label>
                                        <input type="password" class="form-control" name="password" placeholder="Introdueix nova contrasenya">
                                    </div>
                                    <button type="submit" class="btn btn-primary"><i class="bi bi-check me-2"></i>Guardar canvis</button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="rutes" role="tabpanel">
                        <div class="card">
                            <div class="card-body">
                                <?php if (empty($rutesCreades)): ?>
                                    <p class="text-muted">No has creat cap ruta encara.</p>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Origen → Destí</th>
                                                    <th>Data</th>
                                                    <th>Places</th>
                                                    <th>Estat</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($rutesCreades as $r): ?>
                                                    <tr>
                                                        <td><?= htmlspecialchars($r['origin']) ?> → <?= htmlspecialchars($r['destination']) ?></td>
                                                        <td><?= date('d/m/Y H:i', strtotime($r['date_time'])) ?></td>
                                                        <td><?= (int)$r['seats'] ?></td>
                                                        <td><span class="badge <?= $r['available'] ? 'bg-success' : 'bg-danger' ?>"><?= $r['available'] ? 'Activa' : 'Inactiva' ?></span></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="reserves" role="tabpanel">
                        <div class="card">
                            <div class="card-body">
                                <?php if (empty($rutesReservades)): ?>
                                    <p class="text-muted">No has reservat cap ruta encara.</p>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Origen → Destí</th>
                                                    <th>Conductor</th>
                                                    <th>Data</th>
                                                    <th>Places</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($rutesReservades as $r): ?>
                                                    <tr>
                                                        <td><?= htmlspecialchars($r['origin']) ?> → <?= htmlspecialchars($r['destination']) ?></td>
                                                        <td><?= htmlspecialchars($r['driver']) ?></td>
                                                        <td><?= date('d/m/Y H:i', strtotime($r['date_time'])) ?></td>
                                                        <td><?= (int)$r['seats'] ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="valoracions" role="tabpanel">
                        <div class="card">
                            <div class="card-body">
                                <?php if (empty($valoracions)): ?>
                                    <p class="text-muted">No has rebut valoracions encara.</p>
                                <?php else: ?>
                                    <?php foreach ($valoracions as $v): 
                                        $stars = round($v['rating']);
                                    ?>
                                        <div class="border-bottom pb-3 mb-3">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div>
                                                    <h6 class="mb-1"><?= htmlspecialchars($v['rater_name']) ?></h6>
                                                    <div class="mb-2">
                                                        <?php for ($i = 0; $i < 5; $i++): ?>
                                                            <i class="bi <?= $i < $stars ? 'bi-star-fill' : 'bi-star' ?>" style="color:<?= $i < $stars ? '#ffc107' : '#ddd' ?>; font-size:0.9rem;"></i>
                                                        <?php endfor; ?>
                                                    </div>
                                                    <?php if ($v['comment']): ?>
                                                        <p class="small text-muted mb-0"><?= htmlspecialchars($v['comment']) ?></p>
                                                    <?php endif; ?>
                                                </div>
                                                <small class="text-muted"><?= date('d/m/Y', strtotime($v['created_at'])) ?></small>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
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