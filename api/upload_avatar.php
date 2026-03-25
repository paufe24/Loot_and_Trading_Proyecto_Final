<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['ok' => false, 'message' => 'Sesión requerida']); exit;
}

require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/csrf.php';
csrf_verify();
$user_id = (int)$_SESSION['user_id'];

if (empty($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['ok' => false, 'message' => 'No se recibió ningún archivo']); exit;
}

$file     = $_FILES['avatar'];
$maxSize  = 5 * 1024 * 1024; // 5 MB
$allowed  = ['image/jpeg','image/jpg','image/png','image/gif','image/webp'];
$exts     = ['image/jpeg'=>'jpg','image/jpg'=>'jpg','image/png'=>'png','image/gif'=>'gif','image/webp'=>'webp'];

if ($file['size'] > $maxSize) {
    echo json_encode(['ok' => false, 'message' => 'La imagen no puede superar 5 MB']); exit;
}

// Verificar tipo real con finfo
$finfo    = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if (!in_array($mimeType, $allowed)) {
    echo json_encode(['ok' => false, 'message' => 'Solo se permiten imágenes (JPG, PNG, GIF, WEBP)']); exit;
}

$dir = dirname(__DIR__) . '/img/avatars/';
if (!is_dir($dir)) mkdir($dir, 0755, true);

// Eliminar avatar anterior si existe
$prev = $conn->prepare("SELECT avatar_url FROM users WHERE id = ?");
$prev->bind_param("i", $user_id);
$prev->execute();
$prevUrl = $prev->get_result()->fetch_assoc()['avatar_url'] ?? '';
if ($prevUrl && strpos($prevUrl, 'img/avatars/') !== false) {
    $prevPath = dirname(__DIR__) . '/' . $prevUrl;
    if (file_exists($prevPath)) unlink($prevPath);
}

$ext      = $exts[$mimeType];
$filename = 'avatar_' . $user_id . '_' . time() . '.' . $ext;
$destPath = $dir . $filename;
$urlPath  = 'img/avatars/' . $filename;

if (!move_uploaded_file($file['tmp_name'], $destPath)) {
    echo json_encode(['ok' => false, 'message' => 'Error al guardar el archivo']); exit;
}

$stmt = $conn->prepare("UPDATE users SET avatar_url = ? WHERE id = ?");
$stmt->bind_param("si", $urlPath, $user_id);
$stmt->execute();

echo json_encode(['ok' => true, 'url' => $urlPath]);
