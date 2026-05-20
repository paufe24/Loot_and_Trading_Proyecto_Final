<?php
// includes/security_headers.php — Headers de seguridad HTTP
// Solo enviar si los headers aún no se han enviado (evita warnings si ya hay output)
if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('X-XSS-Protection: 1; mode=block');
}
