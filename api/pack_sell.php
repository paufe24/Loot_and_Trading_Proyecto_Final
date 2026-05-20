<?php
require_once dirname(__DIR__) . '/includes/session.php';
header('Content-Type: application/json; charset=utf-8');
if (!isset($_SESSION['user_id'])) { echo json_encode(['ok'=>false,'message'=>'No autenticado']); exit; }
require_once dirname(__DIR__) . '/includes/db.php';
$uid = (int)$_SESSION['user_id'];

$ids = [];
if (!empty($_POST['pack_card_id'])) {
    $ids = [(int)$_POST['pack_card_id']];
} elseif (!empty($_POST['pack_card_ids'])) {
    $ids = array_map('intval', (array)$_POST['pack_card_ids']);
}
if (!$ids) { echo json_encode(['ok'=>false,'message'=>'Sin cartas']); exit; }

$placeholders = implode(',', array_fill(0, count($ids), '?'));
$types = str_repeat('i', count($ids));

// Fetch cards that belong to this user and are not sold
$st = $conn->prepare("SELECT id, lujanitos_value FROM pack_cards WHERE id IN ($placeholders) AND user_id = ? AND sold = 0");
$params = array_merge($ids, [$uid]);
$types .= 'i';
$st->bind_param($types, ...$params);
$st->execute();
$rows = $st->get_result()->fetch_all(MYSQLI_ASSOC);
if (!$rows) { echo json_encode(['ok'=>false,'message'=>'No hay cartas vendibles']); exit; }

$total = array_sum(array_column($rows, 'lujanitos_value'));
$totalInt = (int)round($total);
$sellIds = array_column($rows, 'id');
$ph2 = implode(',', array_fill(0, count($sellIds), '?'));
$types2 = str_repeat('i', count($sellIds));

$conn->begin_transaction();
try {
    $upd = $conn->prepare("UPDATE pack_cards SET sold = 1 WHERE id IN ($ph2) AND user_id = ?");
    $p2 = array_merge($sellIds, [$uid]);
    $upd->bind_param($types2 . 'i', ...$p2);
    $upd->execute();

    // Add LJ to user — use the real decimal total, rounded to nearest int
    $add = $conn->prepare("UPDATE users SET lootcoins = lootcoins + ? WHERE id = ?");
    $add->bind_param("ii", $totalInt, $uid);
    $add->execute();

    $conn->commit();
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['ok'=>false,'message'=>$e->getMessage()]); exit;
}

$bal = $conn->prepare("SELECT lootcoins FROM users WHERE id = ?");
$bal->bind_param("i",$uid); $bal->execute();
$newBal = (int)$bal->get_result()->fetch_assoc()['lootcoins'];

echo json_encode(['ok'=>true,'total_lj'=>round($total,2),'new_balance'=>$newBal]);
