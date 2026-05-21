<?php
/**
 * Genera una subasta automática con carta aleatoria desde cards_pool cada 30 min.
 * Llamado desde apuestas.php al cargar (silencioso).
 */
require_once dirname(__DIR__) . '/includes/db.php';
header('Content-Type: application/json; charset=utf-8');

// Asegurar tabla auctions existe
$conn->query("CREATE TABLE IF NOT EXISTS auctions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    card_name VARCHAR(255) NOT NULL,
    card_image VARCHAR(500) NOT NULL,
    card_game VARCHAR(50) NOT NULL DEFAULT 'Pokémon',
    badge_color VARCHAR(20) NOT NULL DEFAULT '#ef4444',
    base_price INT NOT NULL DEFAULT 10,
    current_bid INT NOT NULL DEFAULT 0,
    current_winner_id INT DEFAULT NULL,
    seller_id INT DEFAULT NULL,
    ends_at DATETIME NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'active',
    auto_generated TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");
try { $conn->query("ALTER TABLE auctions ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP"); } catch (Exception $e) {}
try { $conn->query("ALTER TABLE auctions ADD COLUMN auto_generated TINYINT(1) NOT NULL DEFAULT 0"); } catch (Exception $e) {}

// Comprobar si ya hay una subasta auto activa creada hace menos de 30 minutos
$check = $conn->query("SELECT id FROM auctions WHERE auto_generated=1 AND status='active' AND created_at >= DATE_SUB(NOW(), INTERVAL 30 MINUTE) LIMIT 1");
if ($check && $check->num_rows > 0) {
    echo json_encode(['ok' => true, 'created' => false, 'reason' => 'Ya existe una subasta automática reciente']);
    exit;
}

// ── Carta aleatoria desde cards_pool ──
$BADGE = [
    'Pokémon'   => '#ef4444',
    'Magic'     => '#8b5cf6',
    'Yu-Gi-Oh!' => '#f59e0b',
    'One Piece' => '#f97316',
];

$res = $conn->query("SELECT card_id, card_name, card_image, card_game, card_rarity, market_price
                     FROM cards_pool ORDER BY RAND() LIMIT 1");
if (!$res || $res->num_rows === 0) {
    echo json_encode(['ok' => false, 'reason' => 'cards_pool vacío. Ejecuta populate_cards_pool.php']);
    exit;
}
$card = $res->fetch_assoc();

$cardName   = $card['card_name'];
$cardImage  = $card['card_image'];
// Normalizar URL de One Piece (cards_pool guarda "api/onepiece_img.php?code=..." sin prefijo)
if (str_starts_with($cardImage, 'api/')) {
    $cardImage = '../' . $cardImage;
}
$cardGame   = $card['card_game'];
$badgeColor = $BADGE[$cardGame] ?? '#ef4444';

$marketPrice = (float)$card['market_price'];
if ($marketPrice >= 1) {
    $basePrice = max(5, (int)round($marketPrice));
} else {
    // Sin precio real: rango por rareza
    $basePrice = match ($card['card_rarity']) {
        'ultra'  => rand(200, 1500),
        'rare'   => rand(30, 200),
        default  => rand(5, 30),
    };
}

// Duración 1 hora
$endsAt = date('Y-m-d H:i:s', strtotime('+1 hour'));
$stmt = $conn->prepare(
    "INSERT INTO auctions (card_name, card_image, card_game, badge_color, base_price, ends_at, status, auto_generated)
     VALUES (?, ?, ?, ?, ?, ?, 'active', 1)"
);
$stmt->bind_param("ssssis", $cardName, $cardImage, $cardGame, $badgeColor, $basePrice, $endsAt);
$stmt->execute();
$newId = $conn->insert_id;

echo json_encode([
    'ok'         => true,
    'created'    => true,
    'auction_id' => $newId,
    'card'       => $cardName,
    'game'       => $cardGame,
    'base_price' => $basePrice,
    'ends_at'    => $endsAt,
]);
