<?php
require_once dirname(__DIR__) . '/includes/db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name     = trim($_POST['name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $address  = trim($_POST['address'] ?? '');

    // Validaciones básicas
    if ($name === '' || $username === '' || $email === '' || $password === '' || $address === '') {
        echo "<script>alert('Todos los campos son obligatorios.'); window.location.href='auth.php';</script>";
        exit;
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "<script>alert('El email no tiene un formato válido.'); window.location.href='auth.php';</script>";
        exit;
    }
    if (strlen($username) < 3 || strlen($username) > 30) {
        echo "<script>alert('El nombre de usuario debe tener entre 3 y 30 caracteres.'); window.location.href='auth.php';</script>";
        exit;
    }
    if (strlen($password) < 6) {
        echo "<script>alert('La contraseña debe tener al menos 6 caracteres.'); window.location.href='auth.php';</script>";
        exit;
    }

    // Verificar si el email ya existe
    $check_email = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $check_email->bind_param("s", $email);
    $check_email->execute();
    $result = $check_email->get_result();
    
    if ($result->num_rows > 0) {
        echo "<script>alert('Este email ya está registrado. Usa otro email o inicia sesión.'); window.location.href='auth.php';</script>";
        $check_email->close();
        exit;
    }
    $check_email->close();

    // Verificar si el username ya existe
    $check_username = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $check_username->bind_param("s", $username);
    $check_username->execute();
    $result = $check_username->get_result();
    
    if ($result->num_rows > 0) {
        echo "<script>alert('Este nombre de usuario ya está en uso. Elige otro.'); window.location.href='auth.php';</script>";
        $check_username->close();
        exit;
    }
    $check_username->close();

    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    // Añadir columna address si no existe
    try { $conn->query("ALTER TABLE users ADD COLUMN address TEXT NULL"); } catch (Exception $e) {}

    $stmt = $conn->prepare("INSERT INTO users (name, email, username, password_hash, lootcoins, address) VALUES (?, ?, ?, ?, 1000, ?)");
    $stmt->bind_param("sssss", $name, $email, $username, $password_hash, $address);

    if ($stmt->execute()) {
        echo "<script>window.location.href='auth.php'; setTimeout(function() { toggleForm('login'); }, 100);</script>";
    } else {
        echo "<script>alert('Error en el registro. Inténtalo de nuevo.'); window.location.href='auth.php';</script>";
    }

    $stmt->close();
    $conn->close();
}
?>