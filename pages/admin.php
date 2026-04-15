<?php
session_start();
if (empty($_SESSION['is_admin'])) {
    header('Location: index.php');
    exit;
}
require_once dirname(__DIR__) . '/includes/db.php';

// Migraciones
try { $conn->query("ALTER TABLE users ADD COLUMN is_admin TINYINT(1) NOT NULL DEFAULT 0"); } catch (Exception $e) {}
try { $conn->query("ALTER TABLE users ADD COLUMN lootcoins INT NOT NULL DEFAULT 1000"); } catch (Exception $e) {}

// ── Acciones POST ──────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=utf-8');
    $action = $_POST['action'] ?? '';

    if ($action === 'toggle_admin') {
        $uid = (int)($_POST['user_id'] ?? 0);
        if ($uid === (int)$_SESSION['user_id']) {
            echo json_encode(['ok' => false, 'message' => 'No puedes quitarte el admin a ti mismo']);
            exit;
        }
        $s = $conn->prepare("UPDATE users SET is_admin = 1 - is_admin WHERE id = ?");
        $s->bind_param("i", $uid);
        $s->execute();
        echo json_encode(['ok' => true]);
        exit;
    }

    if ($action === 'set_coins') {
        $uid   = (int)($_POST['user_id'] ?? 0);
        $coins = (int)($_POST['coins'] ?? 0);
        if ($coins < 0) $coins = 0;
        $s = $conn->prepare("UPDATE users SET lootcoins = ? WHERE id = ?");
        $s->bind_param("ii", $coins, $uid);
        $s->execute();
        echo json_encode(['ok' => true]);
        exit;
    }

    if ($action === 'delete_auction') {
        $aid = (int)($_POST['auction_id'] ?? 0);
        $conn->prepare("DELETE FROM auctions WHERE id = ?")->execute() || null;
        $s = $conn->prepare("DELETE FROM auctions WHERE id = ?");
        $s->bind_param("i", $aid);
        $s->execute();
        echo json_encode(['ok' => true]);
        exit;
    }

    echo json_encode(['ok' => false, 'message' => 'Acción desconocida']);
    exit;
}

// ── Stats globales ─────────────────────────────────────────────
$totalUsers     = (int)($conn->query("SELECT COUNT(*) n FROM users")->fetch_assoc()['n'] ?? 0);
$totalAuctions  = (int)($conn->query("SELECT COUNT(*) n FROM auctions")->fetch_assoc()['n'] ?? 0);
$activeAuctions = (int)($conn->query("SELECT COUNT(*) n FROM auctions WHERE status='active'")->fetch_assoc()['n'] ?? 0);
$totalOrders    = (int)($conn->query("SELECT COUNT(*) n FROM cart_orders")->fetch_assoc()['n'] ?? 0);
$totalCoins     = (int)($conn->query("SELECT COALESCE(SUM(lootcoins),0) n FROM users")->fetch_assoc()['n'] ?? 0);
$totalActivity  = (int)($conn->query("SELECT COUNT(*) n FROM user_activity")->fetch_assoc()['n'] ?? 0);
$totalBids      = 0;
try { $totalBids = (int)($conn->query("SELECT COUNT(*) n FROM auction_bids")->fetch_assoc()['n'] ?? 0); } catch (Exception $e) {}
$totalRevenue   = 0;
try { $totalRevenue = (float)($conn->query("SELECT COALESCE(SUM(total_amount),0) n FROM cart_orders WHERE status='paid'")->fetch_assoc()['n'] ?? 0); } catch (Exception $e) {}

// ── Usuarios ───────────────────────────────────────────────────
$users = [];
$r = $conn->query("SELECT id, name, username, email, lootcoins, is_admin, created_at FROM users ORDER BY created_at DESC");
while ($row = $r->fetch_assoc()) $users[] = $row;

