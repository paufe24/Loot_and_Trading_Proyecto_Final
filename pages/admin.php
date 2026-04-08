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
$totalOrders    = (int)($conn->query("SELECT COUNT(*) n FROM cart_orders")->fetch_assoc()['n'] ?? 0) ;
$totalCoins     = (int)($conn->query("SELECT COALESCE(SUM(lootcoins),0) n FROM users")->fetch_assoc()['n'] ?? 0);
$totalActivity  = (int)($conn->query("SELECT COUNT(*) n FROM user_activity")->fetch_assoc()['n'] ?? 0);

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
    <style>
        .admin-wrap { max-width: 1300px; margin: 0 auto; padding: 30px 20px 80px; }
        .admin-title { font-size: 2rem; font-weight: 800; margin-bottom: 6px; }
        .admin-sub   { color: var(--text-secondary); margin-bottom: 32px; }

        /* Stats */
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 16px; margin-bottom: 40px; }
        .stat-card  { background: #fff; border: 1px solid var(--border-color); border-radius: 20px; padding: 22px 20px; text-align: center; }
        body.dark .stat-card { background: #1e293b; border-color: #334155; }
        .stat-card .num  { font-size: 2rem; font-weight: 800; color: var(--accent-blue); }
        .stat-card .lbl  { font-size: .8rem; font-weight: 700; color: var(--text-secondary); margin-top: 4px; text-transform: uppercase; letter-spacing: .04em; }

        /* Sections */
        .admin-section { background: #fff; border: 1px solid var(--border-color); border-radius: 20px; padding: 28px; margin-bottom: 28px; }
        body.dark .admin-section { background: #1e293b; border-color: #334155; }
        .admin-section h2 { font-size: 1.2rem; font-weight: 800; margin-bottom: 18px; }

        /* Table */
        .admin-table { width: 100%; border-collapse: collapse; font-size: .88rem; }
        .admin-table th { text-align: left; padding: 8px 12px; font-size: .75rem; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; color: var(--text-secondary); border-bottom: 2px solid var(--border-color); }
        .admin-table td { padding: 10px 12px; border-bottom: 1px solid var(--border-color); vertical-align: middle; }
        .admin-table tr:last-child td { border-bottom: none; }
        body.dark .admin-table th, body.dark .admin-table td { border-color: #334155; }

        /* Badges */
        .badge-admin  { background: rgba(245,158,11,.15); color: #d97706; font-size: .72rem; font-weight: 800; padding: 2px 8px; border-radius: 20px; }
        .badge-user   { background: rgba(100,116,139,.1);  color: #64748b; font-size: .72rem; font-weight: 800; padding: 2px 8px; border-radius: 20px; }
        .badge-active { background: rgba(16,185,129,.15); color: #059669; font-size: .72rem; font-weight: 800; padding: 2px 8px; border-radius: 20px; }
        .badge-ended  { background: rgba(100,116,139,.1);  color: #64748b; font-size: .72rem; font-weight: 800; padding: 2px 8px; border-radius: 20px; }

        /* Inline controls */
        .coins-input { width: 90px; padding: 5px 8px; border: 1.5px solid var(--border-color); border-radius: 8px; font-size: .85rem; font-family: 'Outfit',sans-serif; background: transparent; color: var(--text-primary); }
        .btn-xs { padding: 5px 12px; border-radius: 8px; font-size: .78rem; font-weight: 700; border: none; cursor: pointer; transition: .2s; }
        .btn-xs.blue   { background: var(--accent-blue); color: #fff; }
        .btn-xs.amber  { background: #f59e0b; color: #fff; }
        .btn-xs.red    { background: #ef4444; color: #fff; }
        .btn-xs:hover  { opacity: .85; transform: translateY(-1px); }
        .btn-xs:disabled { opacity: .4; cursor: not-allowed; transform: none; }

        .activity-type-badge { font-size: .7rem; font-weight: 800; padding: 2px 7px; border-radius: 20px; background: rgba(59,130,246,.1); color: #3b82f6; }
    </style>
</head>
<body>
<script>(function(){ if(localStorage.getItem('theme')==='dark') document.body.classList.add('dark'); })();</script>
<?php include dirname(__DIR__) . '/includes/navbar.php'; ?>

<div class="main-wrapper">
<div class="admin-wrap">
    <div class="admin-title">🛡️ Panel de Administración</div>
    <div class="admin-sub">Bienvenido, <?php echo htmlspecialchars($_SESSION['username']); ?>. Gestiona usuarios, subastas y estadísticas.</div>

    <!-- Stats -->
    <div class="stats-grid">
        <div class="stat-card"><div class="num"><?php echo number_format($totalUsers); ?></div><div class="lbl">Usuarios</div></div>
        <div class="stat-card"><div class="num"><?php echo number_format($totalAuctions); ?></div><div class="lbl">Subastas totales</div></div>
        <div class="stat-card"><div class="num"><?php echo number_format($activeAuctions); ?></div><div class="lbl">Subastas activas</div></div>
        <div class="stat-card"><div class="num"><?php echo number_format($totalOrders); ?></div><div class="lbl">Pedidos</div></div>
        <div class="stat-card"><div class="num"><?php echo number_format($totalCoins); ?></div><div class="lbl">LootCoins totales</div></div>
        <div class="stat-card"><div class="num"><?php echo number_format($totalActivity); ?></div><div class="lbl">Acciones registradas</div></div>
    </div>

    <!-- Usuarios -->
    <div class="admin-section">
        <h2>👥 Usuarios (<?php echo count($users); ?>)</h2>
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

    <!-- Subastas -->
    <div class="admin-section">
        <h2>🔨 Subastas recientes</h2>
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

    <!-- Actividad global -->
    <div class="admin-section">
        <h2>📋 Actividad reciente global</h2>
        <div style="overflow-x:auto;">
        <table class="admin-table">
            <thead>
                <tr><th>Usuario</th><th>Tipo</th><th>Título</th><th>Descripción</th><th>Fecha</th></tr>
            </thead>
            <tbody>
            <?php foreach ($activity as $ac): ?>
                <tr>
                    <td style="font-weight:700"><?php echo htmlspecialchars($ac['username']); ?></td>
                    <td><span class="activity-type-badge"><?php echo htmlspecialchars($ac['activity_type']); ?></span></td>
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

<script src="../assets/js/csrf.js?v=<?php echo time(); ?>"></script>
<script src="../assets/js/script.js?v=<?php echo time(); ?>"></script>
<script>
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
</script>
</body>
</html>
