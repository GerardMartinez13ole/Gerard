<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Chat - Ruta <?= htmlspecialchars($route['origin'] . ' → ' . $route['destination']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style_global.css">
    <link rel="stylesheet" href="css/style_chat_professional.css">
</head>
<body class="bg-light">
    <?php require_once 'includes/header.php'; ?>

    <main class="container py-5">
        <div class="mb-4">
            <h1 class="h4">Chat</h1>
            <p class="text-muted">Conversa segura amb altres usuaris</p>
        </div>

        <div class="row justify-content-center">
            <div class="col-12 col-lg-8">
                <?php if ($me === $driver_email && empty($participant)): ?>
                    <div class="card">
                        <div class="card-body">
                            <h5 class="mb-4">Conversaciones actives</h5>
                            <?php if (empty($participants)): ?>
                                <div class="text-center text-muted py-4">
                                    <i class="bi bi-chat-dots" style="font-size: 2rem;"></i>
                                    <p class="mt-2">No hi ha converses encara</p>
                                </div>
                            <?php else: ?>
                                <ul class="list-group">
                                    <?php foreach ($participants as $p): ?>
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            <div class="d-flex align-items-center">
                                                <div class="participant-avatar me-2">
                                                    <?= strtoupper(substr($p, 0, 1)) ?>
                                                </div>
                                                <span><?= htmlspecialchars($p) ?></span>
                                            </div>
                                            <a href="index.php?action=chat&route_id=<?= $route_id ?>&participant=<?= urlencode($p) ?>" 
                                               class="btn btn-sm btn-primary">
                                                <i class="bi bi-chat-dots me-1"></i>Abrir
                                            </a>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </div>
                    </div>

                <?php else: ?>
                    <div class="chat-container">
                        <div class="chat-header">
                            <div>
                                <h5><?= htmlspecialchars($route['origin']) ?> → <?= htmlspecialchars($route['destination']) ?></h5>
                                <small style="opacity: 0.8;">Ruta del <?= date('d/m/Y', strtotime($route['date_time'])) ?></small>
                            </div>
                            <div class="participant-info">
                                <div class="participant-avatar">
                                    <?= strtoupper(substr(!empty($participant) ? $participant : $driver_email, 0, 1)) ?>
                                </div>
                                <div class="d-none d-md-block">
                                    <small style="opacity: 0.8;">Conectat amb</small><br>
                                    <?= htmlspecialchars(substr(!empty($participant) ? $participant : $driver_email, 0, 20)) ?>
                                </div>
                            </div>
                        </div>

                        <div class="chat-body">
                            <?php if (empty($messages)): ?>
                                <div class="chat-empty">
                                    <div>
                                        <i class="bi bi-chat-dots"></i>
                                        <p>Sé el primer a escriure un missatge</p>
                                    </div>
                                </div>
                            <?php else: ?>
                                <?php foreach ($messages as $m): ?>
                                    <?php $is_me = ($m['sender_email'] === $me); ?>
                                    <div class="chat-message <?= $is_me ? 'me' : 'other' ?>">
                                        <div class="message-content">
                                            <div class="message-bubble">
                                                <?= nl2br(htmlspecialchars($m['message'])) ?>
                                            </div>
                                            <div class="message-meta">
                                                <?= date('d/m H:i', strtotime($m['created_at'])) ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>

                        <div class="chat-footer">
                            <form method="post" action="index.php?action=chat&route_id=<?= $route_id ?><?php if (!empty($participant)) echo '&participant=' . urlencode($participant); ?>" class="w-100 d-flex gap-2">
                                <input type="hidden" name="receiver" value="<?= htmlspecialchars(!empty($participant) ? $participant : $driver_email) ?>">
                                <textarea name="message" class="form-control" rows="2" placeholder="Escriu un missatge..." required></textarea>
                                <button type="submit" class="btn-send">
                                    <i class="bi bi-send"></i>
                                    <span class="d-none d-sm-inline">Enviar</span>
                                </button>
                            </form>
                        </div>
                    </div>

                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger mt-3">
                            <?php foreach ($errors as $e) echo htmlspecialchars($e) . '<br>'; ?>
                        </div>
                    <?php endif; ?>

                    <div class="mt-4 text-center">
                        <a href="index.php?action=route_details&id=<?= $route_id ?>" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left me-2"></i>Tornar a la ruta
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <?php require_once 'includes/footer.php'; ?>

    <script>
        const chatBody = document.querySelector('.chat-body');
        if (chatBody) {
            chatBody.scrollTop = chatBody.scrollHeight;
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>