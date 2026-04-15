<?php
session_start();
$nombre_usuario = isset($_SESSION['username']) ? $_SESSION['username'] : null;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Loot&Trading | Mercado Completo</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        .price-chart-section { background: #f8f9fd; border-radius: 14px; padding: 14px 16px; margin-bottom: 16px; }
        body.dark .price-chart-section { background: #0f172a; }
        .price-chart-header { display: flex; justify-content: space-between; align-items: center; font-size: 0.78rem; font-weight: 700; color: #64748b; margin-bottom: 10px; }
        .price-change-badge { font-size: 0.72rem; font-weight: 800; padding: 2px 8px; border-radius: 20px; }
        .price-change-badge.up { background: #dcfce7; color: #16a34a; }
        .price-change-badge.down { background: #fee2e2; color: #dc2626; }
        .cond-rating { display: inline-flex; align-items: center; gap: 4px; font-weight: 800; font-size: 0.75rem; padding: 2px 8px; border-radius: 6px; }
    </style>
</head>
<body>
    <script>(function(){ if(localStorage.getItem('theme')==='dark') document.body.classList.add('dark'); })();</script>

    <?php include dirname(__DIR__) . '/includes/navbar.php'; ?>

    <div class="main-wrapper mercado-page">
        <div class="mercado-layout">
            <aside class="filters-sidebar">
                <h3>Filtros</h3>
                <div class="filter-group">
                    <label>Buscar carta:</label>
                    <input type="text" id="filter-search" placeholder="Ej. Charizard...">
                </div>
                <div class="filter-group">
                    <label>Precio (€):</label>
                    <div style="display:flex;gap:8px;align-items:center;">
                        <input type="number" id="filter-price-min" min="0" step="0.01" placeholder="Mín" style="width:80px;padding:6px 8px;border-radius:8px;border:1px solid #e2e8f0;font-family:'Outfit',sans-serif;font-size:.85rem;">
                        <span style="color:#64748b;">—</span>
                        <input type="number" id="filter-price-max" min="0" step="0.01" placeholder="Máx" style="width:80px;padding:6px 8px;border-radius:8px;border:1px solid #e2e8f0;font-family:'Outfit',sans-serif;font-size:.85rem;">
                    </div>
                </div>
                <div class="filter-group">
                    <label>Estado (Condición):</label>
                    <div><input type="checkbox" id="cond-gem-mint" value="gem-mint"> Gem Mint (10)</div>
                    <div><input type="checkbox" id="cond-mint" value="mint"> Mint (9)</div>
                    <div><input type="checkbox" id="cond-near-mint" value="near-mint"> Near Mint (7-8)</div>
                    <div><input type="checkbox" id="cond-played" value="played"> Played (1-6)</div>
                </div>
                <button class="btn-main full-width" id="apply-filters-btn">Aplicar Filtros</button>
                <button class="btn-main full-width" id="clear-filters-btn" style="background:#64748b;margin-top:8px;">Limpiar</button>
            </aside>
            
            <div class="mercado-content">
                <?php $game = $_GET['game'] ?? 'pokemon'; ?>
                <div class="mercado-game-tabs">
                    <a href="mercado.php?game=pokemon" class="mgame-tab <?php echo $game==='pokemon'?'active':''; ?>">
                        <img src="../img/pokemon.png" alt="Pokémon">
                    </a>
                    <a href="mercado.php?game=yugioh" class="mgame-tab <?php echo $game==='yugioh'?'active':''; ?>">
                        <img src="../img/yugioh.png" alt="Yu-Gi-Oh!">
                    </a>
                    <a href="mercado.php?game=magic" class="mgame-tab <?php echo $game==='magic'?'active':''; ?>">
                        <img src="../img/magic.png" alt="Magic">
                    </a>
                    <a href="mercado.php?game=onepiece" class="mgame-tab <?php echo $game==='onepiece'?'active':''; ?>">
                        <img src="../img/onepiece.png" alt="One Piece">
                    </a>
                </div>
                <div class="section-head">
                    <h2 id="mercado-title">Cargando Mercado...</h2>
                    <p>Catálogo completo con todas las expansiones.</p>
                </div>
                <div id="mercado-grid" class="cards-grid"></div>
                <div class="load-more-container">
                    <button id="mercado-load-more" class="btn-main">Cargar más cartas</button>
                </div>
            </div>
        </div>
    </div>

    <div id="card-modal" class="modal-overlay">
        <div class="modal-content">
            <button class="close-modal" id="close-modal-btn">×</button>
            <div class="modal-grid">
                <div class="modal-left">
                    <div class="modal-card-wrapper">
                        <img id="modal-img" src="" alt="Carta" class="modal-img">
                        <div class="scan-line"></div>
                    </div>
                    <button id="modal-toggle-fav" class="btn-cart modal-fav-btn" type="button">⭐ Añadir a favoritos</button>
                    <button onclick="openMarketAlert()" style="width:100%;padding:10px;border-radius:12px;border:1.5px solid #e2e8f0;background:none;font-size:.82rem;font-weight:700;cursor:pointer;color:#64748b;font-family:'Outfit',sans-serif;">🔔 Alerta de precio</button>
                </div>
                <div class="modal-right">
                    <div class="modal-header">
                        <span id="modal-badge" class="card-badge"></span>
                        <h2 id="modal-title"></h2>
                        <h3 id="modal-price" class="price-big"></h3>
                    </div>
                    <div class="price-chart-section">
                        <div class="price-chart-header">
                            <span>📈 Histórico de Precios (30 días)</span>
                            <span id="price-chart-change" class="price-change-badge"></span>
                        </div>
                        <div style="position:relative;height:140px;">
                            <canvas id="price-chart"></canvas>
                        </div>
                    </div>
                    <div class="market-table-container">
                        <h4>🛒 Ofertas de Vendedores</h4>
                        <table class="market-table">
                            <thead>
                                <tr><th>Vendedor</th><th>Estado</th><th>Precio</th><th>Acción</th></tr>
                            </thead>
                            <tbody id="market-list"></tbody>
                        </table>
                    </div>
                    <div class="modal-btns-sticky">
                        <button id="modal-add-best" class="btn-main full-width" style="margin-top:0;">Añadir mejor oferta al Carrito</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Alerta de Precio (Mercado) -->
    <div id="market-alert-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:9000;align-items:center;justify-content:center;">
        <div style="background:var(--bg-card,#fff);border-radius:24px;padding:32px;width:340px;max-width:95vw;box-shadow:0 24px 80px rgba(0,0,0,.25);">
            <h3 style="font-size:1.2rem;font-weight:800;margin-bottom:6px;">🔔 Alerta de precio</h3>
            <p style="color:#64748b;font-size:.88rem;margin-bottom:18px;">
                Te notificaremos cuando <strong id="market-alert-name"></strong> aparezca en subasta a tu precio objetivo o menos.
            </p>
            <label style="font-size:.82rem;font-weight:700;color:#0f172a;">Precio objetivo (LC)</label>
            <input type="number" id="market-alert-price" min="1" placeholder="Ej: 300"
                   style="width:100%;margin:6px 0 18px;padding:12px 14px;border-radius:12px;border:1px solid #e2e8f0;font-size:1rem;font-family:'Outfit',sans-serif;box-sizing:border-box;">
            <div style="display:flex;gap:10px;">
                <button onclick="closeMarketAlert()" style="flex:1;padding:12px;border-radius:12px;border:2px solid #e2e8f0;background:none;font-weight:700;cursor:pointer;font-family:'Outfit',sans-serif;">Cancelar</button>
                <button onclick="submitMarketAlert()" style="flex:1;padding:12px;border-radius:12px;background:#3b82f6;color:#fff;border:none;font-weight:800;cursor:pointer;font-family:'Outfit',sans-serif;">Crear alerta</button>
            </div>
        </div>
    </div>

    <script src="../assets/js/csrf.js?v=<?php echo time(); ?>"></script>
    <script src="../assets/js/script.js?v=<?php echo time(); ?>"></script>
    <script>
    const MARKET_LOGGED_IN = <?php echo isset($_SESSION['user_id']) ? 'true' : 'false'; ?>;

    function openMarketAlert() {
        if (!MARKET_LOGGED_IN) { window.location.href = 'auth.php'; return; }
        const name = document.getElementById('modal-title').textContent;
        document.getElementById('market-alert-name').textContent = name;
        document.getElementById('market-alert-price').value = 100;
        document.getElementById('market-alert-modal').style.display = 'flex';
    }
    function closeMarketAlert() {
        document.getElementById('market-alert-modal').style.display = 'none';
    }
    document.getElementById('market-alert-modal').addEventListener('click', function(e) {
        if (e.target === this) closeMarketAlert();
    });
    async function submitMarketAlert() {
        const name  = document.getElementById('modal-title').textContent;
        const game  = document.getElementById('modal-badge').textContent;
        const price = parseInt(document.getElementById('market-alert-price').value);
        if (!price || price <= 0) { alert('Introduce un precio válido'); return; }
        const fd = new FormData();
        fd.append('action',       'create');
        fd.append('card_name',    name);
        fd.append('card_game',    game);
        fd.append('target_price', price);
        fd.append('csrf_token',   document.querySelector('meta[name="csrf-token"]')?.content || '');
        const res  = await fetch('../api/price_alerts.php', { method: 'POST', body: fd });
        const data = await res.json();
        closeMarketAlert();
        if (data.ok) {
            const toast = document.createElement('div');
            toast.textContent = '✅ Alerta creada — te avisaremos en tu perfil';
            toast.style.cssText = 'position:fixed;bottom:24px;left:50%;transform:translateX(-50%);background:#0f172a;color:#fff;padding:12px 24px;border-radius:50px;font-weight:700;font-size:.88rem;z-index:9999;';
            document.body.appendChild(toast);
            setTimeout(() => toast.remove(), 3000);
        } else {
            alert(data.message || 'Error al crear la alerta');
        }
    }
    </script>

<?php include dirname(__DIR__) . '/includes/footer.php'; ?>
</body>
</html>