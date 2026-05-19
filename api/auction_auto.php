<?php
/**
 * Genera una subasta automática con carta aleatoria cada hora.
 * Se llama: desde apuestas.php al cargar (silencioso), o desde un cron.
 * Solo crea una nueva subasta si no hay ninguna activa generada automáticamente
 * en la última hora.
 */
require_once dirname(__DIR__) . '/includes/db.php';

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
    auto_generated TINYINT(1) NOT NULL DEFAULT 0
)");

// Comprobar si ya hay una subasta auto activa creada hace menos de 30 minutos
$check = $conn->query("SELECT id FROM auctions WHERE auto_generated=1 AND status='active' AND created_at >= DATE_SUB(NOW(), INTERVAL 30 MINUTE) LIMIT 1");
if (!$check) {
    // Añadir columna created_at si no existe
    $conn->query("ALTER TABLE auctions ADD COLUMN IF NOT EXISTS created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
    $conn->query("ALTER TABLE auctions ADD COLUMN IF NOT EXISTS auto_generated TINYINT(1) NOT NULL DEFAULT 0");
    $check = $conn->query("SELECT id FROM auctions WHERE auto_generated=1 AND status='active' AND created_at >= DATE_SUB(NOW(), INTERVAL 30 MINUTE) LIMIT 1");
}
if ($check && $check->num_rows > 0) {
    echo json_encode(['ok'=>true,'created'=>false,'reason'=>'Ya existe una subasta automática reciente']); exit;
}

// ── Obtener carta aleatoria ──────────────────────────────────────
function curlGet2($url) {
    $ch = curl_init($url);
    curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_TIMEOUT=>12,
        CURLOPT_SSL_VERIFYPEER=>false,CURLOPT_FOLLOWLOCATION=>true,
        CURLOPT_HTTPHEADER=>['User-Agent: LootTrading/1.0 (student-project)']]);
    $r = curl_exec($ch); curl_close($ch);
    return json_decode($r, true);
}

$games = ['pokemon','magic','yugioh','onepiece'];
$game  = $games[array_rand($games)];

$cardName  = '';
$cardImage = '';
$cardGame  = '';
$badgeColor = '#ef4444';
$basePrice  = 10;

if ($game === 'pokemon') {
    $page = rand(1, 280);
    $data = curlGet2("https://api.pokemontcg.io/v2/cards?pageSize=20&page={$page}");
    $pool = array_values(array_filter($data['data']??[], fn($c)=>!empty($c['images']['large'])));
    if ($pool) {
        shuffle($pool); $c = $pool[0];
        $cardName  = $c['name'];
        $cardImage = $c['images']['large'];
        $cardGame  = 'Pokémon';
        $badgeColor = '#ef4444';
        $p  = $c['tcgplayer']['prices'] ?? [];
        $pr = (float)($p['holofoil']['market'] ?? $p['normal']['market'] ?? $p['reverseHolofoil']['market'] ?? 15);
        $basePrice = max(5, (int)round($pr));
    }
} elseif ($game === 'magic') {
    $c = curlGet2("https://api.scryfall.com/cards/random");
    $img = $c['image_uris']['large'] ?? ($c['card_faces'][0]['image_uris']['large'] ?? null);
    if ($img) {
        $cardName  = $c['name'];
        $cardImage = $img;
        $cardGame  = 'Magic';
        $badgeColor = '#8b5cf6';
        $pr = (float)($c['prices']['eur'] ?? $c['prices']['usd'] ?? 15);
        $basePrice = max(5, (int)round($pr));
    }
} elseif ($game === 'yugioh') {
    $offset = rand(0, 200) * 10;
    $resp = curlGet2("https://db.ygoprodeck.com/api/v7/cardinfo.php?num=10&offset={$offset}&sort=id");
    $pool = array_values(array_filter($resp['data']??[], fn($c)=>!empty($c['card_images'][0]['image_url'])));
    if ($pool) {
        shuffle($pool); $c = $pool[0];
        $cardName  = $c['name'];
        $cardImage = $c['card_images'][0]['image_url'];
        $cardGame  = 'Yu-Gi-Oh!';
        $badgeColor = '#f59e0b';
        $p  = $c['card_prices'][0] ?? [];
        $pr = (float)($p['cardmarket_price'] ?? $p['tcgplayer_price'] ?? 10);
        $basePrice = max(5, (int)round($pr));
    }
} else { // onepiece
    $sets = ['OP01'=>121,'OP02'=>121,'OP03'=>121,'OP04'=>122,'OP05'=>122,'OP06'=>122,'OP07'=>121,'OP08'=>120,'OP09'=>119];
    $setKeys = array_keys($sets);
    $set  = $setKeys[array_rand($setKeys)];
    $num  = rand(1, $sets[$set]);
    $code = $set . '-' . str_pad($num, 3, '0', STR_PAD_LEFT);
    $cardName  = "One Piece {$code}";
    $cardImage = "../api/onepiece_img.php?code={$code}";
    $cardGame  = 'One Piece';
    $badgeColor = '#f97316';
    $pct = $num / $sets[$set];
    $basePrice = $pct >= 0.92 ? rand(50,500) : ($pct >= 0.70 ? rand(10,50) : rand(3,15));
}

// Fallback si no se obtuvo carta
if (!$cardName || !$cardImage) {
    echo json_encode(['ok'=>false,'reason'=>'No se pudo obtener carta aleatoria']); exit;
}

// ── Crear subasta (dura 1 hora) ──────────────────────────────────
$endsAt = date('Y-m-d H:i:s', strtotime('+1 hour'));
$stmt = $conn->prepare("INSERT INTO auctions (card_name, card_image, card_game, badge_color, base_price, ends_at, status, auto_generated)
    VALUES (?,?,?,?,?,?,'active',1)");
$stmt->bind_param("ssssis", $cardName, $cardImage, $cardGame, $badgeColor, $basePrice, $endsAt);
$stmt->execute();
$newId = $conn->insert_id;

echo json_encode(['ok'=>true,'created'=>true,'auction_id'=>$newId,'card'=>$cardName,'game'=>$cardGame,'base_price'=>$basePrice,'ends_at'=>$endsAt]);
