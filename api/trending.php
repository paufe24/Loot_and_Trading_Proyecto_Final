<?php
// api/trending.php — Cartas "Lo más buscado" de la home.
// Se sirven desde la BD local (cards_pool) para que sea INSTANTÁNEO y no
// dependa de APIs externas lentas (antes hacía llamadas en vivo a
// pokemontcg.io/Scryfall/YGOProDeck y tardaba minutos).
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');
require_once dirname(__DIR__) . '/includes/db.php';

$cards = [];
$res = $conn->query(
    "SELECT card_name, card_image, card_game, market_price
     FROM cards_pool
     WHERE card_image <> ''
     ORDER BY RAND()
     LIMIT 10"
);
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $img = $row['card_image'];
        // One Piece guarda 'api/onepiece_img.php?...'; la home está en /pages/
        if (strncmp($img, 'api/', 4) === 0) { $img = '../' . $img; }
        $price = (float)$row['market_price'];
        if ($price <= 0) { $price = rand(3, 80); }
        $cards[] = [
            'name'  => $row['card_name'],
            'img'   => $img,
            'badge' => $row['card_game'],
            'price' => round($price, 2),
        ];
    }
}

echo json_encode(['ok' => true, 'cards' => $cards]);
