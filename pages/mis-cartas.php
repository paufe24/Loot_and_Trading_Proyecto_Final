<?php
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: auth.php'); exit; }
require_once dirname(__DIR__) . '/includes/db.php';
$uid = (int)$_SESSION['user_id'];

// Migración: añadir shipping_status a cart_orders
try { $conn->query("ALTER TABLE cart_orders ADD COLUMN shipping_status ENUM('pending','processing','shipped','delivered') DEFAULT 'pending'"); } catch(Exception $e){}

// 1. Cartas de pedidos del carrito
$cartItems = [];
$stC = $conn->prepare(
    "SELECT oi.id AS item_id, oi.card_name, oi.card_image, oi.card_game,
            oi.card_price, oi.quantity, o.order_number, o.id AS order_id,
            o.shipping_status, o.created_at
     FROM cart_order_items oi
     JOIN cart_orders o ON o.id = oi.order_id
     WHERE o.user_id = ?
     ORDER BY o.created_at DESC"
);
if ($stC) { $stC->bind_param("i",$uid); $stC->execute(); $cartItems = $stC->get_result()->fetch_all(MYSQLI_ASSOC); }

// 2. Cartas ganadas en subastas (solo delivery)
$auctionItems = [];
$stA = $conn->prepare(
    "SELECT a.id AS auction_id, a.card_name, a.card_image, a.card_game,
            a.current_bid AS card_price, a.badge_color,
            cl.status AS shipping_status, cl.created_at
     FROM auctions a
     JOIN auction_claims cl ON cl.auction_id = a.id AND cl.user_id = ?
     WHERE a.current_winner_id = ? AND cl.choice = 'delivery'
     ORDER BY cl.created_at DESC"
);
if ($stA) { $stA->bind_param("ii",$uid,$uid); $stA->execute(); $auctionItems = $stA->get_result()->fetch_all(MYSQLI_ASSOC); }

