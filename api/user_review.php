<?php
require_once dirname(__DIR__) . '/includes/session.php';
header('Content-Type: application/json; charset=utf-8');
if (!isset($_SESSION['user_id'])) { echo json_encode(['ok'=>false,'message'=>'No autenticado']); exit; }
require_once dirname(__DIR__) . '/includes/db.php';
$uid = (int)$_SESSION['user_id'];

// Migración: crear tabla si no existe
$conn->query("CREATE TABLE IF NOT EXISTS user_reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reviewer_id INT NOT NULL,
    reviewed_user_id INT NOT NULL,
    rating TINYINT(1) NOT NULL,
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_review (reviewer_id, reviewed_user_id),
    INDEX idx_reviewed (reviewed_user_id)
)");

$action = $_GET['action'] ?? $_POST['action'] ?? '';

if ($action === 'get') {
    $targetId = (int)($_GET['user_id'] ?? 0);
    if (!$targetId) { echo json_encode(['ok'=>false]); exit; }

    $st = $conn->prepare("SELECT AVG(rating) AS avg_r, COUNT(*) AS total FROM user_reviews WHERE reviewed_user_id = ?");
    $st->bind_param("i", $targetId);
    $st->execute();
    $row = $st->get_result()->fetch_assoc();

    $myReview = null;
    $st2 = $conn->prepare("SELECT rating, comment FROM user_reviews WHERE reviewer_id = ? AND reviewed_user_id = ?");
    $st2->bind_param("ii", $uid, $targetId);
    $st2->execute();
    $myRow = $st2->get_result()->fetch_assoc();
    if ($myRow) $myReview = $myRow;

    echo json_encode([
        'ok'         => true,
        'avg_rating' => $row['avg_r'] ? round((float)$row['avg_r'], 1) : null,
        'total'      => (int)$row['total'],
        'my_review'  => $myReview,
    ]);
    exit;
}

if ($action === 'list') {
    $targetId = (int)($_GET['user_id'] ?? 0);
    if (!$targetId) { echo json_encode(['ok'=>false]); exit; }

    $st = $conn->prepare(
        "SELECT r.rating, r.comment, r.created_at, u.username, u.name, u.avatar_url
         FROM user_reviews r
         JOIN users u ON u.id = r.reviewer_id
         WHERE r.reviewed_user_id = ?
         ORDER BY r.created_at DESC LIMIT 50"
    );
    $st->bind_param("i", $targetId);
    $st->execute();
    $reviews = $st->get_result()->fetch_all(MYSQLI_ASSOC);

    $st2 = $conn->prepare("SELECT AVG(rating) AS avg_r, COUNT(*) AS total FROM user_reviews WHERE reviewed_user_id = ?");
    $st2->bind_param("i", $targetId);
    $st2->execute();
    $row = $st2->get_result()->fetch_assoc();

    echo json_encode([
        'ok'         => true,
        'reviews'    => $reviews,
        'avg_rating' => $row['avg_r'] ? round((float)$row['avg_r'], 1) : null,
        'total'      => (int)$row['total'],
    ]);
    exit;
}

if ($action === 'submit') {
    $targetId = (int)($_POST['reviewed_user_id'] ?? 0);
    $rating   = (int)($_POST['rating'] ?? 0);
    $comment  = trim($_POST['comment'] ?? '');

    if (!$targetId || $targetId === $uid) { echo json_encode(['ok'=>false,'message'=>'ID inválido']); exit; }
    if ($rating < 1 || $rating > 5) { echo json_encode(['ok'=>false,'message'=>'Puntuación inválida']); exit; }

    $st = $conn->prepare("INSERT INTO user_reviews (reviewer_id, reviewed_user_id, rating, comment)
        VALUES (?,?,?,?)
        ON DUPLICATE KEY UPDATE rating=VALUES(rating), comment=VALUES(comment), created_at=NOW()");
    $st->bind_param("iiis", $uid, $targetId, $rating, $comment);
    $st->execute();

    // Devolver nueva media
    $st2 = $conn->prepare("SELECT AVG(rating) AS avg_r, COUNT(*) AS total FROM user_reviews WHERE reviewed_user_id = ?");
    $st2->bind_param("i", $targetId);
    $st2->execute();
    $row = $st2->get_result()->fetch_assoc();

    echo json_encode([
        'ok'         => true,
        'avg_rating' => round((float)$row['avg_r'], 1),
        'total'      => (int)$row['total'],
    ]);
    exit;
}

echo json_encode(['ok'=>false,'message'=>'Acción no válida']);
