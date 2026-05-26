<?php
// api/csrf_token.php — Devuelve el token CSRF actual de la sesión.
// Lo usa assets/js/csrf.js para auto-renovar el token si ha caducado.
require_once dirname(__DIR__) . '/includes/session.php';
require_once dirname(__DIR__) . '/includes/csrf.php';
header('Content-Type: application/json; charset=utf-8');
echo json_encode(['token' => csrf_token()]);
