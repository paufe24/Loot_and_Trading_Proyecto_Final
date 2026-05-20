<?php
require_once dirname(__DIR__) . '/includes/session.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/csrf.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['unread' => 0, 'items' => []]); exit;
}
$user_id = (int)$_SESSION['user_id'];

$action = $_GET['action'] ?? $_POST['action'] ?? 'count';

if ($action === 'mark_read') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') csrf_verify();
    $st = $conn->prepare("UPDATE price_alerts SET read_at=NOW() WHERE user_id=? AND triggered_at IS NOT NULL AND read_at IS NULL");
    $st->bind_param("i", $user_id);
    $st->execute();
    $st->close();
    echo json_encode(['ok' => true]); exit;
}

if ($action === 'list') {
    $st = $conn->prepare(
        "SELECT card_name, card_game, target_price, triggered_at, read_at
         FROM price_alerts
         WHERE user_id=? AND triggered_at IS NOT NULL
         ORDER BY triggered_at DESC LIMIT 15"
    );
    $st->bind_param("i", $user_id);
    $st->execute();
    $items = $st->get_result()->fetch_all(MYSQLI_ASSOC);
    $st->close();

    $st2 = $conn->prepare("SELECT COUNT(*) FROM price_alerts WHERE user_id=? AND triggered_at IS NOT NULL AND read_at IS NULL");
    $st2->bind_param("i", $user_id);
    $st2->execute();
    $st2->bind_result($unread);
    $st2->fetch();
    $st2->close();
    echo json_encode(['unread' => $unread, 'items' => $items]); exit;
}

// count (default)
$st = $conn->prepare("SELECT COUNT(*) FROM price_alerts WHERE user_id=? AND triggered_at IS NOT NULL AND read_at IS NULL");
$st->bind_param("i", $user_id);
$st->execute();
$st->bind_result($count);
$st->fetch();
$st->close();
echo json_encode(['unread' => $count]);
