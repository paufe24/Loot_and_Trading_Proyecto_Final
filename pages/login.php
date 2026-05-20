<?php
require_once dirname(__DIR__) . '/includes/session.php';
require_once dirname(__DIR__) . '/includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    // Migración: añadir columnas si no existen
    try { $conn->query("ALTER TABLE users ADD COLUMN is_admin TINYINT(1) NOT NULL DEFAULT 0"); } catch (Exception $e) {}
    try { $conn->query("ALTER TABLE users ADD COLUMN is_envios TINYINT(1) NOT NULL DEFAULT 0"); } catch (Exception $e) {}

    // ── Rate Limiting: máximo 5 intentos cada 15 minutos por IP ──
    $conn->query("CREATE TABLE IF NOT EXISTS login_attempts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ip VARCHAR(45) NOT NULL,
        username VARCHAR(100) NOT NULL,
        attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_ip_time (ip, attempted_at)
    )");
    $ip = $_SERVER['REMOTE_ADDR'];
    $maxAttempts = 5;
    $windowMinutes = 15;

    $stLimit = $conn->prepare("SELECT COUNT(*) FROM login_attempts WHERE ip = ? AND attempted_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE)");
    $stLimit->bind_param("s", $ip);
    $stLimit->execute();
    $stLimit->bind_result($attempts);
    $stLimit->fetch();
    $stLimit->close();

    if ($attempts >= $maxAttempts) {
        echo "<script>alert('Demasiados intentos. Espera 15 minutos.'); window.location.href='auth.php'</script>";
        exit;
    }

    // Registrar intento
    $stLog = $conn->prepare("INSERT INTO login_attempts (ip, username) VALUES (?, ?)");
    $stLog->bind_param("ss", $ip, $username);
    $stLog->execute();
    $stLog->close();

    // Limpiar intentos viejos (>1 hora)
    $conn->query("DELETE FROM login_attempts WHERE attempted_at < DATE_SUB(NOW(), INTERVAL 1 HOUR)");

    // ── Verificar credenciales ──
    $stmt = $conn->prepare("SELECT id, username, password_hash, is_admin, is_envios FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    $genericError = 'Usuario o contraseña incorrectos';

    if ($user = $result->fetch_assoc()) {
        if (password_verify($password, $user['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['username']  = $user['username'];
            $_SESSION['is_admin']  = (bool)$user['is_admin'];
            $_SESSION['is_envios'] = (bool)$user['is_envios'];
            header("Location: " . ((bool)$user['is_envios'] ? "envios.php" : "index.php"));
            exit;
        } else {
            echo "<script>alert('$genericError'); window.location.href='auth.php'</script>";
        }
    } else {
        echo "<script>alert('$genericError'); window.location.href='auth.php'</script>";
    }

    $stmt->close();
    $conn->close();
}
?>