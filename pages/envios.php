<?php
session_start();
if (empty($_SESSION['is_envios']) && empty($_SESSION['is_admin'])) {
    header('Location: index.php');
    exit;
}
require_once dirname(__DIR__) . '/includes/db.php';

// Migraciones
try { $conn->query("ALTER TABLE cart_orders ADD COLUMN shipment_status ENUM('pending','processing','shipped','delivered','done') NOT NULL DEFAULT 'pending'"); } catch (Exception $e) {}

// ── Acción AJAX ────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=utf-8');
    $type      = $_POST['type']      ?? '';   // 'order' | 'claim'
    $id        = (int)($_POST['id']  ?? 0);
    $newStatus = $_POST['status']    ?? '';
    $allowed   = ['pending','processing','shipped','delivered','done'];

    if (!$id || !in_array($newStatus, $allowed, true)) {
        echo json_encode(['ok' => false, 'message' => 'Datos inválidos']);
        exit;
    }

    if ($type === 'order') {
        $s = $conn->prepare("UPDATE cart_orders SET shipment_status = ? WHERE id = ?");
    } else {
        $s = $conn->prepare("UPDATE auction_claims SET status = ? WHERE id = ?");
    }
    $s->bind_param("si", $newStatus, $id);
    echo json_encode(['ok' => $s->execute()]);
    exit;
}

