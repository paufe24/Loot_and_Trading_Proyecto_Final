<?php
session_start();

$isFetch = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'fetch';

if (!isset($_SESSION['user_id'])) {
    if ($isFetch) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(401);
        echo json_encode(['ok' => false, 'message' => 'Debes iniciar sesión']);
        exit;
    }
    header('Location: ../auth.php');
    exit;
}

require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/csrf.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') csrf_verify();

$userId = (int)$_SESSION['user_id'];
$action = $_POST['action'] ?? $_GET['action'] ?? '';

$conn->query("CREATE TABLE IF NOT EXISTS user_favorites (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    card_id VARCHAR(255) NOT NULL,
    card_name VARCHAR(255) NOT NULL,
    card_image VARCHAR(500) NOT NULL,
    card_game VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_user_card (user_id, card_id),
    INDEX idx_user_created (user_id, created_at),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)");

function isFavorited($userId, $cardId) {
    global $conn;
    $stmt = $conn->prepare('SELECT 1 FROM user_favorites WHERE user_id = ? AND card_id = ? LIMIT 1');
    if (!$stmt) return false;
    $stmt->bind_param('is', $userId, $cardId);
    if (!$stmt->execute()) return false;
    $res = $stmt->get_result();
    return (bool)$res->fetch_assoc();
}

if ($action === 'status') {
    $cardId = (string)($_GET['card_id'] ?? '');
    header('Content-Type: application/json; charset=utf-8');
    if ($cardId === '') {
        http_response_code(400);
        echo json_encode(['ok' => false, 'message' => 'card_id requerido']);
        exit;
    }
    echo json_encode(['ok' => true, 'favorited' => isFavorited($userId, $cardId)]);
    exit;
}

if ($action === 'list') {
    header('Content-Type: application/json; charset=utf-8');
    $stmt = $conn->prepare('SELECT card_id, card_name, card_image, card_game, created_at FROM user_favorites WHERE user_id = ? ORDER BY created_at DESC');
    if (!$stmt) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'message' => 'Error interno del servidor']);
        exit;
    }
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $res = $stmt->get_result();
    $items = [];
    while ($row = $res->fetch_assoc()) {
        $items[] = $row;
    }
    echo json_encode(['ok' => true, 'items' => $items]);
    exit;
}

if ($action === 'toggle' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=utf-8');

    $cardId = (string)($_POST['card_id'] ?? '');
    $cardName = (string)($_POST['card_name'] ?? '');
    $cardImage = (string)($_POST['card_image'] ?? '');
    $cardGame = (string)($_POST['card_game'] ?? '');

    if ($cardId === '' || $cardName === '' || $cardImage === '' || $cardGame === '') {
        http_response_code(400);
        echo json_encode(['ok' => false, 'message' => 'Datos de carta incompletos']);
        exit;
    }

    $exists = isFavorited($userId, $cardId);
    if ($exists) {
        $stmt = $conn->prepare('DELETE FROM user_favorites WHERE user_id = ? AND card_id = ?');
        if (!$stmt) {
            http_response_code(500);
            echo json_encode(['ok' => false, 'message' => 'Error interno del servidor']);
            exit;
        }
        $stmt->bind_param('is', $userId, $cardId);
        $ok = $stmt->execute();
        if (!$ok) {
            http_response_code(500);
            echo json_encode(['ok' => false, 'message' => 'Error interno del servidor']);
            exit;
        }
        echo json_encode(['ok' => true, 'favorited' => false]);
        exit;
    }

    $stmt = $conn->prepare('INSERT INTO user_favorites (user_id, card_id, card_name, card_image, card_game) VALUES (?, ?, ?, ?, ?)');
    if (!$stmt) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'message' => 'Error interno del servidor']);
        exit;
    }
    $stmt->bind_param('issss', $userId, $cardId, $cardName, $cardImage, $cardGame);
    $ok = $stmt->execute();
    if (!$ok) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'message' => 'Error interno del servidor']);
        exit;
    }

    echo json_encode(['ok' => true, 'favorited' => true]);
    exit;
}

header('Content-Type: application/json; charset=utf-8');
http_response_code(400);
echo json_encode(['ok' => false, 'message' => 'Acción no válida']);
