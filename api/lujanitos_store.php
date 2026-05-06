<?php
// api/lujanitos_store.php — Compra simulada de Lujanitos (1€ = 1 LJ)
session_start();
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/csrf.php';
require_once dirname(__DIR__) . '/includes/gamification.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['ok' => false, 'message' => 'Debes iniciar sesión']); exit;
}
$user_id = (int)$_SESSION['user_id'];

csrf_verify();

$amount = (int)($_POST['amount'] ?? 0);

$valid = [100, 500, 1000, 5000];
if (!in_array($amount, $valid, true)) {
    echo json_encode(['ok' => false, 'message' => 'Paquete inválido']); exit;
}

// Migración por si acaso
try { $conn->query("ALTER TABLE users ADD COLUMN lootcoins INT NOT NULL DEFAULT 1000"); } catch (Exception $e) {}

// Añadir Lujanitos al saldo
$st = $conn->prepare("UPDATE users SET lootcoins = lootcoins + ? WHERE id = ?");
$st->bind_param("ii", $amount, $user_id);
$st->execute();

// Registrar en actividad
$conn->query("CREATE TABLE IF NOT EXISTS user_activity (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    activity_type VARCHAR(30) NOT NULL,
    title VARCHAR(255) NOT NULL,
    description VARCHAR(500) NOT NULL,
    ref_id INT NULL,
    amount DECIMAL(10,2) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_created (user_id, created_at),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)");
$euros = $amount; // 1€ = 1 LJ
$ins = $conn->prepare(
    "INSERT INTO user_activity (user_id, activity_type, title, description, amount) VALUES (?,?,?,?,?)"
);
$type  = 'purchase';
$title = "Compra de Lujanitos";
$desc  = "Compraste $amount Lujanitos por {$euros}€";
$ins->bind_param("isssd", $user_id, $type, $title, $desc, $euros);
$ins->execute();

addXP($conn, $user_id, 5);

// Devolver nuevo saldo
$res     = $conn->query("SELECT lootcoins FROM users WHERE id=$user_id");
$newBal  = (int)$res->fetch_assoc()['lootcoins'];

echo json_encode(['ok' => true, 'balance' => $newBal, 'added' => $amount, 'message' => "¡$amount Lujanitos añadidos!"]);
