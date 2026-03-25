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
                    <label>Rareza:</label>
                    <div><input type="checkbox" id="rar-common" value="common"> Común (&lt;$10)</div>
                    <div><input type="checkbox" id="rar-rare" value="rare"> Rara ($10–$50)</div>
                    <div><input type="checkbox" id="rar-ultra" value="ultra"> Secreta / Ultra (&gt;$50)</div>
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
                        <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/1/1a/Pok%C3%A9mon_Trading_Card_Game_logo.svg/1200px-Pok%C3%A9mon_Trading_Card_Game_logo.svg.png" alt="Pokémon">
                    </a>
                    <a href="mercado.php?game=yugioh" class="mgame-tab <?php echo $game==='yugioh'?'active':''; ?>">
                        <img src="https://upload.wikimedia.org/wikipedia/en/thumb/9/9b/Yu-Gi-Oh%21_The_Dark_Side_of_Dimensions_logo.png/250px-Yu-Gi-Oh%21_The_Dark_Side_of_Dimensions_logo.png" alt="Yu-Gi-Oh!" style="height:28px;">
                    </a>
                    <a href="mercado.php?game=magic" class="mgame-tab <?php echo $game==='magic'?'active':''; ?>">
                        <img src="https://upload.wikimedia.org/wikipedia/en/thumb/a/a9/Magic-the-gathering-logo.svg/1280px-Magic-the-gathering-logo.svg.png" alt="Magic">
                    </a>
                    <a href="mercado.php?game=onepiece" class="mgame-tab <?php echo $game==='onepiece'?'active':''; ?>">
                        <img src="https://upload.wikimedia.org/wikipedia/en/thumb/9/90/One_Piece_Card_Game_Logo.png/250px-One_Piece_Card_Game_Logo.png" alt="One Piece">
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
                    <button id="modal-add-best" class="btn-main full-width">Añadir al Carrito</button>
                </div>
            </div>
        </div>
    </div>

    <script src="../assets/js/csrf.js?v=<?php echo time(); ?>"></script>
    <script src="../assets/js/script.js?v=<?php echo time(); ?>"></script>
</body>
</html>