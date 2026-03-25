<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$game   = $_GET['game'] ?? '';
$offset = intval($_GET['offset'] ?? 0);

if ($game === 'onepiece') {
    $cache_file = dirname(__DIR__) . '/cache/cache_onepiece.json';
    $cache_ttl  = 86400; // 24h

    // Servir desde caché si existe y es reciente
    if (file_exists($cache_file) && (time() - filemtime($cache_file)) < $cache_ttl) {
        echo file_get_contents($cache_file);
        exit;
    }

    // Descargar y guardar en caché
    $ch = curl_init('https://www.optcgapi.com/api/allSetCards/');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['User-Agent: Mozilla/5.0', 'Accept: application/json']);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($response && $httpCode === 200) {
        file_put_contents($cache_file, $response);
        echo $response;
    } else {
        // Caché expirada pero API no responde — servir caché antigua si existe
        if (file_exists($cache_file)) {
            echo file_get_contents($cache_file);
        } else {
            http_response_code(502);
            echo json_encode(['error' => "Error API One Piece (HTTP $httpCode)"]);
        }
    }
    exit;
}

if ($game !== 'yugioh') {
    http_response_code(400);
    echo json_encode(['error' => 'Juego no soportado en proxy']);
    exit;
}

$url = "https://db.ygoprodeck.com/api/v7/cardinfo.php?num=20&offset={$offset}&sort=name";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 15);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['User-Agent: TCGVerse/1.0']);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if (!$response || $httpCode !== 200) {
    http_response_code(502);
    echo json_encode(['error' => "Error API (HTTP $httpCode)"]);
    exit;
}

echo $response;
