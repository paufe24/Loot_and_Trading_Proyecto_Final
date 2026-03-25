<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['ok' => false, 'message' => 'Sesión requerida']); exit;
}

require_once dirname(__DIR__) . '/includes/csrf.php';
csrf_verify();

if (empty($_FILES['card_image']) || $_FILES['card_image']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['ok' => false, 'message' => 'No se recibió ningún archivo']); exit;
}

$file    = $_FILES['card_image'];
$maxSize = 5 * 1024 * 1024;
$allowed = ['image/jpeg','image/jpg','image/png','image/gif','image/webp'];
$exts    = ['image/jpeg'=>'jpg','image/jpg'=>'jpg','image/png'=>'png','image/gif'=>'gif','image/webp'=>'webp'];

if ($file['size'] > $maxSize) {
    echo json_encode(['ok' => false, 'message' => 'La imagen no puede superar 5 MB']); exit;
}

$finfo    = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if (!in_array($mimeType, $allowed)) {
    echo json_encode(['ok' => false, 'message' => 'Solo se permiten imágenes (JPG, PNG, GIF, WEBP)']); exit;
}

$dir = dirname(__DIR__) . '/img/cards/';
if (!is_dir($dir)) mkdir($dir, 0755, true);

$ext      = $exts[$mimeType];
$filename = 'card_' . $_SESSION['user_id'] . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
$destPath = $dir . $filename;
$urlPath  = 'img/cards/' . $filename;

if (!move_uploaded_file($file['tmp_name'], $destPath)) {
    echo json_encode(['ok' => false, 'message' => 'Error al guardar el archivo']); exit;
}

echo json_encode(['ok' => true, 'url' => $urlPath]);
