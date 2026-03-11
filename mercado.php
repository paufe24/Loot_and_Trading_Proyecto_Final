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
    <script>(function(){ if(localStorage.getItem('theme')==='dark') document.body.classList.add('dark'); })();</script>

    <nav class="nav-dock">
        <div class="spacer"></div>
        <a href="index.php" class="nav-item" title="Inicio">
            <svg class="nav-icon" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955a1.126 1.126 0 0 1 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25"/></svg>
        </a>
        <a href="mercado.php?game=pokemon" class="nav-item" title="Pokémon"><img src="img/pokemon.png" alt="Pokémon" class="nav-logo"></a>
        <a href="mercado.php?game=yugioh" class="nav-item" title="Yu-Gi-Oh!"><img src="img/yugioh.png" alt="Yu-Gi-Oh!" class="nav-logo"></a>
        <a href="mercado.php?game=magic" class="nav-item" title="Magic: The Gathering"><img src="img/magic.png" alt="Magic" class="nav-logo"></a>
        <a href="mercado.php?game=onepiece" class="nav-item" title="One Piece"><img src="img/onepiece.png" alt="One Piece" class="nav-logo"></a>
        <div class="spacer"></div>
        <button class="nav-item dark-toggle" onclick="toggleDarkMode()" title="Cambiar tema">
            <svg class="nav-icon icon-sun" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><circle cx="12" cy="12" r="5"/><path stroke-linecap="round" d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"/></svg>
            <svg class="nav-icon icon-moon" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
        </button>
        <a href="cart.php" class="nav-item" title="Carrito">
            🛒
        </a>
        <?php if ($nombre_usuario): ?>
            <a href="profile.php" class="nav-item user-active" title="Ver Perfil">
                <svg class="nav-icon" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z"/></svg>
                <?php echo htmlspecialchars($nombre_usuario); ?>
            </a>
            <a href="logout.php" class="nav-item logout-btn" title="Cerrar Sesión">
                <svg class="nav-icon" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9"/></svg>
            </a>
        <?php else: ?>
            <a href="auth.php" class="nav-item" title="Iniciar Sesión">
                <svg class="nav-icon" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z"/></svg>
            </a>
        <?php endif; ?>
    </nav>

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
                    <button id="modal-toggle-fav" class="btn-cart modal-fav-btn" type="button">⭐ Añadir a favoritos</button>
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
                    <button id="modal-add-best" class="btn-main full-width">Añadir al Carrito</button>
                </div>
            </div>
        </div>
    </div>

    <script src="script.js?v=<?php echo time(); ?>"></script>
</body>
</html>