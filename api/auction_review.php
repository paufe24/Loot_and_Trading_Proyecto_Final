<?php
require_once dirname(__DIR__) . '/includes/session.php';
header('Content-Type: application/json; charset=utf-8');
if (!isset($_SESSION['user_id'])) { echo json_encode(['ok'=>false,'message'=>'No autenticado']); exit; }
require_once dirname(__DIR__) . '/includes/db.php';
$uid = (int)$_SESSION['user_id'];

// Crear tabla si no existe
$conn->query("CREATE TABLE IF NOT EXISTS auction_reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    auction_id INT NOT NULL,
    reviewer_id INT NOT NULL,
    seller_id INT DEFAULT NULL,
    rating TINYINT NOT NULL DEFAULT 5,
    comment TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_review (auction_id, reviewer_id),
    INDEX idx_seller (seller_id)
)");

$auctionId = (int)($_POST['auction_id'] ?? 0);
$rating    = max(1, min(5, (int)($_POST['rating'] ?? 5)));
$comment   = trim(strip_tags($_POST['comment'] ?? ''));

if (!$auctionId) { echo json_encode(['ok'=>false,'message'=>'Subasta no válida']); exit; }

// Verificar que el usuario ganó esta subasta
$st = $conn->prepare("SELECT id, seller_id, card_name FROM auctions WHERE id = ? AND current_winner_id = ? AND status = 'ended'");
$st->bind_param("ii", $auctionId, $uid);
$st->execute();
$auction = $st->get_result()->fetch_assoc();
if (!$auction) { echo json_encode(['ok'=>false,'message'=>'No puedes valorar esta subasta']); exit; }

$sellerId = (int)($auction['seller_id'] ?? 0);

$ins = $conn->prepare("INSERT INTO auction_reviews (auction_id, reviewer_id, seller_id, rating, comment) VALUES (?,?,?,?,?) ON DUPLICATE KEY UPDATE rating=VALUES(rating), comment=VALUES(comment)");
$ins->bind_param("iiiis", $auctionId, $uid, $sellerId, $rating, $comment);
$ins->execute();

// Calcular rating medio del vendedor
$avg = null;
if ($sellerId) {
    $r = $conn->query("SELECT ROUND(AVG(rating),1) AS avg_rating, COUNT(*) AS total FROM auction_reviews WHERE seller_id = {$sellerId}");
    $avg = $r ? $r->fetch_assoc() : null;
}

echo json_encode(['ok'=>true,'message'=>'Valoración guardada','avg_rating'=>$avg['avg_rating']??null,'total_reviews'=>$avg['total']??0]);
