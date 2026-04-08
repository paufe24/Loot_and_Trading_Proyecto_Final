<?php
/**
 * seed_prices.php — Genera 30 dias de precio historico para las cartas de Supabase
 * Usa los precios actuales de las APIs externas como base y simula variaciones realistas.
 *
 * Ejecutar: php seed_prices.php   O   http://localhost/Loot_and_Trading_Proyecto_Final/seed_prices.php
 *
 * IMPORTANTE: Ejecutar solo una vez. Si se ejecuta de nuevo, inserta datos duplicados.
 */

set_time_limit(600);
header('Content-Type: text/plain; charset=utf-8');

$SUPABASE_URL = 'https://twnpxipxtmgdohjckbjn.supabase.co';
$SUPABASE_KEY = 'sb_publishable_lu1sQo0v_aKaXvNQCh6SOw_ENj5JTPu';

$TCG_IDS = [
    'pokemon'  => '21899972-d4ce-41e6-aeac-70d7a198d9ed',
    'yugioh'   => '14d81db0-0e72-40da-a764-633d63b9a008',
    'magic'    => 'b292bb55-9b18-4438-a134-1cc049e19867',
    'onepiece' => '3e7c1156-bfdd-4583-98fa-6564a8ea5f35',
];

function supabase_get($url, $key) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            "apikey: $key",
            "Authorization: Bearer $key",
        ],
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_TIMEOUT => 30,
    ]);
    $res = curl_exec($ch);
    curl_close($ch);
    return json_decode($res, true);
}

function supabase_post($url, $key, $data) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => [
            "apikey: $key",
            "Authorization: Bearer $key",
            "Content-Type: application/json",
            "Prefer: return=minimal",
        ],
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_TIMEOUT => 30,
    ]);
    $res = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return $code;
}

function fetch_pokemon_prices() {
    $prices = [];
    for ($page = 1; $page <= 3; $page++) {
        $url = "https://api.tcgdex.net/v2/en/cards?pagination:page=$page&pagination:itemsPerPage=50";
        $cards = json_decode(file_get_contents($url), true);
        if (!$cards) break;
        foreach ($cards as $c) {
            if (empty($c['id'])) continue;
            $detail = @json_decode(file_get_contents("https://api.tcgdex.net/v2/en/cards/{$c['id']}"), true);
            if (!$detail) continue;
            $p = $detail['pricing']['cardmarket']['trend'] ?? $detail['pricing']['tcgplayer']['holofoil']['marketPrice'] ?? null;
            if ($p && floatval($p) > 0) {
                $prices[$detail['name']] = floatval($p);
            }
        }
        if (count($prices) >= 80) break;
    }
    return $prices;
}

function fetch_yugioh_prices() {
    $prices = [];
    $url = "https://db.ygoprodeck.com/api/v7/cardinfo.php?num=100&offset=0&sort=price";
    $json = json_decode(file_get_contents($url), true);
    if ($json && isset($json['data'])) {
        foreach ($json['data'] as $card) {
            $p = $card['card_prices'][0]['tcgplayer_price'] ?? null;
            if ($p && floatval($p) > 0) {
                $prices[$card['name']] = floatval($p);
            }
        }
    }
    return $prices;
}

function fetch_magic_prices() {
    $prices = [];
    for ($page = 1; $page <= 2; $page++) {
        $url = "https://api.scryfall.com/cards/search?q=f:commander&order=usd&dir=desc&page=$page";
        $json = json_decode(file_get_contents($url), true);
        if (!$json || !isset($json['data'])) break;
        foreach ($json['data'] as $card) {
            $p = $card['prices']['usd'] ?? $card['prices']['usd_foil'] ?? null;
            if ($p && floatval($p) > 0) {
                $prices[$card['name']] = floatval($p);
            }
        }
        if (count($prices) >= 80) break;
        sleep(1); // Scryfall rate limit
    }
    return $prices;
}