// ── Pedidos de carrito ─────────────────────────────────────────
$orders = [];
$r = $conn->query("
    SELECT co.id, co.order_number, co.total_amount, co.shipment_status AS status,
           co.created_at, u.username AS buyer, u.email, u.address
    FROM cart_orders co
    JOIN users u ON u.id = co.user_id
    WHERE co.status = 'paid'
    ORDER BY FIELD(co.shipment_status,'pending','processing','shipped','delivered','done'),
             co.created_at DESC
");
while ($row = $r->fetch_assoc()) {
    // Items del pedido
    $ri = $conn->prepare("SELECT card_name, card_image, card_game, quantity, card_price FROM cart_order_items WHERE order_id = ? LIMIT 10");
    $ri->bind_param("i", $row['id']);
    $ri->execute();
    $row['items'] = $ri->get_result()->fetch_all(MYSQLI_ASSOC);
    $orders[] = $row;
}

// ── Envíos de subastas ─────────────────────────────────────────
$auctionShipments = [];
$r = $conn->query("
    SELECT cl.id AS claim_id, cl.status, cl.address, cl.created_at,
           a.card_name, a.card_image, a.card_game, a.current_bid,
           u.username AS winner, u.email
    FROM auction_claims cl
    JOIN auctions a ON a.id  = cl.auction_id
    JOIN users    u ON u.id  = cl.user_id
    WHERE cl.choice = 'delivery'
    ORDER BY FIELD(cl.status,'pending','processing','shipped','delivered','done'),
             cl.created_at DESC
");
while ($row = $r->fetch_assoc()) $auctionShipments[] = $row;

// ── Contadores ─────────────────────────────────────────────────
$statusLabels = ['pending'=>'Pendiente','processing'=>'Preparando','shipped'=>'Enviado','delivered'=>'Entregado','done'=>'Completado'];
$statusColors = ['pending'=>'#f59e0b','processing'=>'#3b82f6','shipped'=>'#8b5cf6','delivered'=>'#10b981','done'=>'#64748b'];
$nextStatus   = ['pending'=>'processing','processing'=>'shipped','shipped'=>'delivered','delivered'=>'done','done'=>null];
$nextLabel    = ['pending'=>'Marcar en preparación','processing'=>'Marcar como enviado','shipped'=>'Marcar como entregado','delivered'=>'Completar','done'=>null];

$counts = ['pending'=>0,'processing'=>0,'shipped'=>0,'delivered'=>0,'done'=>0];
foreach ($orders          as $s) $counts[$s['status']]  = ($counts[$s['status']]  ?? 0) + 1;
foreach ($auctionShipments as $s) $counts[$s['status']] = ($counts[$s['status']]  ?? 0) + 1;
$totalShipments = count($orders) + count($auctionShipments);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Envíos | Loot&Trading</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/styles.css?v=<?php echo time(); ?>">
    <style>
        .envios-wrap  { max-width: 1100px; margin: 0 auto; padding: 30px 20px 80px; }
        .envios-title { font-size: 2rem; font-weight: 800; margin-bottom: 4px; }
        .envios-sub   { color: var(--text-secondary); margin-bottom: 28px; }

        .envios-stats { display: flex; gap: 12px; flex-wrap: wrap; margin-bottom: 28px; }
        .envios-stat  { background: #fff; border: 1px solid var(--border-color); border-radius: 16px; padding: 14px 20px; display: flex; align-items: center; gap: 12px; }
        body.dark .envios-stat { background: #1e293b; border-color: #334155; }
        .envios-stat .dot   { width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0; }
        .envios-stat .count { font-size: 1.4rem; font-weight: 800; }
        .envios-stat .lbl   { font-size: .78rem; font-weight: 700; color: var(--text-secondary); text-transform: uppercase; letter-spacing: .04em; }

        .filter-bar  { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 20px; }
        .filter-btn  { padding: 7px 16px; border-radius: 50px; border: 2px solid var(--border-color); background: #fff; font-weight: 700; font-size: .82rem; cursor: pointer; transition: .2s; color: var(--text-secondary); font-family:'Outfit',sans-serif; }
        .filter-btn:hover, .filter-btn.active { border-color: var(--accent-blue); color: var(--accent-blue); background: rgba(59,130,246,.06); }
        body.dark .filter-btn { background: #1e293b; }

        .section-label { font-size: .72rem; font-weight: 800; text-transform: uppercase; letter-spacing: .08em; color: var(--text-secondary); margin: 24px 0 10px; padding-left: 4px; }

        .shipment-card { background: #fff; border: 1px solid var(--border-color); border-radius: 20px; padding: 18px 20px; display: grid; grid-template-columns: 64px 1fr auto; gap: 16px; align-items: start; margin-bottom: 12px; transition: box-shadow .2s; }
        .shipment-card:hover { box-shadow: 0 6px 24px rgba(0,0,0,.08); }
        body.dark .shipment-card { background: #1e293b; border-color: #334155; }
        .shipment-card img.main-img { width: 64px; height: 90px; border-radius: 10px; object-fit: cover; }
        .card-name  { font-weight: 800; margin-bottom: 3px; font-size: .95rem; }
        .card-meta  { color: var(--text-secondary); font-size: .82rem; margin-bottom: 6px; }
        .addr-line  { font-size: .82rem; background: #f1f5f9; border-radius: 8px; padding: 5px 10px; display: inline-block; margin-bottom: 8px; }
        body.dark .addr-line { background: #0f172a; }
        .status-badge { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: .75rem; font-weight: 800; }
        .type-badge   { display: inline-block; padding: 2px 8px; border-radius: 20px; font-size: .7rem; font-weight: 800; margin-left: 8px; }
        .type-order   { background: rgba(59,130,246,.1); color: #3b82f6; }
        .type-auction { background: rgba(139,92,246,.1); color: #7c3aed; }

        .items-mini { display: flex; gap: 6px; flex-wrap: wrap; margin-top: 6px; }
        .item-chip  { background: #f8f9fd; border: 1px solid var(--border-color); border-radius: 8px; padding: 3px 8px; font-size: .75rem; font-weight: 700; }
        body.dark .item-chip { background: #0f172a; border-color: #334155; }

        .shipment-actions { display: flex; flex-direction: column; gap: 8px; align-items: flex-end; min-width: 180px; }
        .btn-advance { padding: 9px 16px; border-radius: 50px; background: var(--accent-blue); color: #fff; font-weight: 700; font-size: .8rem; border: none; cursor: pointer; transition: .2s; white-space: nowrap; font-family:'Outfit',sans-serif; }
        .btn-advance:hover { background: #2563eb; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(59,130,246,.3); }
        .btn-advance:disabled { opacity: .4; cursor: not-allowed; transform: none; }
        .status-select { padding: 6px 10px; border-radius: 10px; border: 2px solid var(--border-color); font-family:'Outfit',sans-serif; font-size: .8rem; font-weight: 700; background: transparent; color: var(--text-primary); cursor: pointer; }

        .empty-envios { text-align: center; padding: 80px 20px; color: var(--text-secondary); }
        .empty-envios .icon { font-size: 3.5rem; margin-bottom: 16px; }
    </style>
</head>
<body>
<script>(function(){ if(localStorage.getItem('theme')==='dark') document.body.classList.add('dark'); })();</script>
<?php include dirname(__DIR__) . '/includes/navbar.php'; ?>

<div class="main-wrapper">
<div class="envios-wrap">
    <div class="envios-title">📦 Gestión de Envíos</div>
    <div class="envios-sub">Pedidos del marketplace y subastas — actualiza el estado de cada envío</div>

    <!-- Stats -->
    <div class="envios-stats">
        <?php foreach ($statusLabels as $key => $label): ?>
        <div class="envios-stat">
            <div class="dot" style="background:<?php echo $statusColors[$key]; ?>"></div>
            <div>
                <div class="count"><?php echo $counts[$key]; ?></div>
                <div class="lbl"><?php echo $label; ?></div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Filtros -->
    <div class="filter-bar">
        <button class="filter-btn active" onclick="filterShipments('all',this)">Todos (<?php echo $totalShipments; ?>)</button>
        <?php foreach ($statusLabels as $key => $label): if (!$counts[$key]) continue; ?>
        <button class="filter-btn" onclick="filterShipments('<?php echo $key; ?>',this)">
            <?php echo $label; ?> (<?php echo $counts[$key]; ?>)
        </button>
        <?php endforeach; ?>
    </div>

    <?php if ($totalShipments === 0): ?>
        <div class="empty-envios"><div class="icon">📭</div><p>No hay envíos registrados todavía</p></div>
    <?php else: ?>

    <!-- ── Pedidos del carrito ── -->
    <?php if (!empty($orders)): ?>
    <div class="section-label">🛒 Pedidos del Marketplace (<?php echo count($orders); ?>)</div>
    <?php foreach ($orders as $o):
        $color = $statusColors[$o['status']];
        $next  = $nextStatus[$o['status']] ?? null;
        $nLbl  = $nextLabel[$o['status']]  ?? null;
        $firstImg = $o['items'][0]['card_image'] ?? '';
    ?>
    <div class="shipment-card" data-status="<?php echo $o['status']; ?>" id="shipment-order-<?php echo $o['id']; ?>">
        <?php if ($firstImg): ?>
            <img class="main-img" src="<?php echo htmlspecialchars($firstImg); ?>" alt="">
        <?php else: ?>
            <div style="width:64px;height:90px;background:var(--border-color);border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1.5rem;">🛒</div>
        <?php endif; ?>
        <div>
            <div class="card-name">
                Pedido <?php echo htmlspecialchars($o['order_number']); ?>
                <span class="type-badge type-order">Marketplace</span>
            </div>
            <div class="card-meta">
                <strong><?php echo htmlspecialchars($o['buyer']); ?></strong>
                · <?php echo htmlspecialchars($o['email']); ?>
                · Total: $<?php echo number_format((float)$o['total_amount'], 2); ?>
            </div>
            <?php $addr = $o['address'] ?? ''; ?>
            <div class="addr-line">📍 <?php echo htmlspecialchars($addr ?: 'Sin dirección registrada'); ?></div>
            <div class="items-mini">
                <?php foreach ($o['items'] as $it): ?>
                    <span class="item-chip">
                        <?php echo htmlspecialchars($it['card_name']); ?>
                        <?php if ($it['quantity'] > 1): ?>(x<?php echo $it['quantity']; ?>)<?php endif; ?>
                    </span>
                <?php endforeach; ?>
            </div>
            <div style="margin-top:8px;">
                <span class="status-badge" style="background:<?php echo $color; ?>22;color:<?php echo $color; ?>"
                      id="badge-order-<?php echo $o['id']; ?>">
                    <?php echo $statusLabels[$o['status']]; ?>
                </span>
                <span style="font-size:.75rem;color:var(--text-secondary);margin-left:8px;">
                    <?php echo date('d/m/Y H:i', strtotime($o['created_at'])); ?>
                </span>
            </div>
        </div>
        <div class="shipment-actions">
            <?php if ($next): ?>
            <button class="btn-advance" id="adv-order-<?php echo $o['id']; ?>"
                    onclick="advance('order',<?php echo $o['id']; ?>,'<?php echo $next; ?>',this)">
                <?php echo $nLbl; ?>
            </button>
            <?php else: ?>
            <span style="font-size:.78rem;color:var(--text-secondary);font-weight:700;">✓ Completado</span>
            <?php endif; ?>
            <select class="status-select" onchange="advance('order',<?php echo $o['id']; ?>,this.value,this)">
                <?php foreach ($statusLabels as $k => $l): ?>
                <option value="<?php echo $k; ?>" <?php echo $k === $o['status'] ? 'selected' : ''; ?>><?php echo $l; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>

    <!-- ── Subastas ganadas con envío ── -->
    <?php if (!empty($auctionShipments)): ?>
    <div class="section-label">🔨 Subastas Ganadas con Envío (<?php echo count($auctionShipments); ?>)</div>
    <?php foreach ($auctionShipments as $s):
        $color = $statusColors[$s['status']];
        $next  = $nextStatus[$s['status']] ?? null;
        $nLbl  = $nextLabel[$s['status']]  ?? null;
    ?>
    <div class="shipment-card" data-status="<?php echo $s['status']; ?>" id="shipment-claim-<?php echo $s['claim_id']; ?>">
        <img class="main-img" src="<?php echo htmlspecialchars($s['card_image']); ?>" alt="">
        <div>
            <div class="card-name">
                <?php echo htmlspecialchars($s['card_name']); ?>
                <span class="type-badge type-auction">Subasta</span>
            </div>
            <div class="card-meta">
                <?php echo htmlspecialchars($s['card_game']); ?> ·
                <?php echo number_format((int)$s['current_bid']); ?> LJ ·
                <strong><?php echo htmlspecialchars($s['winner']); ?></strong>
                · <?php echo htmlspecialchars($s['email']); ?>
            </div>
            <div class="addr-line">📍 <?php echo htmlspecialchars($s['address'] ?: 'Sin dirección'); ?></div>
            <div>
                <span class="status-badge" style="background:<?php echo $color; ?>22;color:<?php echo $color; ?>"
                      id="badge-claim-<?php echo $s['claim_id']; ?>">
                    <?php echo $statusLabels[$s['status']]; ?>
                </span>
                <span style="font-size:.75rem;color:var(--text-secondary);margin-left:8px;">
                    <?php echo date('d/m/Y H:i', strtotime($s['created_at'])); ?>
                </span>
            </div>
        </div>
        <div class="shipment-actions">
            <?php if ($next): ?>
            <button class="btn-advance" id="adv-claim-<?php echo $s['claim_id']; ?>"
                    onclick="advance('claim',<?php echo $s['claim_id']; ?>,'<?php echo $next; ?>',this)">
                <?php echo $nLbl; ?>
            </button>
            <?php else: ?>
            <span style="font-size:.78rem;color:var(--text-secondary);font-weight:700;">✓ Completado</span>
            <?php endif; ?>
            <select class="status-select" onchange="advance('claim',<?php echo $s['claim_id']; ?>,this.value,this)">
                <?php foreach ($statusLabels as $k => $l): ?>
                <option value="<?php echo $k; ?>" <?php echo $k === $s['status'] ? 'selected' : ''; ?>><?php echo $l; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>

    <?php endif; ?>
</div>
</div>

<script src="../assets/js/csrf.js?v=<?php echo time(); ?>"></script>
<script src="../assets/js/script.js?v=<?php echo time(); ?>"></script>
<script>
const STATUS_LABELS = <?php echo json_encode($statusLabels); ?>;
const STATUS_COLORS = <?php echo json_encode($statusColors); ?>;
const NEXT_STATUS   = <?php echo json_encode($nextStatus); ?>;
const NEXT_LABEL    = <?php echo json_encode($nextLabel); ?>;

async function advance(type, id, newStatus, el) {
    if (!newStatus) return;
    el.disabled = true;
    const fd = new FormData();
    fd.append('type',   type);
    fd.append('id',     id);
    fd.append('status', newStatus);
    const res  = await fetch('envios.php', { method:'POST', headers:{'X-Requested-With':'fetch'}, body:fd });
    const data = await res.json();

    if (data.ok) {
        const key   = type === 'order' ? 'order-' + id : 'claim-' + id;
        const card  = document.getElementById('shipment-' + key);
        const badge = document.getElementById('badge-' + key);
        const color = STATUS_COLORS[newStatus];

        badge.textContent       = STATUS_LABELS[newStatus];
        badge.style.background  = color + '22';
        badge.style.color       = color;
        card.dataset.status     = newStatus;

        // Actualizar botón avanzar
        const advBtn = document.getElementById('adv-' + key);
        const next   = NEXT_STATUS[newStatus];
        if (advBtn) {
            if (next) {
                advBtn.textContent = NEXT_LABEL[newStatus];
                advBtn.onclick = () => advance(type, id, next, advBtn);
                advBtn.disabled = false;
            } else {
                advBtn.replaceWith(Object.assign(document.createElement('span'), {
                    textContent: '✓ Completado',
                    style: 'font-size:.78rem;color:var(--text-secondary);font-weight:700;'
                }));
            }
        }

        // Sincronizar select
        const sel = card.querySelector('.status-select');
        if (sel) { sel.value = newStatus; sel.disabled = false; }

        if (typeof showToast === 'function') showToast('Estado actualizado', 'success');
    }
    if (el.tagName === 'SELECT') el.disabled = false;
}

function filterShipments(status, btn) {
    document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    document.querySelectorAll('.shipment-card').forEach(card => {
        card.style.display = (status === 'all' || card.dataset.status === status) ? '' : 'none';
    });
}
</script>
</body>
</html>
