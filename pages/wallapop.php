<?php
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: auth.php'); exit; }
require_once dirname(__DIR__) . '/includes/db.php';
$uid      = (int)$_SESSION['user_id'];
$username = $_SESSION['username'] ?? '';

// Saldo actual del usuario
$r    = $conn->query("SELECT lootcoins FROM users WHERE id=$uid");
$me   = $r->fetch_assoc();
$coins = number_format((float)($me['lootcoins'] ?? 0), 2);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wallapop Cartas | Loot&Trading</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/styles.css?v=<?php echo time(); ?>">
    <style>
        .wp-wrap { max-width: 1200px; margin: 0 auto; padding: 20px 20px 80px; }

        /* ─── Header ─── */
        .wp-header {
            display: flex; align-items: center; justify-content: space-between;
            margin-bottom: 24px; gap: 12px; flex-wrap: wrap;
        }
        .wp-title { font-size: 1.6rem; font-weight: 800; }
        .wp-title span { color: var(--accent-blue); }
        .wp-coins {
            font-size: .85rem; font-weight: 700; background: #fef3c7; color: #92400e;
            border-radius: 50px; padding: 6px 14px; display: flex; align-items: center; gap: 6px;
        }
        body.dark .wp-coins { background: #422006; color: #fcd34d; }

        /* ─── Tabs ─── */
        .wp-tabs { display: flex; gap: 8px; margin-bottom: 20px; }
        .wp-tab {
            padding: 10px 22px; border-radius: 50px; font-weight: 700; font-size: .88rem;
            border: 2px solid var(--border-color); cursor: pointer; background: transparent;
            color: var(--text-secondary); transition: all .2s; font-family: 'Outfit',sans-serif;
        }
        .wp-tab.active { background: var(--accent-blue); color: #fff; border-color: var(--accent-blue); }
        .wp-tab:hover:not(.active) { border-color: var(--accent-blue); color: var(--accent-blue); }

        /* ─── Filtros ─── */
        .wp-filters {
            display: flex; gap: 10px; margin-bottom: 24px; flex-wrap: wrap; align-items: center;
        }
        .wp-search {
            flex: 1; min-width: 180px; padding: 12px 18px; border-radius: 50px;
            border: 2px solid var(--border-color); font-family: 'Outfit',sans-serif;
            font-size: .9rem; background: #f8f9fd; color: var(--text-primary); transition: border-color .2s;
        }
        body.dark .wp-search { background: #0f172a; border-color: #334155; color: #e2e8f0; }
        .wp-search:focus { outline: none; border-color: var(--accent-blue); }
        .wp-game-filter {
            padding: 12px 16px; border-radius: 50px; border: 2px solid var(--border-color);
            font-family: 'Outfit',sans-serif; font-size: .85rem;
            background: #f8f9fd; color: var(--text-primary); cursor: pointer;
        }
        body.dark .wp-game-filter { background: #0f172a; border-color: #334155; color: #e2e8f0; }
        .btn-publish {
            padding: 12px 24px; border-radius: 50px; background: var(--accent-blue);
            color: #fff; border: none; font-weight: 800; font-size: .9rem;
            cursor: pointer; transition: all .2s; white-space: nowrap; font-family: 'Outfit',sans-serif;
        }
        .btn-publish:hover { background: #2563eb; transform: translateY(-1px); }

        /* ─── Grid de listados ─── */
        .wp-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(210px, 1fr));
            gap: 18px;
        }
        .wp-card {
            background: #fff; border: 1.5px solid var(--border-color); border-radius: 18px;
            overflow: hidden; transition: all .22s; cursor: pointer; display: flex; flex-direction: column;
        }
        body.dark .wp-card { background: #1e293b; border-color: #334155; }
        .wp-card:hover { transform: translateY(-4px); box-shadow: 0 12px 32px rgba(0,0,0,.1); }
        .wp-card-img {
            aspect-ratio: 2/3; background: #f1f5f9; overflow: hidden; flex-shrink: 0;
            display: flex; align-items: center; justify-content: center;
        }
        body.dark .wp-card-img { background: #0f172a; }
        .wp-card-img img { width: 100%; height: 100%; object-fit: cover; }
        .wp-card-img .no-img { font-size: 3.5rem; }
        .wp-card-body { padding: 12px 14px; flex: 1; display: flex; flex-direction: column; gap: 4px; }
        .wp-card-name { font-weight: 800; font-size: .9rem; line-height: 1.2; }
        .wp-card-game { font-size: .75rem; color: var(--text-secondary); font-weight: 600; text-transform: uppercase; letter-spacing: .04em; }
        .wp-card-price { font-size: 1.05rem; font-weight: 800; color: var(--accent-blue); margin-top: auto; padding-top: 8px; }
        .wp-card-seller { font-size: .78rem; color: var(--text-secondary); margin-top: 2px; }
        .wp-card-badge {
            display: inline-block; font-size: .65rem; font-weight: 800; padding: 2px 8px;
            border-radius: 20px; margin-top: 4px;
        }
        .badge-physical { background: #fef3c7; color: #92400e; }
        .badge-digital   { background: #dbeafe; color: #1e40af; }
        body.dark .badge-physical { background: #422006; color: #fcd34d; }
        body.dark .badge-digital  { background: #1e3a5f; color: #93c5fd; }

        /* ─── Mis anuncios ─── */
        .my-listing-card {
            display: flex; align-items: center; gap: 14px;
            background: #fff; border: 1.5px solid var(--border-color);
            border-radius: 16px; padding: 14px 18px; margin-bottom: 12px;
        }
        body.dark .my-listing-card { background: #1e293b; border-color: #334155; }
        .my-listing-img {
            width: 56px; height: 80px; border-radius: 10px; object-fit: cover;
            background: #f1f5f9; flex-shrink: 0; display: flex; align-items: center; justify-content: center;
            overflow: hidden;
        }
        .my-listing-img img { width: 100%; height: 100%; object-fit: cover; }
        .my-listing-info { flex: 1; min-width: 0; }
        .my-listing-name { font-weight: 800; font-size: .92rem; }
        .my-listing-meta { font-size: .8rem; color: var(--text-secondary); margin-top: 2px; }
        .my-listing-price { font-weight: 800; color: var(--accent-blue); }
        .status-active   { color: #10b981; }
        .status-sold     { color: var(--text-secondary); }
        .status-cancelled{ color: #ef4444; }
        .btn-cancel-listing {
            padding: 7px 16px; border-radius: 50px; background: #f1f5f9;
            color: var(--text-secondary); border: 2px solid var(--border-color);
            font-weight: 700; font-size: .8rem; cursor: pointer; transition: all .2s;
            font-family: 'Outfit',sans-serif;
        }
        .btn-cancel-listing:hover { background: #fee2e2; color: #dc2626; border-color: #fca5a5; }

        /* ─── Modales ─── */
        .wp-modal-overlay {
            position: fixed; inset: 0; background: rgba(15,23,42,.78);
            backdrop-filter: blur(10px); z-index: 4000;
            display: none; align-items: center; justify-content: center; padding: 20px;
        }
        .wp-modal-overlay.open { display: flex; }
        .wp-modal-box {
            background: #fff; border-radius: 24px; width: 100%;
            max-height: 90vh; overflow-y: auto;
            box-shadow: 0 30px 70px rgba(0,0,0,.3);
        }
        body.dark .wp-modal-box { background: #1e293b; }

        /* Modal detalle */
        .detail-modal-box { max-width: 560px; }
        .detail-img {
            width: 100%; max-height: 380px; object-fit: contain;
            border-radius: 18px 18px 0 0; background: #f1f5f9;
        }
        body.dark .detail-img-wrap { background: #0f172a; }
        .detail-img-wrap {
            background: #f1f5f9; border-radius: 18px 18px 0 0;
            display: flex; align-items: center; justify-content: center;
            min-height: 220px; font-size: 5rem;
        }
        .detail-body { padding: 24px 28px 28px; }
        .detail-title { font-size: 1.4rem; font-weight: 800; margin-bottom: 4px; }
        .detail-game  { font-size: .82rem; color: var(--text-secondary); font-weight: 700; text-transform: uppercase; letter-spacing: .05em; margin-bottom: 12px; }
        .detail-desc  { font-size: .9rem; color: var(--text-secondary); margin-bottom: 16px; line-height: 1.55; }
        .detail-seller { display: flex; align-items: center; gap: 10px; margin-bottom: 20px; }
        .detail-seller-av {
            width: 38px; height: 38px; border-radius: 50%; background: linear-gradient(135deg,#667eea,#764ba2);
            display: flex; align-items: center; justify-content: center; color: #fff; font-weight: 800; font-size: .95rem;
            overflow: hidden; flex-shrink: 0;
        }
        .detail-seller-av img { width: 100%; height: 100%; object-fit: cover; }
        .detail-price-row { display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; }
        .detail-price { font-size: 1.6rem; font-weight: 800; color: var(--accent-blue); }
        .btn-buy {
            padding: 14px 36px; border-radius: 50px; background: #10b981;
            color: #fff; border: none; font-weight: 800; font-size: 1rem;
            cursor: pointer; transition: all .2s; font-family: 'Outfit',sans-serif;
        }
        .btn-buy:hover { background: #059669; transform: translateY(-1px); }
        .btn-buy:disabled { opacity: .5; cursor: not-allowed; transform: none; }

        /* Modal publicar */
        .create-modal-box { max-width: 620px; }
        .create-modal-head {
            padding: 28px 28px 0; font-size: 1.3rem; font-weight: 800; margin-bottom: 20px;
        }
        .wp-form { padding: 0 28px 28px; display: flex; flex-direction: column; gap: 14px; }
        .wp-form label { font-size: .82rem; font-weight: 800; color: var(--text-secondary); margin-bottom: 4px; display: block; }
        .wp-form input, .wp-form textarea, .wp-form select {
            width: 100%; padding: 12px 16px; border-radius: 12px;
            border: 2px solid var(--border-color); font-family: 'Outfit',sans-serif;
            font-size: .9rem; background: #f8f9fd; color: var(--text-primary); transition: border-color .2s;
            box-sizing: border-box;
        }
        body.dark .wp-form input, body.dark .wp-form textarea, body.dark .wp-form select {
            background: #0f172a; border-color: #334155; color: #e2e8f0;
        }
        .wp-form input:focus, .wp-form textarea:focus, .wp-form select:focus {
            outline: none; border-color: var(--accent-blue);
        }
        .wp-form textarea { resize: vertical; min-height: 80px; }
        .wp-form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        .my-cards-picker { display: flex; gap: 8px; overflow-x: auto; padding: 8px 0; }
        .my-card-option {
            flex-shrink: 0; width: 70px; cursor: pointer; border-radius: 10px; overflow: hidden;
            border: 3px solid transparent; transition: border-color .2s;
        }
        .my-card-option.selected { border-color: var(--accent-blue); }
        .my-card-option img { width: 100%; aspect-ratio: 2/3; object-fit: cover; display: block; }
        .wp-img-preview {
            width: 90px; height: 130px; border-radius: 10px; overflow: hidden;
            background: #f1f5f9; display: flex; align-items: center; justify-content: center;
            font-size: 2rem;
        }
        body.dark .wp-img-preview { background: #0f172a; }
        .wp-img-preview img { width: 100%; height: 100%; object-fit: cover; }
        .btn-submit-listing {
            padding: 14px 28px; border-radius: 50px; background: var(--accent-blue);
            color: #fff; border: none; font-weight: 800; font-size: 1rem;
            cursor: pointer; transition: all .2s; font-family: 'Outfit',sans-serif; align-self: flex-end;
        }
        .btn-submit-listing:hover { background: #2563eb; }
        .btn-submit-listing:disabled { opacity: .5; cursor: not-allowed; }

        .wp-modal-close {
            position: absolute; top: 14px; right: 18px;
            background: none; border: none; font-size: 1.6rem; cursor: pointer;
            color: var(--text-secondary); line-height: 1; z-index: 2;
        }
        .empty-state { text-align: center; padding: 60px 20px; color: var(--text-secondary); }
        .empty-state .emoji { font-size: 3rem; margin-bottom: 12px; }
        .loading-state { text-align: center; padding: 40px; color: var(--text-secondary); }
    </style>
</head>
<body>
<script>(function(){ if(localStorage.getItem('theme')==='dark') document.body.classList.add('dark'); })();</script>
<?php include dirname(__DIR__) . '/includes/navbar.php'; ?>

<div class="wp-wrap">
    <!-- Header -->
    <div class="wp-header">
        <div>
            <div class="wp-title">🏷️ Wallapop <span>Cartas</span></div>
            <div style="font-size:.85rem;color:var(--text-secondary);margin-top:4px;">Compra y vende cartas con otros jugadores</div>
        </div>
        <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
            <div class="wp-coins">🪙 <?php echo $coins; ?> LootCoins</div>
            <button class="btn-publish" onclick="openCreateModal()">+ Publicar anuncio</button>
        </div>
    </div>

    <!-- Tabs -->
    <div class="wp-tabs">
        <button class="wp-tab active" onclick="switchTab('browse')">Explorar</button>
        <button class="wp-tab" onclick="switchTab('mine')">Mis anuncios</button>
    </div>

    <!-- Tab: Explorar -->
    <div id="tab-browse">
        <div class="wp-filters">
            <input type="text" class="wp-search" id="search-input" placeholder="Buscar carta..." oninput="debouncedLoad()">
            <select class="wp-game-filter" id="game-filter" onchange="loadListings()">
                <option value="">Todos los juegos</option>
                <option value="pokemon">Pokémon</option>
                <option value="yugioh">Yu-Gi-Oh!</option>
                <option value="magic">Magic</option>
                <option value="onepiece">One Piece</option>
            </select>
        </div>
        <div id="listings-grid" class="wp-grid">
            <div class="loading-state" style="grid-column:1/-1">Cargando anuncios...</div>
        </div>
    </div>

    <!-- Tab: Mis anuncios -->
    <div id="tab-mine" style="display:none;">
        <div id="my-listings-list">
            <div class="loading-state">Cargando...</div>
        </div>
    </div>
</div>

<!-- Modal: detalle de anuncio -->
<div class="wp-modal-overlay" id="detail-modal" onclick="if(event.target===this)closeDetail()">
    <div class="wp-modal-box detail-modal-box" style="position:relative;" id="detail-box">
        <button class="wp-modal-close" onclick="closeDetail()">×</button>
        <div id="detail-content"></div>
    </div>
</div>

<!-- Modal: publicar anuncio -->
<div class="wp-modal-overlay" id="create-modal" onclick="if(event.target===this)closeCreateModal()">
    <div class="wp-modal-box create-modal-box" style="position:relative;">
        <button class="wp-modal-close" onclick="closeCreateModal()">×</button>
        <div class="create-modal-head">📦 Publicar anuncio</div>
        <form class="wp-form" id="create-form" onsubmit="submitListing(event)">
            <!-- Cartas compradas (picker) -->
            <div>
                <label>Selecciona de tus cartas (opcional)</label>
                <div class="my-cards-picker" id="my-cards-picker">
                    <div style="color:var(--text-secondary);font-size:.82rem;padding:4px 0;">Cargando...</div>
                </div>
            </div>
            <div style="border-top:1px solid var(--border-color);padding-top:14px;">
                <label>Nombre de la carta *</label>
                <input type="text" id="f-name" name="card_name" required placeholder="Ej: Charizard EX">
            </div>
            <div class="wp-form-row">
                <div>
                    <label>Juego</label>
                    <select id="f-game" name="card_game">
                        <option value="">Sin especificar</option>
                        <option value="pokemon">Pokémon</option>
                        <option value="yugioh">Yu-Gi-Oh!</option>
                        <option value="magic">Magic</option>
                        <option value="onepiece">One Piece</option>
                    </select>
                </div>
                <div>
                    <label>Tipo</label>
                    <select id="f-type" name="listing_type">
                        <option value="physical">Física (foto)</option>
                        <option value="digital">Digital</option>
                    </select>
                </div>
            </div>
            <div>
                <label>Set / Expansión</label>
                <input type="text" id="f-set" name="card_set" placeholder="Ej: Base Set, Legendary Collection...">
            </div>
            <div>
                <label>Precio (LootCoins) *</label>
                <input type="number" id="f-price" name="price" min="1" step="0.01" required placeholder="0.00">
            </div>
            <div>
                <label>Descripción</label>
                <textarea id="f-desc" name="description" placeholder="Estado, condiciones, detalles del envío..."></textarea>
            </div>
            <div>
                <label>Foto de la carta</label>
                <div style="display:flex;align-items:center;gap:12px;">
                    <div class="wp-img-preview" id="img-preview">📷</div>
                    <div>
                        <input type="file" id="f-img" name="image" accept="image/*" onchange="previewImg(this)" style="font-size:.82rem;">
                        <div style="font-size:.75rem;color:var(--text-secondary);margin-top:4px;">JPG, PNG, WEBP — máx 5MB</div>
                        <input type="hidden" id="f-imgurl" name="image_url">
                    </div>
                </div>
            </div>
            <button type="submit" class="btn-submit-listing" id="submit-btn">Publicar anuncio</button>
        </form>
    </div>
</div>

<div class="toast-container" id="toast-container"></div>

<script>
const MY_ID = <?php echo $uid; ?>;
const GAME_LABELS = {pokemon:'Pokémon',yugioh:'Yu-Gi-Oh!',magic:'Magic',onepiece:'One Piece','':`Varios`};
const GAME_IMGS = {
    pokemon:'https://images.pokemontcg.io/base1/4_hires.png',
    yugioh:'', magic:'', onepiece:''
};

function esc(str) {
    const d = document.createElement('div');
    d.appendChild(document.createTextNode(str ?? ''));
    return d.innerHTML;
}

function toast(msg, type='info') {
    const c = document.getElementById('toast-container');
    const t = document.createElement('div');
    t.className = `toast ${type}`;
    t.innerHTML = `<span>${{success:'✅',error:'❌',info:'ℹ️'}[type]||'ℹ️'}</span>${msg}`;
    c.appendChild(t);
    setTimeout(() => t.remove(), 3500);
}

/* ─── Tabs ─── */
function switchTab(tab) {
    document.getElementById('tab-browse').style.display = tab === 'browse' ? '' : 'none';
    document.getElementById('tab-mine').style.display   = tab === 'mine'   ? '' : 'none';
    document.querySelectorAll('.wp-tab').forEach((b,i) => b.classList.toggle('active', (i===0&&tab==='browse')||(i===1&&tab==='mine')));
    if (tab === 'mine') loadMyListings();
    else loadListings();
}

/* ─── Cargar anuncios ─── */
let searchTimer;
function debouncedLoad() { clearTimeout(searchTimer); searchTimer = setTimeout(loadListings, 350); }

async function loadListings() {
    const q    = document.getElementById('search-input').value.trim();
    const game = document.getElementById('game-filter').value;
    let url = `../api/wallapop.php?action=list`;
    if (game) url += `&game=${encodeURIComponent(game)}`;
    if (q)    url += `&q=${encodeURIComponent(q)}`;
    document.getElementById('listings-grid').innerHTML = '<div class="loading-state" style="grid-column:1/-1">Cargando...</div>';
    const res  = await fetch(url);
    const data = await res.json();
    renderListings(data.listings || []);
}

function renderListings(listings) {
    const grid = document.getElementById('listings-grid');
    if (!listings.length) {
        grid.innerHTML = `<div class="empty-state" style="grid-column:1/-1">
            <div class="emoji">🃏</div>
            <div>No hay anuncios disponibles</div>
            <div style="font-size:.82rem;margin-top:6px;">¡Sé el primero en publicar!</div>
        </div>`;
        return;
    }
    grid.innerHTML = '';
    listings.forEach(l => {
        const card = document.createElement('div');
        card.className = 'wp-card';
        card.onclick = () => openDetail(l);
        const gameLabel = GAME_LABELS[l.card_game] || l.card_game;
        const imgHtml = l.image_url
            ? `<img src="${esc(l.image_url)}" alt="${esc(l.card_name)}" onerror="this.parentElement.innerHTML='<span class=\\'no-img\\'>🃏</span>'">`
            : `<span class="no-img">🃏</span>`;
        const badgeClass = l.listing_type === 'digital' ? 'badge-digital' : 'badge-physical';
        const badgeText  = l.listing_type === 'digital' ? 'Digital' : 'Física';
        card.innerHTML = `
            <div class="wp-card-img">${imgHtml}</div>
            <div class="wp-card-body">
                <div class="wp-card-name">${esc(l.card_name)}</div>
                <div class="wp-card-game">${esc(gameLabel)}</div>
                <span class="wp-card-badge ${badgeClass}">${badgeText}</span>
                <div class="wp-card-price">🪙 ${parseFloat(l.price).toFixed(2)}</div>
                <div class="wp-card-seller">@${esc(l.seller_name)}</div>
            </div>`;
        grid.appendChild(card);
    });
}

/* ─── Mis anuncios ─── */
async function loadMyListings() {
    document.getElementById('my-listings-list').innerHTML = '<div class="loading-state">Cargando...</div>';
    const res  = await fetch('../api/wallapop.php?action=my_listings');
    const data = await res.json();
    const list = document.getElementById('my-listings-list');
    if (!data.listings || !data.listings.length) {
        list.innerHTML = `<div class="empty-state"><div class="emoji">📦</div><div>No tienes anuncios publicados</div></div>`;
        return;
    }
    list.innerHTML = '';
    data.listings.forEach(l => {
        const stClass = {active:'status-active',sold:'status-sold',cancelled:'status-cancelled'}[l.status] || '';
        const stLabel = {active:'Activo',sold:`Vendido a @${esc(l.buyer_name||'?')}`,cancelled:'Cancelado'}[l.status] || l.status;
        const imgHtml = l.image_url ? `<img src="${esc(l.image_url)}" alt="">` : `<span style="font-size:1.8rem">🃏</span>`;
        const div = document.createElement('div');
        div.className = 'my-listing-card';
        div.innerHTML = `
            <div class="my-listing-img">${imgHtml}</div>
            <div class="my-listing-info">
                <div class="my-listing-name">${esc(l.card_name)}</div>
                <div class="my-listing-meta">${esc(GAME_LABELS[l.card_game]||l.card_game)} · <span class="${stClass}">${stLabel}</span></div>
                <div class="my-listing-price">🪙 ${parseFloat(l.price).toFixed(2)}</div>
            </div>
            ${l.status === 'active' ? `<button class="btn-cancel-listing" onclick="cancelListing(${l.id}, this)">Retirar</button>` : ''}`;
        list.appendChild(div);
    });
}

async function cancelListing(id, btn) {
    if (!confirm('¿Retirar este anuncio?')) return;
    btn.disabled = true;
    const fd = new FormData();
    fd.append('action','cancel'); fd.append('listing_id', id);
    await fetch('../api/wallapop.php', { method:'POST', body:fd });
    toast('Anuncio retirado', 'info');
    loadMyListings();
}

/* ─── Modal detalle ─── */
let currentListing = null;
function openDetail(listing) {
    currentListing = listing;
    const box = document.getElementById('detail-content');
    const gameLabel = GAME_LABELS[listing.card_game] || listing.card_game;
    const imgSection = listing.image_url
        ? `<img class="detail-img" src="${listing.image_url}" alt="${listing.card_name}">`
        : `<div class="detail-img-wrap"><span style="font-size:5rem">🃏</span></div>`;
    const avHtml = listing.seller_avatar
        ? `<img src="${listing.seller_avatar}" alt="">`
        : (listing.seller_name||'?').charAt(0).toUpperCase();
    const badgeClass = listing.listing_type === 'digital' ? 'badge-digital' : 'badge-physical';
    const badgeText  = listing.listing_type === 'digital' ? 'Digital' : 'Física';
    box.innerHTML = `
        ${imgSection}
        <div class="detail-body">
            <div style="margin-bottom:6px;"><span class="wp-card-badge ${badgeClass}">${badgeText}</span></div>
            <div class="detail-title">${listing.card_name}</div>
            <div class="detail-game">${gameLabel}${listing.card_set ? ' · ' + listing.card_set : ''}</div>
            ${listing.description ? `<div class="detail-desc">${listing.description}</div>` : ''}
            <div class="detail-seller">
                <div class="detail-seller-av">${avHtml}</div>
                <div>
                    <div style="font-weight:700;font-size:.88rem;">@${listing.seller_name}</div>
                    <div style="font-size:.78rem;color:var(--text-secondary);">Vendedor</div>
                </div>
            </div>
            <div class="detail-price-row">
                <div class="detail-price">🪙 ${parseFloat(listing.price).toFixed(2)}</div>
                <button class="btn-buy" id="buy-btn" onclick="buyListing(${listing.id})">Comprar</button>
            </div>
        </div>`;
    document.getElementById('detail-modal').classList.add('open');
}

function closeDetail() {
    document.getElementById('detail-modal').classList.remove('open');
}

async function buyListing(listingId) {
    const btn = document.getElementById('buy-btn');
    btn.disabled = true; btn.textContent = 'Comprando...';
    const fd = new FormData();
    fd.append('action','buy'); fd.append('listing_id', listingId);
    const res  = await fetch('../api/wallapop.php', { method:'POST', body:fd });
    const data = await res.json();
    if (data.ok) {
        toast(data.message, 'success');
        closeDetail();
        loadListings();
    } else {
        toast(data.message || 'Error al comprar', 'error');
        btn.disabled = false; btn.textContent = 'Comprar';
    }
}

/* ─── Modal publicar ─── */
let myCards = [];
async function openCreateModal() {
    document.getElementById('create-modal').classList.add('open');
    if (!myCards.length) {
        const res  = await fetch('../api/wallapop.php?action=my_cards');
        const data = await res.json();
        myCards = data.cards || [];
        renderCardPicker();
    }
}

function renderCardPicker() {
    const picker = document.getElementById('my-cards-picker');
    if (!myCards.length) {
        picker.innerHTML = '<div style="color:var(--text-secondary);font-size:.82rem;">No tienes cartas compradas</div>';
        return;
    }
    picker.innerHTML = '';
    myCards.forEach((c, i) => {
        const div = document.createElement('div');
        div.className = 'my-card-option';
        div.title = c.card_name;
        div.onclick = () => selectCard(c, div);
        div.innerHTML = c.card_image
            ? `<img src="${c.card_image}" alt="${c.card_name}">`
            : `<div style="height:105px;display:flex;align-items:center;justify-content:center;background:#f1f5f9;font-size:1.5rem;">🃏</div>`;
        picker.appendChild(div);
    });
}

function selectCard(card, el) {
    document.querySelectorAll('.my-card-option').forEach(x => x.classList.remove('selected'));
    el.classList.add('selected');
    document.getElementById('f-name').value = card.card_name || '';
    document.getElementById('f-game').value = card.card_game || '';
    document.getElementById('f-price').value = card.card_price ? parseFloat(card.card_price).toFixed(2) : '';
    document.getElementById('f-imgurl').value = card.card_image || '';
    document.getElementById('f-type').value = 'digital';
    if (card.card_image) {
        document.getElementById('img-preview').innerHTML = `<img src="${card.card_image}" alt="">`;
    }
}

function previewImg(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => {
            document.getElementById('img-preview').innerHTML = `<img src="${e.target.result}" alt="">`;
        };
        reader.readAsDataURL(input.files[0]);
        document.getElementById('f-imgurl').value = '';
    }
}

function closeCreateModal() {
    document.getElementById('create-modal').classList.remove('open');
}

async function submitListing(e) {
    e.preventDefault();
    const btn = document.getElementById('submit-btn');
    btn.disabled = true; btn.textContent = 'Publicando...';

    const form = document.getElementById('create-form');
    const fd   = new FormData(form);
    fd.set('action','create');

    const res  = await fetch('../api/wallapop.php', { method:'POST', body:fd });
    const data = await res.json();
    if (data.ok) {
        toast('¡Anuncio publicado!', 'success');
        closeCreateModal();
        form.reset();
        document.getElementById('img-preview').innerHTML = '📷';
        document.querySelectorAll('.my-card-option').forEach(x => x.classList.remove('selected'));
        loadListings();
    } else {
        toast(data.message || 'Error al publicar', 'error');
    }
    btn.disabled = false; btn.textContent = 'Publicar anuncio';
}

// Init
loadListings();
</script>
<script src="../assets/js/csrf.js?v=<?php echo time(); ?>"></script>
</body>
</html>
