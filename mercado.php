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
    <link rel="stylesheet" href="styles.css">
</head>
<body>

    <nav class="nav-dock">
        <a href="index.php" class="nav-item">🏠</a>
        <a href="mercado.php?game=pokemon" class="nav-item">⚡</a>
        <a href="mercado.php?game=yugioh" class="nav-item">👁️</a>
        <a href="mercado.php?game=magic" class="nav-item">🔥</a>
        <a href="mercado.php?game=onepiece" class="nav-item">☠️</a>
        <div class="spacer"></div>
        <?php if ($nombre_usuario): ?>
            <div class="nav-item user-active">
                👤<span><?php echo htmlspecialchars($nombre_usuario); ?></span>
            </div>
            <a href="logout.php" class="nav-item logout-btn">🚪</a>
        <?php else: ?>
            <a href="auth.php" class="nav-item">👤</a>
        <?php endif; ?>
    </nav>

    <div class="main-wrapper mercado-page">
        <div class="mercado-layout">
            <aside class="filters-sidebar">
                <h3>Filtros</h3>
                <div class="filter-group">
                    <label>Buscar carta:</label>
                    <input type="text" placeholder="Ej. Charizard...">
                </div>
                <div class="filter-group">
                    <label>Rareza:</label>
                    <div><input type="checkbox"> Común</div>
                    <div><input type="checkbox"> Rara</div>
                    <div><input type="checkbox"> Secreta / Ultra</div>
                </div>
                <div class="filter-group">
                    <label>Estado (Condición):</label>
                    <div><input type="checkbox"> Mint</div>
                    <div><input type="checkbox"> Near Mint</div>
                    <div><input type="checkbox"> Played</div>
                </div>
                <button class="btn-main full-width">Aplicar Filtros</button>
            </aside>
            
            <div class="mercado-content">
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
                </div>
                <div class="modal-right">
                    <div class="modal-header">
                        <span id="modal-badge" class="card-badge"></span>
                        <h2 id="modal-title"></h2>
                        <h3 id="modal-price" class="price-big"></h3>
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
                    <button class="btn-main full-width">Añadir al Carrito</button>
                </div>
            </div>
        </div>
    </div>

    <script src="script.js"></script>
</body>
</html>