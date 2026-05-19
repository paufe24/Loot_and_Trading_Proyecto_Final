<?php
session_start();
error_reporting(0);
ini_set('display_errors', 0);
ob_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['ok'=>false,'message'=>'No autenticado']); exit;
}

require_once dirname(__DIR__) . '/includes/db.php';
$uid  = (int)$_SESSION['user_id'];
$game = trim($_POST['game'] ?? 'pokemon');
$COST = 50;

if (!in_array($game, ['pokemon','magic','yugioh','onepiece'], true)) {
    echo json_encode(['ok'=>false,'message'=>'Juego inválido']); exit;
}

// Verificar saldo
$st = $conn->prepare("SELECT lootcoins FROM users WHERE id = ?");
$st->bind_param("i",$uid); $st->execute();
$row = $st->get_result()->fetch_assoc();
if (!$row || (int)$row['lootcoins'] < $COST) {
    echo json_encode(['ok'=>false,'message'=>'Lujanitos insuficientes. Necesitas 50 LJ para abrir un sobre.']); exit;
}

// Crear tabla si no existe
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
    opened_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user (user_id)
)");
// Allow decimal LJ if table already existed with INT column
$conn->query("ALTER TABLE pack_cards MODIFY COLUMN lujanitos_value DECIMAL(10,2) NOT NULL DEFAULT 0.10");

// ── Helpers ────────────────────────────────────────────────────
function mk($id,$name,$img,$game,$rarity,$price,$lj) {
    return ['card_id'=>(string)$id,'card_name'=>$name,'card_image'=>$img,
            'card_game'=>$game,'card_rarity'=>$rarity,
            'market_price'=>round((float)$price,2),'lujanitos_value'=>round((float)$lj,2)];
}

function rarityPokemon($r) {
    $r = strtolower($r ?? '');
    if (str_contains($r,'hyper')||str_contains($r,'rainbow')||str_contains($r,'secret')||
        str_contains($r,'illustration rare')||str_contains($r,'special illustration')) return 'ultra';
    if (str_contains($r,'ultra')||str_contains($r,'shiny')||str_contains($r,'v-union')) return 'ultra';
    if (str_contains($r,'rare')) return 'rare';
    return 'common';
}
function rarityMagic($r)  { return match(strtolower($r??'')) {'mythic'=>'ultra','rare'=>'rare',default=>'common'}; }
function rarityYGO($price) { return $price>=25?'ultra':($price>=4?'rare':'common'); }
function rarityOP($r) {
    $r = strtolower($r??'');
    return (str_contains($r,'secret')||str_contains($r,'special'))?'ultra':(str_contains($r,'rare')||str_contains($r,'sr')?'rare':'common');
}
function toLJ($price,$rarity) {
    $p = (float)$price;
    if ($p > 0) return round($p, 2);
    return match($rarity){'ultra'=>rand(500,2000),'rare'=>rand(50,300),default=>rand(5,40)};
}
function curlGet($url, $headers=[]) {
    $ch = curl_init($url);
    curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_TIMEOUT=>12,
        CURLOPT_SSL_VERIFYPEER=>false,CURLOPT_FOLLOWLOCATION=>true,
        CURLOPT_HTTPHEADER=>array_merge(['User-Agent: LootTrading/1.0 (student-project)'],$headers)]);
    $r = curl_exec($ch); curl_close($ch);
    return json_decode($r, true);
}

// ── Fetch cartas según juego ──────────────────────────────────
$cards = [];

if ($game === 'pokemon') {
    $page = rand(1, 280);
    $data = curlGet("https://api.pokemontcg.io/v2/cards?pageSize=15&page={$page}");
    $pool = array_values(array_filter($data['data']??[], fn($c)=>!empty($c['images']['large'])));
    shuffle($pool);
    foreach (array_slice($pool,0,5) as $c) {
        $p  = $c['tcgplayer']['prices'] ?? [];
        $pr = (float)($p['holofoil']['market'] ?? $p['normal']['market'] ??
                      $p['reverseHolofoil']['market'] ?? $p['1stEditionHolofoil']['market'] ?? 0);
        $r  = rarityPokemon($c['rarity'] ?? '');
        $cards[] = mk($c['id'],$c['name'],$c['images']['large'],'Pokémon',$r,$pr,toLJ($pr,$r));
    }
}

elseif ($game === 'magic') {
    $seen = [];
    $attempts = 0;
    while (count($cards) < 5 && $attempts < 10) {
        $attempts++;
        $c = curlGet("https://api.scryfall.com/cards/random");
        $img = $c['image_uris']['large'] ?? ($c['card_faces'][0]['image_uris']['large'] ?? null);
        if (!$img || in_array($c['id']??'',$seen)) { usleep(80000); continue; }
        $seen[] = $c['id'];
        $pr = (float)($c['prices']['eur'] ?? $c['prices']['eur_foil'] ?? $c['prices']['usd'] ?? 0);
        $r  = rarityMagic($c['rarity'] ?? 'common');
        $cards[] = mk($c['id'],$c['name'],$img,'Magic',$r,$pr,toLJ($pr,$r));
        usleep(80000);
    }
}

