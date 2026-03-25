<?php
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: auth.php'); exit; }
require_once dirname(__DIR__) . '/includes/db.php';
$uid      = (int)$_SESSION['user_id'];
$username = $_SESSION['username'] ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Amigos | Loot&Trading</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/styles.css?v=<?php echo time(); ?>">
    <style>
        .amigos-wrap {
            max-width: 680px; margin: 90px auto 40px; padding: 0 16px;
        }
        .amigos-title { font-size: 1.5rem; font-weight: 800; margin-bottom: 20px; }

        /* Search */
        .search-row {
            display: flex; gap: 10px; margin-bottom: 28px;
        }
        .search-row input {
            flex: 1; padding: 12px 18px; border-radius: 50px;
            border: 2px solid var(--border-color); font-family: 'Outfit',sans-serif;
            font-size: .9rem; background: #f8f9fd; color: var(--text-primary);
            transition: border-color .2s;
        }
        body.dark .search-row input { background: #0f172a; border-color: #334155; color: #e2e8f0; }
        .search-row input:focus { outline: none; border-color: var(--accent-blue); }
        .search-row button {
            padding: 12px 22px; border-radius: 50px; background: var(--accent-blue);
            color: #fff; border: none; font-weight: 800; font-size: .85rem; cursor: pointer;
            font-family: 'Outfit',sans-serif; transition: background .2s;
        }
        .search-row button:hover { background: #2563eb; }

        /* Search results */
        #search-results { margin-bottom: 20px; }

        /* Section label */
        .section-label {
            font-size: .72rem; font-weight: 800; color: var(--text-secondary);
            text-transform: uppercase; letter-spacing: .06em; margin-bottom: 10px;
        }

        /* User card */
        .user-card {
            display: flex; align-items: center; gap: 14px;
            padding: 14px 16px; border-radius: 16px;
            background: #fff; border: 1.5px solid var(--border-color);
            margin-bottom: 10px; transition: box-shadow .18s;
        }
        body.dark .user-card { background: #1e293b; border-color: #334155; }
        .user-card:hover { box-shadow: 0 4px 16px rgba(0,0,0,.07); }
        .ucard-av {
            width: 46px; height: 46px; border-radius: 50%; flex-shrink: 0;
            background: linear-gradient(135deg,#667eea,#764ba2);
            display: flex; align-items: center; justify-content: center;
            font-size: 1.1rem; color: #fff; font-weight: 800; overflow: hidden;
        }
        .ucard-av img { width: 100%; height: 100%; object-fit: cover; border-radius: 50%; }
        .ucard-info { flex: 1; min-width: 0; }
        .ucard-name { font-weight: 700; font-size: .95rem; }
        .ucard-user { font-size: .78rem; color: var(--text-secondary); }
        .ucard-actions { display: flex; gap: 8px; flex-shrink: 0; }
        .btn-sm {
            padding: 7px 16px; border-radius: 50px; font-weight: 800; font-size: .76rem;
            border: 2px solid transparent; cursor: pointer; transition: all .18s;
            font-family: 'Outfit',sans-serif; white-space: nowrap;
        }
        .btn-add    { background: var(--accent-blue); color: #fff; border-color: var(--accent-blue); }
        .btn-add:hover { background: #2563eb; }
        .btn-accept { background: #10b981; color: #fff; border-color: #10b981; }
        .btn-accept:hover { background: #059669; }
        .btn-reject { background: #f1f5f9; color: var(--text-secondary); border-color: var(--border-color); }
        .btn-reject:hover { background: #fee2e2; color: #dc2626; border-color: #fca5a5; }
        body.dark .btn-reject { background: #0f172a; }
        .btn-profile { background: transparent; color: var(--accent-blue); border-color: var(--accent-blue); }
        .btn-profile:hover { background: #eff6ff; }
        body.dark .btn-profile:hover { background: #1e3a5f; }
        .status-sent { font-size: .76rem; color: var(--text-secondary); font-weight: 700; padding: 7px 4px; }

        /* Empty state */
        .empty-state { text-align: center; padding: 40px 20px; color: var(--text-secondary); }
        .empty-state .big-icon { font-size: 3rem; margin-bottom: 12px; }

        /* ── Modal perfil amigo: full-screen, igual que profile.php ── */
        .profile-modal-overlay {
            position: fixed; inset: 0; z-index: 5000;
            overflow-y: auto; display: none;
            background: var(--bg-main);
        }
        .profile-modal-overlay.open { display: block; }

        /* Botón cerrar flotante */
        .pm-close-btn {
            position: fixed; top: 80px; right: 24px; z-index: 5100;
            width: 42px; height: 42px; border-radius: 50%;
            background: rgba(255,255,255,.9); backdrop-filter: blur(8px);
            border: 1.5px solid var(--border-color);
            font-size: 1.3rem; cursor: pointer; color: var(--text-secondary);
            display: none; align-items: center; justify-content: center;
            box-shadow: 0 4px 16px rgba(0,0,0,.12); transition: all .2s;
        }
        body.dark .pm-close-btn { background: rgba(30,41,59,.9); border-color: #334155; color: #e2e8f0; }
        .pm-close-btn:hover { background: #fee2e2; color: #dc2626; border-color: #fca5a5; }
        /* el botón se muestra/oculta por JS */

        /* Reusar exactamente los mismos estilos del profile.php */
        .pm-profile-hero {
            position: relative; min-height: 70vh;
            display: flex; align-items: center; justify-content: center;
            overflow: hidden; padding: 0 20px; background: #fff;
        }
        body.dark .pm-profile-hero { background: #0f172a; }
        .pm-profile-hero .card-wall-bg {
            grid-template-columns: repeat(5, 1fr);
            mask-image: radial-gradient(circle at center, rgba(0,0,0,0) 0%, rgba(0,0,0,0.9) 70%);
            -webkit-mask-image: radial-gradient(circle at center, rgba(0,0,0,0) 0%, rgba(0,0,0,0.9) 70%);
        }
        .pm-profile-hero::before, .pm-profile-hero::after {
            content: ''; position: absolute; left: 0; width: 100%; height: 180px; z-index: 1; pointer-events: none;
        }
        .pm-profile-hero::before { top: 0; background: linear-gradient(to bottom, #fff 0%, transparent 100%); }
        .pm-profile-hero::after  { bottom: 0; background: linear-gradient(to top, #fff 0%, transparent 100%); }
        body.dark .pm-profile-hero::before { background: linear-gradient(to bottom, #0f172a 0%, transparent 100%); }
        body.dark .pm-profile-hero::after  { background: linear-gradient(to top, #0f172a 0%, transparent 100%); }

        /* profile-header, profile-name, etc. ya están en styles.css o en profile.php
           Las duplicamos aquí con prefijo pm- para no chocar */
        .pm-profile-header {
            background: rgba(255,255,255,0.95); backdrop-filter: blur(20px);
            border-radius: 24px; padding: 40px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.15);
            border: 1px solid rgba(255,255,255,0.2);
            text-align: center; position: relative; z-index: 2;
        }
        body.dark .pm-profile-header { background: rgba(30,41,59,0.95); border-color: rgba(51,65,85,.3); }
        .pm-avatar {
            width: 120px; height: 120px; border-radius: 50%; margin: 0 auto 20px;
            background: linear-gradient(135deg,#667eea,#764ba2);
            display: flex; align-items: center; justify-content: center;
            font-size: 3rem; color: #fff; font-weight: 800;
            box-shadow: 0 10px 30px rgba(102,126,234,.3); overflow: hidden;
        }
        .pm-avatar img { width: 100%; height: 100%; object-fit: cover; border-radius: 50%; }
        .pm-pname { font-size: 2.5rem; font-weight: 800; letter-spacing: -1px; color: #0f172a; margin-bottom: 10px; }
        body.dark .pm-pname { color: #e2e8f0; }
        .pm-pusername { font-size: 1.2rem; color: var(--text-secondary); margin-bottom: 30px; }
        .pm-pstats { display: flex; justify-content: center; gap: 60px; margin-top: 30px; }
        .pm-pstat { text-align: center; }
        .pm-pstat-n { font-size: 2.5rem; font-weight: 800; color: var(--accent-blue); }
        .pm-pstat-l { font-size: .9rem; color: var(--text-secondary); margin-top: 5px; font-weight: 600; }

        /* Content */
        .pm-profile-content { max-width: 1200px; margin: 0 auto; padding: 40px 20px 60px; }
        .pm-profile-sections { display: grid; grid-template-columns: repeat(auto-fit, minmax(350px,1fr)); gap: 30px; }
        .pm-profile-section {
            background: rgba(255,255,255,0.95); backdrop-filter: blur(20px);
            border-radius: 24px; padding: 40px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.15);
            border: 1px solid rgba(255,255,255,0.2);
        }
        body.dark .pm-profile-section { background: rgba(30,41,59,0.95); border-color: rgba(51,65,85,.3); }
        .pm-section-title { font-size: 1.5rem; font-weight: 800; margin-bottom: 25px; color: #0f172a; letter-spacing: -.5px; }
        body.dark .pm-section-title { color: #e2e8f0; }
        .pm-info-item {
            display: flex; justify-content: space-between; align-items: center;
            padding: 15px 0; border-bottom: 1px solid var(--border-color);
        }
        .pm-info-item:last-child { border-bottom: none; }
        .pm-info-label { font-weight: 600; color: var(--text-secondary); }
        .pm-info-value { color: var(--text-primary); font-weight: 500; }

        /* Activity */
        .pm-activity-list { display: flex; flex-direction: column; gap: 12px; margin-top: 10px; }
        .pm-activity-item {
            background: rgba(255,255,255,.75); border: 1px solid var(--border-color);
            border-radius: 16px; padding: 14px 16px;
            display: grid; grid-template-columns: 48px 1fr; gap: 12px; align-items: start;
        }
        body.dark .pm-activity-item { background: rgba(30,41,59,.75); border-color: #334155; }
        .pm-activity-thumb {
            width: 48px; height: 64px; border-radius: 12px; object-fit: cover;
            border: 1px solid rgba(0,0,0,.06); background: #fff;
        }
        body.dark .pm-activity-thumb { background: #0f172a; }
        .pm-activity-title { font-weight: 900; margin-bottom: 4px; }
        .pm-activity-desc  { color: var(--text-secondary); font-weight: 600; font-size: .95rem; margin-bottom: 8px; }
        .pm-activity-date  { color: var(--text-secondary); font-weight: 700; font-size: .85rem; }

        /* Won auctions */
        .pm-won-list { display: flex; flex-direction: column; gap: 12px; margin-top: 10px; }
        .pm-won-item {
            display: grid; grid-template-columns: 52px 1fr; gap: 12px; align-items: center;
            background: rgba(255,255,255,.75); border: 1px solid var(--border-color);
            border-radius: 16px; padding: 12px;
        }
        body.dark .pm-won-item { background: rgba(30,41,59,.75); border-color: #334155; }
        .pm-won-img { width: 52px; height: 72px; border-radius: 10px; object-fit: cover; }
        .pm-won-name { font-weight: 900; margin-bottom: 4px; }
        .pm-won-meta { color: var(--text-secondary); font-size: .85rem; margin-bottom: 4px; }
        .pm-won-badge {
            font-size: .78rem; font-weight: 800; padding: 3px 10px; border-radius: 20px;
            background: rgba(16,185,129,.15); color: #059669; display: inline-block;
        }

        /* Favorites */
        .pm-fav-grid { display: grid; grid-template-columns: repeat(2,1fr); gap: 14px; margin-top: 10px; }
        .pm-fav-card {
            background: rgba(255,255,255,.75); border: 1px solid var(--border-color);
            border-radius: 18px; padding: 12px;
            display: grid; grid-template-columns: 52px 1fr; gap: 12px; align-items: center;
        }
        body.dark .pm-fav-card { background: rgba(30,41,59,.75); border-color: #334155; }
        .pm-fav-thumb { width: 52px; height: 72px; border-radius: 12px; object-fit: cover; }
        .pm-fav-name { font-weight: 900; margin-bottom: 4px; line-height: 1.2; }
        .pm-fav-game { color: var(--text-secondary); font-weight: 700; font-size: .9rem; }

        .pm-empty-state { text-align: center; padding: 60px 20px; color: var(--text-secondary); }
        .pm-empty-state .pm-empty-icon { font-size: 4rem; margin-bottom: 20px; opacity: .6; }

        @media (max-width: 768px) {
            .pm-profile-content { padding: 20px 15px 40px; }
            .pm-profile-header { padding: 30px 20px; }
            .pm-pstats { gap: 30px; }
            .pm-pstat-n { font-size: 2rem; }
            .pm-pname  { font-size: 2rem; }
            .pm-profile-sections { grid-template-columns: 1fr; gap: 20px; }
            .pm-profile-section { padding: 30px 20px; }
            .pm-fav-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
<script>(function(){ if(localStorage.getItem('theme')==='dark') document.body.classList.add('dark'); })();</script>
<?php include dirname(__DIR__) . '/includes/navbar.php'; ?>

<div class="amigos-wrap">
    <div class="amigos-title">Amigos</div>

    <!-- Búsqueda -->
    <div class="search-row">
        <input type="text" id="search-input" placeholder="Buscar usuario..." autocomplete="off">
        <button onclick="doSearch()">Buscar</button>
    </div>
    <div id="search-results"></div>

    <!-- Solicitudes pendientes -->
    <div id="requests-section" style="display:none;">
        <div class="section-label">Solicitudes pendientes</div>
        <div id="requests-list"></div>
    </div>

    <!-- Mis amigos -->
    <div class="section-label">Mis amigos</div>
    <div id="friends-list">
        <div class="empty-state"><div class="big-icon">⏳</div><p>Cargando...</p></div>
    </div>
</div>

<!-- Botón cerrar flotante -->
<button class="pm-close-btn" id="pm-close-btn" onclick="closeProfile()">×</button>

<!-- Modal: perfil de amigo (full-screen, igual que profile.php) -->
<div class="profile-modal-overlay" id="profile-modal-overlay">

    <!-- Hero con card-wall-bg -->
    <div class="pm-profile-hero">
        <div class="card-wall-bg">
            <div class="wall-column col-up">
                <div class="wall-img" style="background-image:url('https://images.pokemontcg.io/base1/4_hires.png')"></div>
                <div class="wall-img" style="background-image:url('https://images.ygoprodeck.com/images/cards/89631139.jpg')"></div>
                <div class="wall-img" style="background-image:url('https://cards.scryfall.io/large/front/b/d/bd8fa327-dd41-4737-8f19-2cf5eb1f7cdd.jpg')"></div>
                <div class="wall-img" style="background-image:url('https://images.pokemontcg.io/swsh35/74_hires.png')"></div>
                <div class="wall-img" style="background-image:url('https://images.ygoprodeck.com/images/cards/46986414.jpg')"></div>
            </div>
            <div class="wall-column col-down">
                <div class="wall-img" style="background-image:url('https://asia-en.onepiece-cardgame.com/images/cardlist/card/OP05-060.png')"></div>
                <div class="wall-img" style="background-image:url('https://images.pokemontcg.io/base1/2_hires.png')"></div>
                <div class="wall-img" style="background-image:url('https://images.ygoprodeck.com/images/cards/33396948.jpg')"></div>
                <div class="wall-img" style="background-image:url('https://asia-en.onepiece-cardgame.com/images/cardlist/card/OP01-016.png')"></div>
                <div class="wall-img" style="background-image:url('https://images.pokemontcg.io/swsh7/215_hires.png')"></div>
            </div>
            <div class="wall-column col-up">
                <div class="wall-img" style="background-image:url('https://cards.scryfall.io/large/front/7/0/70901356-3266-4bd9-aacc-f06c27271de5.jpg')"></div>
                <div class="wall-img" style="background-image:url('https://asia-en.onepiece-cardgame.com/images/cardlist/card/OP01-025.png')"></div>
                <div class="wall-img" style="background-image:url('https://images.pokemontcg.io/neo1/9_hires.png')"></div>
                <div class="wall-img" style="background-image:url('https://cards.scryfall.io/large/front/e/3/e3285e6b-3e79-4d7c-bf96-d920f973b122.jpg')"></div>
                <div class="wall-img" style="background-image:url('https://images.pokemontcg.io/swsh11/186_hires.png')"></div>
            </div>
            <div class="wall-column col-down">
                <div class="wall-img" style="background-image:url('https://cards.scryfall.io/large/front/2/3/2398892d-28e9-4009-81ec-0d544af79d2b.jpg')"></div>
                <div class="wall-img" style="background-image:url('https://asia-en.onepiece-cardgame.com/images/cardlist/card/OP01-120.png')"></div>
                <div class="wall-img" style="background-image:url('https://images.pokemontcg.io/base1/15_hires.png')"></div>
                <div class="wall-img" style="background-image:url('https://images.ygoprodeck.com/images/cards/4031928.jpg')"></div>
                <div class="wall-img" style="background-image:url('https://images.pokemontcg.io/swsh4/43_hires.png')"></div>
            </div>
            <div class="wall-column col-up">
                <div class="wall-img" style="background-image:url('https://cards.scryfall.io/large/front/f/2/f2bc06cb-2f22-4313-82a2-a7e7b2564f4d.jpg')"></div>
                <div class="wall-img" style="background-image:url('https://asia-en.onepiece-cardgame.com/images/cardlist/card/OP01-121.png')"></div>
                <div class="wall-img" style="background-image:url('https://images.pokemontcg.io/swsh1/1_hires.png')"></div>
                <div class="wall-img" style="background-image:url('https://images.ygoprodeck.com/images/cards/10000020.jpg')"></div>
                <div class="wall-img" style="background-image:url('https://images.pokemontcg.io/base1/2_hires.png')"></div>
            </div>
        </div>
        <div class="hero-content">
            <div class="pm-profile-header">
                <div class="pm-avatar" id="pm-av"></div>
                <h1 class="pm-pname" id="pm-name">Cargando...</h1>
                <p class="pm-pusername" id="pm-user"></p>
                <div class="pm-pstats">
                    <div class="pm-pstat"><div class="pm-pstat-n" id="pm-stat-cartas">—</div><div class="pm-pstat-l">Cartas</div></div>
                    <div class="pm-pstat"><div class="pm-pstat-n" id="pm-stat-cols">—</div><div class="pm-pstat-l">Colecciones</div></div>
                    <div class="pm-pstat"><div class="pm-pstat-n" id="pm-stat-pujas">—</div><div class="pm-pstat-l">Pujas</div></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Contenido -->
    <div class="pm-profile-content">
        <div class="pm-profile-sections">

            <div class="pm-profile-section">
                <h3 class="pm-section-title">📋 Información Personal</h3>
                <div id="pm-info">
                    <div class="pm-info-item"><span class="pm-info-label">Nombre</span><span class="pm-info-value" id="pm-info-nombre">—</span></div>
                    <div class="pm-info-item"><span class="pm-info-label">Usuario</span><span class="pm-info-value" id="pm-info-usuario">—</span></div>
                    <div class="pm-info-item"><span class="pm-info-label">Miembro desde</span><span class="pm-info-value" id="pm-info-fecha">—</span></div>
                </div>
            </div>

            <div class="pm-profile-section">
                <h3 class="pm-section-title">🎯 Actividad Reciente</h3>
                <div id="pm-activity"></div>
            </div>

            <div class="pm-profile-section">
                <h3 class="pm-section-title">🏆 Subastas Ganadas</h3>
                <div id="pm-auctions"></div>
            </div>

            <div class="pm-profile-section">
                <h3 class="pm-section-title">⭐ Favoritos</h3>
                <div id="pm-favorites"></div>
            </div>

        </div>
    </div>

</div>

<div class="toast-container" id="toast-container"></div>

<script>
const MY_ID = <?php echo $uid; ?>;

function fixUrl(u) { return u && !u.startsWith('http') && !u.startsWith('/') ? '../' + u : (u || ''); }

function toast(msg, type='info') {
    const c = document.getElementById('toast-container');
    const t = document.createElement('div');
    t.className = `toast ${type}`;
    t.innerHTML = `<span>${{success:'✅',error:'❌',info:'ℹ️'}[type]||'ℹ️'}</span>${msg}`;
    c.appendChild(t);
    setTimeout(() => t.remove(), 3500);
}

function escHtml(s) {
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

/* ── Cargar amigos y solicitudes ── */
async function loadFriends() {
    try {
        const [frRes, rqRes] = await Promise.all([
            fetch('../api/friends.php?action=list'),
            fetch('../api/friends.php?action=requests')
        ]);
        const frData = await frRes.json();
        const rqData = await rqRes.json();

        // Solicitudes
        const rqSec  = document.getElementById('requests-section');
        const rqList = document.getElementById('requests-list');
        if (rqData.ok && rqData.requests && rqData.requests.length) {
            rqSec.style.display = '';
            rqList.innerHTML = '';
            rqData.requests.forEach(r => rqList.appendChild(buildRequestCard(r)));
        } else {
            rqSec.style.display = 'none';
        }

        // Amigos
        const frList = document.getElementById('friends-list');
        if (frData.ok && frData.friends && frData.friends.length) {
            frList.innerHTML = '';
            frData.friends.forEach(f => frList.appendChild(buildFriendCard(f)));
        } else {
            frList.innerHTML = `<div class="empty-state"><div class="big-icon">👥</div><p>Aún no tienes amigos.<br>¡Búscalos arriba!</p></div>`;
        }
    } catch(e) {
        document.getElementById('friends-list').innerHTML = `<div class="empty-state"><p>Error al cargar.</p></div>`;
    }
}

function buildFriendCard(f) {
    const div = document.createElement('div');
    div.className = 'user-card';
    const avHtml = f.avatar_url
        ? `<img src="${escHtml(fixUrl(f.avatar_url))}" alt="" onerror="this.style.display='none'">`
        : (f.username||'?').charAt(0).toUpperCase();
    div.innerHTML = `
        <div class="ucard-av">${avHtml}</div>
        <div class="ucard-info">
            <div class="ucard-name">${escHtml(f.name || f.username)}</div>
            <div class="ucard-user">@${escHtml(f.username)}</div>
        </div>
        <div class="ucard-actions">
            <button class="btn-sm btn-profile" onclick="openProfile(${f.id})">Ver perfil</button>
        </div>`;
    return div;
}

function buildRequestCard(r) {
    const div = document.createElement('div');
    div.className = 'user-card';
    div.id = `rrow-${r.id}`;
    const avHtml = r.avatar_url
        ? `<img src="${escHtml(fixUrl(r.avatar_url))}" alt="">`
        : (r.username||'?').charAt(0).toUpperCase();
    div.innerHTML = `
        <div class="ucard-av">${avHtml}</div>
        <div class="ucard-info">
            <div class="ucard-name">${escHtml(r.name || r.username)}</div>
            <div class="ucard-user">Quiere ser tu amigo</div>
        </div>
        <div class="ucard-actions">
            <button class="btn-sm btn-accept" onclick="acceptReq(${r.friendship_id},this)">✓ Aceptar</button>
            <button class="btn-sm btn-reject" onclick="rejectReq(${r.friendship_id},this)">✗</button>
        </div>`;
    return div;
}

/* ── Búsqueda ── */
let searchTimeout;
document.getElementById('search-input').addEventListener('input', function() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(doSearch, 350);
});
document.getElementById('search-input').addEventListener('keydown', e => { if(e.key==='Enter') doSearch(); });

async function doSearch() {
    const q   = document.getElementById('search-input').value.trim();
    const box = document.getElementById('search-results');
    if (q.length < 2) { box.innerHTML = ''; return; }
    box.innerHTML = '<div style="font-size:.84rem;color:var(--text-secondary);padding:8px 0;">Buscando...</div>';
    const res  = await fetch(`../api/friends.php?action=search&q=${encodeURIComponent(q)}`);
    const data = await res.json();
    box.innerHTML = '';
    if (!data.ok || !data.users.length) {
        box.innerHTML = '<div style="font-size:.84rem;color:var(--text-secondary);padding:8px 0;">Sin resultados</div>';
        return;
    }
    data.users.forEach(u => {
        const div = document.createElement('div');
        div.className = 'user-card';
        const avHtml = u.avatar_url
            ? `<img src="${escHtml(fixUrl(u.avatar_url))}" alt="">`
            : (u.username||'?').charAt(0).toUpperCase();
        const st  = u.friendship_status;
        const req = parseInt(u.requester_id)||0;
        let actions = '';
        if (!st) {
            actions = `<button class="btn-sm btn-add" onclick="sendReq(${u.id},this)">+ Añadir</button>`;
        } else if (st === 'pending' && req === MY_ID) {
            actions = `<span class="status-sent">Solicitud enviada</span>`;
        } else if (st === 'pending' && req !== MY_ID) {
            actions = `
                <button class="btn-sm btn-accept" onclick="acceptReq(${u.friendship_id||0},this)">✓</button>
                <button class="btn-sm btn-reject" onclick="rejectReq(${u.friendship_id||0},this)">✗</button>`;
        } else if (st === 'accepted') {
            actions = `<button class="btn-sm btn-profile" onclick="openProfile(${u.id})">Ver perfil</button>`;
        }
        div.innerHTML = `
            <div class="ucard-av">${avHtml}</div>
            <div class="ucard-info">
                <div class="ucard-name">${escHtml(u.name||u.username)}</div>
                <div class="ucard-user">@${escHtml(u.username)}</div>
            </div>
            <div class="ucard-actions">${actions}</div>`;
        box.appendChild(div);
    });
}

async function sendReq(userId, btn) {
    btn.disabled = true; btn.textContent = '...';
    const fd = new FormData();
    fd.append('action','send'); fd.append('user_id', userId);
    const res  = await fetch('../api/friends.php', {method:'POST',body:fd});
    const data = await res.json();
    if (data.ok) { toast('Solicitud enviada','success'); btn.closest('.ucard-actions').innerHTML = '<span class="status-sent">Solicitud enviada</span>'; }
    else { toast(data.message||'Error','error'); btn.disabled=false; btn.textContent='+ Añadir'; }
}

async function acceptReq(fid, btn) {
    btn.disabled = true;
    const fd = new FormData();
    fd.append('action','respond'); fd.append('friendship_id', fid); fd.append('answer','accepted');
    await fetch('../api/friends.php', {method:'POST',body:fd});
    toast('¡Ahora sois amigos!','success');
    loadFriends();
}

async function rejectReq(fid, btn) {
    btn.disabled = true;
    const fd = new FormData();
    fd.append('action','respond'); fd.append('friendship_id', fid); fd.append('answer','rejected');
    await fetch('../api/friends.php', {method:'POST',body:fd});
    loadFriends();
}

/* ── Modal: perfil completo igual que profile.php ── */
const GAME_LABELS = { pokemon:'Pokémon', yugioh:'Yu-Gi-Oh!', magic:'Magic', onepiece:'One Piece' };

function fmtDate(dateStr) {
    const d = new Date(dateStr);
    return d.toLocaleDateString('es',{day:'2-digit',month:'2-digit',year:'numeric'})
         + ' ' + d.toLocaleTimeString('es',{hour:'2-digit',minute:'2-digit'});
}

function emptyState(icon, text) {
    return `<div class="pm-empty-state"><div class="pm-empty-icon">${icon}</div><p>${text}</p></div>`;
}

async function openProfile(userId) {
    const overlay = document.getElementById('profile-modal-overlay');
    overlay.classList.add('open');
    overlay.scrollTop = 0;
    document.body.style.overflow = 'hidden';
    document.getElementById('pm-close-btn').style.display = 'flex';

    // Reset loading
    document.getElementById('pm-av').innerHTML = '';
    document.getElementById('pm-name').textContent = 'Cargando...';
    document.getElementById('pm-user').textContent = '';
    document.getElementById('pm-stat-cartas').textContent = '—';
    document.getElementById('pm-stat-cols').textContent   = '—';
    document.getElementById('pm-stat-pujas').textContent  = '—';
    document.getElementById('pm-info-nombre').textContent  = '—';
    document.getElementById('pm-info-usuario').textContent = '—';
    document.getElementById('pm-info-fecha').textContent   = '—';
    document.getElementById('pm-activity').innerHTML  = emptyState('⏳','Cargando...');
    document.getElementById('pm-auctions').innerHTML  = emptyState('⏳','Cargando...');
    document.getElementById('pm-favorites').innerHTML = emptyState('⏳','Cargando...');

    const res  = await fetch(`../api/friends.php?action=profile&user_id=${userId}`);
    const data = await res.json();
    if (!data.ok) { toast('Error al cargar perfil','error'); return; }
    const p = data.profile;

    // Avatar + nombre + stats
    document.getElementById('pm-av').innerHTML = p.avatar_url
        ? `<img src="${escHtml(fixUrl(p.avatar_url))}" alt="">`
        : (p.username||'?').charAt(0).toUpperCase();
    document.getElementById('pm-name').textContent = p.name || p.username;
    document.getElementById('pm-user').textContent = '@' + p.username;

    const s = p.stats || {};
    document.getElementById('pm-stat-cartas').textContent = s.cartas      ?? 0;
    document.getElementById('pm-stat-cols').textContent   = s.colecciones ?? 0;
    document.getElementById('pm-stat-pujas').textContent  = s.pujas       ?? 0;

    // Info personal
    document.getElementById('pm-info-nombre').textContent  = p.name || p.username;
    document.getElementById('pm-info-usuario').textContent = '@' + p.username;
    if (p.created_at) {
        const d = new Date(p.created_at);
        document.getElementById('pm-info-fecha').textContent = d.toLocaleDateString('es',{day:'2-digit',month:'2-digit',year:'numeric'});
    }

    // Actividad reciente
    const actEl = document.getElementById('pm-activity');
    if (p.activity && p.activity.length) {
        actEl.innerHTML = '<div class="pm-activity-list">'
            + p.activity.map(a => `
            <div class="pm-activity-item">
                <div></div>
                <div>
                    <div class="pm-activity-title">${escHtml(a.title)}</div>
                    <div class="pm-activity-desc">${escHtml(a.description)}</div>
                    <div class="pm-activity-date">${fmtDate(a.created_at)}</div>
                </div>
            </div>`).join('') + '</div>';
    } else {
        actEl.innerHTML = emptyState('📚','Aún no tiene actividad');
    }

    // Subastas ganadas
    const aucEl = document.getElementById('pm-auctions');
    if (p.won_auctions && p.won_auctions.length) {
        aucEl.innerHTML = '<div class="pm-won-list">'
            + p.won_auctions.map(a => {
                const game = GAME_LABELS[a.card_game] || a.card_game || '';
                let badge = '';
                if (a.choice === 'exchange')
                    badge = `<span class="pm-won-badge">🪙 Canjeada por ${Number(a.lujanitos_awarded).toLocaleString()} LJ</span>`;
                else if (a.choice === 'delivery')
                    badge = `<span class="pm-won-badge">📦 Envío solicitado</span>`;
                const img = escHtml(a.card_image || 'https://upload.wikimedia.org/wikipedia/en/3/3b/Pokemon_Trading_Card_Game_cardback.jpg');
                return `<div class="pm-won-item">
                    <img class="pm-won-img" src="${img}" alt="${escHtml(a.card_name)}"
                         onerror="this.src='https://upload.wikimedia.org/wikipedia/en/3/3b/Pokemon_Trading_Card_Game_cardback.jpg'">
                    <div>
                        <div class="pm-won-name">${escHtml(a.card_name)}</div>
                        <div class="pm-won-meta">${escHtml(game)} — ${Number(a.current_bid).toLocaleString()} LJ</div>
                        ${badge}
                    </div>
                </div>`;
            }).join('') + '</div>';
    } else {
        aucEl.innerHTML = emptyState('🎯','Aún no ha ganado ninguna subasta');
    }

    // Favoritos
    const favEl = document.getElementById('pm-favorites');
    if (p.favorites && p.favorites.length) {
        favEl.innerHTML = '<div class="pm-fav-grid">'
            + p.favorites.map(f => `
            <div class="pm-fav-card">
                <img class="pm-fav-thumb" src="${escHtml(f.card_image)}" alt="${escHtml(f.card_name)}"
                     onerror="this.src='https://upload.wikimedia.org/wikipedia/en/3/3b/Pokemon_Trading_Card_Game_cardback.jpg'">
                <div>
                    <div class="pm-fav-name">${escHtml(f.card_name)}</div>
                    <div class="pm-fav-game">${escHtml(f.card_game)}</div>
                </div>
            </div>`).join('') + '</div>';
    } else {
        favEl.innerHTML = emptyState('💝','No tiene cartas favoritas');
    }
}

function closeProfile() {
    document.getElementById('profile-modal-overlay').classList.remove('open');
    document.getElementById('pm-close-btn').style.display = 'none';
    document.body.style.overflow = '';
}

loadFriends();
</script>
<script src="../assets/js/csrf.js?v=<?php echo time(); ?>"></script>
</body>
</html>
