<?php
session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/classes/Sql.php';

if (empty($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$config = require __DIR__ . '/config.php';
$sql = new Sql($config);

// Normalize emails to avoid mismatches (trim + lowercase)
$me = strtolower(trim($_SESSION['user']['correu'] ?? ''));

// obtenir id
$route_id = isset($_GET['route_id']) ? (int)$_GET['route_id'] : 0;
if ($route_id <= 0) {
    header('Location: rutes_disponibles.php');
    exit;
}

// obtenir ruta i conductor
$route = $sql->fetch("SELECT r.*, u.nom AS driver_name, u.correu AS driver_email FROM rutes r JOIN usuaris u ON r.user_email = u.correu WHERE r.id = ?", [$route_id]);
if (!$route) {
    header('Location: rutes_disponibles.php');
    exit;
}

// normalitzar correu del driver
$driver_email = strtolower(trim($route['driver_email'] ?? ''));
$driver_name = $route['driver_name'] ?? '—';

// participant des de GET (opcional) i normalitzar
$participant = isset($_GET['participant']) ? strtolower(trim($_GET['participant'])) : null;

// Si l'usuari no és el conductor i no s'ha passat participant, el participant serà sempre el conductor
if ($me !== $driver_email && !$participant) {
    $participant = $driver_email;
}

// Si l'usuari és el conductor i no hi ha participant, mostrar llista de participants disponibles
$participants = [];
if ($me === $driver_email && !$participant) {
    // obtenir participants distincts normalitzats (excloure el conductor)
    $rows = $sql->select(
        "SELECT DISTINCT LOWER(email) AS participant FROM (
            SELECT sender_email AS email FROM messages WHERE route_id = ?
            UNION
            SELECT receiver_email AS email FROM messages WHERE route_id = ?
        ) t WHERE LOWER(email) != ?",
        [$route_id, $route_id, $driver_email]
    );
    foreach ($rows as $r) {
        if (!empty($r['participant'])) $participants[] = $r['participant'];
    }
}

// Proces envio de missatge
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message'])) {
    $msg = trim($_POST['message']);
    $receiver = strtolower(trim($_POST['receiver'] ?? ''));

    if ($msg === '') {
        $errors[] = 'El mensaje no puede estar vacío.';
    } elseif ($receiver === '' ) {
        $errors[] = 'Destinatari invàlid.';
    } else {
        try {
            // Inserir correus normalitzats
            $sql->insert("INSERT INTO messages (route_id, sender_email, receiver_email, message) VALUES (?, ?, ?, ?)", [$route_id, $me, $receiver, $msg]);
            // redirigir per evitar reenvío del form; si hi ha participant mantenir-lo a la query
            $qs = "route_id={$route_id}";
            if (!empty($participant)) $qs .= "&participant=" . urlencode($participant);
            header("Location: chat.php?{$qs}");
            exit;
        } catch (Exception $e) {
            $errors[] = 'Error al enviar el mensaje.';
        }
    }
}

// Si tenim participant, carregar missatges entre me i participant
$messages = [];
if (!empty($participant)) {
    $other = $participant;
    $messages = $sql->select(
        "SELECT * FROM messages 
         WHERE route_id = ? 
           AND (
             (LOWER(sender_email) = ? AND LOWER(receiver_email) = ?)
             OR
             (LOWER(sender_email) = ? AND LOWER(receiver_email) = ?)
           )
         ORDER BY created_at ASC",
        [$route_id, $me, $other, $other, $me]
    );
}
?>
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
    <?php require_once __DIR__ . '/includes/header.php'; ?>

    <main class="container py-5">
        <div class="mb-4">
            <h1 class="h4">Chat</h1>
            <p class="text-muted">Conversa segura amb altres usuaris</p>
        </div>

        <div class="row justify-content-center">
            <div class="col-12 col-lg-8">
                <?php if ($me === $driver_email && empty($participant)): ?>
                    <!-- LISTA DE PARTICIPANTES PARA EL CONDUCTOR -->
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
                                            <a href="chat.php?route_id=<?= $route_id ?>&participant=<?= urlencode($p) ?>" 
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
                    <!-- CHAT CONVERSATION -->
                    <div class="chat-container">
                        <!-- CHAT HEADER -->
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

                        <!-- CHAT BODY -->
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

                        <!-- CHAT FOOTER -->
                        <div class="chat-footer">
                            <form method="post" action="chat.php?route_id=<?= $route_id ?><?php if (!empty($participant)) echo '&participant=' . urlencode($participant); ?>" class="w-100 d-flex gap-2">
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
                        <a href="route_details.php?id=<?= $route_id ?>" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left me-2"></i>Tornar a la ruta
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <?php require_once __DIR__ . '/includes/footer.php'; ?>

    <!-- Auto-scroll al final del chat -->
    <script>
        const chatBody = document.querySelector('.chat-body');
        if (chatBody) {
            chatBody.scrollTop = chatBody.scrollHeight;
        }
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
