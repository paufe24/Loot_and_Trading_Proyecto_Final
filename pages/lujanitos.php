<?php
session_start();
require_once dirname(__DIR__) . '/includes/db.php';

$logged_in = isset($_SESSION['user_id']);
$balance   = 0;
if ($logged_in) {
    try { $conn->query("ALTER TABLE users ADD COLUMN lootcoins INT NOT NULL DEFAULT 1000"); } catch (Exception $e) {}
    $r = $conn->query("SELECT lootcoins FROM users WHERE id=" . (int)$_SESSION['user_id']);
    if ($r) $balance = (int)$r->fetch_assoc()['lootcoins'];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Loot&Trading | Tienda de Lujanitos</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/styles.css?v=<?php echo time(); ?>">
    <style>
        .lj-page {
            max-width: 900px;
            margin: 0 auto;
            padding: 100px 20px 80px;
        }
        .lj-hero {
            text-align: center;
            margin-bottom: 48px;
        }
        .lj-hero-icon {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            margin: 0 auto 16px;
            display: block;
            box-shadow: 0 8px 32px rgba(59,130,246,.25);
        }
        .lj-hero h1 {
            font-size: 2.2rem;
            font-weight: 800;
            margin-bottom: 8px;
        }
        .lj-hero p {
            color: var(--text-secondary);
            font-size: 1rem;
            max-width: 480px;
            margin: 0 auto;
        }
        .lj-balance-bar {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: #fff;
            border-radius: 20px;
            padding: 14px 28px;
            font-weight: 800;
            font-size: 1.05rem;
            margin: 0 auto 48px;
            max-width: 320px;
            box-shadow: 0 8px 24px rgba(59,130,246,.35);
        }
        .lj-balance-bar span { font-size: 1.4rem; }

        /* Paquetes */
        .lj-packages {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-bottom: 48px;
        }
        @media (min-width: 600px) {
            .lj-packages { grid-template-columns: repeat(4, 1fr); }
        }
        .lj-pkg {
            background: var(--bg-card, #fff);
            border: 2px solid var(--border-color, #e2e8f0);
            border-radius: 24px;
            padding: 28px 16px 22px;
            text-align: center;
            cursor: pointer;
            transition: all .2s;
            position: relative;
            overflow: hidden;
        }
        .lj-pkg:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 48px rgba(0,0,0,.12);
            border-color: #3b82f6;
        }
        .lj-pkg.popular {
            border-color: #3b82f6;
            box-shadow: 0 8px 28px rgba(59,130,246,.25);
        }
        .lj-pkg-badge {
            position: absolute;
            top: 12px;
            right: 12px;
            background: #3b82f6;
            color: #fff;
            font-size: .65rem;
            font-weight: 800;
            padding: 2px 8px;
            border-radius: 20px;
            text-transform: uppercase;
            letter-spacing: .5px;
        }
        .lj-pkg-icon {
            width: 56px;
            height: 56px;
            border-radius: 50%;
            object-fit: cover;
            margin: 0 auto 12px;
            display: block;
        }
        .lj-pkg-amount {
            font-size: 1.8rem;
            font-weight: 800;
            color: #3b82f6;
            margin-bottom: 2px;
        }
        .lj-pkg-label {
            font-size: .78rem;
            font-weight: 700;
            color: var(--text-secondary);
            margin-bottom: 14px;
        }
        .lj-pkg-price {
            font-size: 1.2rem;
            font-weight: 800;
            color: var(--text-primary, #0f172a);
            margin-bottom: 14px;
        }
        .lj-pkg-rate {
            font-size: .72rem;
            color: var(--text-secondary);
            margin-bottom: 18px;
            font-weight: 600;
        }
        .btn-buy {
            width: 100%;
            padding: 11px;
            border-radius: 14px;
            background: #3b82f6;
            color: #fff;
            border: none;
            font-weight: 800;
            font-size: .9rem;
            cursor: pointer;
            font-family: 'Outfit', sans-serif;
            transition: background .15s;
        }
        .btn-buy:hover { background: #2563eb; }
        .btn-buy:disabled { background: #94a3b8; cursor: default; }

        /* Info */
        .lj-info {
            background: var(--bg-card, #fff);
            border: 1px solid var(--border-color, #e2e8f0);
            border-radius: 20px;
            padding: 28px 32px;
        }
        .lj-info h3 { font-size: 1.05rem; font-weight: 800; margin-bottom: 16px; }
        .lj-info-row {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            margin-bottom: 14px;
            font-size: .9rem;
            color: var(--text-secondary);
            font-weight: 600;
        }
        .lj-info-row .lj-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #3b82f6;
            margin-top: 6px;
            flex-shrink: 0;
        }

        /* Toast */
        .lj-toast {
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
        .lj-toast.show { transform: translateX(-50%) translateY(0); }

        body.dark .lj-pkg { background: #1e293b; border-color: #334155; }
        body.dark .lj-info { background: #1e293b; border-color: #334155; }
    </style>
</head>
<body>
    <script>(function(){ if(localStorage.getItem('theme')==='dark') document.body.classList.add('dark'); })();</script>

    <?php include dirname(__DIR__) . '/includes/navbar.php'; ?>

    <div class="lj-page">
        <div class="lj-hero">
            <img class="lj-hero-icon" src="../img/lujanito.svg" alt="Lujanito" onerror="this.src='../img/pokemon.png'">
            <h1 data-i18n="lj.title">Tienda de Lujanitos 💰</h1>
            <p data-i18n="lj.desc">Consigue Lujanitos para pujar en subastas, comprar cartas y participar en el mercado. 1€ = 1 Lujanito.</p>
        </div>

        <?php if ($logged_in): ?>
        <div class="lj-balance-bar">
            <span>💰</span>
            Tu saldo: <strong id="balance-display"><?php echo number_format($balance); ?></strong> LJ
        </div>
        <?php else: ?>
        <div style="text-align:center;margin-bottom:40px;">
            <a href="auth.php" class="btn-main" data-i18n="lj.login_to_buy">Inicia sesión para comprar</a>
        </div>
        <?php endif; ?>

        <div class="lj-packages">
            <!-- 100 LJ -->
            <div class="lj-pkg">
                <img class="lj-pkg-icon" src="../img/lujanito.svg" alt="LJ" onerror="this.src='../img/pokemon.png'">
                <div class="lj-pkg-amount">100</div>
                <div class="lj-pkg-label">Lujanitos</div>
                <div class="lj-pkg-price">1 €</div>
                <div class="lj-pkg-rate">1 LJ / €</div>
                <button class="btn-buy" onclick="buy(100, this)" <?php echo !$logged_in ? 'disabled' : ''; ?> data-i18n="lj.buy">
                    Comprar
                </button>
            </div>
            <!-- 500 LJ -->
            <div class="lj-pkg popular">
                <div class="lj-pkg-badge" data-i18n="lj.popular">Popular</div>
                <img class="lj-pkg-icon" src="../img/lujanito.svg" alt="LJ" onerror="this.src='../img/pokemon.png'">
                <div class="lj-pkg-amount">500</div>
                <div class="lj-pkg-label">Lujanitos</div>
                <div class="lj-pkg-price">5 €</div>
                <div class="lj-pkg-rate">1 LJ / €</div>
                <button class="btn-buy" onclick="buy(500, this)" <?php echo !$logged_in ? 'disabled' : ''; ?> data-i18n="lj.buy">
                    Comprar
                </button>
            </div>
            <!-- 1000 LJ -->
            <div class="lj-pkg">
                <img class="lj-pkg-icon" src="../img/lujanito.svg" alt="LJ" onerror="this.src='../img/pokemon.png'">
                <div class="lj-pkg-amount">1.000</div>
                <div class="lj-pkg-label">Lujanitos</div>
                <div class="lj-pkg-price">10 €</div>
                <div class="lj-pkg-rate">1 LJ / €</div>
                <button class="btn-buy" onclick="buy(1000, this)" <?php echo !$logged_in ? 'disabled' : ''; ?> data-i18n="lj.buy">
                    Comprar
                </button>
            </div>
            <!-- 5000 LJ -->
            <div class="lj-pkg">
                <img class="lj-pkg-icon" src="../img/lujanito.svg" alt="LJ" onerror="this.src='../img/pokemon.png'">
                <div class="lj-pkg-amount">5.000</div>
                <div class="lj-pkg-label">Lujanitos</div>
                <div class="lj-pkg-price">50 €</div>
                <div class="lj-pkg-rate">1 LJ / €</div>
                <button class="btn-buy" onclick="buy(5000, this)" <?php echo !$logged_in ? 'disabled' : ''; ?> data-i18n="lj.buy">
                    Comprar
                </button>
            </div>
        </div>

        <div class="lj-info">
            <h3 data-i18n="lj.what_are">¿Qué son los Lujanitos?</h3>
            <div class="lj-info-row"><span class="lj-dot"></span><span data-i18n-html="lj.info_row1">La moneda oficial de Loot&amp;Trading. Úsalos para pujar en subastas y comprar cartas en el mercado.</span></div>
            <div class="lj-info-row"><span class="lj-dot"></span><span data-i18n-html="lj.info_row2">Al registrarte recibes <strong>1.000 LJ de bienvenida</strong> sin coste.</span></div>
            <div class="lj-info-row"><span class="lj-dot"></span><span data-i18n-html="lj.info_row3">Cambio fijo: <strong>1 € = 1 Lujanito</strong>. Lo que ves es lo que pagas.</span></div>
            <div class="lj-info-row"><span class="lj-dot"></span><span data-i18n-html="lj.info_row4">Los Lujanitos se añaden al instante a tu saldo después de confirmar.</span></div>
            <div class="lj-info-row"><span class="lj-dot"></span><span data-i18n-html="lj.info_row5">Compra simulada — no se realiza ningún cargo real.</span></div>
        </div>
    </div>

    <div class="lj-toast" id="lj-toast"></div>

    <script src="../assets/js/csrf.js?v=<?php echo time(); ?>"></script>
    <script>
    const IS_LOGGED = <?php echo $logged_in ? 'true' : 'false'; ?>;

    function showToastLJ(msg, ok = true) {
        const t = document.getElementById('lj-toast');
        t.textContent = msg;
        t.style.background = ok ? '#0f172a' : '#ef4444';
        t.classList.add('show');
        setTimeout(() => t.classList.remove('show'), 3200);
    }

    async function buy(amount, btn) {
        if (!IS_LOGGED) { window.location.href = 'auth.php'; return; }
        btn.disabled = true;
        btn.textContent = 'Procesando…';

        const fd = new FormData();
        fd.append('amount', amount);
        fd.append('csrf_token', window.CSRF_TOKEN || document.querySelector('meta[name="csrf-token"]')?.content || '');

        try {
            const res  = await fetch('../api/lujanitos_store.php', { method: 'POST', body: fd });
            const data = await res.json();
            if (data.ok) {
                const disp = document.getElementById('balance-display');
                if (disp) disp.textContent = data.balance.toLocaleString('es-ES');
                showToastLJ('✅ ' + data.message);
            } else {
                showToastLJ('âŒ ' + (data.message || 'Error'), false);
            }
        } catch (e) {
            showToastLJ('âŒ Error de conexión', false);
        }

        btn.disabled = false;
        btn.textContent = 'Comprar';
    }
    </script>

<?php include dirname(__DIR__) . '/includes/footer.php'; ?>
</body>
</html>
