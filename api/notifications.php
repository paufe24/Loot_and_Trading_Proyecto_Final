<?php
session_start();
require_once dirname(__DIR__) . '/includes/db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['unread' => 0, 'items' => []]); exit;
}
$user_id = (int)$_SESSION['user_id'];

$action = $_GET['action'] ?? $_POST['action'] ?? 'count';

if ($action === 'mark_read') {
    $conn->query("UPDATE price_alerts SET read_at=NOW() WHERE user_id=$user_id AND triggered_at IS NOT NULL AND read_at IS NULL");
    echo json_encode(['ok' => true]); exit;
}

if ($action === 'list') {
    $res = $conn->query(
        "SELECT card_name, card_game, target_price, triggered_at, read_at
         FROM price_alerts
         WHERE user_id=$user_id AND triggered_at IS NOT NULL
         ORDER BY triggered_at DESC LIMIT 15"
    );
    $items = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    $unread = (int)$conn->query(
        "SELECT COUNT(*) FROM price_alerts WHERE user_id=$user_id AND triggered_at IS NOT NULL AND read_at IS NULL"
    )->fetch_row()[0];
    echo json_encode(['unread' => $unread, 'items' => $items]); exit;
}

// count (default)
$res   = $conn->query("SELECT COUNT(*) FROM price_alerts WHERE user_id=$user_id AND triggered_at IS NOT NULL AND read_at IS NULL");
$count = (int)$res->fetch_row()[0];
echo json_encode(['unread' => $count]);
