<?php
session_start();
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT id, username, password_hash FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($user = $result->fetch_assoc()) {
        if (password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            header("Location: index.php"); // ← era index.html, ESTE ES EL BUG PRINCIPAL
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