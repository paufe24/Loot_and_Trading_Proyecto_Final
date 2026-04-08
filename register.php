<?php
include 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = $_POST['password'];

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

    $stmt = $conn->prepare("INSERT INTO users (name, email, username, password_hash) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $name, $email, $username, $password_hash);

    if ($stmt->execute()) {
        echo "<script>window.location.href='auth.php'; setTimeout(function() { toggleForm('login'); }, 100);</script>";
    } else {
        echo "<script>alert('Error en el registro. Inténtalo de nuevo.'); window.location.href='auth.php';</script>";
    }

    $stmt->close();
    $conn->close();
}
?>