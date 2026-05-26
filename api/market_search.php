<?php
// api/market_search.php — Búsqueda de cartas por nombre en el catálogo local
// (cards_pool). Si se pasa ?game=, se acota a ese juego (el de la pestaña).
// Devuelve cartas en el mismo formato que espera createCardHTML() del mercado.
header('Content-Type: application/json; charset=utf-8');
require_once dirname(__DIR__) . '/includes/db.php';

$q = trim($_GET['q'] ?? '');
if ($q === '') { echo json_encode(['ok' => true, 'cards' => []]); exit; }

// Color del badge según el juego (igual que los cargadores del mercado)
$colors = [
    'Pokémon'   => '#eab308',
    'Yu-Gi-Oh!' => '#a855f7',
    'Magic'     => '#ef4444',
    'One Piece' => '#0ea5e9',
];
// Clave de pestaña (?game=pokemon) -> etiqueta real en la BD
$gameMap = [
    'pokemon'  => 'Pokémon',
    'yugioh'   => 'Yu-Gi-Oh!',
    'magic'    => 'Magic',
    'onepiece' => 'One Piece',
];
$gameLabel = $gameMap[$_GET['game'] ?? ''] ?? '';

$like = '%' . $q . '%';
if ($gameLabel !== '') {
    // Acotado al juego de la pestaña
    $st = $conn->prepare(
        "SELECT card_id, card_name, card_image, card_game, market_price
         FROM cards_pool
         WHERE card_name LIKE ? AND card_image <> '' AND card_game = ?
         ORDER BY market_price DESC
         LIMIT 80"
    );
    $st->bind_param('ss', $like, $gameLabel);
} else {
    // Sin pestaña concreta: busca en los 4 juegos
    $st = $conn->prepare(
        "SELECT card_id, card_name, card_image, card_game, market_price
         FROM cards_pool
         WHERE card_name LIKE ? AND card_image <> ''
         ORDER BY market_price DESC
         LIMIT 80"
    );
    $st->bind_param('s', $like);
}
$st->execute();
$res = $st->get_result();

$cards = [];
while ($row = $res->fetch_assoc()) {
    $img = $row['card_image'];
    // One Piece guarda 'api/onepiece_img.php?...'; el mercado está en /pages/
    if (strncmp($img, 'api/', 4) === 0) { $img = '../' . $img; }
    $game = $row['card_game'];
    $cards[] = [
        'name'    => $row['card_name'],
        'img'     => $img,
        'badge'   => $game,
        'color'   => $colors[$game] ?? '#3b82f6',
        'price'   => round((float)$row['market_price'], 2),
        'card_id' => (string)$row['card_id'],
        'id'      => (string)$row['card_id'],
    ];
}
$st->close();

echo json_encode(['ok' => true, 'cards' => $cards]);