$statusLabels = ['pending'=>'Recibido','processing'=>'Preparando','shipped'=>'Enviado','delivered'=>'Entregado'];
$statusColors = ['pending'=>'#f59e0b','processing'=>'#3b82f6','shipped'=>'#8b5cf6','delivered'=>'#10b981'];
$statusSteps  = ['pending'=>1,'processing'=>2,'shipped'=>3,'delivered'=>4];
$trackerLabels = ['Recibido','Preparando','Enviado','Entregado'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Cartas | Loot&Trading</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/styles.css?v=<?php echo time(); ?>">
    <style>
        .mc-layout { display: grid; grid-template-columns: 260px 1fr; gap: 30px; max-width: 1300px; margin: 30px auto; padding: 0 20px 60px; }
        @media(max-width:768px){ .mc-layout { grid-template-columns: 1fr; } }

        .mc-sidebar { position: sticky; top: 80px; align-self: start; }
        .mc-sidebar-box { background: #fff; border: 1.5px solid var(--border-color); border-radius: 20px; padding: 24px; }
        body.dark .mc-sidebar-box { background: #1e293b; border-color: #334155; }

        .mc-sidebar-box h3 { font-size: 1rem; font-weight: 800; margin-bottom: 20px; }
        .mc-filter-group { margin-bottom: 20px; }
        .mc-filter-group label { display: block; font-weight: 700; font-size: .78rem; text-transform: uppercase; letter-spacing: .04em; color: var(--text-secondary); margin-bottom: 10px; }
        .mc-filter-group input[type=text] {
            width: 100%; padding: 10px 12px; border: 2px solid var(--border-color); border-radius: 12px;
            font-family: 'Outfit',sans-serif; font-size: .9rem; background: #f8f9fd; color: var(--text-primary); box-sizing: border-box;
        }
        body.dark .mc-filter-group input[type=text] { background: #0f172a; border-color: #334155; color: #e2e8f0; }
        .mc-filter-group input[type=text]:focus { outline: none; border-color: var(--accent-blue); }

        .mc-game-btns { display: flex; flex-direction: column; gap: 6px; }
        .mc-game-btn {
            padding: 9px 14px; border-radius: 10px; border: 2px solid var(--border-color);
            font-weight: 700; font-size: .85rem; cursor: pointer; text-align: left;
            background: #f8f9fd; color: var(--text-primary); transition: all .2s;
        }
        body.dark .mc-game-btn { background: #0f172a; border-color: #334155; }
        .mc-game-btn:hover, .mc-game-btn.active { border-color: var(--accent-blue); color: var(--accent-blue); background: rgba(59,130,246,.08); }

        .mc-status-btns { display: flex; flex-direction: column; gap: 6px; }
        .mc-status-btn {
            padding: 9px 14px; border-radius: 10px; border: 2px solid var(--border-color);
            font-weight: 700; font-size: .85rem; cursor: pointer; text-align: left;
            background: #f8f9fd; color: var(--text-primary); transition: all .2s;
        }
        body.dark .mc-status-btn { background: #0f172a; border-color: #334155; }
        .mc-status-btn:hover, .mc-status-btn.active { border-color: var(--accent-blue); color: var(--accent-blue); background: rgba(59,130,246,.08); }

        .mc-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; flex-wrap: wrap; gap: 10px; }
        .mc-header h2 { font-size: 1.5rem; font-weight: 800; }
        .mc-count { color: var(--text-secondary); font-size: .9rem; }

        .mc-grid { display: grid; grid-template-columns: repeat(auto-fill,minmax(280px,1fr)); gap: 18px; }

        .mc-card {
            background: #fff; border: 1.5px solid var(--border-color); border-radius: 20px;
            overflow: hidden; transition: transform .2s, box-shadow .2s;
        }
        body.dark .mc-card { background: #1e293b; border-color: #334155; }
        .mc-card:hover { transform: translateY(-3px); box-shadow: 0 12px 30px rgba(0,0,0,.1); }

        .mc-card-img { width: 100%; height: 160px; object-fit: cover; object-position: top; }
        .mc-card-body { padding: 16px; }
        .mc-card-meta { display: flex; align-items: center; gap: 8px; margin-bottom: 8px; }
        .mc-card-name { font-weight: 800; font-size: .95rem; margin-bottom: 4px; }
        .mc-card-price { font-weight: 700; color: var(--accent-blue); font-size: .9rem; margin-bottom: 14px; }

        .mc-source-tag { font-size: .72rem; font-weight: 800; padding: 3px 10px; border-radius: 20px; }
        .mc-source-cart    { background: rgba(59,130,246,.12); color: #2563eb; }
        .mc-source-auction { background: rgba(139,92,246,.12); color: #7c3aed; }

        .mc-tracker { display: flex; gap: 0; position: relative; margin-top: 12px; }
        .mc-tracker-step { flex: 1; text-align: center; position: relative; }
        .mc-tracker-dot {
            width: 24px; height: 24px; border-radius: 50%; margin: 0 auto 5px;
            display: flex; align-items: center; justify-content: center;
            font-size: .68rem; font-weight: 800; transition: background .4s;
        }
        .mc-tracker-label { font-size: .65rem; font-weight: 700; }
        .mc-tracker-line {
            position: absolute; top: 12px; left: calc(50% + 12px); right: calc(-50% + 12px);
            height: 2px; transition: background .4s;
        }

        .mc-empty { grid-column: 1/-1; text-align: center; padding: 60px 20px; color: var(--text-secondary); }
        .mc-empty-icon { font-size: 3rem; margin-bottom: 12px; }
        .mc-empty h3 { font-size: 1.2rem; font-weight: 800; color: var(--text-primary); margin-bottom: 8px; }

        .mc-refresh-note { font-size: .75rem; color: var(--text-secondary); text-align: right; margin-bottom: 10px; }
    </style>
</head>
<body>
<script>(function(){ if(localStorage.getItem('theme')==='dark') document.body.classList.add('dark'); })();</script>
<?php include dirname(__DIR__) . '/includes/navbar.php'; ?>

<div class="mc-layout">
    <!-- Sidebar filtros -->
    <aside class="mc-sidebar">
        <div class="mc-sidebar-box">
            <h3>🔍 Filtros</h3>

            <div class="mc-filter-group">
                <label>Buscar carta</label>
                <input type="text" id="mc-search" placeholder="Ej: Charizard...">
            </div>

            <div class="mc-filter-group">
                <label>Juego</label>
                <div class="mc-game-btns">
                    <button class="mc-game-btn active" data-game="all">Todos los juegos</button>
                    <button class="mc-game-btn" data-game="Pokémon">🟡 Pokémon</button>
                    <button class="mc-game-btn" data-game="Yu-Gi-Oh!">🟣 Yu-Gi-Oh!</button>
                    <button class="mc-game-btn" data-game="Magic">🔴 Magic: The Gathering</button>
                    <button class="mc-game-btn" data-game="One Piece">🟠 One Piece</button>
                </div>
            </div>

            <div class="mc-filter-group">
                <label>Estado envío</label>
                <div class="mc-status-btns">
                    <button class="mc-status-btn active" data-status="all">Todos</button>
                    <button class="mc-status-btn" data-status="pending">🟡 Recibido</button>
                    <button class="mc-status-btn" data-status="processing">🔵 Preparando</button>
                    <button class="mc-status-btn" data-status="shipped">🟣 Enviado</button>
                    <button class="mc-status-btn" data-status="delivered">🟢 Entregado</button>
                </div>
            </div>
        </div>
    </aside>

    <!-- Contenido principal -->
    <div>
        <div class="mc-header">
            <h2>📦 Mis Cartas</h2>
            <span class="mc-count" id="mc-count"></span>
        </div>
        <div class="mc-refresh-note" id="mc-refresh-note">Actualiza el estado cada 5 min.</div>

        <div class="mc-grid" id="mc-grid">
            <?php
            $allItems = [];

            // Cartas del carrito
            foreach ($cartItems as $it) {
                $st  = $it['shipping_status'] ?? 'pending';
                $allItems[] = [
                    'type'     => 'cart',
                    'key'      => 'cart-' . $it['order_id'],
                    'name'     => $it['card_name'],
                    'image'    => $it['card_image'],
                    'game'     => $it['card_game'],
                    'price'    => '$' . number_format((float)$it['card_price'], 2),
                    'badge_color' => '#3b82f6',
                    'status'   => $st,
                    'order_id' => $it['order_id'],
                    'auction_id' => null,
                ];
            }

            // Cartas de subastas
            foreach ($auctionItems as $it) {
                $st = $it['shipping_status'] ?? 'pending';
                $allItems[] = [
                    'type'       => 'auction',
                    'key'        => 'auc-' . $it['auction_id'],
                    'name'       => $it['card_name'],
                    'image'      => $it['card_image'],
                    'game'       => $it['card_game'],
                    'price'      => number_format((int)$it['card_price']) . ' LJ',
                    'badge_color'=> $it['badge_color'] ?? '#8b5cf6',
                    'status'     => $st,
                    'order_id'   => null,
                    'auction_id' => $it['auction_id'],
                ];
            }

            if (empty($allItems)): ?>
                <div class="mc-empty">
                    <div class="mc-empty-icon">🃏</div>
                    <h3>Aún no tienes cartas</h3>
                    <p>Compra en el mercado o gana una subasta para verlas aquí.</p>
                    <a href="index.php" class="btn-main" style="margin-top:16px;display:inline-block;">Explorar cartas</a>
                </div>
            <?php else:
                foreach ($allItems as $it):
                    $st    = $it['status'];
                    $step  = $statusSteps[$st]  ?? 1;
                    $color = $statusColors[$st]  ?? '#94a3b8';
            ?>
            <div class="mc-card"
                 data-key="<?php echo htmlspecialchars($it['key']); ?>"
                 data-game="<?php echo htmlspecialchars($it['game']); ?>"
                 data-status="<?php echo htmlspecialchars($st); ?>"
                 data-order-id="<?php echo (int)($it['order_id'] ?? 0); ?>"
                 data-auction-id="<?php echo (int)($it['auction_id'] ?? 0); ?>">
                <img class="mc-card-img" src="<?php echo htmlspecialchars($it['image']); ?>"
                     onerror="this.src='https://upload.wikimedia.org/wikipedia/en/3/3b/Pokemon_Trading_Card_Game_cardback.jpg'" alt="">
                <div class="mc-card-body">
                    <div class="mc-card-meta">
                        <span class="auction-game-badge" style="background:<?php echo htmlspecialchars($it['badge_color']); ?>"><?php echo htmlspecialchars($it['game']); ?></span>
                        <span class="mc-source-tag <?php echo $it['type']==='cart' ? 'mc-source-cart' : 'mc-source-auction'; ?>">
                            <?php echo $it['type']==='cart' ? '🛒 Compra' : '🏆 Subasta'; ?>
                        </span>
                    </div>
                    <div class="mc-card-name"><?php echo htmlspecialchars($it['name']); ?></div>
                    <div class="mc-card-price"><?php echo htmlspecialchars($it['price']); ?></div>

                    <!-- Tracker de envío -->
                    <div class="mc-tracker" id="tracker-<?php echo htmlspecialchars($it['key']); ?>">
                        <?php foreach ($trackerLabels as $i => $lbl):
                            $done = ($i + 1) <= $step; ?>
                        <div class="mc-tracker-step">
                            <div class="mc-tracker-dot" style="background:<?php echo $done ? $color : 'var(--border-color)'; ?>;color:<?php echo $done ? '#fff' : 'var(--text-secondary)'; ?>;">
                                <?php echo $done ? '✓' : ($i+1); ?>
                            </div>
                            <div class="mc-tracker-label" style="color:<?php echo $done ? 'var(--text-primary)' : 'var(--text-secondary)'; ?>"><?php echo $lbl; ?></div>
                            <?php if ($i < 3): ?>
                            <div class="mc-tracker-line" style="background:<?php echo ($i+1 < $step) ? $color : 'var(--border-color)'; ?>;"></div>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; endif; ?>
        </div>
    </div>
</div>

<script>
/* ── Filtros ── */
let activeGame   = 'all';
let activeStatus = 'all';

function applyFilters() {
    const q = (document.getElementById('mc-search')?.value || '').toLowerCase().trim();
    let visible = 0;
    document.querySelectorAll('#mc-grid .mc-card').forEach(card => {
        const name   = (card.querySelector('.mc-card-name')?.textContent || '').toLowerCase();
        const game   = card.dataset.game || '';
        const status = card.dataset.status || '';
        let show = true;
        if (q && !name.includes(q)) show = false;
        if (activeGame !== 'all'   && game   !== activeGame)   show = false;
        if (activeStatus !== 'all' && status !== activeStatus) show = false;
        card.style.display = show ? '' : 'none';
        if (show) visible++;
    });
    const countEl = document.getElementById('mc-count');
    if (countEl) countEl.textContent = visible + ' carta' + (visible !== 1 ? 's' : '');
}

document.getElementById('mc-search')?.addEventListener('input', applyFilters);

document.querySelectorAll('.mc-game-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        document.querySelectorAll('.mc-game-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        activeGame = btn.dataset.game;
        applyFilters();
    });
});

document.querySelectorAll('.mc-status-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        document.querySelectorAll('.mc-status-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        activeStatus = btn.dataset.status;
        applyFilters();
    });
});

applyFilters();

/* ── Auto-refresh de estado de envío cada 5 min ── */
const STATUS_LABELS = {pending:'Recibido',processing:'Preparando',shipped:'Enviado',delivered:'Entregado'};
const STATUS_COLORS = {pending:'#f59e0b',processing:'#3b82f6',shipped:'#8b5cf6',delivered:'#10b981'};
const STATUS_STEPS  = {pending:1,processing:2,shipped:3,delivered:4};
const TRACKER_LBLS  = ['Recibido','Preparando','Enviado','Entregado'];

function rebuildTracker(key, newStatus) {
    const tracker = document.getElementById('tracker-' + key);
    if (!tracker) return;
    const card = tracker.closest('.mc-card');
    if (card) card.dataset.status = newStatus;
    const step  = STATUS_STEPS[newStatus]  || 1;
    const color = STATUS_COLORS[newStatus] || '#94a3b8';
    tracker.innerHTML = TRACKER_LBLS.map((lbl, i) => {
        const done = (i + 1) <= step;
        return `<div class="mc-tracker-step">
            <div class="mc-tracker-dot" style="background:${done?color:'var(--border-color)'};color:${done?'#fff':'var(--text-secondary)'};">${done?'✓':(i+1)}</div>
            <div class="mc-tracker-label" style="color:${done?'var(--text-primary)':'var(--text-secondary)'}">${lbl}</div>
            ${i<3?`<div class="mc-tracker-line" style="background:${(i+1<step)?color:'var(--border-color)'};"></div>`:''}
        </div>`;
    }).join('');
}

async function refreshShipments() {
    try {
        const res  = await fetch('../api/shipments.php');
        const data = await res.json();
        if (!data.ok) return;
        data.cart.forEach(r => {
            document.querySelectorAll(`[data-order-id="${r.order_id}"]`).forEach(card => {
                const key = card.dataset.key;
                if (key) rebuildTracker(key, r.shipping_status);
            });
        });
        data.auctions.forEach(r => {
            document.querySelectorAll(`[data-auction-id="${r.auction_id}"]`).forEach(card => {
                const key = card.dataset.key;
                if (key) rebuildTracker(key, r.shipping_status);
            });
        });
        const note = document.getElementById('mc-refresh-note');
        if (note) note.textContent = 'Última actualización: ' + new Date().toLocaleTimeString('es-ES');
        applyFilters();
    } catch(e) {}
}

setInterval(refreshShipments, 5 * 60 * 1000); // cada 5 minutos
</script>
</body>
</html>
