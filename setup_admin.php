<?php
require_once 'includes/db.php';

// Migración columna is_admin
try { $conn->query("ALTER TABLE users ADD COLUMN is_admin TINYINT(1) NOT NULL DEFAULT 0"); } catch (Exception $e) {}

$username = 'alexadmin';
$password = 'alexadmin123';
$hash     = password_hash($password, PASSWORD_DEFAULT);

// ¿Ya existe?
$check = $conn->prepare("SELECT id FROM users WHERE username = ?");
$check->bind_param("s", $username);
$check->execute();
$exists = $check->get_result()->fetch_assoc();

if ($exists) {
    $s = $conn->prepare("UPDATE users SET password_hash = ?, is_admin = 1 WHERE username = ?");
    $s->bind_param("ss", $hash, $username);
    $s->execute();
    $msg = "Usuario '$username' ya existía → contraseña actualizada y admin activado.";
} else {
    $name  = 'Alex Admin';
    $email = 'alexadmin@loottrading.local';
    $coins = 999999;
    $s = $conn->prepare("INSERT INTO users (name, username, email, password_hash, lootcoins, address, is_admin) VALUES (?, ?, ?, ?, ?, '', 1)");
    $s->bind_param("ssssi", $name, $username, $email, $hash, $coins);
    $s->execute();
    $msg = "Usuario '$username' creado correctamente.";
}
?>
<!DOCTYPE html>
<html lang="es">
<head><meta charset="UTF-8"><title>Setup Admin</title>
<style>body{font-family:sans-serif;max-width:500px;margin:60px auto;padding:20px;}</style>
</head>
<body>
<h2>✅ <?php echo $msg; ?></h2>
<hr>
<p><b>Usuario:</b> alexadmin</p>
<p><b>Contraseña:</b> alexadmin123</p>
<hr>
<p style="color:red;"><b>⚠️ Elimina este archivo cuando termines:</b><br>
<code>C:\xampp\htdocs\Loot_and_Trading_Proyecto_Final\setup_admin.php</code></p>
<a href="pages/auth.php">→ Ir al login</a>
</body>
</html>
