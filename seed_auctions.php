<?php
// seed_auctions.php — Termina las subastas activas viejas y genera N nuevas desde cards_pool.
// Ejecutar UNA vez: php seed_auctions.php  (o desde navegador)
require_once __DIR__ . '/includes/db.php';

set_time_limit(60);
header('Content-Type: text/plain; charset=utf-8');

$NUEVAS = 15; // Cuántas subastas activas crear

echo "=== Reseed de subastas ===\n\n";

// Asegurar columnas necesarias
try { $conn->query("ALTER TABLE auctions ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP"); } catch (Exception $e) {}
try { $conn->query("ALTER TABLE auctions ADD COLUMN auto_generated TINYINT(1) NOT NULL DEFAULT 0"); } catch (Exception $e) {}

// Terminar todas las subastas activas anteriores
$conn->query("UPDATE auctions SET status='ended', ends_at=NOW() WHERE status='active'");
echo "[OK] Subastas activas anteriores terminadas: {$conn->affected_rows}\n\n";

$BADGE = [
    'Pokémon'   => '#ef4444',
    'Magic'     => '#8b5cf6',
    'Yu-Gi-Oh!' => '#f59e0b',
    'One Piece' => '#f97316',
];

// Sacar N cartas aleatorias del pool, variadas por juego
$st = $conn->prepare("SELECT card_id, card_name, card_image, card_game, card_rarity, market_price
                      FROM cards_pool ORDER BY RAND() LIMIT ?");
$st->bind_param("i", $NUEVAS);
$st->execute();
$cards = $st->get_result()->fetch_all(MYSQLI_ASSOC);

if (empty($cards)) {
    echo "[ERROR] cards_pool está vacío. Ejecuta primero populate_cards_pool.php\n";
    exit;
}

$ins = $conn->prepare(
    "INSERT INTO auctions (card_name, card_image, card_game, badge_color, base_price, ends_at, status, auto_generated)
     VALUES (?, ?, ?, ?, ?, ?, 'active', 1)"
);

$creadas = 0;
foreach ($cards as $i => $card) {
    $cardName  = $card['card_name'];
    $cardImage = $card['card_image'];
    if (str_starts_with($cardImage, 'api/')) {
        $cardImage = '../' . $cardImage;
    }
    $cardGame   = $card['card_game'];
    $badgeColor = $BADGE[$cardGame] ?? '#ef4444';

    $marketPrice = (float)$card['market_price'];
    if ($marketPrice >= 1) {
        $basePrice = max(5, (int)round($marketPrice));
    } else {
        $basePrice = match ($card['card_rarity']) {
            'ultra'  => rand(200, 1500),
            'rare'   => rand(30, 200),
            default  => rand(5, 30),
        };
    }

    // Duraciones escalonadas: 30 min, 1h, 1.5h, 2h, 2.5h, ... así que terminan en momentos distintos
    $minutes = 30 + ($i * 20);
    $endsAt  = date('Y-m-d H:i:s', strtotime("+{$minutes} minutes"));

    $ins->bind_param("ssssis", $cardName, $cardImage, $cardGame, $badgeColor, $basePrice, $endsAt);
    $ins->execute();
    if ($ins->affected_rows > 0) {
        $creadas++;
        echo sprintf("  + [%s] %-30s  base: %d LJ  termina en %dmin\n",
            $cardGame, mb_substr($cardName, 0, 30), $basePrice, $minutes);
    }
}

echo "\n[OK] {$creadas} subastas nuevas creadas con cartas reales del pool.\n";
