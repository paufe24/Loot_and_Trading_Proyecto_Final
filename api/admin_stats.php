<?php
session_start();
if (empty($_SESSION['is_admin'])) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'message' => 'No autorizado']);
    exit;
}
require_once dirname(__DIR__) . '/includes/db.php';
header('Content-Type: application/json; charset=utf-8');

$stat = $_GET['stat'] ?? '';

switch ($stat) {

    // ── Registros de usuarios por mes (últimos 6 meses) ──
    case 'users_per_month':
        $rows = [];
        $r = $conn->query("
            SELECT DATE_FORMAT(created_at, '%Y-%m') AS mes,
                   COUNT(*) AS total
            FROM users
            WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
            GROUP BY mes
            ORDER BY mes ASC
        ");
        while ($row = $r->fetch_assoc()) $rows[] = $row;
        echo json_encode(['ok' => true, 'data' => $rows]);
        break;

    // ── Ventas (pedidos) por mes (últimos 6 meses) ──
    case 'orders_per_month':
        $rows = [];
        $r = $conn->query("
            SELECT DATE_FORMAT(created_at, '%Y-%m') AS mes,
                   COUNT(*) AS total,
                   COALESCE(SUM(total_amount), 0) AS revenue
            FROM cart_orders
            WHERE status = 'paid'
              AND created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
            GROUP BY mes
            ORDER BY mes ASC
        ");
        while ($row = $r->fetch_assoc()) $rows[] = $row;
        echo json_encode(['ok' => true, 'data' => $rows]);
        break;

    // ── Cartas más vendidas (top 10 por cantidad) ──
    case 'top_cards_sold':
        $rows = [];
        $r = $conn->query("
            SELECT card_name, card_game, SUM(quantity) AS total_sold,
                   SUM(subtotal) AS total_revenue
            FROM cart_order_items
            GROUP BY card_id, card_name, card_game
            ORDER BY total_sold DESC
            LIMIT 10
        ");
        while ($row = $r->fetch_assoc()) $rows[] = $row;
        echo json_encode(['ok' => true, 'data' => $rows]);
        break;

    // ── Distribución de ventas por juego ──
    case 'sales_by_game':
        $rows = [];
        $r = $conn->query("
            SELECT card_game, COUNT(*) AS total_items, SUM(subtotal) AS revenue
            FROM cart_order_items
            GROUP BY card_game
            ORDER BY revenue DESC
        ");
        while ($row = $r->fetch_assoc()) $rows[] = $row;
        echo json_encode(['ok' => true, 'data' => $rows]);
        break;

    // ── Subastas por juego (activas + terminadas) ──
    case 'auctions_by_game':
        $rows = [];
        $r = $conn->query("
            SELECT card_game,
                   SUM(status = 'active') AS activas,
                   SUM(status = 'ended') AS terminadas,
                   COUNT(*) AS total
            FROM auctions
            GROUP BY card_game
            ORDER BY total DESC
        ");
        while ($row = $r->fetch_assoc()) $rows[] = $row;
        echo json_encode(['ok' => true, 'data' => $rows]);
        break;

    // ── Actividad de usuarios por tipo ──
    case 'activity_by_type':
        $rows = [];
        $r = $conn->query("
            SELECT activity_type, COUNT(*) AS total
            FROM user_activity
            GROUP BY activity_type
            ORDER BY total DESC
        ");
        while ($row = $r->fetch_assoc()) $rows[] = $row;
        echo json_encode(['ok' => true, 'data' => $rows]);
        break;

    // ── Actividad diaria (últimos 14 días) ──
    case 'activity_daily':
        $rows = [];
        $r = $conn->query("
            SELECT DATE(created_at) AS dia, COUNT(*) AS total
            FROM user_activity
            WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 14 DAY)
            GROUP BY dia
            ORDER BY dia ASC
        ");
        while ($row = $r->fetch_assoc()) $rows[] = $row;
        echo json_encode(['ok' => true, 'data' => $rows]);
        break;

    // ── Top usuarios por Lujanitos ──
    case 'top_users_coins':
        $rows = [];
        $r = $conn->query("
            SELECT username, lootcoins
            FROM users
            ORDER BY lootcoins DESC
            LIMIT 10
        ");
        while ($row = $r->fetch_assoc()) $rows[] = $row;
        echo json_encode(['ok' => true, 'data' => $rows]);
        break;

    // ── Top usuarios por XP / Nivel ──
    case 'top_users_xp':
        $rows = [];
        $r = $conn->query("
            SELECT username, xp
            FROM users
            ORDER BY xp DESC
            LIMIT 10
        ");
        while ($row = $r->fetch_assoc()) $rows[] = $row;
        echo json_encode(['ok' => true, 'data' => $rows]);
        break;

    // ── Pujas por hora del día (patrón de uso) ──
    case 'bids_by_hour':
        $rows = [];
        $r = $conn->query("
            SELECT HOUR(created_at) AS hora, COUNT(*) AS total
            FROM auction_bids
            GROUP BY hora
            ORDER BY hora ASC
        ");
        while ($row = $r->fetch_assoc()) $rows[] = $row;
        echo json_encode(['ok' => true, 'data' => $rows]);
        break;

    // ── Ingresos por subastas (pujas ganadoras por mes) ──
    case 'auction_revenue':
        $rows = [];
        $r = $conn->query("
            SELECT DATE_FORMAT(ends_at, '%Y-%m') AS mes,
                   COUNT(*) AS subastas,
                   COALESCE(SUM(current_bid), 0) AS total_bid
            FROM auctions
            WHERE status = 'ended' AND current_bid > 0
              AND ends_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
            GROUP BY mes
            ORDER BY mes ASC
        ");
        while ($row = $r->fetch_assoc()) $rows[] = $row;
        echo json_encode(['ok' => true, 'data' => $rows]);
        break;

    default:
        echo json_encode(['ok' => false, 'message' => 'Stat desconocida']);
}
