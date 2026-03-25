<?php
session_start();

// Verificar si el usuario está logueado
if (!isset($_SESSION['user_id'])) {
    header("Location: auth.php");
    exit;
}

require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/csrf.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    csrf_verify();
    $name     = $_POST['name'];
    $username = $_POST['username'];
    $email    = $_POST['email'];
    $address  = trim($_POST['address'] ?? '');
    $user_id  = $_SESSION['user_id'];

    // Verificar si el email ya existe (excepto el actual)
    $check_email = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $check_email->bind_param("si", $email, $user_id);
    $check_email->execute();
    $result = $check_email->get_result();
    
    if ($result->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Este email ya está registrado. Usa otro email.']);
        $check_email->close();
        exit;
    }
    $check_email->close();

    // Verificar si el username ya existe (excepto el actual)
    $check_username = $conn->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
    $check_username->bind_param("si", $username, $user_id);
    $check_username->execute();
    $result = $check_username->get_result();
    
    if ($result->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Este nombre de usuario ya está en uso. Elige otro.']);
        $check_username->close();
        exit;
    }
    $check_username->close();

    // Añadir columna address si no existe (por compatibilidad)
    try { $conn->query("ALTER TABLE users ADD COLUMN address TEXT NULL"); } catch (Exception $e) {}

    // Actualizar datos del usuario
    $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, username = ?, address = ? WHERE id = ?");
    $stmt->bind_param("ssssi", $name, $email, $username, $address, $user_id);

    if ($stmt->execute()) {
        // Actualizar sesión
        $_SESSION['username'] = $username;
        echo json_encode(['success' => true, 'message' => 'Perfil actualizado correctamente.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al actualizar el perfil. Inténtalo de nuevo.']);
    }

    $stmt->close();
    $conn->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
}
?>
