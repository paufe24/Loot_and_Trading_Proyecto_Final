<?php
session_start();
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/gamification.php';
require_once dirname(__DIR__) . '/includes/avatar_helper.php';

// Ensure XP column exists
try { $conn->query("ALTER TABLE users ADD COLUMN xp INT NOT NULL DEFAULT 0"); } catch (Exception $e) {}
try { $conn->query("ALTER TABLE users ADD COLUMN lootcoins INT NOT NULL DEFAULT 1000"); } catch (Exception $e) {}

// Top 10 por nivel (XP)
$topXp = $conn->query("
    SELECT id, username, avatar_url, xp
    FROM users
    WHERE status = 'active' AND is_admin = 0
    ORDER BY xp DESC
    LIMIT 10
")->fetch_all(MYSQLI_ASSOC);

// Top 10 por Lujanitos
$topLC = $conn->query("
    SELECT id, username, avatar_url, lootcoins
    FROM users
    WHERE status = 'active' AND is_admin = 0
    ORDER BY lootcoins DESC
    LIMIT 10
")->fetch_all(MYSQLI_ASSOC);

// Top 10 cartas más vendidas (mercado + wallapop)
$topCards = $conn->query("
    SELECT card_name, card_game, card_image, SUM(qty) AS total_sold
    FROM (
        SELECT coi.card_name, coi.card_game, coi.card_image, coi.quantity AS qty
        FROM cart_order_items coi
        JOIN cart_orders co ON coi.order_id = co.id
        WHERE co.status = 'paid'
        UNION ALL
        SELECT wl.card_name, wl.card_game, wl.image_url AS card_image, 1 AS qty
        FROM wallapop_listings wl
        WHERE wl.status = 'sold'
    ) combined
    GROUP BY card_name, card_game, card_image
    ORDER BY total_sold DESC
    LIMIT 10
")->fetch_all(MYSQLI_ASSOC);

function getMedalColor(int $pos): string {
    return match($pos) { 1 => '#FFD700', 2 => '#C0C0C0', 3 => '#CD7F32', default => '#94a3b8' };
}
function getMedalBg(int $pos): string {
    return match($pos) { 1 => 'rgba(255,215,0,.12)', 2 => 'rgba(192,192,192,.12)', 3 => 'rgba(205,127,50,.12)', default => 'transparent' };
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rankings | Loot&Trading</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/styles.css?v=<?php echo time(); ?>">
    <style>
    .rank-hero {
        background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0f172a 100%);
        padding: 72px 24px 48px;
        text-align: center;
        position: relative;
        overflow: hidden;
    }
    .rank-hero::before {
        content: '';
        position: absolute; inset: 0;
        background: radial-gradient(ellipse at 50% 0%, rgba(59,130,246,.25) 0%, transparent 70%);
    }
    .rank-hero h1 {
        font-size: clamp(2rem, 5vw, 3.5rem);
        font-weight: 800;
        color: #fff;
        position: relative;
        letter-spacing: -1px;
    }
    .rank-hero p {
        color: #94a3b8;
        font-size: 1.05rem;
        margin-top: 8px;
        position: relative;
    }

    .rank-tabs {
        display: flex;
        justify-content: center;
        gap: 8px;
        padding: 32px 24px 0;
        flex-wrap: wrap;
    }
    .rank-tab {
        padding: 10px 24px;
        border-radius: 50px;
        border: 2px solid #e2e8f0;
        background: transparent;
        font-family: 'Outfit', sans-serif;
        font-weight: 700;
        font-size: .9rem;
        color: #64748b;
        cursor: pointer;
        transition: all .2s;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .rank-tab:hover { border-color: #3b82f6; color: #3b82f6; background: rgba(59,130,246,.06); }
    .rank-tab.active { background: #3b82f6; border-color: #3b82f6; color: #fff; box-shadow: 0 4px 14px rgba(59,130,246,.35); }
    body.dark .rank-tab { border-color: #334155; color: #94a3b8; }
    body.dark .rank-tab:hover { border-color: #3b82f6; color: #3b82f6; }

    .rank-section { display: none; max-width: 760px; margin: 32px auto 64px; padding: 0 16px; }
    .rank-section.active { display: block; }

    .rank-list { display: flex; flex-direction: column; gap: 10px; }

    .rank-row {
        display: flex;
        align-items: center;
        gap: 16px;
        padding: 16px 20px;
        border-radius: 16px;
        border: 1.5px solid #e2e8f0;
        background: #fff;
        transition: transform .15s, box-shadow .15s;
    }
    .rank-row:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(0,0,0,.08); }
    body.dark .rank-row { background: #1e293b; border-color: #334155; }
    body.dark .rank-row:hover { box-shadow: 0 8px 24px rgba(0,0,0,.3); }

    .rank-pos {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 800;
        font-size: .95rem;
        flex-shrink: 0;
    }

    .rank-avatar {
        width: 48px;
        height: 48px;
        border-radius: 50%;
        object-fit: cover;
        flex-shrink: 0;
        border: 2px solid #e2e8f0;
    }
    body.dark .rank-avatar { border-color: #334155; }
    .rank-avatar-placeholder {
        width: 48px;
        height: 48px;
        border-radius: 50%;
        background: linear-gradient(135deg, #3b82f6, #8b5cf6);
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 800;
        font-size: 1.1rem;
        color: #fff;
        flex-shrink: 0;
    }

    .rank-info { flex: 1; min-width: 0; }
    .rank-name {
        font-weight: 700;
        font-size: 1rem;
        color: #0f172a;
        text-decoration: none;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        display: block;
    }
    body.dark .rank-name { color: #e2e8f0; }
    .rank-name:hover { color: #3b82f6; }
    .rank-sub {
        font-size: .8rem;
        color: #64748b;
        margin-top: 2px;
    }

    .rank-badge {
        font-weight: 800;
        font-size: .95rem;
        white-space: nowrap;
        text-align: right;
    }

    /* Card ranking */
    .rank-card-img {
        width: 44px;
        height: 62px;
        object-fit: contain;
        border-radius: 6px;
        flex-shrink: 0;
        background: #f1f5f9;
    }
    body.dark .rank-card-img { background: #0f172a; }
    .rank-card-placeholder {
        width: 44px;
        height: 62px;
        border-radius: 6px;
        background: #f1f5f9;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        font-size: 1.4rem;
    }
    body.dark .rank-card-placeholder { background: #0f172a; }

    .game-pill {
        display: inline-block;
        padding: 2px 10px;
        border-radius: 50px;
        font-size: .72rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .5px;
        margin-top: 3px;
    }
    .game-pokemon  { background: #fef3c7; color: #d97706; }
    .game-yugioh   { background: #ede9fe; color: #7c3aed; }
    .game-magic    { background: #fce7f3; color: #db2777; }
    .game-onepiece { background: #fee2e2; color: #dc2626; }
    .game-default  { background: #f1f5f9; color: #64748b; }
    body.dark .game-pokemon  { background: rgba(217,119,6,.15); }
    body.dark .game-yugioh   { background: rgba(124,58,237,.15); }
    body.dark .game-magic    { background: rgba(219,39,119,.15); }
    body.dark .game-onepiece { background: rgba(220,38,38,.15); }
    body.dark .game-default  { background: rgba(100,116,139,.15); }

    .rank-empty {
        text-align: center;
        padding: 48px 24px;
        color: #94a3b8;
        font-size: 1rem;
    }
    .rank-empty span { font-size: 2.5rem; display: block; margin-bottom: 12px; }

    body.dark .rank-hero { background: linear-gradient(135deg, #0a0f1a 0%, #131c2b 50%, #0a0f1a 100%); }
    </style>
</head>
<body>
<script>(function(){ if(localStorage.getItem('theme')==='dark') document.body.classList.add('dark'); })();</script>

<?php include dirname(__DIR__) . '/includes/navbar.php'; ?>

<div class="rank-hero">
    <h1 data-i18n="rank.title">Rankings</h1>
    <p data-i18n="rank.subtitle">Los mejores coleccionistas de Loot &amp; Trading</p>
</div>

<div class="rank-tabs">
    <button class="rank-tab active" onclick="showTab('xp', this)">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
        <span data-i18n="rank.by_level">Top Nivel</span>
    </button>
    <button class="rank-tab" onclick="showTab('lc', this)">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l4 2"/></svg>
        <span data-i18n="rank.by_lujanitos">Top Lujanitos</span>
    </button>
    <button class="rank-tab" onclick="showTab('cards', this)">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 7.125C2.25 6.504 2.754 6 3.375 6h6c.621 0 1.125.504 1.125 1.125v3.75c0 .621-.504 1.125-1.125 1.125h-6a1.125 1.125 0 0 1-1.125-1.125v-3.75ZM14.25 8.625c0-.621.504-1.125 1.125-1.125h5.25c.621 0 1.125.504 1.125 1.125v8.25c0 .621-.504 1.125-1.125 1.125h-5.25a1.125 1.125 0 0 1-1.125-1.125v-8.25ZM3.75 16.125c0-.621.504-1.125 1.125-1.125h5.25c.621 0 1.125.504 1.125 1.125v2.25c0 .621-.504 1.125-1.125 1.125h-5.25a1.125 1.125 0 0 1-1.125-1.125v-2.25Z"/></svg>
        <span data-i18n="rank.by_cards">Cartas Más Vendidas</span>
    </button>
</div>

<!-- TOP NIVEL -->
<section class="rank-section active" id="tab-xp">
    <div class="rank-list">
    <?php if (empty($topXp)): ?>
        <div class="rank-empty"><span>🏆</span>Todavía no hay datos de niveles.</div>
    <?php else: ?>
        <?php foreach ($topXp as $i => $u):
            $pos  = $i + 1;
            $info = getLevelInfo((int)$u['xp']);
            $mc   = getMedalColor($pos);
            $mbg  = getMedalBg($pos);
        ?>
        <div class="rank-row" style="background-color:<?= $mbg ?>">
            <div class="rank-pos" style="background:<?= $mc ?>20;color:<?= $mc ?>;"><?= $pos ?></div>
            <?php $rav = resolveAvatarUrl($u['avatar_url'] ?? '', '../'); ?>
            <?php if ($rav): ?>
                <img class="rank-avatar" src="<?= htmlspecialchars($rav) ?>" alt="">
            <?php else: ?>
                <div class="rank-avatar-placeholder"><?= strtoupper(mb_substr($u['username'], 0, 1)) ?></div>
            <?php endif; ?>
            <div class="rank-info">
                <a class="rank-name" href="profile.php?user=<?= $u['id'] ?>"><?= htmlspecialchars($u['username']) ?></a>
                <div class="rank-sub"><?= htmlspecialchars($info['title']) ?> &middot; Nv. <?= $info['level'] ?> &middot; <?= number_format($u['xp']) ?> XP</div>
            </div>
            <div class="rank-badge" style="color:<?= $mc ?>">Lv. <?= $info['level'] ?></div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
    </div>
</section>

<!-- TOP LUJANITOS -->
<section class="rank-section" id="tab-lc">
    <div class="rank-list">
    <?php if (empty($topLC)): ?>
        <div class="rank-empty"><span>💰</span>Todavía no hay datos de Lujanitos.</div>
    <?php else: ?>
        <?php foreach ($topLC as $i => $u):
            $pos = $i + 1;
            $mc  = getMedalColor($pos);
            $mbg = getMedalBg($pos);
        ?>
        <div class="rank-row" style="background-color:<?= $mbg ?>">
            <div class="rank-pos" style="background:<?= $mc ?>20;color:<?= $mc ?>;"><?= $pos ?></div>
            <?php $rav = resolveAvatarUrl($u['avatar_url'] ?? '', '../'); ?>
            <?php if ($rav): ?>
                <img class="rank-avatar" src="<?= htmlspecialchars($rav) ?>" alt="">
            <?php else: ?>
                <div class="rank-avatar-placeholder"><?= strtoupper(mb_substr($u['username'], 0, 1)) ?></div>
            <?php endif; ?>
            <div class="rank-info">
                <a class="rank-name" href="profile.php?user=<?= $u['id'] ?>"><?= htmlspecialchars($u['username']) ?></a>
                <div class="rank-sub">Lujanitos en balance</div>
            </div>
            <div class="rank-badge" style="color:#f59e0b"><?= number_format($u['lootcoins']) ?> LJ</div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
    </div>
</section>

<!-- TOP CARTAS VENDIDAS -->
<section class="rank-section" id="tab-cards">
    <div class="rank-list">
    <?php if (empty($topCards)): ?>
        <div class="rank-empty"><span>🎴</span>Todavía no hay ventas registradas.</div>
    <?php else: ?>
        <?php
        $gameEmoji = ['pokemon'=>'⚡','yugioh'=>'🌟','magic'=>'🔮','onepiece'=>'â˜ ï¸'];
        foreach ($topCards as $i => $c):
            $pos  = $i + 1;
            $mc   = getMedalColor($pos);
            $mbg  = getMedalBg($pos);
            $game = strtolower($c['card_game'] ?? '');
            $gameCls = in_array($game, ['pokemon','yugioh','magic','onepiece']) ? "game-$game" : 'game-default';
            $gameLabel = ucfirst($c['card_game'] ?? 'Desconocido');
        ?>
        <div class="rank-row" style="background-color:<?= $mbg ?>">
            <div class="rank-pos" style="background:<?= $mc ?>20;color:<?= $mc ?>;"><?= $pos ?></div>
            <?php if ($c['card_image']): ?>
                <img class="rank-card-img" src="<?= htmlspecialchars($c['card_image']) ?>" alt="" loading="lazy">
            <?php else: ?>
                <div class="rank-card-placeholder">🎴</div>
            <?php endif; ?>
            <div class="rank-info">
                <span class="rank-name" style="cursor:default"><?= htmlspecialchars($c['card_name']) ?></span>
                <span class="game-pill <?= $gameCls ?>"><?= ($gameEmoji[$game] ?? '🎴') ?> <?= $gameLabel ?></span>
            </div>
            <div class="rank-badge" style="color:#10b981"><?= (int)$c['total_sold'] ?> vendidas</div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
    </div>
</section>

<script>
function showTab(id, btn) {
    document.querySelectorAll('.rank-section').forEach(s => s.classList.remove('active'));
    document.querySelectorAll('.rank-tab').forEach(b => b.classList.remove('active'));
    document.getElementById('tab-' + id).classList.add('active');
    btn.classList.add('active');
}
</script>

</body>
</html>
