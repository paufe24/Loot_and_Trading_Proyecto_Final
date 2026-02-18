<?php
session_start();
$nombre_usuario = isset($_SESSION['username']) ? $_SESSION['username'] : null;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TCG Verse | Collector's Hub</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
    <style>
        /* Ocultar secciones inactivas — CRÍTICO */
        .game-section {
            display: none !important;
        }
        .game-section.active {
            display: block !important;
        }

        /* Nav activo */
        .nav-item.active {
            background: rgba(255,255,255,0.15);
            border-radius: 12px;
        }

        /* Cards */
        .cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
            gap: 16px;
            padding: 20px;
        }
        .tcg-item {
            background: #1a1a2e;
            border-radius: 12px;
            overflow: hidden;
            cursor: pointer;
            transition: transform 0.2s;
            position: relative;
        }
        .tcg-item:hover {
            transform: translateY(-4px);
        }
        .tcg-item img {
            width: 100%;
            height: 220px;
            object-fit: cover;
            display: block;
        }
        .card-info {
            padding: 8px 10px;
        }
        .card-name {
            font-size: 0.78rem;
            font-weight: 600;
            color: #fff;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .card-price {
            font-size: 0.75rem;
            color: #4ade80;
            margin-top: 2px;
        }
        .card-badge {
            position: absolute;
            top: 6px;
            left: 6px;
            background: rgba(0,0,0,0.7);
            color: #fff;
            font-size: 0.6rem;
            font-weight: bold;
            padding: 2px 7px;
            border-radius: 20px;
            text-transform: uppercase;
        }
        .section-head {
            padding: 30px 20px 10px;
        }
        .section-head h2 {
            font-size: 1.8rem;
            font-weight: 800;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .section-head p {
            color: #888;
            margin-top: 4px;
        }
        .load-more-container {
            text-align: center;
            padding: 20px;
        }
    </style>
</head>
<body>

    <nav class="nav-dock">
        <div class="nav-item active" onclick="switchGame('pokemon')"  title="Pokémon">⚡</div>
        <div class="nav-item"       onclick="switchGame('yugioh')"   title="Yu-Gi-Oh!">👁️</div>
        <div class="nav-item"       onclick="switchGame('magic')"    title="Magic">🔥</div>
        <div class="nav-item"       onclick="switchGame('onepiece')" title="One Piece">☠️</div>

        <div style="flex-grow: 1;"></div>

        <?php if ($nombre_usuario): ?>
            <div class="nav-item" title="Perfil de <?php echo htmlspecialchars($nombre_usuario); ?>" style="color: var(--accent-blue);">
                👤<br><span style="font-size:0.6rem;"><?php echo htmlspecialchars($nombre_usuario); ?></span>
            </div>
            <a href="logout.php" class="nav-item" title="Cerrar Sesión" style="color:#ef4444; text-decoration:none;">🚪</a>
        <?php else: ?>
            <a href="auth.php" class="nav-item" title="Iniciar Sesión" style="text-decoration:none;">👤</a>
        <?php endif; ?>
    </nav>

    <header class="hero">
        <div class="card-wall-bg">
            <div class="wall-column col-up">
                <div class="wall-img" style="background-image: url('https://images.pokemontcg.io/swsh1/1_hires.png')"></div>
                <div class="wall-img" style="background-image: url('https://images.pokemontcg.io/swsh1/2_hires.png')"></div>
            </div>
            <div class="wall-column col-down">
                <div class="wall-img" style="background-image: url('https://images.pokemontcg.io/swsh1/4_hires.png')"></div>
                <div class="wall-img" style="background-image: url('https://images.pokemontcg.io/swsh1/5_hires.png')"></div>
            </div>
            <div class="wall-column col-up">
                <div class="wall-img" style="background-image: url('https://images.pokemontcg.io/swsh1/7_hires.png')"></div>
                <div class="wall-img" style="background-image: url('https://images.pokemontcg.io/swsh1/8_hires.png')"></div>
            </div>
            <div class="wall-column col-down">
                <div class="wall-img" style="background-image: url('https://images.pokemontcg.io/swsh1/10_hires.png')"></div>
                <div class="wall-img" style="background-image: url('https://images.pokemontcg.io/swsh1/11_hires.png')"></div>
            </div>
            <div class="wall-column col-up">
                <div class="wall-img" style="background-image: url('https://images.pokemontcg.io/swsh1/13_hires.png')"></div>
                <div class="wall-img" style="background-image: url('https://images.pokemontcg.io/swsh1/14_hires.png')"></div>
            </div>
        </div>

        <div class="hero-content">
            <div class="hero-text">
                <?php if ($nombre_usuario): ?>
                    <p style="color: var(--accent-blue); font-weight: bold; margin-bottom: 10px;">
                        ¡Hola de nuevo, <?php echo htmlspecialchars($nombre_usuario); ?>!
                    </p>
                <?php endif; ?>
                <h1>Tu Colección<br><span>Sin Límites.</span></h1>
                <p>La base de datos definitiva de TCGs.</p>
                <button onclick="document.getElementById('main-content').scrollIntoView()" class="btn-main">Ver Catálogo</button>
            </div>
        </div>
    </header>

    <main id="main-content" class="category-section">

        <!-- POKÉMON -->
        <div id="section-pokemon" class="game-section active">
            <div class="section-head">
                <h2 style="color: #eab308;">⚡ Pokémon TCG</h2>
                <p>Cartas destacadas de la colección.</p>
            </div>
            <div id="pokemon-grid" class="cards-grid"></div>
            <div class="load-more-container">
                <button class="btn-load" onclick="loadMorePokemonCards()">Cargar Más Pokémon</button>
            </div>
        </div>

        <!-- YU-GI-OH -->
        <div id="section-yugioh" class="game-section">
            <div class="section-head">
                <h2 style="color: #a855f7;">👁️ Yu-Gi-Oh!</h2>
                <p>Base de datos global en tiempo real.</p>
            </div>
            <div id="yugioh-grid" class="cards-grid"></div>
            <div class="load-more-container">
                <button class="btn-load" onclick="loadMoreYugiohCards()">Cargar Más Yu-Gi-Oh!</button>
            </div>
        </div>

        <!-- MAGIC -->
        <div id="section-magic" class="game-section">
            <div class="section-head">
                <h2 style="color: #ef4444;">🔥 Magic: The Gathering</h2>
                <p>Cartas destacadas de la colección.</p>
            </div>
            <div id="magic-grid" class="cards-grid"></div>
            <div class="load-more-container">
                <button class="btn-load" onclick="loadMoreMagicCards()">Cargar Más Magic</button>
            </div>
        </div>

        <!-- ONE PIECE -->
        <div id="section-onepiece" class="game-section">
            <div class="section-head">
                <h2 style="color: #f97316;">☠️ One Piece TCG</h2>
                <p>Cartas destacadas de la colección.</p>
            </div>
            <div id="onepiece-grid" class="cards-grid"></div>
        </div>

    </main>

    <!-- MODAL -->
    <div id="card-modal" class="modal-overlay" style="display:none;">
        <div class="modal-content">
            <button class="close-modal" onclick="closeModal()">×</button>
            <div class="modal-grid">
                <div class="modal-left">
                    <div class="modal-card-wrapper">
                        <img id="modal-img" src="" alt="Carta" class="modal-img">
                        <div class="scan-line"></div>
                    </div>
                </div>
                <div class="modal-right">
                    <div class="modal-header">
                        <span id="modal-badge" class="card-badge">Juego</span>
                        <h2 id="modal-title">Nombre de la Carta</h2>
                        <h3 id="modal-price" class="price-big">$0.00</h3>
                    </div>
                    <div class="price-history-box">
                        <h4>📉 Histórico de Precios (30 días)</h4>
                        <div class="fake-chart">
                            <div class="bar" style="height: 40%"></div>
                            <div class="bar" style="height: 60%"></div>
                            <div class="bar" style="height: 50%"></div>
                            <div class="bar" style="height: 80%"></div>
                            <div class="bar" style="height: 70%"></div>
                            <div class="bar" style="height: 90%"></div>
                            <div class="bar active" style="height: 95%"></div>
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
                    <button class="btn-main full-width">Añadir al Carrito</button>
                </div>
            </div>
            <div class="related-section">
                <h4>Cartas Relacionadas</h4>
                <div id="related-grid" class="cards-grid mini-grid"></div>
            </div>
        </div>
    </div>

    <script src="script.js"></script>
</body>
</html>
