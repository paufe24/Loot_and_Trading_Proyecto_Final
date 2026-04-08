<?php
session_start();
require_once dirname(__DIR__) . '/includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    // Migración: añadir columna is_admin si no existe
    try { $conn->query("ALTER TABLE users ADD COLUMN is_admin TINYINT(1) NOT NULL DEFAULT 0"); } catch (Exception $e) {}

    $stmt = $conn->prepare("SELECT id, username, password_hash, is_admin, is_envios FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($user = $result->fetch_assoc()) {
        if (password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['username']  = $user['username'];
            $_SESSION['is_admin']  = (bool)$user['is_admin'];
            $_SESSION['is_envios'] = (bool)$user['is_envios'];
            header("Location: " . ((bool)$user['is_envios'] ? "envios.php" : "index.php"));
            exit;
        } else {
            echo "<script>alert('Contraseña incorrecta'); window.location.href='auth.html'</script>";
        }
    } else {
        echo "<script>alert('El usuario no existe'); window.location.href='auth.html'</script>";
    }

    $stmt->close();
    $conn->close();
}
?>