elseif ($game === 'yugioh') {
    // Fetch a pool of cards and pick 5 random ones (more reliable than calling randomcard 5 times)
    $offset = rand(0, 200) * 10;
    $resp = curlGet("https://db.ygoprodeck.com/api/v7/cardinfo.php?num=20&offset={$offset}&sort=id");
    $pool = $resp['data'] ?? [];
    if (empty($pool)) {
        // fallback: try random endpoint
        $seen = []; $attempts = 0;
        while (count($cards) < 5 && $attempts < 12) {
            $attempts++;
            $resp2 = curlGet("https://db.ygoprodeck.com/api/v7/randomcard.php");
            $c = $resp2['data'][0] ?? $resp2;
            $img = $c['card_images'][0]['image_url'] ?? null;
            if (!$img || in_array($c['id']??0,$seen)) { usleep(60000); continue; }
            $seen[] = $c['id'];
            $p  = $c['card_prices'][0] ?? [];
            $pr = (float)($p['cardmarket_price'] ?? $p['tcgplayer_price'] ?? 0);
            $r  = rarityYGO($pr);
            $cards[] = mk((string)$c['id'],$c['name'],$img,'Yu-Gi-Oh!',$r,$pr,toLJ($pr,$r));
        }
    } else {
        shuffle($pool);
        foreach (array_slice($pool, 0, 5) as $c) {
            $img = $c['card_images'][0]['image_url'] ?? null;
            if (!$img) continue;
            $p  = $c['card_prices'][0] ?? [];
            $pr = (float)($p['cardmarket_price'] ?? $p['tcgplayer_price'] ?? 0);
            $r  = rarityYGO($pr);
            $cards[] = mk((string)$c['id'],$c['name'],$img,'Yu-Gi-Oh!',$r,$pr,toLJ($pr,$r));
        }
    }
}

elseif ($game === 'onepiece') {
    // Las URLs de One Piece TCG siguen el patrón predecible: SET-NNN.png
    // Generamos 5 combinaciones aleatorias de miles de posibles cartas reales
    $sets = [
        'OP01'=>121,'OP02'=>121,'OP03'=>121,'OP04'=>122,
        'OP05'=>122,'OP06'=>122,'OP07'=>119,'OP08'=>120,'OP09'=>119,
    ];
    $setKeys = array_keys($sets);
    $seen = [];
    $attempts = 0;
    while (count($cards) < 5 && $attempts < 20) {
        $attempts++;
        $set   = $setKeys[array_rand($setKeys)];
        $max   = $sets[$set];
        $num   = rand(1, $max);
        $code  = $set . '-' . str_pad($num, 3, '0', STR_PAD_LEFT);
        if (in_array($code, $seen)) continue;
        $seen[] = $code;
        $img   = "../api/onepiece_img.php?code={$code}";
        // Rareza por número: últimas cartas de cada set son más raras
        $pct   = $num / $max;
        $r     = $pct >= 0.92 ? 'ultra' : ($pct >= 0.70 ? 'rare' : 'common');
        $pr    = match($r) { 'ultra'=>rand(50,2500), 'rare'=>rand(10,80), default=>rand(2,25) };
        $cards[] = mk($code, "One Piece {$code}", $img, 'One Piece', $r, (float)$pr, toLJ((float)$pr, $r));
    }
}

if (count($cards) < 1) {
    ob_clean();
    echo json_encode(['ok'=>false,'message'=>'No se pudieron obtener cartas. Inténtalo de nuevo.']); exit;
}

// Descontar LJ y guardar cartas
$conn->begin_transaction();
try {
    $upd = $conn->prepare("UPDATE users SET lootcoins = lootcoins - ? WHERE id = ? AND lootcoins >= ?");
    $upd->bind_param("iii",$COST,$uid,$COST);
    $upd->execute();
    if ($upd->affected_rows < 1) throw new Exception('Saldo insuficiente');

    $ins = $conn->prepare("INSERT INTO pack_cards (user_id,card_id,card_name,card_image,card_game,card_rarity,market_price,lujanitos_value) VALUES (?,?,?,?,?,?,?,?)");
    foreach ($cards as &$c) {
        $ins->bind_param("isssssdd",$uid,$c['card_id'],$c['card_name'],$c['card_image'],
                         $c['card_game'],$c['card_rarity'],$c['market_price'],$c['lujanitos_value']);
        $ins->execute();
        $c['pack_card_id'] = $conn->insert_id;
    }
    unset($c);
    $conn->commit();
} catch (Exception $e) {
    $conn->rollback();
    ob_clean();
    echo json_encode(['ok'=>false,'message'=>$e->getMessage()]); exit;
}

// Obtener nuevo saldo
$st2 = $conn->prepare("SELECT lootcoins FROM users WHERE id = ?");
$st2->bind_param("i",$uid); $st2->execute();
$newBalance = (int)$st2->get_result()->fetch_assoc()['lootcoins'];

ob_clean();
echo json_encode(['ok'=>true,'cards'=>$cards,'new_balance'=>$newBalance,'cost'=>$COST]);
