<?php
// populate_cards_pool.php — Pobla la tabla cards_pool con ~300 cartas reales por juego
// usando las APIs externas (Pokémon TCG, Scryfall, YGOProDeck) y códigos generados (One Piece).
// Ejecutar UNA VEZ desde el navegador: http://localhost/Loot_and_Trading_Proyecto_Final/populate_cards_pool.php
// O por CLI: php populate_cards_pool.php

require_once __DIR__ . '/includes/db.php';

set_time_limit(600); // 10 minutos por si las APIs son lentas
header('Content-Type: text/plain; charset=utf-8');

$TARGET_PER_GAME = 300;

echo "=== Pool de cartas: poblado inicial ===\n\n";

// ── Crear tabla ──
$conn->query("CREATE TABLE IF NOT EXISTS cards_pool (
    id INT AUTO_INCREMENT PRIMARY KEY,
    card_id VARCHAR(255) NOT NULL,
    card_name VARCHAR(255) NOT NULL,
    card_image VARCHAR(500) NOT NULL,
    card_game VARCHAR(50) NOT NULL,
    card_rarity VARCHAR(20) NOT NULL DEFAULT 'common',
    market_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_card (card_id, card_game),
    INDEX idx_game (card_game),
    INDEX idx_game_rarity (card_game, card_rarity)
)");
echo "[OK] Tabla cards_pool lista\n\n";

function curlGet($url, $headers = []) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTPHEADER => array_merge(
            ['User-Agent: LootTrading/1.0 (student-project)'],
            $headers
        ),
    ]);
    $r = curl_exec($ch);
    curl_close($ch);
    return json_decode($r, true);
}

function rarityPokemon($r) {
    $r = strtolower($r ?? '');
    if (str_contains($r,'hyper')||str_contains($r,'rainbow')||str_contains($r,'secret')||
        str_contains($r,'illustration rare')||str_contains($r,'special illustration')) return 'ultra';
    if (str_contains($r,'ultra')||str_contains($r,'shiny')||str_contains($r,'v-union')) return 'ultra';
    if (str_contains($r,'rare')) return 'rare';
    return 'common';
}
function rarityMagic($r) {
    return match(strtolower($r ?? '')) {'mythic'=>'ultra','rare'=>'rare',default=>'common'};
}
function rarityYGO($price) { return $price>=25?'ultra':($price>=4?'rare':'common'); }

$ins = $conn->prepare("INSERT IGNORE INTO cards_pool (card_id, card_name, card_image, card_game, card_rarity, market_price) VALUES (?, ?, ?, ?, ?, ?)");

// ── POKÉMON ────────────────────────────────────────────────
echo "→ Pokémon: descargando hasta {$TARGET_PER_GAME} cartas...\n";
$count = 0;
$page  = 1;
while ($count < $TARGET_PER_GAME && $page <= 30) {
    $data = curlGet("https://api.pokemontcg.io/v2/cards?pageSize=50&page={$page}");
    $cards = $data['data'] ?? [];
    if (empty($cards)) break;
    foreach ($cards as $c) {
        $img = $c['images']['large'] ?? $c['images']['small'] ?? null;
        if (!$img) continue;
        $p  = $c['tcgplayer']['prices'] ?? [];
        $pr = (float)($p['holofoil']['market'] ?? $p['normal']['market'] ??
                      $p['reverseHolofoil']['market'] ?? $p['1stEditionHolofoil']['market'] ?? 0);
        $r  = rarityPokemon($c['rarity'] ?? '');
        $id = (string)$c['id'];
        $nm = $c['name'] ?? 'Pokémon';
        $game = 'Pokémon';
        $ins->bind_param("sssssd", $id, $nm, $img, $game, $r, $pr);
        if ($ins->execute() && $ins->affected_rows > 0) $count++;
        if ($count >= $TARGET_PER_GAME) break;
    }
    $page++;
    usleep(200000); // 200ms entre páginas
}
echo "  [OK] Pokémon: {$count} cartas insertadas\n\n";

