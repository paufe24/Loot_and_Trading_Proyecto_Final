<?php
// api/price_alerts.php — CRUD alertas de precio
require_once dirname(__DIR__) . '/includes/session.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/csrf.php';
require_once dirname(__DIR__) . '/includes/gamification.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['ok' => false, 'message' => 'No autenticado']); exit;
}
$user_id = (int)$_SESSION['user_id'];

// Asegurar tabla
runGamificationMigrations($conn);

$action = $_POST['action'] ?? $_GET['action'] ?? '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') csrf_verify();

switch ($action) {
    case 'list':   listAlerts($conn, $user_id);   break;
    case 'create': createAlert($conn, $user_id);  break;
    case 'delete': deleteAlert($conn, $user_id);  break;
    default: echo json_encode(['ok' => false, 'message' => 'Acción desconocida']);
}

function listAlerts($conn, int $user_id): void {
    $st = $conn->prepare(
        "SELECT id, card_name, card_game, target_price, is_active, triggered_at, created_at
         FROM price_alerts WHERE user_id=? ORDER BY created_at DESC LIMIT 50"
    );
    $st->bind_param("i", $user_id);
    $st->execute();
    echo json_encode(['ok' => true, 'alerts' => $st->get_result()->fetch_all(MYSQLI_ASSOC)]);
}

function createAlert($conn, int $user_id): void {
    $card_name    = trim($_POST['card_name']    ?? '');
    $card_game    = trim($_POST['card_game']    ?? '');
    $target_price = (int)($_POST['target_price'] ?? 0);

    if ($card_name === '' || $target_price <= 0) {
        echo json_encode(['ok' => false, 'message' => 'Datos inválidos']); return;
    }

    // Límite: 10 alertas activas por usuario
    $count = (int)$conn->query("SELECT COUNT(*) FROM price_alerts WHERE user_id=$user_id AND is_active=1")->fetch_row()[0];
    if ($count >= 10) {
        echo json_encode(['ok' => false, 'message' => 'Máximo 10 alertas activas']); return;
    }

    $st = $conn->prepare(
        "INSERT INTO price_alerts (user_id, card_name, card_game, target_price) VALUES (?,?,?,?)"
    );
    $st->bind_param("issi", $user_id, $card_name, $card_game, $target_price);
    $st->execute();
    echo json_encode(['ok' => true, 'id' => $conn->insert_id, 'message' => 'Alerta creada']);
}

function deleteAlert($conn, int $user_id): void {
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) { echo json_encode(['ok' => false, 'message' => 'ID inválido']); return; }

    $st = $conn->prepare("DELETE FROM price_alerts WHERE id=? AND user_id=?");
    $st->bind_param("ii", $id, $user_id);
    $st->execute();
    echo json_encode(['ok' => $conn->affected_rows > 0]);
}
