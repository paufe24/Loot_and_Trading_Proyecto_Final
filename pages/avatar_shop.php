<?php
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: auth.php'); exit; }
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/gamification.php';
runGamificationMigrations($conn);

$stmt = $conn->prepare("SELECT name, username, lootcoins, avatar_url, xp FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

$levelInfo = getLevelInfo((int)($user['xp'] ?? 0));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Loot&Trading | Tienda de Avatares</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/styles.css?v=<?php echo time(); ?>">
    <style>
        /* ── Layout (same as lujanitos.php) ── */
        .av-page {
            max-width: 900px;
            margin: 0 auto;
            padding: 100px 20px 80px;
        }

        /* ── Hero (centered, like lujanitos) ── */
        .av-hero { text-align: center; margin-bottom: 36px; }
        .av-hero-icon {
            font-size: 3.6rem;
            display: block;
            margin-bottom: 12px;
        }
        .av-hero h1 { font-size: 2.2rem; font-weight: 800; margin-bottom: 8px; }
        .av-hero p {
            color: var(--text-secondary);
            font-size: 1rem;
            max-width: 480px;
            margin: 0 auto;
        }

        /* ── Balance bar (same gradient pill as lujanitos) ── */
        .av-balance {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            background: linear-gradient(135deg, #8b5cf6 0%, #6d28d9 100%);
            color: #fff;
            border-radius: 20px;
            padding: 14px 28px;
            font-weight: 800;
            font-size: 1.05rem;
            margin: 0 auto 36px;
            max-width: 320px;
            box-shadow: 0 8px 24px rgba(139,92,246,.35);
        }
        .av-balance span { font-size: 1.4rem; }

        /* ── Filter pills ── */
        .av-filters {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 24px;
            flex-wrap: wrap;
        }
        .av-filter {
            padding: 8px 18px;
            border-radius: 50px;
            border: 2px solid var(--border-color, #e2e8f0);
            background: transparent;
            font-family: 'Outfit', sans-serif;
            font-weight: 700;
            font-size: .82rem;
            cursor: pointer;
            color: var(--text-secondary, #64748b);
            transition: all .2s;
        }
        .av-filter:hover { border-color: #8b5cf6; color: #8b5cf6; }
        .av-filter.active { background: #8b5cf6; color: #fff; border-color: #8b5cf6; }
        body.dark .av-filter { border-color: #334155; color: #94a3b8; }
        body.dark .av-filter.active { background: #8b5cf6; color: #fff; border-color: #8b5cf6; }
        .av-count {
            margin-left: auto;
            font-size: .82rem;
            font-weight: 600;
            color: var(--text-secondary);
        }

        /* ── Grid ── */
        .av-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-bottom: 48px;
        }
        @media (min-width: 600px) {
            .av-grid { grid-template-columns: repeat(3, 1fr); }
        }
        @media (min-width: 820px) {
            .av-grid { grid-template-columns: repeat(4, 1fr); }
        }

        /* ── Avatar card (same style as lj-pkg) ── */
        .av-card {
            background: var(--bg-card, #fff);
            border: 2px solid var(--border-color, #e2e8f0);
            border-radius: 24px;
            padding: 24px 16px 18px;
            text-align: center;
            cursor: pointer;
            transition: all .2s;
            position: relative;
            overflow: hidden;
        }
        .av-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 48px rgba(0,0,0,.12);
        }
        body.dark .av-card { background: #1e293b; border-color: #334155; }

        /* Rarity borders */
        .av-card[data-rarity="rare"]      { border-color: #3b82f6; }
        .av-card[data-rarity="epic"]       { border-color: #8b5cf6; }
        .av-card[data-rarity="legendary"]  { border-color: #f59e0b; box-shadow: 0 8px 28px rgba(245,158,11,.18); }
        .av-card.is-equipped { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,.2); }
        .av-card.is-owned    { border-color: #10b981; }

        /* Card badge (top-right) */
        .av-badge {
            position: absolute;
            top: 12px;
            right: 12px;
            font-size: .65rem;
            font-weight: 800;
            padding: 2px 8px;
            border-radius: 20px;
            text-transform: uppercase;
            letter-spacing: .5px;
            color: #fff;
        }
        .av-badge.badge-equipped { background: #3b82f6; }
        .av-badge.badge-owned    { background: #10b981; }

        /* Card avatar image */
        .av-card-img {
            width: 72px;
            height: 72px;
            border-radius: 50%;
            object-fit: cover;
            margin: 0 auto 12px;
            display: block;
        }

        /* Card text */
        .av-card-name {
            font-size: .92rem;
            font-weight: 800;
            margin-bottom: 4px;
        }
        .av-rarity {
            display: inline-block;
            font-size: .68rem;
            font-weight: 800;
            padding: 2px 10px;
            border-radius: 20px;
            text-transform: uppercase;
            letter-spacing: .5px;
            margin-bottom: 12px;
        }
        .av-rarity.common    { background: rgba(100,116,139,.1); color: #64748b; }
        .av-rarity.rare      { background: rgba(59,130,246,.1);  color: #3b82f6; }
        .av-rarity.epic      { background: rgba(139,92,246,.1);  color: #8b5cf6; }
        .av-rarity.legendary { background: rgba(245,158,11,.1);  color: #d97706; }
        body.dark .av-rarity.common { background: rgba(148,163,184,.1); color: #94a3b8; }

        .av-card-price {
            font-size: .85rem;
            font-weight: 800;
            color: #d97706;
            margin-bottom: 14px;
        }
        .av-card-price.is-free { color: #10b981; }

        /* Card button (same as btn-buy) */
        .av-btn {
            width: 100%;
            padding: 11px;
            border-radius: 14px;
            border: none;
            font-weight: 800;
            font-size: .85rem;
            cursor: pointer;
            font-family: 'Outfit', sans-serif;
            transition: background .15s, transform .15s;
        }
        .av-btn.buy        { background: #f59e0b; color: #fff; }
        .av-btn.buy:hover   { background: #d97706; }
        .av-btn.claim       { background: #10b981; color: #fff; }
        .av-btn.claim:hover  { background: #059669; }
        .av-btn.equip       { background: #3b82f6; color: #fff; }
        .av-btn.equip:hover  { background: #2563eb; }
        .av-btn.unequip {
            background: transparent;
            border: 2px solid var(--border-color, #e2e8f0);
            color: var(--text-secondary, #64748b);
        }
        .av-btn.unequip:hover { border-color: #ef4444; color: #ef4444; }

        /* ── Info section (same as lj-info) ── */
        .av-info {
            background: var(--bg-card, #fff);
            border: 1px solid var(--border-color, #e2e8f0);
            border-radius: 20px;
            padding: 28px 32px;
        }
        body.dark .av-info { background: #1e293b; border-color: #334155; }
        .av-info h3 { font-size: 1.05rem; font-weight: 800; margin-bottom: 16px; }
        .av-info-row {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            margin-bottom: 14px;
            font-size: .9rem;
            color: var(--text-secondary);
            font-weight: 600;
        }
        .av-info-row .av-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #8b5cf6;
            margin-top: 6px;
            flex-shrink: 0;
        }

        /* ── Toast (same pattern as lujanitos) ── */
        .av-toast {
            position: fixed;
            bottom: 28px;
            left: 50%;
            transform: translateX(-50%) translateY(80px);
            background: #0f172a;
            color: #fff;
            padding: 14px 28px;
            border-radius: 50px;
            font-weight: 700;
            font-size: .9rem;
            z-index: 9999;
            transition: transform .3s cubic-bezier(.34,1.56,.64,1);
            white-space: nowrap;
        }
        .av-toast.show { transform: translateX(-50%) translateY(0); }

        /* ── Modal ── */
        .av-modal-bg {
            position: fixed; inset: 0;
            background: rgba(15,23,42,.55);
            backdrop-filter: blur(6px);
            z-index: 5000;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .av-modal-bg.open { display: flex; }
        .av-modal {
            background: var(--bg-card, #fff);
            border-radius: 24px;
            padding: 32px;
            max-width: 360px;
            width: 100%;
            box-shadow: 0 24px 64px rgba(0,0,0,.2);
            text-align: center;
        }
        body.dark .av-modal { background: #1e293b; }
        .av-modal img { width: 90px; height: 90px; border-radius: 50%; margin-bottom: 16px; }
        .av-modal h3 { font-size: 1.15rem; font-weight: 800; margin: 0 0 4px; }
        .av-modal .modal-price { font-size: 1.05rem; font-weight: 800; color: #d97706; margin-bottom: 20px; }
        .av-modal-btns { display: flex; gap: 10px; }
        .av-modal-btns .av-btn { flex: 1; }
        .av-btn.cancel { background: #f1f5f9; color: #64748b; }
        body.dark .av-btn.cancel { background: #334155; color: #94a3b8; }

        /* ── Animations ── */
        @keyframes avPop { 0% { transform: scale(1); } 50% { transform: scale(1.06); } 100% { transform: scale(1); } }
        .av-pop { animation: avPop .4s ease; }

        /* ── Empty state ── */
        .av-empty {
            grid-column: 1 / -1;
            text-align: center;
            padding: 48px 20px;
            color: var(--text-secondary);
            font-size: .92rem;
            font-weight: 600;
        }
        .av-empty .big-icon { font-size: 2.4rem; margin-bottom: 8px; }
    </style>
</head>
<body>
    <script>(function(){ if(localStorage.getItem('theme')==='dark') document.body.classList.add('dark'); })();</script>

    <?php include dirname(__DIR__) . '/includes/navbar.php'; ?>

    <div class="av-page">
        <!-- Hero -->
        <div class="av-hero">
            <span class="av-hero-icon">🎭</span>
            <h1 data-i18n="av.title">Tienda de Avatares</h1>
            <p data-i18n="av.desc">Personaliza tu perfil con avatares únicos. Compra con Lujanitos y equípalos al instante.</p>
        </div>

        <!-- Balance -->
        <div class="av-balance">
            <span>💰</span>
            Tu saldo: <strong id="balance-amount"><?php echo number_format((int)$user['lootcoins']); ?></strong> LJ
        </div>

        <!-- Filters -->
        <div class="av-filters">
            <button class="av-filter active" data-filter="all" data-i18n="av.all">Todos</button>
            <button class="av-filter" data-filter="basico" data-i18n="av.free">Gratis</button>
            <button class="av-filter" data-filter="fantasia" data-i18n="av.fantasy">Fantasía</button>
            <button class="av-filter" data-filter="aventura" data-i18n="av.adventure">Aventura</button>
            <button class="av-filter" data-filter="tech">Tech</button>
            <button class="av-filter" data-filter="legendario" data-i18n="av.legendary">Legendario</button>
            <span class="av-count"><span id="count-owned">0</span> / <span id="count-total">0</span> <span data-i18n="av.unlocked">desbloqueados</span></span>
        </div>

        <!-- Grid -->
        <div class="av-grid" id="avatars-grid">
            <div class="av-empty">
                <div class="big-icon">⏳</div>
                <span data-i18n="av.loading">Cargando avatares...</span>
            </div>
        </div>

        <!-- Info -->
        <div class="av-info">
            <h3 data-i18n="av.how_it_works">¿Cómo funciona?</h3>
            <div class="av-info-row"><span class="av-dot"></span><span data-i18n-html="av.info1">Compra avatares con tus <strong>Lujanitos</strong> o reclama los gratuitos.</span></div>
            <div class="av-info-row"><span class="av-dot"></span><span data-i18n-html="av.info2">Haz clic en <strong>Equipar</strong> para usarlo como foto de perfil al instante.</span></div>
            <div class="av-info-row"><span class="av-dot"></span><span data-i18n-html="av.info3">Tu avatar se mostrará en tu perfil, rankings, amigos y subastas.</span></div>
            <div class="av-info-row"><span class="av-dot"></span><span data-i18n-html="av.info4">Puedes subir también una foto personalizada desde <strong>Mi Perfil</strong>.</span></div>
            <div class="av-info-row"><span class="av-dot"></span><span data-i18n-html="av.info5">Los avatares legendarios tienen efectos exclusivos. ¡Collecciónalos todos!</span></div>
        </div>
    </div>

    <!-- Modal -->
    <div class="av-modal-bg" id="shop-modal" onclick="if(event.target===this)closeModal()">
        <div class="av-modal">
            <img id="modal-avatar" src="" alt="">
            <h3 id="modal-name"></h3>
            <div class="modal-price" id="modal-price"></div>
            <div class="av-modal-btns">
                <button class="av-btn cancel" onclick="closeModal()">Cancelar</button>
                <button class="av-btn buy" id="modal-confirm" onclick="confirmPurchase()">Comprar</button>
            </div>
        </div>
    </div>

    <!-- Toast -->
    <div class="av-toast" id="av-toast"></div>

    <?php include dirname(__DIR__) . '/includes/footer.php'; ?>

    <script src="../assets/js/csrf.js?v=<?php echo time(); ?>"></script>
    <script>
const CSRF = document.querySelector('meta[name="csrf-token"]')?.content || '';
let allAvatars = [];
let currentFilter = 'all';
let pendingBuyId = null;

// ── Avatar SVG generator (client-side mirror of PHP) ──
const AVATAR_DEFS = {
    explorer:       { c1:'#3b82f6', c2:'#1d4ed8', icon:'🧭' },
    rookie:         { c1:'#10b981', c2:'#059669', icon:'🌱' },
    traveler:       { c1:'#8b5cf6', c2:'#6d28d9', icon:'🗺️' },
    warrior:        { c1:'#ef4444', c2:'#b91c1c', icon:'⚔️' },
    dark_mage:      { c1:'#7c3aed', c2:'#4c1d95', icon:'🔮' },
    archer:         { c1:'#059669', c2:'#065f46', icon:'🏹' },
    pirate:         { c1:'#d97706', c2:'#92400e', icon:'🏴‍☠️' },
    astronaut:      { c1:'#0ea5e9', c2:'#0369a1', icon:'🚀' },
    golden_dragon:  { c1:'#f59e0b', c2:'#d97706', icon:'🐉' },
    shadow_ninja:   { c1:'#334155', c2:'#0f172a', icon:'🥷' },
    phoenix:        { c1:'#f97316', c2:'#ea580c', icon:'🔥' },
    samurai:        { c1:'#dc2626', c2:'#7f1d1d', icon:'⛩️' },
    hacker:         { c1:'#22c55e', c2:'#15803d', icon:'💻' },
    celestial_king: { c1:'#eab308', c2:'#a16207', icon:'👑' },
    valkyrie:       { c1:'#ec4899', c2:'#be185d', icon:'🦋' },
    cyborg:         { c1:'#64748b', c2:'#334155', icon:'🤖' },
    fox_spirit:     { c1:'#f97316', c2:'#9a3412', icon:'🦊' },
    deck_god:       { c1:'#fbbf24', c2:'#b45309', icon:'🃏' },
    cosmic_phoenix: { c1:'#a855f7', c2:'#581c87', icon:'🌌' },
    ancient_titan:  { c1:'#78716c', c2:'#292524', icon:'🗿' },
};

function avatarSvgUrl(key) {
    const d = AVATAR_DEFS[key];
    if (!d) return '';
    const svg = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 200"><defs><linearGradient id="g" x1="0%" y1="0%" x2="100%" y2="100%"><stop offset="0%" style="stop-color:${d.c1}"/><stop offset="100%" style="stop-color:${d.c2}"/></linearGradient></defs><rect width="200" height="200" rx="100" fill="url(#g)"/><text x="100" y="115" text-anchor="middle" font-size="80">${d.icon}</text></svg>`;
    return 'data:image/svg+xml,' + encodeURIComponent(svg);
}

const RARITY_LABELS = { common:'Común', rare:'Raro', epic:'Épico', legendary:'Legendario' };

// ── Load avatars ──
async function loadAvatars() {
    try {
        const r = await fetch('../api/avatar_shop.php?action=list');
        const d = await r.json();
        if (!d.ok) { showToast(d.message || 'Error', true); return; }
        allAvatars = d.items;
        document.getElementById('balance-amount').textContent = d.lootcoins.toLocaleString();
        renderAvatars();
    } catch(e) { showToast('Error al cargar la tienda', true); }
}

function renderAvatars() {
    const grid = document.getElementById('avatars-grid');
    const filtered = currentFilter === 'all' ? allAvatars : allAvatars.filter(a => a.category === currentFilter);

    const owned = allAvatars.filter(a => a.owned).length;
    document.getElementById('count-owned').textContent = owned;
    document.getElementById('count-total').textContent = allAvatars.length;

    if (filtered.length === 0) {
        grid.innerHTML = '<div class="av-empty"><div class="big-icon">🔍</div>No hay avatares en esta categoría</div>';
        return;
    }

    grid.innerHTML = filtered.map(a => {
        const imgUrl = avatarSvgUrl(a.image_url);
        const isEquipped = a.equipped;
        const isOwned = a.owned;
        const isFree = a.price === 0;

        let badge = '';
        if (isEquipped) badge = '<span class="av-badge badge-equipped">✓ Equipado</span>';
        else if (isOwned) badge = '<span class="av-badge badge-owned">✓ Tuyo</span>';

        let priceHtml = '';
        if (!isOwned) {
            priceHtml = isFree
                ? '<div class="av-card-price is-free">Gratis</div>'
                : `<div class="av-card-price">💰 ${a.price.toLocaleString()} LJ</div>`;
        } else {
            priceHtml = '<div class="av-card-price" style="color:var(--text-secondary);font-size:.78rem">En tu colección</div>';
        }

        let btn = '';
        if (isEquipped) {
            btn = `<button class="av-btn unequip" onclick="event.stopPropagation();unequipAvatar()">Desequipar</button>`;
        } else if (isOwned) {
            btn = `<button class="av-btn equip" onclick="event.stopPropagation();equipAvatar(${a.id})">Equipar</button>`;
        } else if (isFree) {
            btn = `<button class="av-btn claim" onclick="event.stopPropagation();buyAvatar(${a.id})">Reclamar</button>`;
        } else {
            btn = `<button class="av-btn buy" onclick="event.stopPropagation();openBuyModal(${a.id})">Comprar</button>`;
        }

        let stateClass = '';
        if (isEquipped) stateClass = 'is-equipped';
        else if (isOwned) stateClass = 'is-owned';

        return `
        <div class="av-card ${stateClass}" data-rarity="${a.rarity}" id="acard-${a.id}">
            ${badge}
            <img class="av-card-img" src="${imgUrl}" alt="${a.name}">
            <div class="av-card-name">${a.name}</div>
            <span class="av-rarity ${a.rarity}">${RARITY_LABELS[a.rarity] || a.rarity}</span>
            ${priceHtml}
            ${btn}
        </div>`;
    }).join('');
}

// ── Filters ──
document.querySelectorAll('.av-filter').forEach(btn => {
    btn.addEventListener('click', () => {
        document.querySelectorAll('.av-filter').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        currentFilter = btn.dataset.filter;
        renderAvatars();
    });
});

// ── Buy flow ──
function openBuyModal(id) {
    const a = allAvatars.find(x => x.id === id);
    if (!a) return;
    pendingBuyId = id;
    document.getElementById('modal-avatar').src = avatarSvgUrl(a.image_url);
    document.getElementById('modal-name').textContent = a.name;
    document.getElementById('modal-price').textContent = '💰 ' + a.price.toLocaleString() + ' Lujanitos';
    document.getElementById('shop-modal').classList.add('open');
}

function closeModal() {
    document.getElementById('shop-modal').classList.remove('open');
    pendingBuyId = null;
}

async function confirmPurchase() {
    if (!pendingBuyId) return;
    await buyAvatar(pendingBuyId);
    closeModal();
}

async function buyAvatar(id) {
    try {
        const fd = new FormData();
        fd.append('action', 'buy');
        fd.append('avatar_id', id);
        fd.append('csrf_token', CSRF);
        const r = await fetch('../api/avatar_shop.php', { method:'POST', body:fd });
        const d = await r.json();
        if (d.ok) {
            showToast(d.message);
            if (d.lootcoins !== undefined) document.getElementById('balance-amount').textContent = d.lootcoins.toLocaleString();
            // Update local state
            const av = allAvatars.find(x => x.id === id);
            if (av) av.owned = true;
            renderAvatars();
            const card = document.getElementById('acard-' + id);
            if (card) { card.classList.add('av-pop'); setTimeout(() => card.classList.remove('av-pop'), 500); }
        } else {
            showToast(d.message || 'Error', true);
        }
    } catch(e) { showToast('Error de red', true); }
}

async function equipAvatar(id) {
    try {
        const fd = new FormData();
        fd.append('action', 'equip');
        fd.append('avatar_id', id);
        fd.append('csrf_token', CSRF);
        const r = await fetch('../api/avatar_shop.php', { method:'POST', body:fd });
        const d = await r.json();
        if (d.ok) {
            showToast(d.message);
            // Update local state
            allAvatars.forEach(a => a.equipped = false);
            const av = allAvatars.find(x => x.id === id);
            if (av) av.equipped = true;
            renderAvatars();
        } else {
            showToast(d.message || 'Error', true);
        }
    } catch(e) { showToast('Error de red', true); }
}

async function unequipAvatar() {
    try {
        const fd = new FormData();
        fd.append('action', 'unequip');
        fd.append('csrf_token', CSRF);
        const r = await fetch('../api/avatar_shop.php', { method:'POST', body:fd });
        const d = await r.json();
        if (d.ok) {
            showToast(d.message);
            allAvatars.forEach(a => a.equipped = false);
            renderAvatars();
        } else {
            showToast(d.message || 'Error', true);
        }
    } catch(e) { showToast('Error de red', true); }
}

// ── Toast ──
function showToast(msg, isError = false) {
    const t = document.getElementById('av-toast');
    t.textContent = (isError ? '❌ ' : '✅ ') + msg;
    t.style.background = isError ? '#ef4444' : '#0f172a';
    t.classList.add('show');
    setTimeout(() => t.classList.remove('show'), 3200);
}

// ── Init ──
loadAvatars();
</script>
</body>
</html>