// ── MAGIC ────────────────────────────────────────────────
echo "→ Magic: descargando hasta {$TARGET_PER_GAME} cartas (Scryfall paginado)...\n";
$count = 0;
$page  = 1;
$searchUrl = "https://api.scryfall.com/cards/search?q=lang%3Aen+game%3Apaper+-is%3Atoken&unique=cards&order=released&page={$page}";
while ($count < $TARGET_PER_GAME && $page <= 5) {
    $url = "https://api.scryfall.com/cards/search?q=lang%3Aen+game%3Apaper+-is%3Atoken&unique=cards&order=released&page={$page}";
    $data = curlGet($url);
    $cards = $data['data'] ?? [];
    if (empty($cards)) break;
    foreach ($cards as $c) {
        $img = $c['image_uris']['large']
            ?? $c['image_uris']['normal']
            ?? ($c['card_faces'][0]['image_uris']['large'] ?? null)
            ?? ($c['card_faces'][0]['image_uris']['normal'] ?? null);
        if (!$img) continue;
        $pr = (float)($c['prices']['eur'] ?? $c['prices']['eur_foil'] ?? $c['prices']['usd'] ?? 0);
        $r  = rarityMagic($c['rarity'] ?? 'common');
        $id = (string)$c['id'];
        $nm = $c['name'] ?? 'Magic Card';
        $game = 'Magic';
        $ins->bind_param("sssssd", $id, $nm, $img, $game, $r, $pr);
        if ($ins->execute() && $ins->affected_rows > 0) $count++;
        if ($count >= $TARGET_PER_GAME) break;
    }
    if (!($data['has_more'] ?? false)) break;
    $page++;
    usleep(150000); // Scryfall pide 100ms+ entre requests
}
echo "  [OK] Magic: {$count} cartas insertadas\n\n";

// ── YU-GI-OH! ────────────────────────────────────────────────
echo "→ Yu-Gi-Oh!: descargando {$TARGET_PER_GAME} cartas...\n";
$count = 0;
$offset = rand(0, 500);
$data = curlGet("https://db.ygoprodeck.com/api/v7/cardinfo.php?num={$TARGET_PER_GAME}&offset={$offset}&sort=id");
$cards = $data['data'] ?? [];
foreach ($cards as $c) {
    $img = $c['card_images'][0]['image_url'] ?? null;
    if (!$img) continue;
    $p  = $c['card_prices'][0] ?? [];
    $pr = (float)($p['cardmarket_price'] ?? $p['tcgplayer_price'] ?? 0);
    $r  = rarityYGO($pr);
    $id = (string)$c['id'];
    $nm = $c['name'] ?? 'Yu-Gi-Oh! Card';
    $game = 'Yu-Gi-Oh!';
    $ins->bind_param("sssssd", $id, $nm, $img, $game, $r, $pr);
    if ($ins->execute() && $ins->affected_rows > 0) $count++;
    if ($count >= $TARGET_PER_GAME) break;
}
echo "  [OK] Yu-Gi-Oh!: {$count} cartas insertadas\n\n";

// ── ONE PIECE ────────────────────────────────────────────────
echo "→ One Piece: generando {$TARGET_PER_GAME} códigos únicos...\n";
$sets = [
    'OP01'=>121,'OP02'=>121,'OP03'=>121,'OP04'=>122,
    'OP05'=>122,'OP06'=>122,'OP07'=>119,'OP08'=>120,'OP09'=>119,
];
$count = 0;
$seen = [];
$attempts = 0;
while ($count < $TARGET_PER_GAME && $attempts < $TARGET_PER_GAME * 3) {
    $attempts++;
    $setKeys = array_keys($sets);
    $set = $setKeys[array_rand($setKeys)];
    $max = $sets[$set];
    $num = rand(1, $max);
    $code = $set . '-' . str_pad($num, 3, '0', STR_PAD_LEFT);
    if (in_array($code, $seen, true)) continue;
    $seen[] = $code;

    $img = "api/onepiece_img.php?code={$code}";
    $pct = $num / $max;
    $r   = $pct >= 0.92 ? 'ultra' : ($pct >= 0.70 ? 'rare' : 'common');
    $pr  = match($r) { 'ultra' => rand(50, 2500), 'rare' => rand(10, 80), default => rand(2, 25) };
    $nm  = "One Piece {$code}";
    $game = 'One Piece';
    $ins->bind_param("sssssd", $code, $nm, $img, $game, $r, $pr);
    if ($ins->execute() && $ins->affected_rows > 0) $count++;
}
echo "  [OK] One Piece: {$count} códigos insertados\n\n";

// ── Resumen ────────────────────────────────────────────────
echo "=== RESUMEN FINAL ===\n";
$res = $conn->query("SELECT card_game, COUNT(*) AS total,
                     SUM(card_rarity='ultra') AS ultras,
                     SUM(card_rarity='rare') AS rares,
                     SUM(card_rarity='common') AS commons
                     FROM cards_pool GROUP BY card_game");
while ($r = $res->fetch_assoc()) {
    echo sprintf("  %-12s → %4d total  (ultra:%d  rare:%d  common:%d)\n",
        $r['card_game'], $r['total'], $r['ultras'], $r['rares'], $r['commons']);
}
echo "\n¡Listo! Ya puedes usar pack_open.php sin llamadas a APIs externas.\n";
