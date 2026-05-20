<?php
require_once dirname(__DIR__) . '/includes/session.php';
require_once dirname(__DIR__) . '/includes/db.php';
$isGuest = !isset($_SESSION['user_id']);
$balance = 0;
if (!$isGuest) {
    $uid = (int)$_SESSION['user_id'];
    $st = $conn->prepare("SELECT lootcoins, username FROM users WHERE id = ?");
    $st->bind_param("i", $uid); $st->execute();
    $user = $st->get_result()->fetch_assoc();
    $balance = (int)($user['lootcoins'] ?? 0);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Abrir Sobres &mdash; Loot &amp; Trading</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <style>
        /* ── Página ── */
        .sobres-page { padding-bottom: 80px; }

        /* ── Hero ── */
        .sobres-hero {
            background: var(--bg-main);
            border-bottom: 1px solid var(--border-color);
            padding: 52px 24px 40px;
            text-align: center;
        }
        .sobres-hero-eyebrow {
            display: inline-block; font-size: .75rem; font-weight: 800; letter-spacing: 1.5px;
            text-transform: uppercase; color: var(--text-secondary); margin-bottom: 10px;
        }
        .sobres-hero h1 {
            font-size: 2.6rem; font-weight: 900; color: var(--text-primary);
            margin: 0 0 8px; letter-spacing: -1.5px;
        }
        .sobres-hero p { color: var(--text-secondary); margin: 0 0 22px; font-size: 1rem; }
        .balance-display {
            display: inline-flex; align-items: center; gap: 8px;
            background: #f1f5f9; border: 1px solid var(--border-color);
            border-radius: 999px; padding: 8px 20px;
            color: var(--text-primary); font-size: .95rem; font-weight: 700;
        }
        body.dark .balance-display { background: #1e293b; border-color: #334155; }
        .balance-display .lj-icon { color: var(--text-primary); font-size: 1.1rem; }

        /* ── Panel central (selector + botón) ── */
        .sobres-main {
            max-width: 780px; margin: 40px auto 0; padding: 0 20px;
        }
        .sobres-panel {
            background: #fff; border: 1px solid var(--border-color);
            border-radius: 24px; padding: 36px 28px;
            box-shadow: 0 2px 16px rgba(0,0,0,.06);
        }
        body.dark .sobres-panel { background: #1e293b; border-color: #334155; box-shadow: 0 2px 16px rgba(0,0,0,.3); }

        .panel-label {
            font-size: .72rem; font-weight: 800; letter-spacing: 1px; text-transform: uppercase;
            color: var(--text-secondary); margin-bottom: 14px; display: block;
        }

        /* ── Game Selector ── */
        .game-selector { display: flex; justify-content: center; gap: 10px; flex-wrap: wrap; margin-bottom: 28px; }
        .game-btn {
            display: flex; flex-direction: column; align-items: center; gap: 6px;
            padding: 14px 20px; border-radius: 14px;
            border: 2px solid var(--border-color);
            background: var(--bg-main);
            color: var(--text-secondary);
            cursor: pointer; transition: all .18s; font-size: .8rem; font-weight: 700;
            text-transform: uppercase; letter-spacing: .5px;
        }
        body.dark .game-btn { background: #0f172a; }
        .game-btn .game-logo { width: 52px; height: 52px; object-fit: contain; display: block; }
        .game-btn:hover { border-color: #6366f1; color: var(--text-primary); transform: translateY(-2px); box-shadow: 0 4px 12px rgba(99,102,241,.15); }
        .game-btn.active { border-width: 2px; background: #fff; color: var(--text-primary); font-weight: 800; box-shadow: 0 4px 16px rgba(0,0,0,.08); }
        body.dark .game-btn.active { background: #1e293b; }
        .game-btn.pokemon.active  { border-color: #ef4444; box-shadow: 0 4px 16px rgba(239,68,68,.2); }
        .game-btn.magic.active    { border-color: #8b5cf6; box-shadow: 0 4px 16px rgba(139,92,246,.2); }
        .game-btn.yugioh.active   { border-color: #f59e0b; box-shadow: 0 4px 16px rgba(245,158,11,.2); }
        .game-btn.onepiece.active { border-color: #ec4899; box-shadow: 0 4px 16px rgba(236,72,153,.2); }

        /* ── Divider ── */
        .panel-divider { height: 1px; background: var(--border-color); margin: 0 0 24px; }

        /* ── Open Button ── */
        .open-section { text-align: center; }
        .btn-open-pack {
            display: inline-flex; align-items: center; gap: 10px;
            background: #0f172a; color: #fff;
            font-size: 1rem; font-weight: 800;
            padding: 14px 36px; border-radius: 999px; border: none; cursor: pointer;
            transition: all .2s; letter-spacing: -.2px;
        }
        body.dark .btn-open-pack { background: #e2e8f0; color: #0f172a; }
        .btn-open-pack:hover:not(:disabled) { background: #1e293b; transform: translateY(-1px); box-shadow: 0 6px 20px rgba(0,0,0,.2); }
        body.dark .btn-open-pack:hover:not(:disabled) { background: #f1f5f9; }
        .btn-open-pack:disabled { opacity: .45; cursor: not-allowed; transform: none; }
        .pack-cost { font-size: .8rem; color: var(--text-secondary); margin-top: 10px; }

        /* ── Pack animation area ── */
        .pack-arena {
            max-width: 500px; margin: 36px auto 0;
            display: flex; flex-direction: column; align-items: center; gap: 16px;
        }
        .pack-envelope {
            width: 190px; height: 266px; position: relative; cursor: pointer;
            display: none; transform-style: preserve-3d;
        }
        .pack-envelope img { width: 100%; height: 100%; object-fit: contain; border-radius: 12px; display: none; }
        .pack-graphic {
            width: 100%; height: 100%; border-radius: 20px;
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            gap: 10px; border: 2px solid var(--border-color);
            box-shadow: 0 8px 32px rgba(0,0,0,.12); overflow: hidden;
        }
        body.dark .pack-graphic { box-shadow: 0 8px 32px rgba(0,0,0,.5); }
        @keyframes packShake {
            0%,100%{transform:rotate(0deg)} 15%{transform:rotate(-7deg)} 30%{transform:rotate(7deg)}
            45%{transform:rotate(-5deg)} 60%{transform:rotate(5deg)} 75%{transform:rotate(-3deg)} 90%{transform:rotate(3deg)}
        }
        .pack-envelope.shaking { animation: packShake .65s cubic-bezier(.36,.07,.19,.97); }
        .pack-hint { font-size: .85rem; color: var(--text-secondary); font-weight: 600; }

        /* ── Cards reveal ── */
        .cards-reveal {
            display: none; flex-direction: column; align-items: center; gap: 24px;
            width: 100%; max-width: 960px; margin: 40px auto 0; padding: 0 20px;
        }
        .cards-row { display: flex; flex-wrap: wrap; justify-content: center; gap: 16px; width: 100%; }

        /* ── Card flip ── */
        .card-slot { perspective: 900px; width: 155px; }
        .card-inner {
            width: 100%; padding-top: 140%; position: relative;
            transform-style: preserve-3d; transition: transform .75s cubic-bezier(.4,0,.2,1);
        }
        .card-slot.flipped .card-inner { transform: rotateY(180deg); }
        .card-face, .card-back {
            position: absolute; top: 0; left: 0; width: 100%; height: 100%;
            backface-visibility: hidden; border-radius: 12px; overflow: hidden;
        }
        .card-back {
            border: 2px solid var(--border-color);
            display: flex; align-items: center; justify-content: center;
            overflow: hidden; background: #e2e8f0;
        }
        body.dark .card-back { border-color: #334155; background: #1e293b; }
        .card-back img { width: 100%; height: 100%; object-fit: cover; display: block; }
        .card-back-logo { font-size: 2.8rem; opacity: .3; display: none; }
        .card-face { transform: rotateY(180deg); background: #f1f5f9; border: 1px solid var(--border-color); }
        body.dark .card-face { background: #0f172a; border-color: #334155; }
        .card-face img { width: 100%; height: 100%; object-fit: cover; display: block; }

        /* Glow rarity */
        .card-slot.rarity-ultra  .card-face { box-shadow: 0 0 20px 5px rgba(250,204,21,.55), 0 0 6px rgba(250,204,21,.4); border-color: #f59e0b; }
        .card-slot.rarity-rare   .card-face { box-shadow: 0 0 14px 3px rgba(139,92,246,.45); border-color: #8b5cf6; }

        /* ── Card info ── */
        .card-info { margin-top: 10px; text-align: center; opacity: 0; transition: opacity .4s .2s; }
        .card-slot.flipped .card-info { opacity: 1; }
        .card-name {
            font-size: .82rem; font-weight: 700; color: var(--text-primary);
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 145px;
        }
        .card-lj { font-size: 1.1rem; font-weight: 900; color: var(--text-primary); margin-top: 4px; }
        .card-badges { display: flex; flex-wrap: wrap; justify-content: center; gap: 4px; margin-top: 5px; }
        .card-game-badge {
            display: inline-block; font-size: .65rem; padding: 2px 8px; border-radius: 99px;
            font-weight: 800; text-transform: uppercase; letter-spacing: .3px;
        }
        .badge-pokemon  { background:#fee2e2; color:#b91c1c; }
        .badge-magic    { background:#ede9fe; color:#6d28d9; }
        .badge-yugioh   { background:#fef3c7; color:#b45309; }
        .badge-onepiece { background:#fce7f3; color:#9d174d; }
        body.dark .badge-pokemon  { background:rgba(239,68,68,.15); color:#fca5a5; }
        body.dark .badge-magic    { background:rgba(139,92,246,.15); color:#c4b5fd; }
        body.dark .badge-yugioh   { background:rgba(245,158,11,.15); color:#fcd34d; }
        body.dark .badge-onepiece { background:rgba(236,72,153,.15); color:#f9a8d4; }

        /* ── Rarity badge ── */
        .rarity-badge { font-size: .65rem; padding: 2px 8px; border-radius: 99px; font-weight: 800; text-transform: uppercase; display: inline-block; }
        .rarity-badge.ultra  { background: #0f172a; color:#fff; font-weight: 900; }
        .rarity-badge.rare   { background: #1e293b; color: #e2e8f0; }
        .rarity-badge.common { background: #f1f5f9; color: #64748b; }
        body.dark .rarity-badge.ultra  { background: #e2e8f0; color: #0f172a; }
        body.dark .rarity-badge.rare   { background: #334155; color: #cbd5e1; }
        body.dark .rarity-badge.common { background: #1e293b; color:#64748b; }

        /* ── Action buttons per card ── */
        .card-slot { perspective: 900px; width: 170px; }
        .card-actions { display: flex; flex-direction: column; gap: 5px; margin-top: 10px; align-items: center; }
        .btn-sell-card {
            font-size: .72rem; padding: 6px 10px; border-radius: 999px; border: none; cursor: pointer; font-weight: 800;
            background: #1d4ed8; color: #fff; transition: all .15s; white-space: nowrap; flex-shrink: 0;
        }
        .btn-sell-card:hover:not(:disabled) { background: #1e40af; }
        .btn-sell-card:disabled { opacity: .3; cursor: default; background: #94a3b8; }
        .btn-keep-card {
            font-size: .72rem; padding: 6px 10px; border-radius: 999px;
            border: 1.5px solid var(--border-color); cursor: pointer; font-weight: 700;
            background: transparent; color: var(--text-secondary); transition: all .15s; white-space: nowrap; flex-shrink: 0;
        }
        .btn-keep-card:hover:not(:disabled) { border-color: var(--text-primary); color: var(--text-primary); }
        .btn-keep-card.kept { border-color: #0f172a; color: #0f172a; background: #f1f5f9; font-weight: 800; }
        body.dark .btn-keep-card.kept { background: #1e293b; color: #e2e8f0; border-color: #e2e8f0; }

        /* ── Bulk actions ── */
        .bulk-actions { display: none; flex-direction: column; align-items: center; gap: 12px; margin-top: 8px; }
        .btn-reveal-all { display: none; }
        .btn-sell-all {
            background: #0f172a; color:#fff;
            padding: 14px 40px; border-radius: 999px; border: none; font-weight: 900; font-size: 1.05rem;
            cursor: pointer; transition: all .2s; box-shadow: 0 4px 20px rgba(0,0,0,.18);
            letter-spacing: -.3px;
        }
        body.dark .btn-sell-all { background: #e2e8f0; color: #0f172a; }
        .btn-sell-all:hover { background: #1e293b; transform: translateY(-2px); box-shadow: 0 8px 28px rgba(0,0,0,.25); }
        body.dark .btn-sell-all:hover { background: #f1f5f9; }
        .btn-sell-all:disabled { opacity: .45; cursor: default; transform: none; }
        .sell-all-hint { color: var(--text-secondary); font-size: .8rem; }

        /* ── Toast ── */
        #sobres-toast {
            position: fixed; bottom: 28px; left: 50%; transform: translateX(-50%) translateY(80px);
            background: #1e293b; color: #f8fafc; padding: 12px 24px; border-radius: 999px;
            font-size: .9rem; font-weight: 600; box-shadow: 0 8px 32px rgba(0,0,0,.25);
            transition: transform .3s, opacity .3s; opacity: 0; z-index: 9999; white-space: nowrap;
            border: 1px solid rgba(255,255,255,.08);
        }
        body:not(.dark) #sobres-toast { background: #0f172a; }
        #sobres-toast.show { transform: translateX(-50%) translateY(0); opacity: 1; }
        #sobres-toast.success { border-color: rgba(16,185,129,.5); }
        #sobres-toast.error   { background: #7f1d1d; border-color: rgba(239,68,68,.7); font-weight: 700; }

        /* ── Opening overlay ── */
        #opening-overlay {
            display: none; position: fixed; inset: 0; background: rgba(15,23,42,.7);
            backdrop-filter: blur(4px);
            z-index: 500; align-items: center; justify-content: center; flex-direction: column; gap: 16px;
        }
        #opening-overlay.show { display: flex; }
        .opening-spinner {
            width: 52px; height: 52px; border: 3px solid rgba(255,255,255,.15);
            border-top-color: #6366f1; border-radius: 50%;
            animation: spin .8s linear infinite;
        }
        @keyframes spin { to { transform: rotate(360deg); } }
        #opening-overlay p { color: #fff; font-size: .95rem; font-weight: 600; }

        /* ── History ── */
        .history-section {
            max-width: 960px; margin: 56px auto 0; padding: 0 20px;
            border-top: 1px solid var(--border-color); padding-top: 40px;
        }
        .history-section h2 {
            font-size: 1.05rem; font-weight: 800; color: var(--text-primary);
            margin: 0 0 18px; display: flex; align-items: center; gap: 8px;
        }
        .history-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(110px, 1fr)); gap: 12px; }
        .hist-card {
            background: #fff; border-radius: 12px; overflow: hidden;
            border: 1px solid var(--border-color);
            box-shadow: 0 1px 4px rgba(0,0,0,.04);
            transition: transform .2s, box-shadow .2s;
            position: relative;
        }
        body.dark .hist-card { background: #1e293b; border-color: #334155; }
        .hist-card:hover { transform: translateY(-3px); box-shadow: 0 6px 20px rgba(0,0,0,.1); }
        .hist-card img { width: 100%; aspect-ratio: 2/3; object-fit: cover; display: block; }
        .hist-card-info { padding: 7px 8px; }
        .hist-card-name { font-size: .65rem; font-weight: 600; color: var(--text-secondary); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .hist-card-lj { font-size: .82rem; font-weight: 900; color: var(--text-primary); }
        .hist-card.sold { opacity: .5; }
        .hist-card.sold::after {
            content: 'Vendida'; position: absolute; top: 5px; right: 5px;
            background: #10b981; color:#fff; font-size: .58rem; font-weight: 800;
            padding: 2px 7px; border-radius: 5px; letter-spacing: .3px;
        }
    </style>
</head>
<body>
<script>(function(){ if(localStorage.getItem('theme')==='dark') document.body.classList.add('dark'); })();</script>
<?php include dirname(__DIR__) . '/includes/navbar.php'; ?>

<!-- Loading overlay -->
<div id="opening-overlay">
    <div class="opening-spinner"></div>
    <p>Abriendo sobre...</p>
</div>

<!-- Toast -->
<div id="sobres-toast"></div>

<!-- Hero -->
<div class="sobres-hero">
    <span class="sobres-hero-eyebrow">Sistema de Sobres</span>
    <h1>Abre Sobres TCG</h1>
    <p>5 cartas aleatorias reales &bull; V&eacute;ndelas o gu&aacute;rdalas en tu colecci&oacute;n</p>
    <div class="balance-display">
        <span class="lj-icon">&#9830;</span>
        <span id="hero-balance"><?php echo number_format($balance); ?></span>&nbsp;LJ disponibles
    </div>
</div>

<!-- Panel principal -->
<div class="sobres-main">
    <div class="sobres-panel">
        <span class="panel-label">Selecciona el juego</span>
        <!-- Game selector -->
        <div class="game-selector">
            <button class="game-btn pokemon active" data-game="pokemon">
                <img class="game-logo" src="../img/pokemon.png" alt="Pokémon">
                Pok&eacute;mon
            </button>
            <button class="game-btn magic" data-game="magic">
                <img class="game-logo" src="../img/magic.png" alt="Magic">
                Magic
            </button>
            <button class="game-btn yugioh" data-game="yugioh">
                <img class="game-logo" src="../img/yugioh.png" alt="Yu-Gi-Oh!">
                Yu-Gi-Oh!
            </button>
            <button class="game-btn onepiece" data-game="onepiece">
                <img class="game-logo" src="../img/onepiece.png" alt="One Piece">
                One Piece
            </button>
        </div>

        <div class="panel-divider"></div>

        <!-- Open button -->
        <div class="open-section">
            <button class="btn-open-pack" id="btn-open">
                Abrir Sobre &mdash; 50 LJ
            </button>
            <div class="pack-cost">Equivale a &euro;50 &bull; 1 LJ = 1 &euro;</div>
        </div>
    </div>
</div>

<!-- Pack envelope animation -->
<div class="pack-arena">
    <div class="pack-envelope" id="pack-envelope">
        <img id="pack-img" src="" alt="Sobre">
        <div class="pack-graphic" id="pack-graphic"></div>
    </div>
    <div class="pack-hint" id="pack-hint"></div>
</div>

<!-- Cards reveal -->
<div class="cards-reveal" id="cards-reveal">
    <div class="cards-row" id="cards-row"></div>
    <div class="bulk-actions" id="bulk-actions">
        <button class="btn-reveal-all" id="btn-reveal-all">Revelar todas</button>
        <button class="btn-sell-all" id="btn-sell-all">Vender todas &mdash; <span id="sell-all-total">0</span> LJ</button>
        <div class="sell-all-hint">Solo vende las que aún no hayas guardado</div>
    </div>
</div>

<!-- History -->
<div class="history-section">
    <h2>
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
        Mis cartas de sobres
    </h2>
    <div class="history-grid" id="history-grid">
        <div id="history-empty" style="grid-column:1/-1;text-align:center;padding:32px 0;color:var(--text-secondary);font-size:.9rem;">
            Aún no has abierto ningún sobre
        </div>
    </div>
</div>

<script>
const CARD_BACK_IMAGES = {
    pokemon:  '../img/parteatrascartas/pokemon.jpg',
    magic:    '../img/parteatrascartas/magic.webp',
    yugioh:   '../img/parteatrascartas/yugioh.webp',
    onepiece: '../img/parteatrascartas/onepiece.jpg',
};
const GAME_EMOJI = { pokemon:'⚡', magic:'✨', yugioh:'🎴', onepiece:'⚓' };
const GAME_BADGE_CLASS = { 'Pokémon':'badge-pokemon', 'Magic':'badge-magic', 'Yu-Gi-Oh!':'badge-yugioh', 'One Piece':'badge-onepiece' };
const RARITY_LABEL = { ultra:'Ultra Rare', rare:'Rare', common:'Common' };

let selectedGame = 'pokemon';
let currentCards = [];
let revealedCount = 0;
let revealInterval = null;
let keptCards = new Set();
let soldCards = new Set();

// ── Game selector ──
document.querySelectorAll('.game-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        document.querySelectorAll('.game-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        selectedGame = btn.dataset.game;
    });
});

function savePendingPack() {
    if (!currentCards.length) return;
    localStorage.setItem('lt_pending_pack', JSON.stringify({
        cards:   currentCards,
        game:    selectedGame,
        soldIds: [...soldCards],
        keptIds: [...keptCards],
    }));
}

// ── Open pack ──
document.getElementById('btn-open').addEventListener('click', openPack);

const IS_GUEST = <?php echo $isGuest ? 'true' : 'false'; ?>;

async function openPack() {
    if (IS_GUEST) {
        showToast('Inicia sesión para poder abrir sobres', 'error');
        setTimeout(() => { window.location.href = 'login.php'; }, 1500);
        return;
    }
    const btn = document.getElementById('btn-open');
    btn.disabled = true;
    localStorage.removeItem('lt_pending_pack');
    document.getElementById('opening-overlay').classList.add('show');

    try {
        const fd = new FormData();
        fd.append('game', selectedGame);
        const res = await fetch('../api/pack_open.php', { method: 'POST', body: fd });
        const data = await res.json();

        if (!data.ok) {
            const msg = data.message || 'Error al abrir sobre';
            showToast(msg, 'error');
            return;
        }

        currentCards = data.cards;
        keptCards.clear();
        soldCards.clear();
        revealedCount = 0;
        savePendingPack();

        // Update balance
        document.getElementById('hero-balance').textContent = data.new_balance.toLocaleString('es-ES');

        // Show pack envelope
        showPackEnvelope(selectedGame);
    } catch(e) {
        showToast('Error de red. Inténtalo de nuevo.', 'error');
    } finally {
        document.getElementById('opening-overlay').classList.remove('show');
        btn.disabled = false;
    }
}

function showPackEnvelope(game) {
    const env     = document.getElementById('pack-envelope');
    const img     = document.getElementById('pack-img');
    const graphic = document.getElementById('pack-graphic');
    const hint    = document.getElementById('pack-hint');
    const reveal  = document.getElementById('cards-reveal');
    const bulk    = document.getElementById('bulk-actions');

    // Reset
    reveal.style.display = 'none';
    bulk.style.display = 'none';
    document.getElementById('cards-row').innerHTML = '';
    if (revealInterval) clearInterval(revealInterval);

    // CSS graphic fallback (always shown, image hidden by default)
    const PACK_STYLES = {
        pokemon:  { bg:'linear-gradient(135deg,#dc2626,#991b1b)', icon:'⚡', label:'Pokémon' },
        magic:    { bg:'linear-gradient(135deg,#5b21b6,#2e1065)', icon:'✨', label:'Magic' },
        yugioh:   { bg:'linear-gradient(135deg,#b45309,#78350f)', icon:'🎴', label:'Yu-Gi-Oh!' },
        onepiece: { bg:'linear-gradient(135deg,#be185d,#831843)', icon:'⚓', label:'One Piece' },
    };
    const ps = PACK_STYLES[game] || PACK_STYLES.pokemon;
    const backSrc = CARD_BACK_IMAGES[game] || '';
    if (backSrc) {
        graphic.style.background = 'none';
        graphic.innerHTML = `<img src="${escHtml(backSrc)}" alt="sobre" style="width:100%;height:100%;object-fit:cover;border-radius:18px;display:block;">`;
    } else {
        graphic.style.background = ps.bg;
        graphic.innerHTML = `<span style="font-size:3.5rem;filter:drop-shadow(0 0 12px rgba(255,255,255,.5))">${ps.icon}</span>`;
    }
    graphic.style.display = 'flex';
    img.style.display = 'none';

    env.style.display = 'block';
    env.classList.remove('shaking');
    hint.textContent = '¡Haz clic en el sobre para abrirlo!';

    env.onclick = () => {
        env.classList.add('shaking');
        hint.textContent = '✨ Abriendo...';
        setTimeout(() => {
            env.style.display = 'none';
            graphic.style.display = 'none';
            hint.textContent = '';
            startCardReveal();
        }, 700);
    };
}

function gameGradient(game) {
    const g = { pokemon:'linear-gradient(135deg,#ef4444,#f97316)', magic:'linear-gradient(135deg,#7c3aed,#4f46e5)', yugioh:'linear-gradient(135deg,#d97706,#92400e)', onepiece:'linear-gradient(135deg,#db2777,#9d174d)' };
    return g[game] || 'linear-gradient(135deg,#1e1b4b,#312e81)';
}

function startCardReveal() {
    const row = document.getElementById('cards-row');
    const reveal = document.getElementById('cards-reveal');
    const bulk   = document.getElementById('bulk-actions');

    reveal.style.display = 'flex';
    bulk.style.display = 'none';

    // Build slots (face-down)
    row.innerHTML = '';
    currentCards.forEach((card, i) => {
        const slot = buildCardSlot(card, i);
        row.appendChild(slot);
    });

    // Reveal one by one with delay
    revealedCount = 0;
    revealInterval = setInterval(() => {
        if (revealedCount >= currentCards.length) {
            clearInterval(revealInterval);
            showBulkActions();
            loadHistory();
            return;
        }
        const slots = row.querySelectorAll('.card-slot');
        flipCard(slots[revealedCount], currentCards[revealedCount]);
        revealedCount++;
    }, 600);
}

function buildCardSlot(card, idx) {
    const slot = document.createElement('div');
    slot.className = 'card-slot';
    slot.dataset.idx = idx;
    slot.dataset.packCardId = card.pack_card_id;

    const ljVal = parseFloat(card.lujanitos_value);
    const ljText = ljVal >= 1 ? Math.round(ljVal).toLocaleString('es-ES') + ' LJ' : ljVal.toFixed(2) + ' LJ';
    const gameKey = card.card_game.replace('é','e').replace('-','').replace('!','').trim();
    const badgeClass = GAME_BADGE_CLASS[card.card_game] || 'badge-pokemon';

    const backImg = CARD_BACK_IMAGES[selectedGame] || '';
    slot.innerHTML = `
        <div class="card-inner">
            <div class="card-back"><img src="${escHtml(backImg)}" alt="reverso"><span class="card-back-logo">🃏</span></div>
            <div class="card-face"><img src="${escHtml(card.card_image)}" alt="${escHtml(card.card_name)}" loading="lazy"></div>
        </div>
        <div class="card-info">
            <div class="card-name" title="${escHtml(card.card_name)}">${escHtml(card.card_name)}</div>
            <div class="card-lj">&#9830; ${ljText}</div>
            <div class="card-badges">
                <span class="card-game-badge ${badgeClass}">${escHtml(card.card_game)}</span>
                <span class="rarity-badge ${card.card_rarity}">${RARITY_LABEL[card.card_rarity] || card.card_rarity}</span>
            </div>
            <div class="card-actions">
                <button class="btn-sell-card" onclick="sellCard(this, ${card.pack_card_id}, ${ljVal})">Vender ${ljText}</button>
                <button class="btn-keep-card" onclick="keepCard(this, ${card.pack_card_id})">Guardar</button>
            </div>
        </div>
    `;
    return slot;
}

function flipCard(slot, card) {
    if (!slot) return;
    slot.classList.add('flipped', `rarity-${card.card_rarity}`);
    // Ultra rare: particle burst (simple glow pulse via CSS class)
    if (card.card_rarity === 'ultra') {
        setTimeout(() => slot.classList.add('glow-burst'), 100);
    }
}

function showBulkActions() {
    const bulk = document.getElementById('bulk-actions');
    bulk.style.display = 'flex';

    // Reveal-all button (in case user hasn't clicked individual ones ─ here all are auto-flipped)
    document.getElementById('btn-reveal-all').style.display = 'none';

    updateSellAllTotal();
}

function updateSellAllTotal() {
    let total = 0;
    currentCards.forEach((card, i) => {
        if (!keptCards.has(card.pack_card_id)) {
            total += parseFloat(card.lujanitos_value);
        }
    });
    const totalText = total >= 1 ? Math.round(total).toLocaleString('es-ES') : total.toFixed(2);
    document.getElementById('sell-all-total').textContent = totalText;
}

// ── Sell one card ──
async function sellCard(btn, packCardId, ljValue) {
    btn.disabled = true;
    const keepBtn = btn.nextElementSibling;
    if (keepBtn) keepBtn.disabled = true;

    const fd = new FormData();
    fd.append('pack_card_id', packCardId);
    try {
        const res = await fetch('../api/pack_sell.php', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.ok) {
            const ljText = ljValue >= 1 ? Math.round(ljValue).toLocaleString('es-ES') : ljValue.toFixed(2);
            showToast(`+${ljText} LJ ingresados`, 'success');
            document.getElementById('hero-balance').textContent = data.new_balance.toLocaleString('es-ES');
            btn.textContent = '✓ Vendida';
            btn.style.background = '#374151';
            soldCards.add(packCardId);
            keptCards.delete(packCardId);
            savePendingPack();
            updateSellAllTotal();
        } else {
            showToast(data.message || 'Error', 'error');
            btn.disabled = false;
            if (keepBtn) keepBtn.disabled = false;
        }
    } catch(e) {
        showToast('Error de red', 'error');
        btn.disabled = false;
        if (keepBtn) keepBtn.disabled = false;
    }
}

// ── Keep one card ──
async function keepCard(btn, packCardId) {
    const isKept = keptCards.has(packCardId);
    // Actualizar UI inmediatamente
    if (isKept) {
        keptCards.delete(packCardId);
        btn.textContent = 'Guardar';
        btn.classList.remove('kept');
    } else {
        keptCards.add(packCardId);
        btn.textContent = '✓ Guardada';
        btn.classList.add('kept');
        showToast('Carta guardada en tu coleccion', 'success');
    }
    savePendingPack();
    updateSellAllTotal();
    // Persistir en DB
    const fd = new FormData();
    fd.append('pack_card_id', packCardId);
    fetch('../api/pack_keep.php', { method: 'POST', body: fd }).catch(()=>{});
}

// ── Sell all (not kept) ──
document.getElementById('btn-sell-all').addEventListener('click', async () => {
    const btn = document.getElementById('btn-sell-all');
    btn.disabled = true;
    const toSell = currentCards.filter(c => !keptCards.has(c.pack_card_id));
    if (!toSell.length) { showToast('No hay cartas para vender', 'error'); btn.disabled=false; return; }

    const fd = new FormData();
    toSell.forEach(c => fd.append('pack_card_ids[]', c.pack_card_id));
    try {
        const res = await fetch('../api/pack_sell.php', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.ok) {
            const total = parseFloat(data.total_lj);
            const totalText = total >= 1 ? Math.round(total).toLocaleString('es-ES') : total.toFixed(2);
            showToast(`+${totalText} LJ ingresados`, 'success');
            document.getElementById('hero-balance').textContent = data.new_balance.toLocaleString('es-ES');
            // Disable sold card buttons
            toSell.forEach(card => {
                soldCards.add(card.pack_card_id);
                const row = document.getElementById('cards-row');
                const slot = row ? row.querySelector(`[data-pack-card-id="${card.pack_card_id}"]`) : null;
                if (slot) {
                    slot.querySelectorAll('.btn-sell-card, .btn-keep-card').forEach(b => b.disabled=true);
                    const sellBtn = slot.querySelector('.btn-sell-card');
                    if (sellBtn) { sellBtn.textContent = '✓ Vendida'; sellBtn.style.background='#374151'; }
                }
            });
            savePendingPack();
            updateSellAllTotal();
        } else {
            showToast(data.message || 'Error', 'error');
        }
    } catch(e) {
        showToast('Error de red', 'error');
    } finally {
        btn.disabled = false;
    }
});

// ── Restore pending pack on reload ──
function restoreCardReveal() {
    const row    = document.getElementById('cards-row');
    const reveal = document.getElementById('cards-reveal');
    const bulk   = document.getElementById('bulk-actions');

    reveal.style.display = 'flex';
    bulk.style.display   = 'none';
    row.innerHTML        = '';

    currentCards.forEach((card, i) => {
        const slot = buildCardSlot(card, i);
        // Mostrar todas las cartas ya volteadas (sin animación)
        slot.classList.add('flipped', `rarity-${card.card_rarity}`);

        if (soldCards.has(card.pack_card_id)) {
            const sb = slot.querySelector('.btn-sell-card');
            const kb = slot.querySelector('.btn-keep-card');
            if (sb) { sb.textContent = '✓ Vendida'; sb.style.background = '#374151'; sb.disabled = true; }
            if (kb) kb.disabled = true;
        } else if (keptCards.has(card.pack_card_id)) {
            const kb = slot.querySelector('.btn-keep-card');
            if (kb) { kb.textContent = '✓ Guardada'; kb.classList.add('kept'); }
        }
        row.appendChild(slot);
    });

    // Forzar opacidad de la info (bypass del delay de transición CSS)
    setTimeout(() => row.querySelectorAll('.card-info').forEach(el => el.style.opacity = '1'), 30);
    showBulkActions();
}

// ── History ──
async function loadHistory() {
    try {
        const res = await fetch('../api/pack_history.php');
        const data = await res.json();
        const grid = document.getElementById('history-grid');
        if (!data.ok || !data.cards.length) { return; }
        grid.innerHTML = '';
        data.cards.forEach(card => {
            const lj = parseFloat(card.lujanitos_value);
            const ljText = lj >= 1 ? Math.round(lj).toLocaleString('es-ES') + ' LJ' : lj.toFixed(2) + ' LJ';
            const div = document.createElement('div');
            div.className = 'hist-card' + (card.sold == 1 ? ' sold' : '');
            div.innerHTML = `
                <img src="${escHtml(card.card_image)}" alt="${escHtml(card.card_name)}" loading="lazy">
                <div class="hist-card-info">
                    <div class="hist-card-name" title="${escHtml(card.card_name)}">${escHtml(card.card_name)}</div>
                    <div class="hist-card-lj">&#9830; ${ljText}</div>
                </div>`;
            grid.appendChild(div);
        });
    } catch(e) {}
}

// ── Toast ──
let toastTimeout;
function showToast(msg, type='') {
    const t = document.getElementById('sobres-toast');
    t.textContent = msg;
    t.className = 'show' + (type ? ' ' + type : '');
    clearTimeout(toastTimeout);
    toastTimeout = setTimeout(() => { t.className = ''; }, 5000);
}

function escHtml(str) {
    if (!str) return '';
    return str.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// Restaurar sobre pendiente si el usuario recargó la página sin decidir
(function() {
    const pp = JSON.parse(localStorage.getItem('lt_pending_pack') || 'null');
    if (pp && Array.isArray(pp.cards) && pp.cards.length) {
        currentCards = pp.cards;
        selectedGame = pp.game || 'pokemon';
        (pp.soldIds || []).forEach(id => soldCards.add(id));
        (pp.keptIds || []).forEach(id => keptCards.add(id));
        document.querySelectorAll('.game-btn').forEach(b => {
            b.classList.toggle('active', b.dataset.game === selectedGame);
        });
        restoreCardReveal();
    }
})();

// Load history on page load
loadHistory();
</script>
</body>
</html>
