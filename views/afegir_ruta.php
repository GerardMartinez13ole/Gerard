<!DOCTYPE html>
<html lang="ca">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Afegir Ruta - CarSharing</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style_global.css">
    <link rel="stylesheet" href="css/style_form_professional.css">
</head>
<body class="bg-light">
    <?php require_once 'includes/header.php'; ?>

    <main class="container py-5">
        <div class="mb-5">
            <h1 class="mb-1">Afegir Nova Ruta</h1>
            <p class="text-muted">Crea una nova ruta i comparteix el teu viatge</p>
        </div>

        <div class="row justify-content-center">
            <div class="col-12 col-md-8 col-lg-6">
                <div class="form-container">
                    <div class="form-header">
                        <div class="form-icon">
                            <i class="bi bi-plus-circle"></i>
                        </div>
                        <h2>Nova Ruta</h2>
                        <p class="mb-0 mt-2" style="opacity: 0.9;">Completa el formulari per publicar el teu viatge</p>
                    </div>

                    <div class="form-content">
                        <?php if (!empty($errors)): ?>
                            <div class="form-alert alert alert-danger">
                                <strong><i class="bi bi-exclamation-circle me-2"></i>Error:</strong>
                                <ul class="mb-0">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?= htmlspecialchars($error) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <form method="post" action="index.php?action=afegir_ruta" novalidate>
                            <div class="form-group">
                                <label class="form-label">
                                    <i class="bi bi-geo-alt-fill form-icon-small"></i>
                                    Origen
                                </label>
                                <input type="text" name="origin" class="form-control" 
                                       placeholder="Ex: Lleida" required
                                       value="<?= htmlspecialchars($origin ?? '') ?>">
                                <div class="form-hint">
                                    <i class="bi bi-info-circle"></i>
                                    Indica la ciutat o lloc de sortida
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="form-label">
                                    <i class="bi bi-geo-alt form-icon-small"></i>
                                    Destí
                                </label>
                                <input type="text" name="destination" class="form-control"
                                       placeholder="Ex: Barcelona" required
                                       value="<?= htmlspecialchars($destination ?? '') ?>">
                                <div class="form-hint">
                                    <i class="bi bi-info-circle"></i>
                                    Indica el destí final del viatge
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="form-label">
                                    <i class="bi bi-calendar-event form-icon-small"></i>
                                    Data i Hora
                                </label>
                                <div class="row form-row">
                                    <div class="col-md-6">
                                        <input type="date" name="date" class="form-control" required
                                               min="<?= date('Y-m-d') ?>"
                                               value="<?= htmlspecialchars($date ?? '') ?>">
                                        <div class="form-hint">
                                            <i class="bi bi-calendar"></i>
                                            Data
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <input type="time" name="time" class="form-control" required
                                               value="<?= htmlspecialchars($time ?? '') ?>">
                                        <div class="form-hint">
                                            <i class="bi bi-clock"></i>
                                            Hora
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="form-label">
                                    <i class="bi bi-people-fill form-icon-small"></i>
                                    Places Disponibles
                                </label>
                                <input type="number" name="seats" class="form-control" 
                                       min="1" max="8" required
                                       value="<?= htmlspecialchars($seats ?? '1') ?>">
                                <div class="form-hint">
                                    <i class="bi bi-info-circle"></i>
                                    Selecciona quants passatgers poden viatjar amb tu (1-8)
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="form-label">
                                    <i class="bi bi-chat-left-text form-icon-small"></i>
                                    Descripció (Opcional)
                                </label>
                                <textarea name="description" class="form-control"
                                          placeholder="Explica als altres passatgers detalls del viatge..."
                                ><?= htmlspecialchars($description ?? '') ?></textarea>
                                <div class="form-hint">
                                    <i class="bi bi-info-circle"></i>
                                    Proporciona informació útil per als passatgers
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="form-label">
                                    <i class="bi bi-coin form-icon-small"></i>
                                    Cost en tokens (Opcional)
                                </label>
                                <input type="number" name="token_cost" class="form-control" min="0" value="<?= htmlspecialchars($token_cost ?? '0') ?>">
                                <div class="form-hint">
                                    <i class="bi bi-info-circle"></i>
                                    Indica quants tokens costarà reservar aquesta ruta per usuari (0 = gratuït)
                                </div>
                            </div>

                            <div class="form-actions">
                                <button type="submit" class="btn-submit">
                                    <i class="bi bi-plus-circle"></i>
                                    Publicar Ruta
                                </button>
                                <a href="index.php?action=menu" class="btn-back">
                                    <i class="bi bi-arrow-left"></i>
                                    Tornar al Menú
                                </a>
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