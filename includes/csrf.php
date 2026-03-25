<?php
// Genera (o recupera) el token CSRF de la sesión.
// La sesión debe estar iniciada antes de llamar a estas funciones.

function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Valida el token enviado en la cabecera X-CSRF-Token o en el POST field csrf_token.
// Llama a exit() con 403 si no es válido.
function csrf_verify(): void {
    $sent = $_SERVER['HTTP_X_CSRF_TOKEN']
         ?? $_POST['csrf_token']
         ?? '';
    $expected = $_SESSION['csrf_token'] ?? '';
    if (!$expected || !hash_equals($expected, $sent)) {
        http_response_code(403);
        echo json_encode(['ok' => false, 'message' => 'Token de seguridad inválido. Recarga la página.']);
        exit;
    }
}
