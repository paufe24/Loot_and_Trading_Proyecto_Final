<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');

function curlGet($url) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => false, CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTPHEADER => ['User-Agent: LootTrading/1.0 (student-project)'],
    ]);
    $r = curl_exec($ch); curl_close($ch);
    return json_decode($r, true);
}

$cards = [];

// ── 3 Pokémon ──────────────────────────────────────────────────
$page = rand(1, 50);
$pk = curlGet("https://api.pokemontcg.io/v2/cards?pageSize=30&page={$page}&q=rarity:Rare");
$pool = array_values(array_filter($pk['data'] ?? [], fn($c) => !empty($c['images']['large'])));
shuffle($pool);
foreach (array_slice($pool, 0, 3) as $c) {
    $p = $c['tcgplayer']['prices'] ?? [];
    $pr = (float)($p['holofoil']['market'] ?? $p['normal']['market'] ?? $p['reverseHolofoil']['market'] ?? rand(5,80));
    $cards[] = ['name'=>$c['name'], 'img'=>$c['images']['large'], 'badge'=>'Pokémon',
                'badge_bg'=>'#fef3c7', 'badge_color'=>'#b45309', 'price'=>round($pr,2)];
}

// ── 3 Magic ────────────────────────────────────────────────────
$seen = [];
$tries = 0;
while (count($seen) < 3 && $tries < 9) {
    $tries++;
    $m = curlGet("https://api.scryfall.com/cards/random?q=rarity:rare");
    $img = $m['image_uris']['large'] ?? ($m['card_faces'][0]['image_uris']['large'] ?? null);
    if (!$img || in_array($m['id'] ?? '', $seen)) { usleep(60000); continue; }
    $seen[] = $m['id'];
    $pr = (float)($m['prices']['eur'] ?? $m['prices']['usd'] ?? rand(2,40));
    $cards[] = ['name'=>$m['name'], 'img'=>$img, 'badge'=>'Magic',
                'badge_bg'=>'#ede9fe', 'badge_color'=>'#6d28d9', 'price'=>round($pr,2)];
    usleep(60000);
}

// ── 2 Yu-Gi-Oh! ───────────────────────────────────────────────
$offset = rand(0, 100) * 10;
$ygo = curlGet("https://db.ygoprodeck.com/api/v7/cardinfo.php?num=20&offset={$offset}&sort=id");
$pool2 = array_values(array_filter($ygo['data'] ?? [], fn($c) => !empty($c['card_images'][0]['image_url'])));
shuffle($pool2);
foreach (array_slice($pool2, 0, 2) as $c) {
    $p = $c['card_prices'][0] ?? [];
    $pr = (float)($p['cardmarket_price'] ?? $p['tcgplayer_price'] ?? rand(1,30));
    $cards[] = ['name'=>$c['name'], 'img'=>$c['card_images'][0]['image_url'], 'badge'=>'Yu-Gi-Oh!',
                'badge_bg'=>'#fef3c7', 'badge_color'=>'#b45309', 'price'=>round($pr,2)];
}

shuffle($cards);
echo json_encode(['ok'=>true,'cards'=>$cards]);
