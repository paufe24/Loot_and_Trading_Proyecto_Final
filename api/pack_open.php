<?php
// api/pack_open.php — Abre un sobre tirando de la tabla cards_pool (sin llamadas externas).
require_once dirname(__DIR__) . '/includes/session.php';
error_reporting(0);
ini_set('display_errors', 0);
ob_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    ob_clean();
    echo json_encode(['ok' => false, 'message' => 'No autenticado']);
    exit;
}

require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/csrf.php';
csrf_verify();

$uid  = (int)$_SESSION['user_id'];
$game = trim($_POST['game'] ?? 'pokemon');
$COST = 50;

$GAME_LABELS = [
    'pokemon'  => 'Pokémon',
    'magic'    => 'Magic',
    'yugioh'   => 'Yu-Gi-Oh!',
    'onepiece' => 'One Piece',
];

if (!isset($GAME_LABELS[$game])) {
    ob_clean();
    echo json_encode(['ok' => false, 'message' => 'Juego inválido']);
    exit;
}

// Verificar saldo
$st = $conn->prepare("SELECT lootcoins FROM users WHERE id = ?");
$st->bind_param("i", $uid);
$st->execute();
$row = $st->get_result()->fetch_assoc();
if (!$row || (int)$row['lootcoins'] < $COST) {
    ob_clean();
    echo json_encode(['ok' => false, 'message' => 'Lujanitos insuficientes. Necesitas 50 LJ para abrir un sobre.']);
    exit;
}

// Asegurar tablas (idempotente)
$conn->query("CREATE TABLE IF NOT EXISTS pack_cards (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    card_id VARCHAR(255) NOT NULL,
    card_name VARCHAR(255) NOT NULL,
    card_image VARCHAR(500) NOT NULL,
    card_game VARCHAR(50) NOT NULL,
    card_rarity VARCHAR(50) DEFAULT 'common',
    market_price DECIMAL(10,2) DEFAULT 0.00,
    lujanitos_value DECIMAL(10,2) NOT NULL DEFAULT 0.10,
    sold TINYINT(1) NOT NULL DEFAULT 0,
    kept TINYINT(1) NOT NULL DEFAULT 0,
    opened_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user (user_id)
)");
$conn->query("ALTER TABLE pack_cards MODIFY COLUMN lujanitos_value DECIMAL(10,2) NOT NULL DEFAULT 0.10");
try { $conn->query("ALTER TABLE pack_cards ADD COLUMN kept TINYINT(1) NOT NULL DEFAULT 0"); } catch (Exception $e) {}

// Helpers
function toLJ($price, $rarity) {
    $p = (float)$price;
    if ($p > 0) return round($p, 2);
    return match($rarity) {
        'ultra' => rand(500, 2000),
        'rare'  => rand(50, 300),
        default => rand(5, 40),
    };
}

function normalizeImg($img) {
    // Las URLs de cards_pool para One Piece están guardadas como "api/onepiece_img.php?code=..."
    // El cliente (pages/sobres.php) necesita "../api/..." porque está en /pages/
    if (str_starts_with($img, 'api/')) {
        return '../' . $img;
    }
    return $img;
}

// ── Sacar 5 cartas aleatorias del pool del juego seleccionado ──
$gameLabel = $GAME_LABELS[$game];
$st = $conn->prepare("SELECT card_id, card_name, card_image, card_game, card_rarity, market_price
                      FROM cards_pool
                      WHERE card_game = ?
                      ORDER BY RAND() LIMIT 5");
$st->bind_param("s", $gameLabel);
$st->execute();
$result = $st->get_result();

$cards = [];
while ($row = $result->fetch_assoc()) {
    $rarity = $row['card_rarity'];
    $price  = (float)$row['market_price'];
    $cards[] = [
        'card_id'        => (string)$row['card_id'],
        'card_name'      => $row['card_name'],
        'card_image'     => normalizeImg($row['card_image']),
        'card_game'      => $row['card_game'],
        'card_rarity'    => $rarity,
        'market_price'   => round($price, 2),
        'lujanitos_value'=> round(toLJ($price, $rarity), 2),
    ];
}
$st->close();

if (count($cards) < 1) {
    ob_clean();
    echo json_encode(['ok' => false, 'message' => 'El pool de cartas está vacío. Ejecuta populate_cards_pool.php primero.']);
    exit;
}

// ── Descontar LJ y guardar cartas en pack_cards ──
$conn->begin_transaction();
try {
    $upd = $conn->prepare("UPDATE users SET lootcoins = lootcoins - ? WHERE id = ? AND lootcoins >= ?");
    $upd->bind_param("iii", $COST, $uid, $COST);
    $upd->execute();
    if ($upd->affected_rows < 1) throw new Exception('Saldo insuficiente');

    $ins = $conn->prepare(
        "INSERT INTO pack_cards (user_id, card_id, card_name, card_image, card_game, card_rarity, market_price, lujanitos_value)
         VALUES (?,?,?,?,?,?,?,?)"
    );
    foreach ($cards as &$c) {
        $ins->bind_param(
            "isssssdd",
            $uid, $c['card_id'], $c['card_name'], $c['card_image'],
            $c['card_game'], $c['card_rarity'], $c['market_price'], $c['lujanitos_value']
        );
        $ins->execute();
        $c['pack_card_id'] = $conn->insert_id;
    }
    unset($c);
    $conn->commit();
} catch (Exception $e) {
    $conn->rollback();
    ob_clean();
    echo json_encode(['ok' => false, 'message' => $e->getMessage()]);
    exit;
}

// Saldo final
$st2 = $conn->prepare("SELECT lootcoins FROM users WHERE id = ?");
$st2->bind_param("i", $uid);
$st2->execute();
$newBalance = (int)$st2->get_result()->fetch_assoc()['lootcoins'];

ob_clean();
echo json_encode(['ok' => true, 'cards' => $cards, 'new_balance' => $newBalance, 'cost' => $COST]);
