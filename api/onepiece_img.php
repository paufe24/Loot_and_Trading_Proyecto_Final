<?php
// Proxy para imágenes de One Piece TCG (evita bloqueo de hotlinking)
$code = preg_replace('/[^A-Za-z0-9\-]/', '', $_GET['code'] ?? '');
if (!$code) { http_response_code(400); exit; }

$url = "https://en.onepiece-cardgame.com/images/cardlist/card/{$code}.png";

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 15,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTPHEADER     => [
        'Referer: https://en.onepiece-cardgame.com/',
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0 Safari/537.36',
        'Accept: image/avif,image/webp,image/apng,image/*,*/*;q=0.8',
    ],
]);
$img    = curl_exec($ch);
$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$ct     = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
curl_close($ch);

if (!$img || $status !== 200) { http_response_code(404); exit; }

header('Content-Type: '    . ($ct ?: 'image/png'));
header('Cache-Control: public, max-age=86400');
echo $img;
