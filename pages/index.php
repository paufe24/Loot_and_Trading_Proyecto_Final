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
    <link rel="stylesheet" href="../assets/css/styles.css?v=<?php echo time(); ?>">
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

    <div class="main-wrapper">
        <header class="hero">
            <div class="card-wall-bg">
                <div class="wall-column col-up">
                    <div class="wall-img" style="background-image: url('https://images.pokemontcg.io/base1/4_hires.png')"></div>
                    <div class="wall-img" style="background-image: url('https://images.ygoprodeck.com/images/cards/89631139.jpg')"></div>
                    <div class="wall-img" style="background-image: url('https://cards.scryfall.io/large/front/b/d/bd8fa327-dd41-4737-8f19-2cf5eb1f7cdd.jpg')"></div>
                    <div class="wall-img" style="background-image: url('https://images.pokemontcg.io/base1/4_hires.png')"></div>
                    <div class="wall-img" style="background-image: url('https://images.ygoprodeck.com/images/cards/89631139.jpg')"></div>
                </div>
                <div class="wall-column col-down">
                    <div class="wall-img" style="background-image: url('https://asia-en.onepiece-cardgame.com/images/cardlist/card/OP05-060.png')"></div>
                    <div class="wall-img" style="background-image: url('https://images.pokemontcg.io/base1/2_hires.png')"></div>
                    <div class="wall-img" style="background-image: url('https://images.ygoprodeck.com/images/cards/46986414.jpg')"></div>
                    <div class="wall-img" style="background-image: url('https://asia-en.onepiece-cardgame.com/images/cardlist/card/OP05-060.png')"></div>
                    <div class="wall-img" style="background-image: url('https://images.pokemontcg.io/base1/2_hires.png')"></div>
                </div>
                <div class="wall-column col-up">
                    <div class="wall-img" style="background-image: url('https://cards.scryfall.io/large/front/7/0/70901356-3266-4bd9-aacc-f06c27271de5.jpg')"></div>
                    <div class="wall-img" style="background-image: url('https://asia-en.onepiece-cardgame.com/images/cardlist/card/OP01-025.png')"></div>
                    <div class="wall-img" style="background-image: url('https://images.pokemontcg.io/base1/15_hires.png')"></div>
                    <div class="wall-img" style="background-image: url('https://cards.scryfall.io/large/front/7/0/70901356-3266-4bd9-aacc-f06c27271de5.jpg')"></div>
                    <div class="wall-img" style="background-image: url('https://asia-en.onepiece-cardgame.com/images/cardlist/card/OP01-025.png')"></div>
                </div>
                <div class="wall-column col-down">
                    <div class="wall-img" style="background-image: url('https://images.ygoprodeck.com/images/cards/33396948.jpg')"></div>
                    <div class="wall-img" style="background-image: url('https://cards.scryfall.io/large/front/2/3/2398892d-28e9-4009-81ec-0d544af79d2b.jpg')"></div>
                    <div class="wall-img" style="background-image: url('https://asia-en.onepiece-cardgame.com/images/cardlist/card/OP01-120.png')"></div>
                    <div class="wall-img" style="background-image: url('https://images.ygoprodeck.com/images/cards/33396948.jpg')"></div>
                    <div class="wall-img" style="background-image: url('https://cards.scryfall.io/large/front/2/3/2398892d-28e9-4009-81ec-0d544af79d2b.jpg')"></div>
                </div>
                <div class="wall-column col-up">
                    <div class="wall-img" style="background-image: url('https://images.pokemontcg.io/neo1/9_hires.png')"></div>
                    <div class="wall-img" style="background-image: url('https://images.ygoprodeck.com/images/cards/10000020.jpg')"></div>
                    <div class="wall-img" style="background-image: url('https://cards.scryfall.io/large/front/f/2/f2bc06cb-2f22-4313-82a2-a7e7b2564f4d.jpg')"></div>
                    <div class="wall-img" style="background-image: url('https://images.pokemontcg.io/neo1/9_hires.png')"></div>
                    <div class="wall-img" style="background-image: url('https://images.ygoprodeck.com/images/cards/10000020.jpg')"></div>
                </div>
                <div class="wall-column col-down">
                    <div class="wall-img" style="background-image: url('https://asia-en.onepiece-cardgame.com/images/cardlist/card/OP01-016.png')"></div>
                    <div class="wall-img" style="background-image: url('https://images.pokemontcg.io/swsh7/215_hires.png')"></div>
                    <div class="wall-img" style="background-image: url('https://images.ygoprodeck.com/images/cards/4031928.jpg')"></div>
                    <div class="wall-img" style="background-image: url('https://asia-en.onepiece-cardgame.com/images/cardlist/card/OP01-016.png')"></div>
                    <div class="wall-img" style="background-image: url('https://images.pokemontcg.io/swsh7/215_hires.png')"></div>
                </div>
                <div class="wall-column col-up">
                    <div class="wall-img" style="background-image: url('https://cards.scryfall.io/large/front/e/3/e3285e6b-3e79-4d7c-bf96-d920f973b122.jpg')"></div>
                    <div class="wall-img" style="background-image: url('https://images.pokemontcg.io/swsh11/186_hires.png')"></div>
                    <div class="wall-img" style="background-image: url('https://asia-en.onepiece-cardgame.com/images/cardlist/card/OP01-121.png')"></div>
                    <div class="wall-img" style="background-image: url('https://cards.scryfall.io/large/front/e/3/e3285e6b-3e79-4d7c-bf96-d920f973b122.jpg')"></div>
                    <div class="wall-img" style="background-image: url('https://images.pokemontcg.io/swsh11/186_hires.png')"></div>
                </div>
                <div class="wall-column col-down">
                    <div class="wall-img" style="background-image: url('https://images.pokemontcg.io/base1/2_hires.png')"></div>
                    <div class="wall-img" style="background-image: url('https://images.ygoprodeck.com/images/cards/46986414.jpg')"></div>
                    <div class="wall-img" style="background-image: url('https://cards.scryfall.io/large/front/b/d/bd8fa327-dd41-4737-8f19-2cf5eb1f7cdd.jpg')"></div>
                    <div class="wall-img" style="background-image: url('https://asia-en.onepiece-cardgame.com/images/cardlist/card/OP05-060.png')"></div>
                    <div class="wall-img" style="background-image: url('https://images.ygoprodeck.com/images/cards/89631139.jpg')"></div>
                </div>
            </div>

            <div class="hero-content" style="margin: 0 auto;">
                <div class="hero-text">
                    <?php if ($nombre_usuario): ?>
                        <p class="welcome-text">¡Hola de nuevo, <?php echo htmlspecialchars($nombre_usuario); ?>!</p>
                    <?php endif; ?>
                    <h1>Loot&Trading<br><span>Marketplace.</span></h1>
                    <p>El mercado definitivo de TCGs con precios en tiempo real.</p>
                    <div style="display: flex; gap: 20px; justify-content: center; align-items: center; margin-top: 30px; flex-wrap: wrap; width: 100%;">
                        <a href="#section-pokemon" class="btn-main" style="margin-top: 0;">Explorar Colecciones</a>
                        <a href="mercado.php?game=pokemon" class="btn-main" style="margin-top: 0; background: linear-gradient(135deg, #3b82f6 0%, #06b6d4 100%); color: white; box-shadow: 0 10px 25px rgba(59,130,246,0.3);">🔍 Buscar en el Mercado</a>
                        <a href="apuestas.php" class="btn-main" style="margin-top: 0; background: linear-gradient(135deg, #8b5cf6 0%, #d946ef 100%); color: white; box-shadow: 0 10px 25px rgba(139, 92, 246, 0.3);">🔮 Entrar a TCG Predictor</a>
                    </div>
                </div>
            </div>
        </header>

        <!-- ===== CARTA DEL DÍA ===== -->
        <section class="cod-section">
            <div class="cod-inner">
                <div class="cod-left">
                    <div class="cod-eyebrow">Carta del día</div>
                    <div class="cod-card-wrap" id="cod-card-wrap" onclick="codOpenModal()" style="cursor:pointer;">
                        <img id="cod-img" src="" alt="Carta del día">
                        <span class="cod-badge" id="cod-badge"></span>
                    </div>
                </div>
                <div class="cod-right">
                    <h2 class="cod-name" id="cod-name">Cargando...</h2>
                    <div class="cod-price-row">
                        <span class="cod-price" id="cod-price">—</span>
                        <span class="cod-trend" id="cod-trend"></span>
                    </div>
                    <p class="cod-desc" id="cod-desc"></p>
                    <div class="cod-opinions-wrap">
                        <div class="cod-opinions-label">Lo que dice la comunidad</div>
                        <div class="cod-opinion-box" id="cod-opinion-box">
                            <div class="cod-opinion-text" id="cod-opinion-text"></div>
                            <div class="cod-opinion-author" id="cod-opinion-author"></div>
                        </div>
                        <div class="cod-dots" id="cod-dots"></div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ===== LO MÁS BUSCADO ===== -->
        <section class="trending-section">
            <div class="trending-inner">
                <div class="trending-header">
                    <h2>🔥 Lo más buscado esta semana</h2>
                    <a href="mercado.php?game=pokemon" class="trending-see-all">Ver todo el mercado →</a>
                </div>
                <div class="trending-scroll">
                    <div class="trending-card" onclick="openModal({card_id:'base1-4',id:'base1-4',name:'Charizard Base Set',img:'https://images.pokemontcg.io/base1/4_hires.png',badge:'Pokémon',color:'#e63329',price:'420.00'})">
                        <img src="https://images.pokemontcg.io/base1/4_hires.png" alt="Charizard">
                        <div class="trending-info">
                            <span class="trending-badge" style="background:#fef3c7;color:#b45309;">Pokémon</span>
                            <p class="trending-name">Charizard Base Set</p>
                            <div class="trending-price-row">
                                <span class="trending-price">$420</span>
                                <span class="trend-badge up">↑ +5%</span>
                            </div>
                        </div>
                    </div>
                    <div class="trending-card" onclick="openModal({card_id:'89631139',id:'89631139',name:'Blue-Eyes White Dragon',img:'https://images.ygoprodeck.com/images/cards/89631139.jpg',badge:'Yu-Gi-Oh!',color:'#a855f7',price:'85.00'})">
                        <img src="https://images.ygoprodeck.com/images/cards/89631139.jpg" alt="Blue-Eyes">
                        <div class="trending-info">
                            <span class="trending-badge" style="background:#f3e8ff;color:#7c3aed;">Yu-Gi-Oh!</span>
                            <p class="trending-name">Blue-Eyes White Dragon</p>
                            <div class="trending-price-row">
                                <span class="trending-price">$85</span>
                                <span class="trend-badge up">↑ +3%</span>
                            </div>
                        </div>
                    </div>
                    <div class="trending-card" onclick="openModal({card_id:'bd8fa327',id:'bd8fa327',name:'Black Lotus',img:'https://cards.scryfall.io/large/front/b/d/bd8fa327-dd41-4737-8f19-2cf5eb1f7cdd.jpg',badge:'Magic',color:'#ef4444',price:'15000.00'})">
                        <img src="https://cards.scryfall.io/large/front/b/d/bd8fa327-dd41-4737-8f19-2cf5eb1f7cdd.jpg" alt="Black Lotus">
                        <div class="trending-info">
                            <span class="trending-badge" style="background:#fee2e2;color:#b91c1c;">Magic</span>
                            <p class="trending-name">Black Lotus</p>
                            <div class="trending-price-row">
                                <span class="trending-price">$15.000</span>
                                <span class="trend-badge up">↑ +2%</span>
                            </div>
                        </div>
                    </div>
                    <div class="trending-card" onclick="openModal({card_id:'OP05-119',id:'OP05-119',name:'Luffy Manga Alt Art',img:'https://asia-en.onepiece-cardgame.com/images/cardlist/card/OP05-119.png',badge:'One Piece',color:'#f97316',price:'2100.00'})">
                        <img src="https://asia-en.onepiece-cardgame.com/images/cardlist/card/OP05-119.png" alt="Luffy Manga">
                        <div class="trending-info">
                            <span class="trending-badge" style="background:#ffedd5;color:#c2410c;">One Piece</span>
                            <p class="trending-name">Luffy Manga Alt Art</p>
                            <div class="trending-price-row">
                                <span class="trending-price">$2.100</span>
                                <span class="trend-badge up">↑ +31%</span>
                            </div>
                        </div>
                    </div>
                    <div class="trending-card" onclick="openModal({card_id:'swsh7-215',id:'swsh7-215',name:'Umbreon VMAX Alt Art',img:'https://images.pokemontcg.io/swsh7/215_hires.png',badge:'Pokémon',color:'#e63329',price:'950.00'})">
                        <img src="https://images.pokemontcg.io/swsh7/215_hires.png" alt="Umbreon VMAX">
                        <div class="trending-info">
                            <span class="trending-badge" style="background:#fef3c7;color:#b45309;">Pokémon</span>
                            <p class="trending-name">Umbreon VMAX Alt Art</p>
                            <div class="trending-price-row">
                                <span class="trending-price">$950</span>
                                <span class="trend-badge up">↑ +18%</span>
                            </div>
                        </div>
                    </div>
                    <div class="trending-card" onclick="openModal({card_id:'OP06-118',id:'OP06-118',name:'Zoro Manga Alt Art',img:'https://asia-en.onepiece-cardgame.com/images/cardlist/card/OP06-118.png',badge:'One Piece',color:'#f97316',price:'980.00'})">
                        <img src="https://asia-en.onepiece-cardgame.com/images/cardlist/card/OP06-118.png" alt="Zoro Manga">
                        <div class="trending-info">
                            <span class="trending-badge" style="background:#ffedd5;color:#c2410c;">One Piece</span>
                            <p class="trending-name">Zoro Manga Alt Art</p>
                            <div class="trending-price-row">
                                <span class="trending-price">$980</span>
                                <span class="trend-badge up">↑ +22%</span>
                            </div>
                        </div>
                    </div>
                    <div class="trending-card" onclick="openModal({card_id:'38033121',id:'38033121',name:'Dark Magician Girl 1st Ed',img:'https://images.ygoprodeck.com/images/cards/38033121.jpg',badge:'Yu-Gi-Oh!',color:'#a855f7',price:'290.00'})">
                        <img src="https://images.ygoprodeck.com/images/cards/38033121.jpg" alt="Dark Magician Girl">
                        <div class="trending-info">
                            <span class="trending-badge" style="background:#f3e8ff;color:#7c3aed;">Yu-Gi-Oh!</span>
                            <p class="trending-name">Dark Magician Girl 1st Ed</p>
                            <div class="trending-price-row">
                                <span class="trending-price">$290</span>
                                <span class="trend-badge up">↑ +12%</span>
                            </div>
                        </div>
                    </div>
                    <div class="trending-card" onclick="openModal({card_id:'swsh12-186',id:'swsh12-186',name:'Lugia V Alt Art',img:'https://images.pokemontcg.io/swsh12/186_hires.png',badge:'Pokémon',color:'#e63329',price:'180.00'})">
                        <img src="https://images.pokemontcg.io/swsh12/186_hires.png" alt="Lugia V">
                        <div class="trending-info">
                            <span class="trending-badge" style="background:#fef3c7;color:#b45309;">Pokémon</span>
                            <p class="trending-name">Lugia V Alt Art</p>
                            <div class="trending-price-row">
                                <span class="trending-price">$180</span>
                                <span class="trend-badge down">↓ -4%</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ===== STATS BANNER ===== -->
        <section class="stats-banner">
            <div class="stats-inner">
                <div class="stat-item">
                    <span class="stat-num">12.000+</span>
                    <span class="stat-label">Cartas disponibles</span>
                </div>
                <div class="stat-divider"></div>
                <div class="stat-item">
                    <span class="stat-num">4</span>
                    <span class="stat-label">TCGs soportados</span>
                </div>
                <div class="stat-divider"></div>
                <div class="stat-item">
                    <span class="stat-num">Real-time</span>
                    <span class="stat-label">Precios actualizados</span>
                </div>
                <div class="stat-divider"></div>
                <div class="stat-item">
                    <span class="stat-num">100%</span>
                    <span class="stat-label">Gratis para coleccionistas</span>
                </div>
            </div>
        </section>

        <main id="main-content">
            <div id="section-pokemon" class="category-section">
                <div class="section-head">
                    <img src="img/pokemon.png" alt="Pokémon" style="height:60px;display:block;margin:0 auto 10px;">
                </div>
                <div id="pokemon-grid" class="cards-grid"></div>
                <div class="load-more-container">
                    <a href="mercado.php?game=pokemon" class="btn-load">Ver todo el catálogo y filtros</a>
                </div>
            </div>

            <div id="section-yugioh" class="category-section">
                <div class="section-head">
                    <img src="img/yugioh.png" alt="Yu-Gi-Oh!" style="height:60px;display:block;margin:0 auto 10px;">
                </div>
                <div id="yugioh-grid" class="cards-grid"></div>
                <div class="load-more-container">
                    <a href="mercado.php?game=yugioh" class="btn-load">Ver todo el catálogo y filtros</a>
                </div>
            </div>

            <div id="section-magic" class="category-section">
                <div class="section-head">
                    <img src="img/magic.png" alt="Magic: The Gathering" style="height:60px;display:block;margin:0 auto 10px;">
                </div>
                <div id="magic-grid" class="cards-grid"></div>
                <div class="load-more-container">
                    <a href="mercado.php?game=magic" class="btn-load">Ver todo el catálogo y filtros</a>
                </div>
            </div>

            <div id="section-onepiece" class="category-section">
                <div class="section-head">
                    <img src="img/onepiece.png" alt="One Piece" style="height:60px;display:block;margin:0 auto 10px;">
                </div>
                <div id="onepiece-grid" class="cards-grid"></div>
                <div class="load-more-container">
                    <a href="mercado.php?game=onepiece" class="btn-load">Ver todo el catálogo y filtros</a>
                </div>
            </div>
        </main>
    </div>

    <div id="card-modal" class="modal-overlay" style="align-items:flex-start;overflow-y:auto;padding:40px 20px;">
        <div class="modal-content" style="margin:auto;flex-shrink:0;">
            <button class="close-modal" id="close-modal-btn" onclick="closeModal()">×</button>
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
                    <button id="modal-add-best" class="btn-main full-width">Añadir mejor oferta al Carrito</button>
                </div>
            </div>
        </div>
        <div class="related-section">
            <h4>Cartas Relacionadas</h4>
            <div id="related-grid" class="cards-grid mini-grid"></div>
        </div>
    </div>
</div>

<script src="../assets/js/csrf.js?v=<?php echo time(); ?>"></script>
    <script src="../assets/js/script.js?v=<?php echo time(); ?>"></script>
</body>
</html>
