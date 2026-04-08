<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$game   = $_GET['game'] ?? '';
$offset = intval($_GET['offset'] ?? 0);

if ($game !== 'yugioh') {
    http_response_code(400);
    echo json_encode(['error' => 'Solo yugioh soportado en proxy']);
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
