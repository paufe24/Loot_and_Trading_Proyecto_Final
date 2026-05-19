<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
if (!isset($_SESSION['user_id'])) { echo json_encode(['ok'=>false]); exit; }
require_once dirname(__DIR__) . '/includes/db.php';
$uid = (int)$_SESSION['user_id'];

// Migración: columna kept en pack_cards
$conn->query("ALTER TABLE pack_cards ADD COLUMN IF NOT EXISTS kept TINYINT(1) NOT NULL DEFAULT 0");

$id = (int)($_POST['pack_card_id'] ?? 0);
if (!$id) { echo json_encode(['ok'=>false,'message'=>'ID inválido']); exit; }

// Verificar que la carta pertenece al usuario y no está vendida
$st = $conn->prepare("SELECT id, kept, card_name, card_image, card_game, lujanitos_value FROM pack_cards WHERE id = ? AND user_id = ? AND sold = 0");
$st->bind_param("ii", $id, $uid);
$st->execute();
$card = $st->get_result()->fetch_assoc();
if (!$card) { echo json_encode(['ok'=>false,'message'=>'Carta no encontrada']); exit; }

$newKept = $card['kept'] ? 0 : 1; // toggle
$upd = $conn->prepare("UPDATE pack_cards SET kept = ? WHERE id = ? AND user_id = ?");
$upd->bind_param("iii", $newKept, $id, $uid);
$upd->execute();

// ── Sincronizar con cart_orders (cola de envíos) ──
$cardIdStr  = "pack-" . $id;
$orderRef   = "PACK-" . $id;

if ($newKept === 1) {
    // Comprobar si ya existe un envío para esta carta (por idempotencia)
    $chk = $conn->prepare("SELECT id FROM cart_orders WHERE order_number = ? LIMIT 1");
    $chk->bind_param("s", $orderRef);
    $chk->execute();
    if (!$chk->get_result()->fetch_assoc()) {
        // Crear envío
        $price = (float)$card['lujanitos_value'];
        $ins = $conn->prepare("INSERT INTO cart_orders (user_id, order_number, total_amount, status, shipment_status) VALUES (?, ?, ?, 'paid', 'pending')");
        $ins->bind_param("isd", $uid, $orderRef, $price);
        $ins->execute();
        $orderId = $conn->insert_id;

        $itemIns = $conn->prepare("INSERT INTO cart_order_items (order_id, card_id, card_name, card_image, card_price, card_game, quantity, subtotal) VALUES (?, ?, ?, ?, ?, ?, 1, ?)");
        $itemIns->bind_param("isssdsd", $orderId, $cardIdStr, $card['card_name'], $card['card_image'], $price, $card['card_game'], $price);
        $itemIns->execute();
    }
} else {
    // Toggle off: eliminar envío solo si sigue en 'pending'
    $del = $conn->prepare("DELETE FROM cart_orders WHERE order_number = ? AND user_id = ? AND shipment_status = 'pending'");
    $del->bind_param("si", $orderRef, $uid);
    $del->execute();
}

echo json_encode(['ok'=>true,'kept'=>$newKept]);
