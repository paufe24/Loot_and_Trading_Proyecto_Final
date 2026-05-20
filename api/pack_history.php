<?php
require_once dirname(__DIR__) . '/includes/session.php';
header('Content-Type: application/json; charset=utf-8');
if (!isset($_SESSION['user_id'])) { echo json_encode(['ok'=>false]); exit; }
require_once dirname(__DIR__) . '/includes/db.php';
$uid = (int)$_SESSION['user_id'];

$st = $conn->prepare("SELECT id, card_name, card_image, card_game, card_rarity, lujanitos_value, sold FROM pack_cards WHERE user_id = ? ORDER BY opened_at DESC LIMIT 60");
$st->bind_param("i",$uid); $st->execute();
$rows = $st->get_result()->fetch_all(MYSQLI_ASSOC);
foreach ($rows as &$r) { $r['sold'] = (int)$r['sold']; }
unset($r);
echo json_encode(['ok'=>true,'cards'=>$rows]);
