<?php
// includes/session.php — Inicio de sesión seguro con cookie hardening
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,           // Expira al cerrar el navegador
        'path'     => '/',
        'domain'   => '',
        'secure'   => isset($_SERVER['HTTPS']), // Solo HTTPS si disponible
        'httponly'  => true,        // JavaScript NO puede leer la cookie
        'samesite' => 'Lax'        // Protege contra CSRF pero permite navegación normal
    ]);
    session_start();

    // Enviar headers de seguridad antes de cualquier output
    require_once __DIR__ . '/security_headers.php';
}