// ── Subastas recientes ─────────────────────────────────────────
$auctions = [];
$r = $conn->query("SELECT a.id, a.card_name, a.card_game, a.current_bid, a.status, a.ends_at,
                          u.username AS seller
                   FROM auctions a
                   LEFT JOIN users u ON u.id = a.seller_id
                   ORDER BY a.id DESC LIMIT 20");
while ($row = $r->fetch_assoc()) $auctions[] = $row;

// ── Actividad reciente global ──────────────────────────────────
$activity = [];
$r = $conn->query("SELECT ua.activity_type, ua.title, ua.description, ua.created_at, u.username
                   FROM user_activity ua
                   JOIN users u ON u.id = ua.user_id
                   ORDER BY ua.created_at DESC LIMIT 20");
while ($row = $r->fetch_assoc()) $activity[] = $row;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Admin | Loot&Trading</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/styles.css?v=<?php echo time(); ?>">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
    <style>
        .admin-wrap { max-width: 1400px; margin: 0 auto; padding: 30px 20px 80px; }

        /* Header */
        .admin-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 32px; flex-wrap: wrap; gap: 16px; }
        .admin-header-left {}
        .admin-title { font-size: 2rem; font-weight: 800; margin-bottom: 4px; display: flex; align-items: center; gap: 10px; }
        .admin-sub { color: var(--text-secondary); font-size: .95rem; }

        /* Tabs */
        .admin-tabs { display: flex; gap: 4px; background: #f1f5f9; border-radius: 14px; padding: 4px; }
        body.dark .admin-tabs { background: #0f172a; }
        .admin-tab { padding: 9px 20px; border-radius: 10px; font-weight: 700; font-size: .85rem; cursor: pointer; border: none; background: transparent; color: var(--text-secondary); font-family: 'Outfit',sans-serif; transition: all .2s; }
        .admin-tab:hover { color: var(--text-primary); }
        .admin-tab.active { background: #fff; color: var(--text-primary); box-shadow: 0 2px 8px rgba(0,0,0,.06); }
        body.dark .admin-tab.active { background: #1e293b; color: #e2e8f0; }

        /* Tab panels */
        .tab-panel { display: none; }
        .tab-panel.active { display: block; }

        /* Stats */
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 16px; margin-bottom: 32px; }
        .stat-card { background: #fff; border: 1px solid var(--border-color); border-radius: 18px; padding: 22px 20px; position: relative; overflow: hidden; transition: transform .2s, box-shadow .2s; }
        .stat-card:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(0,0,0,.06); }
        body.dark .stat-card { background: #1e293b; border-color: #334155; }
        body.dark .stat-card:hover { box-shadow: 0 8px 24px rgba(0,0,0,.3); }
        .stat-card .stat-icon { width: 42px; height: 42px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; margin-bottom: 14px; }
        .stat-card .stat-value { font-size: 1.9rem; font-weight: 800; line-height: 1; margin-bottom: 4px; }
        .stat-card .stat-label { font-size: .78rem; font-weight: 700; color: var(--text-secondary); text-transform: uppercase; letter-spacing: .04em; }
        .stat-icon.blue { background: rgba(59,130,246,.1); color: #3b82f6; }
        .stat-icon.green { background: rgba(16,185,129,.1); color: #10b981; }
        .stat-icon.amber { background: rgba(245,158,11,.1); color: #f59e0b; }
        .stat-icon.purple { background: rgba(139,92,246,.1); color: #8b5cf6; }
        .stat-icon.red { background: rgba(239,68,68,.1); color: #ef4444; }
        .stat-icon.cyan { background: rgba(6,182,212,.1); color: #06b6d4; }
        .stat-icon.pink { background: rgba(236,72,153,.1); color: #ec4899; }
        .stat-icon.teal { background: rgba(20,184,166,.1); color: #14b8a6; }

        /* Charts grid */
        .charts-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; margin-bottom: 28px; }
        .chart-card { background: #fff; border: 1px solid var(--border-color); border-radius: 18px; padding: 24px; }
        body.dark .chart-card { background: #1e293b; border-color: #334155; }
        .chart-card h3 { font-size: 1rem; font-weight: 800; margin-bottom: 4px; }
        .chart-card .chart-sub { font-size: .8rem; color: var(--text-secondary); margin-bottom: 18px; }
        .chart-card canvas { max-height: 280px; }
        .chart-card.full-width { grid-column: 1 / -1; }

        /* Sections */
        .admin-section { background: #fff; border: 1px solid var(--border-color); border-radius: 18px; padding: 24px; margin-bottom: 20px; }
        body.dark .admin-section { background: #1e293b; border-color: #334155; }
        .admin-section-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 18px; flex-wrap: wrap; gap: 8px; }
        .admin-section-header h2 { font-size: 1.1rem; font-weight: 800; display: flex; align-items: center; gap: 8px; }
        .section-badge { font-size: .72rem; font-weight: 800; padding: 3px 10px; border-radius: 20px; background: rgba(59,130,246,.1); color: #3b82f6; }

        /* Table */
        .admin-table { width: 100%; border-collapse: collapse; font-size: .85rem; }
        .admin-table th { text-align: left; padding: 10px 12px; font-size: .72rem; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; color: var(--text-secondary); border-bottom: 2px solid var(--border-color); }
        .admin-table td { padding: 10px 12px; border-bottom: 1px solid var(--border-color); vertical-align: middle; }
        .admin-table tr:last-child td { border-bottom: none; }
        .admin-table tbody tr { transition: background .15s; }
        .admin-table tbody tr:hover { background: rgba(59,130,246,.03); }
        body.dark .admin-table tbody tr:hover { background: rgba(96,165,250,.04); }
        body.dark .admin-table th, body.dark .admin-table td { border-color: #334155; }

        /* Badges */
        .badge-admin  { background: rgba(245,158,11,.15); color: #d97706; font-size: .72rem; font-weight: 800; padding: 3px 10px; border-radius: 20px; }
        .badge-user   { background: rgba(100,116,139,.1);  color: #64748b; font-size: .72rem; font-weight: 800; padding: 3px 10px; border-radius: 20px; }
        .badge-active { background: rgba(16,185,129,.15); color: #059669; font-size: .72rem; font-weight: 800; padding: 3px 10px; border-radius: 20px; }
        .badge-ended  { background: rgba(100,116,139,.1);  color: #64748b; font-size: .72rem; font-weight: 800; padding: 3px 10px; border-radius: 20px; }

        /* Inline controls */
        .coins-input { width: 90px; padding: 6px 10px; border: 1.5px solid var(--border-color); border-radius: 10px; font-size: .85rem; font-family: 'Outfit',sans-serif; background: transparent; color: var(--text-primary); }
        body.dark .coins-input { border-color: #475569; }
        .btn-xs { padding: 6px 14px; border-radius: 10px; font-size: .78rem; font-weight: 700; border: none; cursor: pointer; transition: .2s; }
        .btn-xs.blue   { background: var(--accent-blue); color: #fff; }
        .btn-xs.amber  { background: #f59e0b; color: #fff; }
        .btn-xs.red    { background: #ef4444; color: #fff; }
        .btn-xs:hover  { opacity: .85; transform: translateY(-1px); }
        .btn-xs:disabled { opacity: .4; cursor: not-allowed; transform: none; }

        .activity-type-badge { font-size: .7rem; font-weight: 800; padding: 3px 9px; border-radius: 20px; }
        .at-order         { background: rgba(16,185,129,.12); color: #059669; }
        .at-auction_win   { background: rgba(245,158,11,.12); color: #d97706; }
        .at-auction_claim { background: rgba(139,92,246,.12); color: #7c3aed; }
        .at-level_up      { background: rgba(59,130,246,.12); color: #3b82f6; }
        .at-achievement   { background: rgba(236,72,153,.12); color: #db2777; }
        .at-price_alert   { background: rgba(6,182,212,.12); color: #0891b2; }
        .at-default       { background: rgba(100,116,139,.1); color: #64748b; }

        /* Ranking cards */
        .ranking-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; margin-bottom: 28px; }
        .ranking-card { background: #fff; border: 1px solid var(--border-color); border-radius: 18px; padding: 24px; }
        body.dark .ranking-card { background: #1e293b; border-color: #334155; }
        .ranking-card h3 { font-size: 1rem; font-weight: 800; margin-bottom: 16px; display: flex; align-items: center; gap: 8px; }
        .ranking-item { display: flex; align-items: center; gap: 12px; padding: 8px 0; border-bottom: 1px solid var(--border-color); }
        .ranking-item:last-child { border-bottom: none; }
        body.dark .ranking-item { border-color: #334155; }
        .ranking-pos { width: 28px; height: 28px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: .78rem; font-weight: 800; background: #f1f5f9; color: var(--text-secondary); flex-shrink: 0; }
        body.dark .ranking-pos { background: #0f172a; }
        .ranking-pos.gold   { background: rgba(245,158,11,.15); color: #d97706; }
        .ranking-pos.silver { background: rgba(148,163,184,.15); color: #64748b; }
        .ranking-pos.bronze { background: rgba(180,83,9,.12); color: #b45309; }
        .ranking-name { font-weight: 700; flex: 1; font-size: .9rem; }
        .ranking-value { font-weight: 800; font-size: .9rem; color: var(--accent-blue); }

        /* Loading placeholder */
        .chart-loading { display: flex; align-items: center; justify-content: center; min-height: 200px; color: var(--text-secondary); font-size: .9rem; }

        @media (max-width: 900px) {
            .charts-grid { grid-template-columns: 1fr; }
            .ranking-grid { grid-template-columns: 1fr; }
            .admin-header { flex-direction: column; align-items: flex-start; }
            .admin-tabs { overflow-x: auto; }
        }
        @media (max-width: 600px) {
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
        }
    </style>
</head>
<body>
<script>(function(){ if(localStorage.getItem('theme')==='dark') document.body.classList.add('dark'); })();</script>
<?php include dirname(__DIR__) . '/includes/navbar.php'; ?>

<div class="main-wrapper">
<div class="admin-wrap">

    <!-- Header -->
    <div class="admin-header">
        <div class="admin-header-left">
            <div class="admin-title">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z"/></svg>
                Panel de Administración
            </div>
            <div class="admin-sub">Bienvenido, <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong>. Vista general de la plataforma.</div>
        </div>
        <div class="admin-tabs">
            <button class="admin-tab active" data-tab="dashboard">Dashboard</button>
            <button class="admin-tab" data-tab="users">Usuarios</button>
            <button class="admin-tab" data-tab="auctions">Subastas</button>
            <button class="admin-tab" data-tab="activity">Actividad</button>
        </div>
    </div>

    <!-- ===================== TAB: DASHBOARD ===================== -->
    <div class="tab-panel active" id="panel-dashboard">

        <!-- KPI Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon blue"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z"/></svg></div>
                <div class="stat-value"><?php echo number_format($totalUsers); ?></div>
                <div class="stat-label">Usuarios</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon green"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25 5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z"/></svg></div>
                <div class="stat-value"><?php echo number_format($totalOrders); ?></div>
                <div class="stat-label">Pedidos</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon amber"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg></div>
                <div class="stat-value">$<?php echo number_format($totalRevenue, 0); ?></div>
                <div class="stat-label">Ingresos mercado</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon purple"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3v11.25A2.25 2.25 0 0 0 6 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0 1 18 16.5h-2.25m-7.5 0h7.5m-7.5 0-1 3m8.5-3 1 3m0 0 .5 1.5m-.5-1.5h-9.5m0 0-.5 1.5"/></svg></div>
                <div class="stat-value"><?php echo number_format($totalAuctions); ?></div>
                <div class="stat-label">Subastas totales</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon cyan"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z"/></svg></div>
                <div class="stat-value"><?php echo number_format($activeAuctions); ?></div>
                <div class="stat-label">Subastas activas</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon red"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.362 5.214A8.252 8.252 0 0 1 12 21 8.25 8.25 0 0 1 6.038 7.047 8.287 8.287 0 0 0 9 9.601a8.983 8.983 0 0 1 3.361-6.867 8.21 8.21 0 0 0 3 2.48Z"/></svg></div>
                <div class="stat-value"><?php echo number_format($totalBids); ?></div>
                <div class="stat-label">Pujas totales</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon pink"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694-4.125-8.25-4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375m16.5 0v3.75m-16.5-3.75v3.75m16.5 0v3.75C20.25 16.153 16.556 18 12 18s-8.25-1.847-8.25-4.125v-3.75m16.5 0c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125"/></svg></div>
                <div class="stat-value"><?php echo number_format($totalCoins); ?></div>
                <div class="stat-label">LootCoins totales</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon teal"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M5.625 4.5h12.75a1.875 1.875 0 0 1 0 3.75H5.625a1.875 1.875 0 0 1 0-3.75Z"/></svg></div>
                <div class="stat-value"><?php echo number_format($totalActivity); ?></div>
                <div class="stat-label">Acciones registradas</div>
            </div>
        </div>

        <!-- Charts Row 1 -->
        <div class="charts-grid">
            <div class="chart-card">
                <h3>Registro de usuarios</h3>
                <p class="chart-sub">Nuevos usuarios por mes (últimos 6 meses)</p>
                <canvas id="chartUsersMonth"></canvas>
            </div>
            <div class="chart-card">
                <h3>Ventas del mercado</h3>
                <p class="chart-sub">Pedidos e ingresos mensuales</p>
                <canvas id="chartOrdersMonth"></canvas>
            </div>
            <div class="chart-card">
                <h3>Ventas por juego</h3>
                <p class="chart-sub">Distribución de ingresos por franquicia</p>
                <canvas id="chartSalesByGame"></canvas>
            </div>
            <div class="chart-card">
                <h3>Subastas por juego</h3>
                <p class="chart-sub">Activas vs terminadas por franquicia</p>
                <canvas id="chartAuctionsByGame"></canvas>
            </div>
        </div>

        <!-- Charts Row 2 -->
        <div class="charts-grid">
            <div class="chart-card">
                <h3>Tipos de actividad</h3>
                <p class="chart-sub">Distribución de acciones de los usuarios</p>
                <canvas id="chartActivityType"></canvas>
            </div>
            <div class="chart-card">
                <h3>Actividad diaria</h3>
                <p class="chart-sub">Acciones registradas (últimos 14 días)</p>
                <canvas id="chartActivityDaily"></canvas>
            </div>
            <div class="chart-card">
                <h3>Hora de pujas</h3>
                <p class="chart-sub">Distribución horaria de pujas en subastas</p>
                <canvas id="chartBidsHour"></canvas>
            </div>
            <div class="chart-card">
                <h3>Ingresos por subastas</h3>
                <p class="chart-sub">Valor de pujas ganadoras por mes</p>
                <canvas id="chartAuctionRevenue"></canvas>
            </div>
        </div>

        <!-- Rankings -->
        <div class="ranking-grid">
            <div class="ranking-card">
                <h3><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#f59e0b" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 18.75h-9m9 0a3 3 0 0 1 3 3h-15a3 3 0 0 1 3-3m9 0v-3.375c0-.621-.503-1.125-1.125-1.125h-.871M7.5 18.75v-3.375c0-.621.504-1.125 1.125-1.125h.872m5.007 0H9.497m5.007 0a7.454 7.454 0 0 1-.982-3.172M9.497 14.25a7.454 7.454 0 0 0 .981-3.172M5.25 4.236c-.982.143-1.954.317-2.916.52A6.003 6.003 0 0 0 7.73 9.728M5.25 4.236V4.5c0 2.108.966 3.99 2.48 5.228M5.25 4.236V2.721C7.456 2.41 9.71 2.25 12 2.25c2.291 0 4.545.16 6.75.47v1.516M18.75 4.236c.982.143 1.954.317 2.916.52A6.003 6.003 0 0 1 16.27 9.728M18.75 4.236V4.5c0 2.108-.966 3.99-2.48 5.228m0 0a6.023 6.023 0 0 1-2.77.896m0 0a6.042 6.042 0 0 1-2.77-.896"/></svg> Top Usuarios por LootCoins</h3>
                <div id="rankingCoins"><div class="chart-loading">Cargando...</div></div>
            </div>
            <div class="ranking-card">
                <h3><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#8b5cf6" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 0 1 1.04 0l2.125 5.111a.563.563 0 0 0 .475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 0 0-.182.557l1.285 5.385a.562.562 0 0 1-.84.61l-4.725-2.885a.562.562 0 0 0-.586 0L6.982 20.54a.562.562 0 0 1-.84-.61l1.285-5.386a.562.562 0 0 0-.182-.557l-4.204-3.602a.562.562 0 0 1 .321-.988l5.518-.442a.563.563 0 0 0 .475-.345L11.48 3.5Z"/></svg> Top Usuarios por XP</h3>
                <div id="rankingXP"><div class="chart-loading">Cargando...</div></div>
            </div>
        </div>

        <!-- Top cards sold -->
        <div class="admin-section">
            <div class="admin-section-header">
                <h2><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z"/></svg> Cartas más vendidas</h2>
            </div>
            <div id="topCardsSold"><div class="chart-loading">Cargando...</div></div>
        </div>

    </div>

    <!-- ===================== TAB: USUARIOS ===================== -->
    <div class="tab-panel" id="panel-users">
        <div class="admin-section">
            <div class="admin-section-header">
                <h2><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z"/></svg> Usuarios</h2>
                <span class="section-badge"><?php echo count($users); ?> registrados</span>
            </div>
            <div style="overflow-x:auto;">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Usuario</th>
                        <th>Nombre</th>
                        <th>Email</th>
                        <th>LootCoins</th>
                        <th>Rol</th>
                        <th>Registro</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($users as $u): ?>
                    <tr id="user-row-<?php echo $u['id']; ?>">
                        <td style="color:var(--text-secondary)"><?php echo $u['id']; ?></td>
                        <td style="font-weight:700"><?php echo htmlspecialchars($u['username']); ?></td>
                        <td><?php echo htmlspecialchars($u['name']); ?></td>
                        <td style="color:var(--text-secondary);font-size:.82rem"><?php echo htmlspecialchars($u['email']); ?></td>
                        <td>
                            <div style="display:flex;gap:6px;align-items:center;">
                                <input class="coins-input" type="number" min="0"
                                       value="<?php echo (int)$u['lootcoins']; ?>"
                                       id="coins-<?php echo $u['id']; ?>">
                                <button class="btn-xs blue" onclick="setCoins(<?php echo $u['id']; ?>)">✓</button>
                            </div>
                        </td>
                        <td>
                            <span class="<?php echo $u['is_admin'] ? 'badge-admin' : 'badge-user'; ?>"
                                  id="role-badge-<?php echo $u['id']; ?>">
                                <?php echo $u['is_admin'] ? 'Admin' : 'Usuario'; ?>
                            </span>
                        </td>
                        <td style="color:var(--text-secondary);font-size:.82rem"><?php echo date('d/m/Y', strtotime($u['created_at'])); ?></td>
                        <td>
                            <?php if ($u['id'] != $_SESSION['user_id']): ?>
                            <button class="btn-xs amber" onclick="toggleAdmin(<?php echo $u['id']; ?>, this)"
                                    id="admin-btn-<?php echo $u['id']; ?>">
                                <?php echo $u['is_admin'] ? 'Quitar admin' : 'Dar admin'; ?>
                            </button>
                            <?php else: ?>
                            <span style="font-size:.75rem;color:var(--text-secondary)">Tú</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            </div>
        </div>
    </div>

    <!-- ===================== TAB: SUBASTAS ===================== -->
    <div class="tab-panel" id="panel-auctions">
        <div class="admin-section">
            <div class="admin-section-header">
                <h2><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3v11.25A2.25 2.25 0 0 0 6 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0 1 18 16.5h-2.25m-7.5 0h7.5m-7.5 0-1 3m8.5-3 1 3m0 0 .5 1.5m-.5-1.5h-9.5m0 0-.5 1.5"/></svg> Subastas recientes</h2>
                <span class="section-badge"><?php echo count($auctions); ?> mostradas</span>
            </div>
            <div style="overflow-x:auto;">
            <table class="admin-table">
                <thead>
                    <tr><th>#</th><th>Carta</th><th>Juego</th><th>Puja actual</th><th>Vendedor</th><th>Estado</th><th>Fin</th><th>Acción</th></tr>
                </thead>
                <tbody>
                <?php foreach ($auctions as $a): ?>
                    <tr id="auction-row-<?php echo $a['id']; ?>">
                        <td style="color:var(--text-secondary)"><?php echo $a['id']; ?></td>
                        <td style="font-weight:700"><?php echo htmlspecialchars($a['card_name']); ?></td>
                        <td style="color:var(--text-secondary)"><?php echo htmlspecialchars($a['card_game']); ?></td>
                        <td><?php echo number_format((int)$a['current_bid']); ?> LJ</td>
                        <td><?php echo htmlspecialchars($a['seller'] ?? '—'); ?></td>
                        <td>
                            <span class="<?php echo $a['status'] === 'active' ? 'badge-active' : 'badge-ended'; ?>">
                                <?php echo $a['status'] === 'active' ? 'Activa' : 'Terminada'; ?>
                            </span>
                        </td>
                        <td style="font-size:.82rem;color:var(--text-secondary)"><?php echo date('d/m H:i', strtotime($a['ends_at'])); ?></td>
                        <td>
                            <button class="btn-xs red" onclick="deleteAuction(<?php echo $a['id']; ?>, this)">Eliminar</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            </div>
        </div>
    </div>

    <!-- ===================== TAB: ACTIVIDAD ===================== -->
    <div class="tab-panel" id="panel-activity">
        <div class="admin-section">
            <div class="admin-section-header">
                <h2><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M5.625 4.5h12.75a1.875 1.875 0 0 1 0 3.75H5.625a1.875 1.875 0 0 1 0-3.75Z"/></svg> Actividad reciente global</h2>
                <span class="section-badge"><?php echo count($activity); ?> últimas</span>
            </div>
            <div style="overflow-x:auto;">
            <table class="admin-table">
                <thead>
                    <tr><th>Usuario</th><th>Tipo</th><th>Título</th><th>Descripción</th><th>Fecha</th></tr>
                </thead>
                <tbody>
                <?php foreach ($activity as $ac):
                    $atClass = 'at-default';
                    $typeMap = ['order'=>'at-order','auction_win'=>'at-auction_win','auction_claim'=>'at-auction_claim','level_up'=>'at-level_up','achievement'=>'at-achievement','price_alert'=>'at-price_alert'];
                    if (isset($typeMap[$ac['activity_type']])) $atClass = $typeMap[$ac['activity_type']];
                ?>
                    <tr>
                        <td style="font-weight:700"><?php echo htmlspecialchars($ac['username']); ?></td>
                        <td><span class="activity-type-badge <?php echo $atClass; ?>"><?php echo htmlspecialchars($ac['activity_type']); ?></span></td>
                        <td><?php echo htmlspecialchars($ac['title']); ?></td>
                        <td style="color:var(--text-secondary);font-size:.82rem"><?php echo htmlspecialchars($ac['description']); ?></td>
                        <td style="color:var(--text-secondary);font-size:.82rem"><?php echo date('d/m/Y H:i', strtotime($ac['created_at'])); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            </div>
        </div>
    </div>

</div>
</div>

<script src="../assets/js/csrf.js?v=<?php echo time(); ?>"></script>
<script src="../assets/js/script.js?v=<?php echo time(); ?>"></script>
<script>
// ── Tab navigation ──
document.querySelectorAll('.admin-tab').forEach(tab => {
    tab.addEventListener('click', () => {
        document.querySelectorAll('.admin-tab').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
        tab.classList.add('active');
        document.getElementById('panel-' + tab.dataset.tab).classList.add('active');
    });
});

// ── Admin AJAX actions ──
async function adminPost(data) {
    const fd = new FormData();
    for (const [k, v] of Object.entries(data)) fd.append(k, v);
    const res = await fetch('admin.php', {
        method: 'POST',
        headers: { 'X-Requested-With': 'fetch' },
        body: fd
    });
    return res.json();
}

async function toggleAdmin(uid, btn) {
    btn.disabled = true;
    const data = await adminPost({ action: 'toggle_admin', user_id: uid });
    if (data.ok) {
        const badge = document.getElementById('role-badge-' + uid);
        const isAdmin = badge.classList.contains('badge-admin');
        badge.className = isAdmin ? 'badge-user' : 'badge-admin';
        badge.textContent = isAdmin ? 'Usuario' : 'Admin';
        btn.textContent = isAdmin ? 'Dar admin' : 'Quitar admin';
        if (typeof showToast === 'function') showToast('Rol actualizado', 'success');
    } else {
        if (typeof showToast === 'function') showToast(data.message || 'Error', 'error');
    }
    btn.disabled = false;
}

async function setCoins(uid) {
    const coins = parseInt(document.getElementById('coins-' + uid).value) || 0;
    const data = await adminPost({ action: 'set_coins', user_id: uid, coins });
    if (data.ok) {
        if (typeof showToast === 'function') showToast('LootCoins actualizados', 'success');
    }
}

async function deleteAuction(aid, btn) {
    if (!confirm('¿Eliminar esta subasta?')) return;
    btn.disabled = true;
    const data = await adminPost({ action: 'delete_auction', auction_id: aid });
    if (data.ok) {
        document.getElementById('auction-row-' + aid)?.remove();
        if (typeof showToast === 'function') showToast('Subasta eliminada', 'success');
    }
    btn.disabled = false;
}

// ── Chart.js config ──
const isDark = document.body.classList.contains('dark');
const gridColor = isDark ? 'rgba(148,163,184,.12)' : 'rgba(0,0,0,.06)';
const textColor = isDark ? '#94a3b8' : '#64748b';

Chart.defaults.color = textColor;
Chart.defaults.font.family = "'Outfit', sans-serif";
Chart.defaults.font.weight = 600;
Chart.defaults.plugins.legend.labels.usePointStyle = true;
Chart.defaults.plugins.legend.labels.pointStyleWidth = 8;
Chart.defaults.plugins.legend.labels.padding = 16;
Chart.defaults.scale.grid = { color: gridColor };
Chart.defaults.scale.border = { display: false };

const GAME_COLORS = {
    pokemon:  '#ef4444',
    yugioh:   '#3b82f6',
    magic:    '#8b5cf6',
    onepiece: '#f97316'
};
const GAME_LABELS = {
    pokemon: 'Pokémon',
    yugioh: 'Yu-Gi-Oh!',
    magic: 'Magic',
    onepiece: 'One Piece'
};

const MONTH_NAMES = ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];
function fmtMonth(ym) {
    const [y, m] = ym.split('-');
    return MONTH_NAMES[parseInt(m)-1] + ' ' + y.slice(2);
}

async function fetchStat(stat) {
    try {
        const r = await fetch('../api/admin_stats.php?stat=' + stat);
        const j = await r.json();
        return j.ok ? j.data : [];
    } catch { return []; }
}

// ── Load all charts ──
(async function() {

    // 1. Users per month
    const usersMonth = await fetchStat('users_per_month');
    new Chart(document.getElementById('chartUsersMonth'), {
        type: 'bar',
        data: {
            labels: usersMonth.map(r => fmtMonth(r.mes)),
            datasets: [{
                label: 'Nuevos usuarios',
                data: usersMonth.map(r => +r.total),
                backgroundColor: 'rgba(59,130,246,.7)',
                borderRadius: 8,
                barPercentage: 0.6
            }]
        },
        options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } }
    });

    // 2. Orders per month
    const ordersMonth = await fetchStat('orders_per_month');
    new Chart(document.getElementById('chartOrdersMonth'), {
        type: 'line',
        data: {
            labels: ordersMonth.map(r => fmtMonth(r.mes)),
            datasets: [
                {
                    label: 'Pedidos',
                    data: ordersMonth.map(r => +r.total),
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16,185,129,.1)',
                    fill: true,
                    tension: 0.4,
                    pointRadius: 4,
                    pointBackgroundColor: '#10b981'
                },
                {
                    label: 'Ingresos ($)',
                    data: ordersMonth.map(r => +r.revenue),
                    borderColor: '#f59e0b',
                    backgroundColor: 'rgba(245,158,11,.1)',
                    fill: true,
                    tension: 0.4,
                    pointRadius: 4,
                    pointBackgroundColor: '#f59e0b',
                    yAxisID: 'y1'
                }
            ]
        },
        options: {
            responsive: true,
            interaction: { mode: 'index', intersect: false },
            scales: {
                y: { beginAtZero: true, ticks: { precision: 0 }, position: 'left' },
                y1: { beginAtZero: true, position: 'right', grid: { display: false }, ticks: { callback: v => '$' + v } }
            }
        }
    });

    // 3. Sales by game (doughnut)
    const salesGame = await fetchStat('sales_by_game');
    new Chart(document.getElementById('chartSalesByGame'), {
        type: 'doughnut',
        data: {
            labels: salesGame.map(r => GAME_LABELS[r.card_game] || r.card_game),
            datasets: [{
                data: salesGame.map(r => +r.revenue),
                backgroundColor: salesGame.map(r => GAME_COLORS[r.card_game] || '#64748b'),
                borderWidth: 0,
                hoverOffset: 8
            }]
        },
        options: {
            responsive: true,
            cutout: '65%',
            plugins: {
                legend: { position: 'bottom' },
                tooltip: { callbacks: { label: ctx => ctx.label + ': $' + (+ctx.raw).toLocaleString() } }
            }
        }
    });

    // 4. Auctions by game (stacked bar)
    const aucGame = await fetchStat('auctions_by_game');
    new Chart(document.getElementById('chartAuctionsByGame'), {
        type: 'bar',
        data: {
            labels: aucGame.map(r => GAME_LABELS[r.card_game] || r.card_game),
            datasets: [
                {
                    label: 'Activas',
                    data: aucGame.map(r => +r.activas),
                    backgroundColor: '#10b981',
                    borderRadius: 6,
                    barPercentage: 0.5
                },
                {
                    label: 'Terminadas',
                    data: aucGame.map(r => +r.terminadas),
                    backgroundColor: '#94a3b8',
                    borderRadius: 6,
                    barPercentage: 0.5
                }
            ]
        },
        options: {
            responsive: true,
            scales: { x: { stacked: true }, y: { stacked: true, beginAtZero: true, ticks: { precision: 0 } } }
        }
    });

    // 5. Activity by type (polar area)
    const actType = await fetchStat('activity_by_type');
    const actColors = { order: '#10b981', auction_win: '#f59e0b', auction_claim: '#8b5cf6', level_up: '#3b82f6', achievement: '#ec4899', price_alert: '#06b6d4' };
    new Chart(document.getElementById('chartActivityType'), {
        type: 'polarArea',
        data: {
            labels: actType.map(r => r.activity_type),
            datasets: [{
                data: actType.map(r => +r.total),
                backgroundColor: actType.map(r => (actColors[r.activity_type] || '#64748b') + 'aa'),
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { position: 'bottom' } },
            scales: { r: { ticks: { display: false }, grid: { color: gridColor } } }
        }
    });

    // 6. Activity daily (area)
    const actDaily = await fetchStat('activity_daily');
    new Chart(document.getElementById('chartActivityDaily'), {
        type: 'line',
        data: {
            labels: actDaily.map(r => { const d = new Date(r.dia); return d.getDate() + '/' + (d.getMonth()+1); }),
            datasets: [{
                label: 'Acciones',
                data: actDaily.map(r => +r.total),
                borderColor: '#8b5cf6',
                backgroundColor: 'rgba(139,92,246,.12)',
                fill: true,
                tension: 0.35,
                pointRadius: 3,
                pointBackgroundColor: '#8b5cf6'
            }]
        },
        options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } }
    });

    // 7. Bids by hour (bar)
    const bidsHour = await fetchStat('bids_by_hour');
    const hoursLabels = Array.from({length: 24}, (_, i) => i + ':00');
    const hoursData = Array(24).fill(0);
    bidsHour.forEach(r => { hoursData[+r.hora] = +r.total; });
    new Chart(document.getElementById('chartBidsHour'), {
        type: 'bar',
        data: {
            labels: hoursLabels,
            datasets: [{
                label: 'Pujas',
                data: hoursData,
                backgroundColor: hoursData.map((v, i) => i >= 18 || i < 6 ? 'rgba(139,92,246,.6)' : 'rgba(59,130,246,.6)'),
                borderRadius: 4,
                barPercentage: 0.7
            }]
        },
        options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } }
    });

    // 8. Auction revenue
    const aucRev = await fetchStat('auction_revenue');
    new Chart(document.getElementById('chartAuctionRevenue'), {
        type: 'bar',
        data: {
            labels: aucRev.map(r => fmtMonth(r.mes)),
            datasets: [{
                label: 'LootCoins ganados',
                data: aucRev.map(r => +r.total_bid),
                backgroundColor: 'rgba(245,158,11,.65)',
                borderRadius: 8,
                barPercentage: 0.6
            }]
        },
        options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { callback: v => v.toLocaleString() + ' LC' } } } }
    });

    // 9. Top users coins ranking
    const topCoins = await fetchStat('top_users_coins');
    const coinsEl = document.getElementById('rankingCoins');
    coinsEl.innerHTML = topCoins.length === 0 ? '<p style="color:var(--text-secondary);text-align:center;padding:20px;">Sin datos</p>' :
        topCoins.map((u, i) => `
            <div class="ranking-item">
                <div class="ranking-pos ${i===0?'gold':i===1?'silver':i===2?'bronze':''}">${i+1}</div>
                <div class="ranking-name">${u.username}</div>
                <div class="ranking-value">${(+u.lootcoins).toLocaleString()} LC</div>
            </div>
        `).join('');

    // 10. Top users XP ranking
    const topXP = await fetchStat('top_users_xp');
    const xpEl = document.getElementById('rankingXP');
    xpEl.innerHTML = topXP.length === 0 ? '<p style="color:var(--text-secondary);text-align:center;padding:20px;">Sin datos</p>' :
        topXP.map((u, i) => `
            <div class="ranking-item">
                <div class="ranking-pos ${i===0?'gold':i===1?'silver':i===2?'bronze':''}">${i+1}</div>
                <div class="ranking-name">${u.username}</div>
                <div class="ranking-value">${(+u.xp).toLocaleString()} XP</div>
            </div>
        `).join('');

    // 11. Top cards sold table
    const topCards = await fetchStat('top_cards_sold');
    const cardsEl = document.getElementById('topCardsSold');
    if (topCards.length === 0) {
        cardsEl.innerHTML = '<p style="color:var(--text-secondary);text-align:center;padding:20px;">Sin datos de ventas todavía</p>';
    } else {
        let html = '<div style="overflow-x:auto"><table class="admin-table"><thead><tr><th>#</th><th>Carta</th><th>Juego</th><th>Unidades vendidas</th><th>Ingresos</th></tr></thead><tbody>';
        topCards.forEach((c, i) => {
            const gameLabel = GAME_LABELS[c.card_game] || c.card_game;
            const gameColor = GAME_COLORS[c.card_game] || '#64748b';
            html += `<tr>
                <td style="color:var(--text-secondary)">${i+1}</td>
                <td style="font-weight:700">${c.card_name}</td>
                <td><span style="font-size:.72rem;font-weight:800;padding:3px 10px;border-radius:20px;background:${gameColor}22;color:${gameColor}">${gameLabel}</span></td>
                <td style="font-weight:700">${(+c.total_sold).toLocaleString()}</td>
                <td style="font-weight:700;color:#10b981">$${(+c.total_revenue).toLocaleString()}</td>
            </tr>`;
        });
        html += '</tbody></table></div>';
        cardsEl.innerHTML = html;
    }

})();
</script>
</body>
</html>
