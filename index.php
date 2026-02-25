<?php
session_start();
$nombre_usuario = isset($_SESSION['username']) ? $_SESSION['username'] : null;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Loot&Trading | Hub de Coleccionistas</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles.css?v=<?php echo time(); ?>">
</head>
<body>

    <nav class="nav-dock">
        <a href="#section-pokemon" class="nav-item">⚡</a>
        <a href="#section-yugioh" class="nav-item">👁️</a>
        <a href="#section-magic" class="nav-item">🔥</a>
        <a href="#section-onepiece" class="nav-item">☠️</a>
        
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

    <div class="main-wrapper">
        <header class="hero">
            <div class="card-wall-bg">
                <div class="wall-column col-up">
                    <div class="wall-img" style="background-image: url('https://images.pokemontcg.io/base1/4_hires.png')"></div>
                    <div class="wall-img" style="background-image: url('https://images.ygoprodeck.com/images/cards/89631139.jpg')"></div>
                    <div class="wall-img" style="background-image: url('https://cards.scryfall.io/large/front/b/d/bd8fa327-dd41-4737-8f19-2cf5eb1f7cdd.jpg')"></div>
                    <div class="wall-img" style="background-image: url('https://images.pokemontcg.io/base1/4_hires.png')"></div>
                    <div class="wall-img" style="background-image: url('https://images.ygoprodeck.com/images/cards/89631139.jpg')"></div>
                    <div class="wall-img" style="background-image: url('https://cards.scryfall.io/large/front/b/d/bd8fa327-dd41-4737-8f19-2cf5eb1f7cdd.jpg')"></div>
                </div>

                <div class="wall-column col-down">
                    <div class="wall-img" style="background-image: url('https://asia-en.onepiece-cardgame.com/images/cardlist/card/OP05-060.png')"></div>
                    <div class="wall-img" style="background-image: url('https://images.pokemontcg.io/base1/2_hires.png')"></div>
                    <div class="wall-img" style="background-image: url('https://images.ygoprodeck.com/images/cards/46986414.jpg')"></div>
                    <div class="wall-img" style="background-image: url('https://asia-en.onepiece-cardgame.com/images/cardlist/card/OP05-060.png')"></div>
                    <div class="wall-img" style="background-image: url('https://images.pokemontcg.io/base1/2_hires.png')"></div>
                    <div class="wall-img" style="background-image: url('https://images.ygoprodeck.com/images/cards/46986414.jpg')"></div>
                </div>

                <div class="wall-column col-up">
                    <div class="wall-img" style="background-image: url('https://cards.scryfall.io/large/front/7/0/70901356-3266-4bd9-aacc-f06c27271de5.jpg')"></div>
                    <div class="wall-img" style="background-image: url('https://asia-en.onepiece-cardgame.com/images/cardlist/card/OP01-025.png')"></div>
                    <div class="wall-img" style="background-image: url('https://images.pokemontcg.io/base1/15_hires.png')"></div>
                    <div class="wall-img" style="background-image: url('https://cards.scryfall.io/large/front/7/0/70901356-3266-4bd9-aacc-f06c27271de5.jpg')"></div>
                    <div class="wall-img" style="background-image: url('https://asia-en.onepiece-cardgame.com/images/cardlist/card/OP01-025.png')"></div>
                    <div class="wall-img" style="background-image: url('https://images.pokemontcg.io/base1/15_hires.png')"></div>
                </div>

                <div class="wall-column col-down">
                    <div class="wall-img" style="background-image: url('https://images.ygoprodeck.com/images/cards/33396948.jpg')"></div>
                    <div class="wall-img" style="background-image: url('https://cards.scryfall.io/large/front/2/3/2398892d-28e9-4009-81ec-0d544af79d2b.jpg')"></div>
                    <div class="wall-img" style="background-image: url('https://asia-en.onepiece-cardgame.com/images/cardlist/card/OP01-120.png')"></div>
                    <div class="wall-img" style="background-image: url('https://images.ygoprodeck.com/images/cards/33396948.jpg')"></div>
                    <div class="wall-img" style="background-image: url('https://cards.scryfall.io/large/front/2/3/2398892d-28e9-4009-81ec-0d544af79d2b.jpg')"></div>
                    <div class="wall-img" style="background-image: url('https://asia-en.onepiece-cardgame.com/images/cardlist/card/OP01-120.png')"></div>
                </div>

                 <div class="wall-column col-up">
                    <div class="wall-img" style="background-image: url('https://images.pokemontcg.io/neo1/9_hires.png')"></div>
                    <div class="wall-img" style="background-image: url('https://images.ygoprodeck.com/images/cards/10000020.jpg')"></div>
                    <div class="wall-img" style="background-image: url('https://cards.scryfall.io/large/front/f/2/f2bc06cb-2f22-4313-82a2-a7e7b2564f4d.jpg')"></div>
                    <div class="wall-img" style="background-image: url('https://images.pokemontcg.io/neo1/9_hires.png')"></div>
                    <div class="wall-img" style="background-image: url('https://images.ygoprodeck.com/images/cards/10000020.jpg')"></div>
                    <div class="wall-img" style="background-image: url('https://cards.scryfall.io/large/front/f/2/f2bc06cb-2f22-4313-82a2-a7e7b2564f4d.jpg')"></div>
                </div>

                <div class="wall-column col-down">
                    <div class="wall-img" style="background-image: url('https://asia-en.onepiece-cardgame.com/images/cardlist/card/OP01-016.png')"></div>
                    <div class="wall-img" style="background-image: url('https://images.pokemontcg.io/swsh7/215_hires.png')"></div>
                    <div class="wall-img" style="background-image: url('https://images.ygoprodeck.com/images/cards/4031928.jpg')"></div>
                    <div class="wall-img" style="background-image: url('https://asia-en.onepiece-cardgame.com/images/cardlist/card/OP01-016.png')"></div>
                    <div class="wall-img" style="background-image: url('https://images.pokemontcg.io/swsh7/215_hires.png')"></div>
                    <div class="wall-img" style="background-image: url('https://images.ygoprodeck.com/images/cards/4031928.jpg')"></div>
                </div>

                <div class="wall-column col-up">
                    <div class="wall-img" style="background-image: url('https://cards.scryfall.io/large/front/e/3/e3285e6b-3e79-4d7c-bf96-d920f973b122.jpg')"></div>
                    <div class="wall-img" style="background-image: url('https://images.pokemontcg.io/swsh11/186_hires.png')"></div>
                    <div class="wall-img" style="background-image: url('https://asia-en.onepiece-cardgame.com/images/cardlist/card/OP01-121.png')"></div>
                    <div class="wall-img" style="background-image: url('https://cards.scryfall.io/large/front/e/3/e3285e6b-3e79-4d7c-bf96-d920f973b122.jpg')"></div>
                    <div class="wall-img" style="background-image: url('https://images.pokemontcg.io/swsh11/186_hires.png')"></div>
                    <div class="wall-img" style="background-image: url('https://asia-en.onepiece-cardgame.com/images/cardlist/card/OP01-121.png')"></div>
                </div>
            </div>

            <div class="hero-content">
                <div class="hero-text">
                    <?php if ($nombre_usuario): ?>
                        <p class="welcome-text">¡Hola de nuevo, <?php echo htmlspecialchars($nombre_usuario); ?>!</p>
                    <?php endif; ?>
                    <h1>Loot&Trading<br><span>Marketplace.</span></h1>
                    <p>El mercado definitivo de TCGs con precios en tiempo real.</p>
                    <a href="#section-pokemon" class="btn-main">Explorar Colecciones</a>
                </div>
            </div>
        </header>

        <main id="main-content">
            <div id="section-pokemon" class="category-section">
                <div class="section-head">
                    <h2>⚡ Pokémon TCG</h2>
                    <p>Cartas más populares y buscadas del mercado.</p>
                </div>
                <div id="pokemon-grid" class="cards-grid"></div>
                <div class="load-more-container">
                    <a href="mercado.php?game=pokemon" class="btn-load">Ver todo el catálogo y filtros</a>
                </div>
            </div>

            <div id="section-yugioh" class="category-section">
                <div class="section-head">
                    <h2>👁️ Yu-Gi-Oh!</h2>
                    <p>Las cartas staple más utilizadas en el formato actual.</p>
                </div>
                <div id="yugioh-grid" class="cards-grid"></div>
                <div class="load-more-container">
                    <a href="mercado.php?game=yugioh" class="btn-load">Ver todo el catálogo y filtros</a>
                </div>
            </div>

            <div id="section-magic" class="category-section">
                <div class="section-head">
                    <h2>🔥 Magic: The Gathering</h2>
                    <p>Cartas legendarias de Commander y Standard.</p>
                </div>
                <div id="magic-grid" class="cards-grid"></div>
                <div class="load-more-container">
                    <a href="mercado.php?game=magic" class="btn-load">Ver todo el catálogo y filtros</a>
                </div>
            </div>

            <div id="section-onepiece" class="category-section">
                <div class="section-head">
                    <h2>☠️ One Piece Card Game</h2>
                    <p>Los líderes y secret rares más valorados.</p>
                </div>
                <div id="onepiece-grid" class="cards-grid"></div>
                <div class="load-more-container">
                    <a href="mercado.php?game=onepiece" class="btn-load">Ver todo el catálogo y filtros</a>
                </div>
            </div>
        </main>
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
                    <button class="btn-main full-width">Añadir mejor oferta al Carrito</button>
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