function fetch_onepiece_prices() {
    $prices = [];
    $url = "https://www.optcgapi.com/api/allSetCards/";
    $json = json_decode(file_get_contents($url), true);
    if ($json) {
        usort($json, fn($a, $b) => ($b['market_price'] ?? 0) - ($a['market_price'] ?? 0));
        foreach (array_slice($json, 0, 100) as $card) {
            if (!empty($card['market_price']) && floatval($card['market_price']) > 0) {
                $prices[$card['card_name']] = floatval($card['market_price']);
            }
        }
    }
    return $prices;
}

function generate_history($basePrice, $days = 30) {
    $history = [];
    $price = $basePrice * (1 + (mt_rand(-15, 5) / 100)); // start slightly different
    for ($i = $days; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        // random daily variation: -5% to +5%
        $change = (mt_rand(-500, 500) / 10000);
        $price = $price * (1 + $change);
        $price = max($price, 0.01);
        $min = $price * (1 - mt_rand(5, 15) / 100);
        $max = $price * (1 + mt_rand(5, 15) / 100);
        $history[] = [
            'recorded_at' => $date,
            'avg_price' => round($price, 2),
            'min_price' => round($min, 2),
            'max_price' => round($max, 2),
            'total_listings' => mt_rand(3, 50),
            'total_sales' => mt_rand(0, 10),
        ];
    }
    return $history;
}

// ===== MAIN =====
echo "=== SEED PRICE HISTORY ===\n\n";

$fetchers = [
    'pokemon'  => 'fetch_pokemon_prices',
    'yugioh'   => 'fetch_yugioh_prices',
    'magic'    => 'fetch_magic_prices',
    'onepiece' => 'fetch_onepiece_prices',
];

$totalInserted = 0;

foreach ($fetchers as $game => $fetchFn) {
    echo "--- $game ---\n";
    echo "Fetching prices from external API...\n";
    $prices = $fetchFn();
    echo "Got " . count($prices) . " cards with prices\n";

    if (empty($prices)) {
        echo "No prices found, skipping\n\n";
        continue;
    }

    // Look up card_ids in Supabase by name in batches
    $names = array_keys($prices);
    $tcgId = $TCG_IDS[$game];
    $batchSize = 20;
    $gameInserted = 0;

    for ($i = 0; $i < count($names); $i += $batchSize) {
        $batch = array_slice($names, $i, $batchSize);
        $quoted = implode(',', array_map(fn($n) => '"' . str_replace('"', '\\"', $n) . '"', $batch));
        $url = "$SUPABASE_URL/rest/v1/cards?select=id,name&tcg_id=eq.$tcgId&name=in.(" . urlencode($quoted) . ")";
        $cards = supabase_get($url, $SUPABASE_KEY);

        if (!$cards || !is_array($cards)) continue;

        foreach ($cards as $card) {
            $cardName = $card['name'];
            $cardId = $card['id'];
            $basePrice = $prices[$cardName] ?? null;
            if (!$basePrice) continue;

            $history = generate_history($basePrice);

            // Insert all 31 days for this card
            $rows = [];
            foreach ($history as $h) {
                $rows[] = [
                    'card_id' => $cardId,
                    'avg_price' => $h['avg_price'],
                    'min_price' => $h['min_price'],
                    'max_price' => $h['max_price'],
                    'total_listings' => $h['total_listings'],
                    'total_sales' => $h['total_sales'],
                    'recorded_at' => $h['recorded_at'],
                ];
            }

            $code = supabase_post("$SUPABASE_URL/rest/v1/price_history", $SUPABASE_KEY, $rows);
            if ($code >= 200 && $code < 300) {
                $gameInserted++;
                echo "  OK: $cardName (" . count($rows) . " days)\n";
            } else {
                echo "  FAIL ($code): $cardName\n";
            }
        }
    }

    $totalInserted += $gameInserted;
    echo "Inserted history for $gameInserted cards ($game)\n\n";
}

echo "=== DONE: $totalInserted cards with 30-day history ===\n";
