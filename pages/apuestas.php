<?php
session_start();
require_once dirname(__DIR__) . '/includes/db.php';
$logged_in    = isset($_SESSION['user_id']);
$user_id      = $logged_in ? (int)$_SESSION['user_id'] : 0;
$lootcoins    = 0;
$user_address = '';
if ($logged_in) {
    $s = $conn->prepare("SELECT lootcoins, address FROM users WHERE id = ?");
    $s->bind_param("i", $user_id);
    $s->execute();
    $row = $s->get_result()->fetch_assoc();
    $lootcoins    = (int)($row['lootcoins'] ?? 0);
    $user_address = $row['address'] ?? '';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subastas | Loot&Trading</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/styles.css?v=<?php echo time(); ?>">
    <style>
        /* ── Selector de modo ───────── */
        .mode-selector {
            display: flex; gap: 14px; justify-content: center;
            padding: 32px 20px 16px; flex-wrap: wrap;
        }
        .mode-btn {
            padding: 14px 36px; border-radius: 50px; border: 2px solid var(--border-color);
            font-weight: 800; font-size: 1rem; cursor: pointer; transition: all .25s;
            background: #fff; color: var(--text-primary); letter-spacing: .01em;
            box-shadow: 0 2px 8px rgba(0,0,0,.06);
        }
        .mode-btn:hover { border-color: var(--accent-blue); color: var(--accent-blue); transform: translateY(-2px); }
        .mode-btn.active {
            background: var(--accent-blue);
            border-color: transparent; color: #fff;
            box-shadow: 0 8px 24px rgba(59,130,246,.35);
            transform: translateY(-2px);
        }
        body.dark .mode-btn { background: #1e293b; }
        body.dark .mode-btn.active { background: var(--accent-blue); }

        /* ── Panel vender ───────────── */
        .sell-panel { max-width: 820px; margin: 30px auto; padding: 0 20px 60px; }
        .sell-panel-title { font-size: 1.7rem; font-weight: 800; margin-bottom: 6px; }
        .sell-panel-desc { color: var(--text-secondary); margin-bottom: 28px; font-size: .95rem; }
        .sell-form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        @media(max-width:600px){ .sell-form-grid { grid-template-columns: 1fr; } }
        .sell-field label {
            display: block; margin-bottom: 8px; font-weight: 700; font-size: .82rem;
            text-transform: uppercase; letter-spacing: .04em; color: var(--text-secondary);
        }
        .sell-field input, .sell-field select {
            width: 100%; padding: 14px 16px; border: 2px solid var(--border-color);
            border-radius: 14px; font-size: .95rem; font-family: 'Outfit',sans-serif;
            background: #fff; color: var(--text-primary); box-sizing: border-box;
            transition: border-color .2s, box-shadow .2s;
        }
        .sell-field input:focus, .sell-field select:focus {
            outline: none; border-color: var(--accent-blue);
            box-shadow: 0 0 0 4px rgba(59,130,246,.12);
        }
        body.dark .sell-field input, body.dark .sell-field select {
            background: #0f172a; border-color: #334155; color: #e2e8f0;
        }
        .sell-preview { margin-top: 28px; }
        .sell-preview-label { font-weight: 700; font-size: .82rem; text-transform: uppercase;
            letter-spacing: .04em; color: var(--text-secondary); margin-bottom: 14px; }
        .sell-preview-card {
            display: flex; gap: 16px; align-items: center;
            background: #fff; border: 2px solid var(--border-color);
            border-radius: 20px; padding: 18px; max-width: 360px;
            box-shadow: 0 4px 16px rgba(0,0,0,.06);
        }
        body.dark .sell-preview-card { background: #1e293b; border-color: #334155; }
        .sell-preview-img-wrap img { width: 80px; border-radius: 12px; object-fit: cover; }
        .sell-preview-info { flex: 1; }

        /* ── Mis subastas tabs ───────── */
        .mine-tabs { display: flex; gap: 12px; padding: 24px 20px 8px; flex-wrap: wrap; }
        .mine-tab {
            padding: 12px 28px; border-radius: 50px; border: 2px solid var(--border-color);
            font-weight: 700; font-size: .9rem; cursor: pointer;
            background: #fff; color: var(--text-primary); transition: all .2s;
        }
        .mine-tab:hover { border-color: var(--accent-blue); color: var(--accent-blue); }
        .mine-tab.active {
            background: var(--accent-blue); color: #fff; border-color: transparent;
            box-shadow: 0 4px 14px rgba(59,130,246,.3);
        }
        body.dark .mine-tab { background: #1e293b; }

        /* ── Claim modal ─────────────── */
        .claim-overlay {
            position: fixed; inset: 0; background: rgba(15,23,42,.75);
            backdrop-filter: blur(10px); z-index: 3000;
            display: none; align-items: center; justify-content: center; padding: 20px;
        }
        .claim-overlay.open { display: flex; }
        .claim-box {
            background: #fff; border-radius: 28px; padding: 40px 36px;
            max-width: 500px; width: 100%;
            box-shadow: 0 30px 70px rgba(0,0,0,.3);
            border: 1px solid var(--border-color); text-align: center;
        }
        .claim-box img { width: 110px; border-radius: 16px; margin-bottom: 18px; box-shadow: 0 8px 24px rgba(0,0,0,.15); }
        .claim-box h3 { font-size: 1.4rem; font-weight: 800; margin-bottom: 8px; }
        .claim-box > p { color: var(--text-secondary); margin-bottom: 28px; font-size: .92rem; }
        .claim-options { display: flex; gap: 14px; flex-wrap: wrap; justify-content: center; margin-bottom: 20px; }
        .claim-opt {
            flex: 1; min-width: 160px; padding: 20px 16px; border: 2px solid var(--border-color);
            border-radius: 20px; cursor: pointer; transition: all .2s; background: #f8f9fd;
            font-weight: 700; font-size: .92rem;
        }
        .claim-opt:hover { border-color: var(--accent-blue); transform: translateY(-2px); box-shadow: 0 6px 18px rgba(59,130,246,.15); }
        .claim-opt.selected {
            border-color: var(--accent-blue); background: rgba(59,130,246,.08);
            box-shadow: 0 6px 18px rgba(59,130,246,.2);
        }
        .claim-opt .opt-icon { font-size: 2.2rem; margin-bottom: 8px; }
        #claim-address-wrap { margin-top: 18px; display: none; text-align: left; }
        #claim-address-wrap textarea {
            width: 100%; box-sizing: border-box; padding: 14px; border-radius: 14px;
            border: 2px solid var(--border-color); font-family: 'Outfit',sans-serif;
            font-size: .92rem; background: #f8f9fd; color: var(--text-primary); resize: vertical;
            transition: border-color .2s;
        }
        #claim-address-wrap textarea:focus { outline: none; border-color: var(--accent-blue); }
        .claim-confirm-btn {
            width: 100%; margin-top: 20px; padding: 16px; border-radius: 50px;
            background: var(--accent-blue); color: #fff; font-weight: 800; font-size: 1rem;
            border: none; cursor: pointer; transition: all .25s;
            box-shadow: 0 6px 20px rgba(59,130,246,.35);
        }
        .claim-confirm-btn:hover { background: #2563eb; transform: translateY(-2px); }
        .claim-cancel-btn {
            background: none; border: none; color: var(--text-secondary);
            cursor: pointer; margin-top: 12px; font-size: .88rem; font-family: 'Outfit',sans-serif;
            transition: color .2s;
        }
        .claim-cancel-btn:hover { color: var(--text-primary); }
        body.dark .claim-box { background: #1e293b; border-color: #334155; }
        body.dark .claim-opt { background: #0f172a; }
        body.dark .claim-opt.selected { background: rgba(59,130,246,.15); }
        body.dark #claim-address-wrap textarea { background: #0f172a; border-color: #334155; color: #e2e8f0; }
    </style>
</head>
<body>
<script>(function(){ if(localStorage.getItem('theme')==='dark') document.body.classList.add('dark'); })();</script>

<?php include dirname(__DIR__) . '/includes/navbar.php'; ?>

<header class="auctions-hero">
    <div class="card-wall-bg">
        <div class="wall-column col-up">
            <div class="wall-img" style="background-image: url('https://images.pokemontcg.io/base1/4_hires.png')"></div>
            <div class="wall-img" style="background-image: url('https://images.ygoprodeck.com/images/cards/89631139.jpg')"></div>
            <div class="wall-img" style="background-image: url('https://cards.scryfall.io/large/front/b/d/bd8fa327-dd41-4737-8f19-2cf5eb1f7cdd.jpg')"></div>
            <div class="wall-img" style="background-image: url('https://images.pokemontcg.io/swsh35/74_hires.png')"></div>
            <div class="wall-img" style="background-image: url('https://images.ygoprodeck.com/images/cards/89631139.jpg')"></div>
        </div>
        <div class="wall-column col-down">
            <div class="wall-img" style="background-image: url('https://asia-en.onepiece-cardgame.com/images/cardlist/card/OP05-060.png')"></div>
            <div class="wall-img" style="background-image: url('https://images.pokemontcg.io/base1/2_hires.png')"></div>
            <div class="wall-img" style="background-image: url('https://images.ygoprodeck.com/images/cards/46986414.jpg')"></div>
            <div class="wall-img" style="background-image: url('https://asia-en.onepiece-cardgame.com/images/cardlist/card/OP01-016.png')"></div>
            <div class="wall-img" style="background-image: url('https://images.pokemontcg.io/swsh7/215_hires.png')"></div>
        </div>
        <div class="wall-column col-up">
            <div class="wall-img" style="background-image: url('https://cards.scryfall.io/large/front/7/0/70901356-3266-4bd9-aacc-f06c27271de5.jpg')"></div>
            <div class="wall-img" style="background-image: url('https://asia-en.onepiece-cardgame.com/images/cardlist/card/OP01-025.png')"></div>
            <div class="wall-img" style="background-image: url('https://images.pokemontcg.io/neo1/9_hires.png')"></div>
            <div class="wall-img" style="background-image: url('https://cards.scryfall.io/large/front/e/3/e3285e6b-3e79-4d7c-bf96-d920f973b122.jpg')"></div>
            <div class="wall-img" style="background-image: url('https://images.pokemontcg.io/swsh11/186_hires.png')"></div>
        </div>
        <div class="wall-column col-down">
            <div class="wall-img" style="background-image: url('https://images.ygoprodeck.com/images/cards/33396948.jpg')"></div>
            <div class="wall-img" style="background-image: url('https://cards.scryfall.io/large/front/2/3/2398892d-28e9-4009-81ec-0d544af79d2b.jpg')"></div>
            <div class="wall-img" style="background-image: url('https://asia-en.onepiece-cardgame.com/images/cardlist/card/OP01-120.png')"></div>
            <div class="wall-img" style="background-image: url('https://images.pokemontcg.io/base1/15_hires.png')"></div>
            <div class="wall-img" style="background-image: url('https://images.ygoprodeck.com/images/cards/4031928.jpg')"></div>
        </div>
        <div class="wall-column col-up">
            <div class="wall-img" style="background-image: url('https://images.pokemontcg.io/swsh4/43_hires.png')"></div>
            <div class="wall-img" style="background-image: url('https://cards.scryfall.io/large/front/f/2/f2bc06cb-2f22-4313-82a2-a7e7b2564f4d.jpg')"></div>
            <div class="wall-img" style="background-image: url('https://asia-en.onepiece-cardgame.com/images/cardlist/card/OP01-121.png')"></div>
            <div class="wall-img" style="background-image: url('https://images.pokemontcg.io/swsh1/1_hires.png')"></div>
            <div class="wall-img" style="background-image: url('https://images.ygoprodeck.com/images/cards/10000020.jpg')"></div>
        </div>
    </div>
    <div class="auctions-hero-content">
        <div class="auctions-hero-card">
            <div style="font-size:.65rem;font-weight:800;text-transform:uppercase;letter-spacing:2px;color:var(--accent-blue);margin-bottom:8px;">Loot&Trading</div>
            <div class="auctions-title">Subastas <span>en vivo</span></div>
            <?php if ($logged_in): ?>
            <div class="balance-pill" style="margin-top:20px;align-self:center;">
                <img src="img/lujanito.svg" alt="Lujanito" style="width:38px;height:38px;border-radius:50%;">
                <div>
                    <div style="font-size:.6rem;font-weight:700;opacity:.85;text-transform:uppercase;letter-spacing:1px;">Lujanitos</div>
                    <div class="balance-amount" id="balance-display"><?php echo number_format($lootcoins); ?></div>
                </div>
            </div>
            <?php else: ?>
            <a href="auth.php" class="btn-main" style="margin-top:20px;">Inicia sesión para pujar</a>
            <?php endif; ?>
        </div>
    </div>
</header>

<div class="auctions-wrap">

    <!-- Selector Modo -->
    <div class="mode-selector">
        <button class="mode-btn active" id="mode-buy-btn"  onclick="setMode('buy',  this)">🛒 Comprar</button>
        <button class="mode-btn"        id="mode-sell-btn" onclick="setMode('sell', this)">📤 Vender</button>
        <?php if ($logged_in): ?>
        <button class="mode-btn"        id="mode-mine-btn" onclick="setMode('mine', this)">👤 Mis Subastas</button>
        <?php endif; ?>
    </div>

    <!-- ===== PANEL COMPRAR ===== -->
    <div id="panel-buy">
        <div class="auction-tabs">
            <button class="auction-tab active" onclick="filterTab('active', this)">⚡ Activas</button>
            <button class="auction-tab"        onclick="filterTab('ended',  this)">✅ Terminadas</button>
        </div>
        <div class="auction-filters" id="auction-filters">
            <button class="filter-btn active" onclick="filterGame('all',      this)">Todos</button>
            <button class="filter-btn"        onclick="filterGame('Pokémon',  this)">Pokémon</button>
            <button class="filter-btn"        onclick="filterGame('Yu-Gi-Oh!',this)">Yu-Gi-Oh!</button>
            <button class="filter-btn"        onclick="filterGame('Magic',    this)">Magic</button>
            <button class="filter-btn"        onclick="filterGame('One Piece',this)">One Piece</button>
        </div>
        <div class="auctions-grid" id="auctions-grid">
            <?php for($i=0;$i<8;$i++): ?>
            <div class="auction-skeleton"></div>
            <?php endfor; ?>
        </div>
    </div>

    <!-- ===== PANEL VENDER ===== -->
    <div id="panel-sell" style="display:none;">
        <div class="sell-panel">
            <h2 class="sell-panel-title">📤 Poner carta en subasta</h2>
            <p class="sell-panel-desc">Elige la carta, establece el precio de salida y cuánto tiempo durará la subasta. Todos los usuarios podrán pujar.</p>

            <form id="sell-form" class="sell-form">
                <div class="sell-form-grid">
                    <div class="sell-field">
                        <label>Nombre de la carta *</label>
                        <input type="text" id="sf-name" placeholder="Ej: Charizard Base Set" required>
                    </div>
                    <div class="sell-field">
                        <label>Imagen de la carta</label>
                        <div style="display:flex;gap:8px;align-items:center;">
                            <input type="url" id="sf-image" placeholder="https://... o sube un archivo" oninput="previewSellCard()" style="flex:1;">
                            <label for="sf-file-input" id="sf-file-btn" style="padding:13px 14px;border-radius:14px;background:var(--accent-blue);color:#fff;font-weight:800;cursor:pointer;font-size:.9rem;white-space:nowrap;transition:.2s;" title="Subir imagen desde galería">📁 Galería</label>
                            <input type="file" id="sf-file-input" accept="image/*" style="display:none;">
                        </div>
                    </div>
                    <div class="sell-field">
                        <label>Juego *</label>
                        <select id="sf-game" onchange="updateSellBadge()">
                            <option value="Pokémon"  data-color="#eab308">Pokémon</option>
                            <option value="Yu-Gi-Oh!" data-color="#a855f7">Yu-Gi-Oh!</option>
                            <option value="Magic"    data-color="#ef4444">Magic: The Gathering</option>
                            <option value="One Piece" data-color="#f97316">One Piece</option>
                        </select>
                    </div>
                    <div class="sell-field">
                        <label>Precio base (Lujanitos) *</label>
                        <input type="number" id="sf-price" min="10" max="999999" value="100" required>
                    </div>
                    <div class="sell-field">
                        <label>Duración</label>
                        <select id="sf-duration">
                            <option value="1">1 hora</option>
                            <option value="6">6 horas</option>
                            <option value="12">12 horas</option>
                            <option value="24" selected>24 horas</option>
                            <option value="48">48 horas</option>
                            <option value="72">72 horas (3 días)</option>
                            <option value="168">168 horas (7 días)</option>
                        </select>
                    </div>
                </div>

                <div class="sell-preview" id="sell-preview">
                    <div class="sell-preview-label">Vista previa</div>
                    <div class="sell-preview-card" id="sell-preview-card">
                        <div class="sell-preview-img-wrap">
                            <img id="sp-img" src="https://upload.wikimedia.org/wikipedia/en/3/3b/Pokemon_Trading_Card_Game_cardback.jpg" alt="">
                        </div>
                        <div class="sell-preview-info">
                            <span class="auction-game-badge" id="sp-badge" style="background:#eab308">Pokémon</span>
                            <div class="auction-name" id="sp-name">Nombre de la carta</div>
                            <div class="auction-current-price" id="sp-price">100 <span style="font-size:.7rem;font-weight:700">LJ</span></div>
                        </div>
                    </div>
                </div>

                <?php if ($logged_in): ?>
                <button type="submit" class="btn-main" style="width:100%;margin-top:20px;padding:16px;">
                    Publicar subasta
                </button>
                <?php else: ?>
                <a href="auth.php" class="btn-main" style="display:block;text-align:center;margin-top:20px;">
                    Inicia sesión para publicar
                </a>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <!-- ===== PANEL MIS SUBASTAS ===== -->
    <?php if ($logged_in): ?>
    <div id="panel-mine" style="display:none;">
        <div class="mine-tabs">
            <button class="mine-tab active" onclick="filterMineTab('bids',this)">📋 Mis Pujas Activas</button>
            <button class="mine-tab"        onclick="filterMineTab('wins',this)">🏆 Subastas Ganadas</button>
        </div>

        <div id="mine-bids-section">
            <div id="mine-bids-grid" class="auctions-grid">
                <div class="auction-skeleton"></div>
                <div class="auction-skeleton"></div>
                <div class="auction-skeleton"></div>
            </div>
        </div>

        <div id="mine-wins-section" style="display:none;">
            <div id="mine-wins-grid" class="auctions-grid"></div>
        </div>
    </div>
    <?php endif; ?>

<!-- Modal claim win -->
<div class="claim-overlay" id="claim-overlay">
    <div class="claim-box">
        <img id="claim-img" src="" alt="">
        <h3 id="claim-card-name">Carta ganada</h3>
        <p>¡Enhorabuena! Has ganado esta subasta. Indica tu dirección de envío para recibir la carta.</p>
        <div id="claim-address-wrap" style="display:block;text-align:left;">
            <label style="font-weight:700;font-size:.82rem;text-transform:uppercase;letter-spacing:.04em;color:var(--text-secondary);display:block;margin-bottom:8px;">Dirección de envío</label>
            <textarea id="claim-address" rows="3" placeholder="Nombre completo, calle, ciudad, código postal..."><?php echo htmlspecialchars($user_address); ?></textarea>
        </div>
        <button class="claim-confirm-btn" onclick="confirmClaim()">📦 Confirmar envío</button>
        <button class="claim-cancel-btn" onclick="closeClaim()">Cancelar</button>
    </div>
</div>

<!-- Modal de puja -->
<div class="bid-overlay" id="bid-overlay">
    <div class="bid-box">
        <div class="bid-box-header">
            <img id="bm-img" class="bid-box-img" src="" alt="">
            <div>
                <div class="bid-box-title"  id="bm-name"></div>
                <div class="bid-box-subtitle" id="bm-game"></div>
            </div>
        </div>

        <div class="bid-info-row">
            <div class="bid-info-cell">
                <div class="bid-info-cell-label">Puja actual</div>
                <div class="bid-info-cell-val orange" id="bm-current">—</div>
            </div>
            <div class="bid-info-cell">
                <div class="bid-info-cell-label">Tu balance</div>
                <div class="bid-info-cell-val purple" id="bm-balance">—</div>
            </div>
            <div class="bid-info-cell">
                <div class="bid-info-cell-label">Puja mínima</div>
                <div class="bid-info-cell-val" id="bm-min">—</div>
            </div>
            <div class="bid-info-cell">
                <div class="bid-info-cell-label">Líder actual</div>
                <div class="bid-info-cell-val" id="bm-leader" style="font-size:.85rem;">—</div>
            </div>
        </div>

        <div class="bid-history">
            <div class="bid-history-title">Historial de pujas</div>
            <div class="bid-history-list" id="bm-history"></div>
        </div>

        <div class="bid-input-label">Tu puja</div>
        <div class="bid-input-wrap">
            <input type="number" id="bm-amount" placeholder="0" min="0" step="10">
            <span class="bid-input-suffix">LC</span>
        </div>
        <div class="bid-quick-row" id="bm-quick-row"></div>

        <div class="bid-actions">
            <button class="btn-cancel-bid" onclick="closeBidModal()">Cancelar</button>
            <button class="btn-confirm-bid" id="bm-confirm">Pujar ahora</button>
        </div>
    </div>
</div>

<div class="toast-container" id="toast-container"></div>

<script>
/* ── Utilidades ──────────────────────── */
function toggleDarkMode() {
    document.body.classList.toggle('dark');
    localStorage.setItem('theme', document.body.classList.contains('dark') ? 'dark' : 'light');
}
function toast(msg, type = 'info') {
    const c = document.getElementById('toast-container');
    const t = document.createElement('div');
    t.className = `toast ${type}`;
    t.innerHTML = `<span>${{success:'✅',error:'❌',info:'ℹ️'}[type]||'ℹ️'}</span>${msg}`;
    c.appendChild(t);
    setTimeout(() => t.remove(), 4000);
}
function formatTime(secs) {
    if (secs <= 0) return 'Terminada';
    const h = Math.floor(secs / 3600);
    const m = Math.floor((secs % 3600) / 60);
    const s = secs % 60;
    if (h > 0) return `${h}h ${m}m`;
    if (m > 0) return `${m}:${s.toString().padStart(2,'0')}`;
    return `${s}s`;
}

/* ── Estado ──────────────────────────── */
const LOGGED_IN   = <?php echo $logged_in ? 'true' : 'false'; ?>;
const MY_USER_ID  = <?php echo $user_id; ?>;
let currentBalance = <?php echo $lootcoins; ?>;
let allAuctions   = [];
let countdownInts = {};
let currentTab    = 'active';
let currentGame   = 'all';
let activeBidAuction = null;

/* ── Cargar subastas ─────────────────── */
async function loadAuctions() {
    const res  = await fetch('../api/auction.php?action=list');
    const data = await res.json();
    if (!data.ok) return;
    allAuctions = data.auctions;
    renderAuctions();
}

function renderAuctions() {
    // Limpiar timers
    Object.values(countdownInts).forEach(clearInterval);
    countdownInts = {};

    let list = allAuctions.filter(a => {
        const isActive = a.status === 'active' && secondsLeft(a.ends_at) > 0;
        if (currentTab === 'active' && !isActive) return false;
        if (currentTab === 'ended'  &&  isActive) return false;
        if (currentGame !== 'all'  && a.card_game !== currentGame) return false;
        return true;
    });

    const grid = document.getElementById('auctions-grid');
    if (!list.length) {
        grid.innerHTML = `<div class="no-auctions" style="grid-column:1/-1">
            <h3>${currentTab === 'active' ? 'No hay subastas activas ahora' : 'No hay subastas terminadas'}</h3>
            <p>Vuelve pronto — se añaden nuevas cada poco tiempo.</p>
        </div>`;
        return;
    }

    grid.innerHTML = '';
    list.forEach(a => grid.appendChild(buildAuctionCard(a)));
}

function buildAuctionCard(a) {
    const secs       = secondsLeft(a.ends_at);
    const isActive   = a.status === 'active' && secs > 0;
    const imWinning  = LOGGED_IN && a.current_winner_id == MY_USER_ID;
    const hasAnyBid  = parseInt(a.current_bid) > 0;
    const minBid     = hasAnyBid ? parseInt(a.current_bid) + 10 : parseInt(a.base_price);

    const div = document.createElement('div');
    div.className = `auction-card${imWinning ? ' is-winning' : ''}${!isActive ? ' is-ended' : ''}`;
    div.id = `acard-${a.id}`;

    const timerClass = !isActive ? 'ended-tag' : secs <= 30 ? 'urgent' : '';

    div.innerHTML = `
        <div class="auction-img-wrap">
            <img src="${a.card_image}" alt="${a.card_name}"
                 onerror="this.src='https://upload.wikimedia.org/wikipedia/en/3/3b/Pokemon_Trading_Card_Game_cardback.jpg'">
            <div class="auction-countdown ${timerClass}" id="timer-${a.id}">
                ${isActive ? formatTime(secs) : 'Terminada'}
            </div>
            ${imWinning && isActive ? '<div class="auction-winning-banner">🏆 Vas ganando</div>' : ''}
        </div>
        <div class="auction-body">
            <span class="auction-game-badge" style="background:${a.badge_color}">${a.card_game}</span>
            <div class="auction-name">${a.card_name}</div>
            <div class="auction-price-section">
                <div>
                    <div class="auction-price-label">${hasAnyBid ? 'Puja actual' : 'Precio base'}</div>
                    <div class="auction-current-price">${(hasAnyBid ? a.current_bid : a.base_price).toLocaleString('es-ES')} <span style="font-size:.7rem;font-weight:700">LC</span></div>
                </div>
                ${hasAnyBid ? `<div class="auction-base-price">Base: ${parseInt(a.base_price).toLocaleString('es-ES')} LJ</div>` : ''}
            </div>
            <div class="auction-leader">
                ${hasAnyBid
                    ? `Líder: <strong>${a.current_winner_name}</strong>`
                    : 'Sin pujas aún — ¡sé el primero!'}
            </div>
            <button class="btn-bid ${imWinning ? 'is-winning-btn' : ''} ${!isActive ? 'ended-btn' : ''}"
                    onclick="openBidModal(${a.id})" ${!isActive || !LOGGED_IN ? 'disabled' : ''}>
                ${!LOGGED_IN ? 'Inicia sesión' : !isActive ? 'Subasta cerrada' : imWinning ? '🏆 Vas ganando' : `Pujar (min. ${minBid.toLocaleString('es-ES')} LJ)`}
            </button>
        </div>
    `;

    // Arrancar countdown
    if (isActive) {
        countdownInts[a.id] = setInterval(() => {
            const left = secondsLeft(a.ends_at);
            const el   = document.getElementById(`timer-${a.id}`);
            if (!el) { clearInterval(countdownInts[a.id]); return; }
            if (left <= 0) {
                el.textContent = 'Terminada';
                el.classList.remove('urgent');
                el.classList.add('ended-tag');
                clearInterval(countdownInts[a.id]);
                // Recargar en 2s
                setTimeout(loadAuctions, 2000);
            } else {
                el.textContent = formatTime(left);
                if (left <= 30) el.classList.add('urgent');
            }
        }, 1000);
    }

    return div;
}

function secondsLeft(endsAt) {
    return Math.max(0, Math.floor((new Date(endsAt).getTime() - Date.now()) / 1000));
}

/* ── Filtros ─────────────────────────── */
function filterTab(tab, btn) {
    currentTab = tab;
    document.querySelectorAll('.auction-tab').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    renderAuctions();
}
function filterGame(game, btn) {
    currentGame = game;
    document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    renderAuctions();
}

/* ── Modal puja ──────────────────────── */
async function openBidModal(auctionId) {
    if (!LOGGED_IN) { window.location.href = 'auth.php'; return; }

    activeBidAuction = allAuctions.find(a => a.id == auctionId);
    if (!activeBidAuction) return;

    const a       = activeBidAuction;
    const curBid  = parseInt(a.current_bid)  || 0;
    const basePr  = parseInt(a.base_price)   || 0;
    const minBid  = curBid > 0 ? curBid + 10 : basePr;

    document.getElementById('bm-img').src     = a.card_image;
    document.getElementById('bm-name').textContent = a.card_name;
    document.getElementById('bm-game').textContent = a.card_game;
    document.getElementById('bm-current').textContent =
        (curBid > 0 ? curBid : basePr).toLocaleString('es-ES') + ' LJ';
    document.getElementById('bm-balance').textContent = currentBalance.toLocaleString('es-ES') + ' LJ';
    document.getElementById('bm-min').textContent     = minBid.toLocaleString('es-ES') + ' LJ';
    document.getElementById('bm-leader').textContent  = a.current_winner_name || '—';
    document.getElementById('bm-amount').value = minBid;
    document.getElementById('bm-amount').min   = minBid;

    // Botones rápidos
    const qr = document.getElementById('bm-quick-row');
    const increments = [0, 50, 100, 250, 500];
    qr.innerHTML = increments.map(inc =>
        `<button class="bid-quick" onclick="setQuickBid(${minBid + inc})">
            ${inc === 0 ? 'Mín' : `+${inc}`} (${(minBid + inc).toLocaleString('es-ES')})
        </button>`
    ).join('');

    // Historial
    const hRes  = await fetch(`../api/auction.php?action=bids&auction_id=${auctionId}`);
    const hData = await hRes.json();
    const hist  = document.getElementById('bm-history');
    if (hData.ok && hData.bids.length) {
        hist.innerHTML = hData.bids.map(b => `
            <div class="bid-history-row">
                <span class="bid-history-user">${b.username}</span>
                <span class="bid-history-amount">${parseInt(b.amount).toLocaleString('es-ES')} LJ</span>
            </div>`).join('');
    } else {
        hist.innerHTML = '<div style="font-size:.78rem;color:var(--text-secondary);padding:4px 0">Sin pujas aún</div>';
    }

    document.getElementById('bid-overlay').classList.add('open');
    document.getElementById('bm-amount').focus();
}

function closeBidModal() {
    document.getElementById('bid-overlay').classList.remove('open');
    activeBidAuction = null;
}
document.getElementById('bid-overlay').addEventListener('click', function(e) {
    if (e.target === this) closeBidModal();
});

function setQuickBid(val) {
    document.getElementById('bm-amount').value = val;
}

document.getElementById('bm-confirm').addEventListener('click', async function() {
    if (!activeBidAuction) return;
    const amount = parseInt(document.getElementById('bm-amount').value) || 0;
    const a      = activeBidAuction;
    const minBid = parseInt(a.current_bid) > 0 ? parseInt(a.current_bid) + 10 : parseInt(a.base_price);

    if (amount < minBid) { toast(`Puja mínima: ${minBid.toLocaleString('es-ES')} LJ`, 'error'); return; }
    if (amount > currentBalance) { toast('No tienes suficientes Lujanitos', 'error'); return; }

    this.disabled = true;
    this.textContent = 'Enviando...';

    const fd = new FormData();
    fd.append('action',     'bid');
    fd.append('auction_id', a.id);
    fd.append('amount',     amount);

    const res  = await fetch('../api/auction.php', { method: 'POST', body: fd });
    const data = await res.json();

    this.disabled = false;
    this.textContent = 'Pujar ahora';

    if (data.ok) {
        currentBalance = data.new_balance;
        document.getElementById('balance-display').textContent = currentBalance.toLocaleString('es-ES');
        closeBidModal();
        toast(`🏆 Puja de ${amount.toLocaleString('es-ES')} LJ registrada — vas ganando`, 'success');
        await loadAuctions();
    } else {
        toast(data.message || 'Error al pujar', 'error');
    }
});

/* ── Refresco automático ─────────────── */
loadAuctions();
setInterval(loadAuctions, 20000); // cada 20s

/* ══════════════════════════════════════
   SELECTOR DE MODO (comprar / vender / mis)
══════════════════════════════════════ */
function setMode(mode, btn) {
    document.querySelectorAll('.mode-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    document.getElementById('panel-buy').style.display  = mode === 'buy'  ? '' : 'none';
    document.getElementById('panel-sell').style.display = mode === 'sell' ? '' : 'none';
    const mine = document.getElementById('panel-mine');
    if (mine) mine.style.display = mode === 'mine' ? '' : 'none';
    if (mode === 'mine') loadMineData();
}

/* ── Subir imagen de carta ───────────── */
document.getElementById('sf-file-input')?.addEventListener('change', async function() {
    const file = this.files[0];
    if (!file) return;
    const btn = document.getElementById('sf-file-btn');
    if (btn) btn.textContent = '⏳';
    const fd = new FormData();
    fd.append('card_image', file);
    try {
        const res  = await fetch('../api/upload_card.php', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.ok) {
            document.getElementById('sf-image').value = data.url;
            previewSellCard();
            toast('Imagen subida correctamente', 'success');
        } else {
            toast(data.message || 'Error al subir la imagen', 'error');
        }
    } catch(e) {
        toast('Error de conexión', 'error');
    } finally {
        if (btn) btn.textContent = '📁 Galería';
        this.value = '';
    }
});

/* ══════════════════════════════════════
   PANEL VENDER
══════════════════════════════════════ */
function previewSellCard() {
    const url = document.getElementById('sf-image')?.value.trim();
    const img = document.getElementById('sp-img');
    if (img && url) img.src = url;
}
function updateSellBadge() {
    const sel = document.getElementById('sf-game');
    const opt = sel.options[sel.selectedIndex];
    const badge = document.getElementById('sp-badge');
    if (badge) {
        badge.textContent = opt.value;
        badge.style.background = opt.dataset.color;
    }
}
document.getElementById('sf-name')?.addEventListener('input', () => {
    const el = document.getElementById('sp-name');
    if (el) el.textContent = document.getElementById('sf-name').value || 'Nombre de la carta';
});
document.getElementById('sf-price')?.addEventListener('input', () => {
    const el = document.getElementById('sp-price');
    if (el) {
        const v = parseInt(document.getElementById('sf-price').value) || 0;
        el.innerHTML = `${v.toLocaleString('es-ES')} <span style="font-size:.7rem;font-weight:700">LJ</span>`;
    }
});

document.getElementById('sell-form')?.addEventListener('submit', async function(e) {
    e.preventDefault();
    if (!LOGGED_IN) { toast('Debes iniciar sesión', 'error'); return; }
    const name     = document.getElementById('sf-name').value.trim();
    const image    = document.getElementById('sf-image').value.trim();
    const gameSel  = document.getElementById('sf-game');
    const game     = gameSel.value;
    const color    = gameSel.options[gameSel.selectedIndex].dataset.color;
    const price    = parseInt(document.getElementById('sf-price').value) || 0;
    const duration = parseInt(document.getElementById('sf-duration').value) || 24;

    if (!name || price < 10) { toast('Nombre y precio son obligatorios (mínimo 10 LJ)', 'error'); return; }

    const btn = this.querySelector('button[type=submit]');
    btn.disabled = true; btn.textContent = 'Publicando...';

    const fd = new FormData();
    fd.append('action',         'create');
    fd.append('card_name',      name);
    fd.append('card_image',     image);
    fd.append('card_game',      game);
    fd.append('badge_color',    color);
    fd.append('base_price',     price);
    fd.append('duration_hours', duration);

    const res  = await fetch('../api/auction.php', { method: 'POST', body: fd });
    const data = await res.json();

    btn.disabled = false; btn.textContent = 'Publicar subasta';

    if (data.ok) {
        toast('¡Subasta publicada! Ya está visible para todos.', 'success');
        document.getElementById('sell-form').reset();
        document.getElementById('sp-name').textContent = 'Nombre de la carta';
        document.getElementById('sp-img').src = 'https://upload.wikimedia.org/wikipedia/en/3/3b/Pokemon_Trading_Card_Game_cardback.jpg';
        document.getElementById('sp-price').innerHTML = '100 <span style="font-size:.7rem;font-weight:700">LJ</span>';
        // Cambiar a modo comprar y recargar
        setMode('buy', document.getElementById('mode-buy-btn'));
        await loadAuctions();
    } else {
        toast(data.message || 'Error al publicar', 'error');
    }
});

/* ══════════════════════════════════════
   PANEL MIS SUBASTAS
══════════════════════════════════════ */
let mineCurrentTab = 'bids';
function filterMineTab(tab, btn) {
    mineCurrentTab = tab;
    document.querySelectorAll('.mine-tab').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    document.getElementById('mine-bids-section').style.display = tab === 'bids' ? '' : 'none';
    document.getElementById('mine-wins-section').style.display = tab === 'wins' ? '' : 'none';
}

async function loadMineData() {
    loadMyBids();
    loadMyWins();
}

async function loadMyBids() {
    const grid = document.getElementById('mine-bids-grid');
    if (!grid) return;
    grid.innerHTML = '<div class="auction-skeleton"></div><div class="auction-skeleton"></div>';

    const res  = await fetch('../api/auction.php?action=my_bids');
    const data = await res.json();
    if (!data.ok) { grid.innerHTML = '<p style="padding:20px;color:var(--text-secondary)">Error al cargar</p>'; return; }

    if (!data.bids.length) {
        grid.innerHTML = '<div class="no-auctions" style="grid-column:1/-1"><h3>Sin pujas activas</h3><p>Aún no has pujado en ninguna subasta.</p></div>';
        return;
    }

    grid.innerHTML = '';
    data.bids.forEach(a => {
        const imWinning = a.current_winner_id == MY_USER_ID;
        const secs = secondsLeft(a.ends_at);
        const isActive = a.status === 'active' && secs > 0;
        const div = document.createElement('div');
        div.className = `auction-card${imWinning ? ' is-winning' : ''}${!isActive ? ' is-ended' : ''}`;
        div.innerHTML = `
            <div class="auction-img-wrap">
                <img src="${a.card_image}" alt="${a.card_name}" onerror="this.src='https://upload.wikimedia.org/wikipedia/en/3/3b/Pokemon_Trading_Card_Game_cardback.jpg'">
                <div class="auction-countdown ${!isActive ? 'ended-tag' : ''}">${isActive ? formatTime(secs) : 'Terminada'}</div>
                ${imWinning && isActive ? '<div class="auction-winning-banner">🏆 Vas ganando</div>' : ''}
            </div>
            <div class="auction-body">
                <span class="auction-game-badge" style="background:${a.badge_color}">${a.card_game}</span>
                <div class="auction-name">${a.card_name}</div>
                <div class="auction-price-section">
                    <div>
                        <div class="auction-price-label">Tu mejor puja</div>
                        <div class="auction-current-price">${parseInt(a.my_best_bid).toLocaleString('es-ES')} <span style="font-size:.7rem;font-weight:700">LJ</span></div>
                    </div>
                </div>
                <div class="auction-leader">${imWinning ? '🏆 Vas ganando' : `Líder: <strong>${a.current_winner_name || '—'}</strong>`}</div>
                ${isActive ? `<button class="btn-bid ${imWinning ? 'is-winning-btn' : ''}" onclick="openBidModal(${a.id})">
                    ${imWinning ? '🏆 Subir puja' : 'Pujar de nuevo'}
                </button>` : '<button class="btn-bid ended-btn" disabled>Subasta cerrada</button>'}
            </div>`;
        grid.appendChild(div);
    });
}

async function loadMyWins() {
    const grid = document.getElementById('mine-wins-grid');
    if (!grid) return;
    grid.innerHTML = '<div class="auction-skeleton"></div><div class="auction-skeleton"></div>';

    const res  = await fetch('../api/auction.php?action=my_wins');
    const data = await res.json();
    if (!data.ok) { grid.innerHTML = '<p style="padding:20px;color:var(--text-secondary)">Error al cargar</p>'; return; }

    if (!data.wins.length) {
        grid.innerHTML = '<div class="no-auctions" style="grid-column:1/-1"><h3>Sin subastas ganadas</h3><p>Cuando ganes una subasta aparecerá aquí.</p></div>';
        return;
    }

    grid.innerHTML = '';
    data.wins.forEach(a => {
        const claimed  = !!a.claim_id;
        const choiceIcon = a.choice === 'exchange' ? '🪙' : a.choice === 'delivery' ? '📦' : '';
        const statusLabel = {pending:'En proceso', processing:'Procesando', shipped:'Enviada', delivered:'Entregada', done:'Completada'}[a.claim_status] || '';
        const div = document.createElement('div');
        div.className = 'auction-card';
        div.innerHTML = `
            <div class="auction-img-wrap">
                <img src="${a.card_image}" alt="${a.card_name}" onerror="this.src='https://upload.wikimedia.org/wikipedia/en/3/3b/Pokemon_Trading_Card_Game_cardback.jpg'">
                <div class="auction-countdown ended-tag">Ganada</div>
            </div>
            <div class="auction-body">
                <span class="auction-game-badge" style="background:${a.badge_color}">${a.card_game}</span>
                <div class="auction-name">${a.card_name}</div>
                <div class="auction-price-section">
                    <div>
                        <div class="auction-price-label">Puja ganadora</div>
                        <div class="auction-current-price">${parseInt(a.current_bid).toLocaleString('es-ES')} <span style="font-size:.7rem;font-weight:700">LJ</span></div>
                    </div>
                </div>
                ${claimed
                    ? `<div style="margin-top:10px;padding:10px;background:rgba(16,185,129,.1);border-radius:10px;font-weight:700;font-size:.85rem;">
                           ${choiceIcon} ${a.choice === 'exchange' ? 'Canjeada' : 'Envío solicitado'} — ${statusLabel}
                       </div>`
                    : `<button class="btn-bid is-winning-btn" onclick="openClaimModal(${a.id}, '${a.card_name.replace(/'/g,"\\'")}', '${a.card_image}', ${a.current_bid})">
                           🎁 Reclamar carta
                       </button>`
                }
            </div>`;
        grid.appendChild(div);
    });
}

/* ══════════════════════════════════════
   MODAL RECLAMAR SUBASTA GANADA
══════════════════════════════════════ */
let claimData = null;

function openClaimModal(auctionId, cardName, cardImg, bid) {
    claimData = { auctionId, cardName, cardImg, bid };
    document.getElementById('claim-img').src = cardImg;
    document.getElementById('claim-card-name').textContent = cardName;
    document.getElementById('claim-overlay').classList.add('open');
    document.getElementById('claim-address').focus();
}
function closeClaim() {
    document.getElementById('claim-overlay').classList.remove('open');
    claimData = null;
}
async function confirmClaim() {
    if (!claimData) return;
    const address = document.getElementById('claim-address').value.trim();
    if (!address) {
        toast('Introduce tu dirección de envío', 'error'); return;
    }
    const fd = new FormData();
    fd.append('action',     'claim_win');
    fd.append('auction_id', claimData.auctionId);
    fd.append('choice',     'delivery');
    fd.append('address',    address);

    const res  = await fetch('../api/auction.php', { method: 'POST', body: fd });
    const data = await res.json();

    if (data.ok) {
        closeClaim();
        toast(data.message, 'success');
        if (data.new_balance !== null) {
            currentBalance = data.new_balance;
            const bd = document.getElementById('balance-display');
            if (bd) bd.textContent = currentBalance.toLocaleString('es-ES');
        }
        loadMyWins();
    } else {
        toast(data.message || 'Error', 'error');
    }
}
</script>
<script src="../assets/js/csrf.js?v=<?php echo time(); ?>"></script>
</body>
</html>
