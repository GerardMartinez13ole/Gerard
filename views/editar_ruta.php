<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Editar ruta - CarSharing</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style_global.css">
</head>
<body class="bg-light">
    <?php require_once 'includes/header.php'; ?>

    <main class="container py-5">
        <div class="mb-5">
            <h1 class="mb-1">Editar ruta</h1>
            <p class="text-muted">Modifica los detalles de tu ruta</p>
        </div>

        <div class="row justify-content-center">
            <div class="col-12 col-md-8">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <ul class="mb-0"><?php foreach ($errors as $e) echo '<li>'.htmlspecialchars($e).'</li>'; ?></ul>
                            </div>
                        <?php endif; ?>

                        <form method="post" action="index.php?action=editar_ruta&id=<?= $id ?>">
                            <div class="mb-3">
                                <label class="form-label">Origen</label>
                                <input type="text" name="origin" class="form-control" required value="<?= htmlspecialchars($_POST['origin'] ?? $rute['origin']) ?>">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Destino</label>
                                <input type="text" name="destination" class="form-control" required value="<?= htmlspecialchars($_POST['destination'] ?? $rute['destination']) ?>">
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Fecha</label>
                                    <input type="date" name="date" class="form-control" required value="<?= htmlspecialchars($_POST['date'] ?? date('Y-m-d', strtotime($rute['date_time']))) ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Hora</label>
                                    <input type="time" name="time" class="form-control" required value="<?= htmlspecialchars($_POST['time'] ?? date('H:i', strtotime($rute['date_time']))) ?>">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Plazas disponibles</label>
                                <input type="number" name="seats" class="form-control" min="1" max="8" value="<?= htmlspecialchars($_POST['seats'] ?? $rute['seats']) ?>">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Descripción (opcional)</label>
                                <textarea name="description" class="form-control" rows="3"><?= htmlspecialchars($_POST['description'] ?? $rute['description']) ?></textarea>
                            </div>

                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" value="1" id="available" name="available" 
                                    <?= (isset($_POST['available']) ? ($_POST['available'] ? 'checked' : '') : ($rute['available'] ? 'checked' : '')) ?>>
                                <label class="form-check-label" for="available">Ruta disponible</label>
                            </div>

                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Guardar</button>
                                <a href="index.php?action=mis_rutes" class="btn btn-outline-secondary">Cancelar</a>
                            </div>
                        </form>

                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php require_once 'includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>