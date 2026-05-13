<?php
session_start();
$nombre_usuario = isset($_SESSION['username']) ? $_SESSION['username'] : null;
require_once dirname(__DIR__) . '/includes/db.php';
try { $conn->query("CREATE TABLE IF NOT EXISTS events (id INT AUTO_INCREMENT PRIMARY KEY, title VARCHAR(255) NOT NULL, description TEXT, location VARCHAR(255), event_date DATE NOT NULL, event_time VARCHAR(30) DEFAULT '', participants VARCHAR(100) DEFAULT '', game_type VARCHAR(50) DEFAULT 'multi', event_type VARCHAR(50) DEFAULT 'quedada', we_participate TINYINT(1) NOT NULL DEFAULT 0, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)"); } catch (Exception $e) {}
$chkEv = $conn->query("SELECT COUNT(*) as c FROM events");
if ($chkEv && $chkEv->fetch_assoc()['c'] == 0) {
    $conn->query("INSERT INTO events (title, description, location, event_date, event_time, participants, game_type, event_type, we_participate) VALUES
        ('Barcelona TCG Open 2025', 'Torneo oficial Pokémon VGC formato Regulación H. Premios en metálico y packs exclusivos para el top 8.', 'Fira Barcelona, Pabellón 3', '2026-05-17', '10:00h – 20:00h', '128 participantes', 'pokemon', 'torneo', 1),
        ('Quedada Magic: Draft Commander', 'Draft de Commander informal. Trae tus mazos o únete al draft exprés que organizamos. Entrada libre.', 'GameZone Madrid, Calle Mayor 12', '2026-05-24', '16:00h – 22:00h', '~30 jugadores', 'magic', 'quedada', 0),
        ('One Piece TCG Regional Valencia', 'Regional oficial One Piece Card Game. Clasificatorio para el Championship europeo. Formato estándar OP09.', 'Palau Velòdrom, Valencia', '2026-05-31', '09:00h – 19:00h', '64 participantes', 'onepiece', 'torneo', 1),
        ('Quedada Intercambio TCG Barcelona', 'Jornada de intercambio multijuego: Pokémon, Yu-Gi-Oh!, Magic y One Piece. Trae tus cartas duplicadas y llévate lo que necesitas.', 'Biblioteca Jaume Fuster, Barcelona', '2026-06-07', '13:00h – 18:00h', 'Entrada libre', 'yugioh', 'intercambio', 0)
    ");
}
$events_db = [];
$evRes = $conn->query("SELECT * FROM events ORDER BY event_date ASC");
if ($evRes) while ($row = $evRes->fetch_assoc()) $events_db[] = $row;
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

        /* ===== SECCIÓN EVENTOS ===== */
        .events-section { padding: 72px 24px; background: var(--bg-main, #f8fafc); }
        body.dark .events-section { background: #0f172a; }
        .events-inner { max-width: 1100px; margin: 0 auto; }
        .events-header { display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 36px; flex-wrap: wrap; gap: 12px; }
        .events-header h2 { font-size: 1.8rem; font-weight: 800; margin: 0 0 4px; }
        .events-subtitle { color: #64748b; font-size: .95rem; margin: 0; }
        .events-we-badge { background: linear-gradient(135deg,#f59e0b,#d97706); color:#fff; font-weight:800; font-size:.8rem; padding:8px 18px; border-radius:50px; white-space:nowrap; align-self:center; }

        .events-grid { display: grid; grid-template-columns: 1fr; gap: 16px; }
        @media(min-width:700px) { .events-grid { grid-template-columns: 1fr 1fr; } }

        .event-card { display: flex; gap: 20px; background: #fff; border: 1px solid #e2e8f0; border-radius: 20px; padding: 22px 24px; transition: box-shadow .2s, transform .2s; }
        .event-card:hover { box-shadow: 0 12px 40px rgba(0,0,0,.10); transform: translateY(-3px); }
        .event-card.event-featured { border-color: #f59e0b; box-shadow: 0 4px 20px rgba(245,158,11,.15); }
        body.dark .event-card { background: #1e293b; border-color: #334155; }
        body.dark .event-card.event-featured { border-color: #d97706; }

        .event-date-block { flex-shrink:0; width:56px; height:56px; background:#e63329; border-radius:14px; display:flex; flex-direction:column; align-items:center; justify-content:center; color:#fff; }
        .event-day  { font-size:1.3rem; font-weight:800; line-height:1; }
        .event-month{ font-size:.6rem; font-weight:800; letter-spacing:.5px; }

        .event-info { flex:1; min-width:0; }
        .event-tags { display:flex; flex-wrap:wrap; gap:6px; margin-bottom:8px; }
        .event-tag  { font-size:.68rem; font-weight:800; padding:2px 9px; border-radius:20px; }
        .event-tag.pokemon   { background:#fef3c7; color:#b45309; }
        .event-tag.yugioh    { background:#f3e8ff; color:#7c3aed; }
        .event-tag.magic     { background:#ede9fe; color:#6d28d9; }
        .event-tag.onepiece  { background:#ffedd5; color:#c2410c; }
        .event-tag.torneo    { background:#dbeafe; color:#1d4ed8; }
        .event-tag.quedada   { background:#d1fae5; color:#065f46; }
        .event-tag.intercambio{ background:#e0f2fe; color:#0369a1; }
        .event-tag.nosotros  { background:#fef9c3; color:#854d0e; }

        .event-name { font-size:1rem; font-weight:800; margin:0 0 6px; color:#0f172a; }
        body.dark .event-name { color:#f1f5f9; }
        .event-desc { font-size:.83rem; color:#64748b; margin:0 0 12px; line-height:1.5; }
        .event-meta { display:flex; flex-wrap:wrap; gap:10px; font-size:.76rem; font-weight:600; color:#94a3b8; }
        .event-meta span { display:flex; align-items:center; gap:3px; }

        /* ===== STATS ANIMACIÓN ===== */
        .stat-animate { opacity: 0; transform: translateY(24px); transition: opacity .6s ease, transform .6s ease; }
        .stat-animate.visible { opacity: 1; transform: translateY(0); }
        .stat-animate:nth-child(1) { transition-delay: 0s; }
        .stat-animate:nth-child(3) { transition-delay: .12s; }
        .stat-animate:nth-child(5) { transition-delay: .24s; }
        .stat-animate:nth-child(7) { transition-delay: .36s; }
    </style>
</head>
<body>
    <script>(function(){ if(localStorage.getItem('theme')==='dark') document.body.classList.add('dark'); })();</script>

    <?php include dirname(__DIR__) . '/includes/navbar.php'; ?>

    <div class="main-wrapper">
        <header class="hero">
            <div class="card-wall-bg">
                <!-- Columna 1 ↑ -->
                <div class="wall-column col-up">
                    <div class="wall-img" style="background-image: url('https://images.pokemontcg.io/base1/4_hires.png')"></div>
                    <div class="wall-img" style="background-image: url('https://images.ygoprodeck.com/images/cards/89631139.jpg')"></div>
                    <div class="wall-img" style="background-image: url('https://cards.scryfall.io/large/front/b/d/bd8fa327-dd41-4737-8f19-2cf5eb1f7cdd.jpg')"></div>
                    <div class="wall-img" style="background-image: url('https://images.pokemontcg.io/swsh7/215_hires.png')"></div>
                    <div class="wall-img" style="background-image: url('https://images.ygoprodeck.com/images/cards/38033121.jpg')"></div>
                    <div class="wall-img" style="background-image: url('https://cards.scryfall.io/large/front/e/3/e3285e6b-3e79-4d7c-bf96-d920f973b122.jpg')"></div>
                    <!-- repeat -->
                    <div class="wall-img" style="background-image: url('https://images.pokemontcg.io/base1/4_hires.png')"></div>
                    <div class="wall-img" style="background-image: url('https://images.ygoprodeck.com/images/cards/89631139.jpg')"></div>
                    <div class="wall-img" style="background-image: url('https://cards.scryfall.io/large/front/b/d/bd8fa327-dd41-4737-8f19-2cf5eb1f7cdd.jpg')"></div>
                    <div class="wall-img" style="background-image: url('https://images.pokemontcg.io/swsh7/215_hires.png')"></div>
                    <div class="wall-img" style="background-image: url('https://images.ygoprodeck.com/images/cards/38033121.jpg')"></div>
                    <div class="wall-img" style="background-image: url('https://cards.scryfall.io/large/front/e/3/e3285e6b-3e79-4d7c-bf96-d920f973b122.jpg')"></div>
                </div>
                <!-- Columna 2 ↓ -->
                <div class="wall-column col-down">
                    <div class="wall-img" style="background-image: url('https://images.pokemontcg.io/base1/2_hires.png')"></div>
                    <div class="wall-img" style="background-image: url('https://images.ygoprodeck.com/images/cards/46986414.jpg')"></div>
                    <div class="wall-img" style="background-image: url('https://cards.scryfall.io/large/front/7/0/70901356-3266-4bd9-aacc-f06c27271de5.jpg')"></div>
                    <div class="wall-img" style="background-image: url('https://images.pokemontcg.io/swsh11/186_hires.png')"></div>
                    <div class="wall-img" style="background-image: url('https://images.ygoprodeck.com/images/cards/83764718.jpg')"></div>
                    <div class="wall-img" style="background-image: url('https://cards.scryfall.io/large/front/f/2/f2bc06cb-2f22-4313-82a2-a7e7b2564f4d.jpg')"></div>
                    <!-- repeat -->
                    <div class="wall-img" style="background-image: url('https://images.pokemontcg.io/base1/2_hires.png')"></div>
                    <div class="wall-img" style="background-image: url('https://images.ygoprodeck.com/images/cards/46986414.jpg')"></div>
                    <div class="wall-img" style="background-image: url('https://cards.scryfall.io/large/front/7/0/70901356-3266-4bd9-aacc-f06c27271de5.jpg')"></div>
                    <div class="wall-img" style="background-image: url('https://images.pokemontcg.io/swsh11/186_hires.png')"></div>
                    <div class="wall-img" style="background-image: url('https://images.ygoprodeck.com/images/cards/83764718.jpg')"></div>
                    <div class="wall-img" style="background-image: url('https://cards.scryfall.io/large/front/f/2/f2bc06cb-2f22-4313-82a2-a7e7b2564f4d.jpg')"></div>
                </div>
                <!-- Columna 3 ↑ -->
                <div class="wall-column col-up">
                    <div class="wall-img" style="background-image: url('https://images.pokemontcg.io/base1/15_hires.png')"></div>
                    <div class="wall-img" style="background-image: url('https://images.ygoprodeck.com/images/cards/33396948.jpg')"></div>
                    <div class="wall-img" style="background-image: url('https://cards.scryfall.io/large/front/2/3/2398892d-28e9-4009-81ec-0d544af79d2b.jpg')"></div>
                    <div class="wall-img" style="background-image: url('https://images.pokemontcg.io/base1/1_hires.png')"></div>
                    <div class="wall-img" style="background-image: url('https://images.ygoprodeck.com/images/cards/4031928.jpg')"></div>
                    <div class="wall-img" style="background-image: url('https://cards.scryfall.io/large/front/b/d/bd8fa327-dd41-4737-8f19-2cf5eb1f7cdd.jpg')"></div>
                    <!-- repeat -->
                    <div class="wall-img" style="background-image: url('https://images.pokemontcg.io/base1/15_hires.png')"></div>
                    <div class="wall-img" style="background-image: url('https://images.ygoprodeck.com/images/cards/33396948.jpg')"></div>
                    <div class="wall-img" style="background-image: url('https://cards.scryfall.io/large/front/2/3/2398892d-28e9-4009-81ec-0d544af79d2b.jpg')"></div>
                    <div class="wall-img" style="background-image: url('https://images.pokemontcg.io/base1/1_hires.png')"></div>
                    <div class="wall-img" style="background-image: url('https://images.ygoprodeck.com/images/cards/4031928.jpg')"></div>
                    <div class="wall-img" style="background-image: url('https://cards.scryfall.io/large/front/b/d/bd8fa327-dd41-4737-8f19-2cf5eb1f7cdd.jpg')"></div>
                </div>
                <!-- Columna 4 ↓ -->
                <div class="wall-column col-down">
                    <div class="wall-img" style="background-image: url('https://images.pokemontcg.io/base1/6_hires.png')"></div>
                    <div class="wall-img" style="background-image: url('https://images.ygoprodeck.com/images/cards/10000020.jpg')"></div>
                    <div class="wall-img" style="background-image: url('https://cards.scryfall.io/large/front/e/3/e3285e6b-3e79-4d7c-bf96-d920f973b122.jpg')"></div>
                    <div class="wall-img" style="background-image: url('https://images.pokemontcg.io/neo1/9_hires.png')"></div>
                    <div class="wall-img" style="background-image: url('https://images.ygoprodeck.com/images/cards/89631139.jpg')"></div>
                    <div class="wall-img" style="background-image: url('https://cards.scryfall.io/large/front/7/0/70901356-3266-4bd9-aacc-f06c27271de5.jpg')"></div>
                    <!-- repeat -->
                    <div class="wall-img" style="background-image: url('https://images.pokemontcg.io/base1/6_hires.png')"></div>
                    <div class="wall-img" style="background-image: url('https://images.ygoprodeck.com/images/cards/10000020.jpg')"></div>
                    <div class="wall-img" style="background-image: url('https://cards.scryfall.io/large/front/e/3/e3285e6b-3e79-4d7c-bf96-d920f973b122.jpg')"></div>
                    <div class="wall-img" style="background-image: url('https://images.pokemontcg.io/neo1/9_hires.png')"></div>
                    <div class="wall-img" style="background-image: url('https://images.ygoprodeck.com/images/cards/89631139.jpg')"></div>
                    <div class="wall-img" style="background-image: url('https://cards.scryfall.io/large/front/7/0/70901356-3266-4bd9-aacc-f06c27271de5.jpg')"></div>
                </div>
                <!-- Columna 5 ↑ -->
                <div class="wall-column col-up">
                    <div class="wall-img" style="background-image: url('https://images.pokemontcg.io/base1/5_hires.png')"></div>
                    <div class="wall-img" style="background-image: url('https://images.ygoprodeck.com/images/cards/46986414.jpg')"></div>
                    <div class="wall-img" style="background-image: url('https://cards.scryfall.io/large/front/f/2/f2bc06cb-2f22-4313-82a2-a7e7b2564f4d.jpg')"></div>
                    <div class="wall-img" style="background-image: url('https://images.pokemontcg.io/base1/4_hires.png')"></div>
                    <div class="wall-img" style="background-image: url('https://images.ygoprodeck.com/images/cards/38033121.jpg')"></div>
                    <div class="wall-img" style="background-image: url('https://cards.scryfall.io/large/front/2/3/2398892d-28e9-4009-81ec-0d544af79d2b.jpg')"></div>
                    <!-- repeat -->
                    <div class="wall-img" style="background-image: url('https://images.pokemontcg.io/base1/5_hires.png')"></div>
                    <div class="wall-img" style="background-image: url('https://images.ygoprodeck.com/images/cards/46986414.jpg')"></div>
                    <div class="wall-img" style="background-image: url('https://cards.scryfall.io/large/front/f/2/f2bc06cb-2f22-4313-82a2-a7e7b2564f4d.jpg')"></div>
                    <div class="wall-img" style="background-image: url('https://images.pokemontcg.io/base1/4_hires.png')"></div>
                    <div class="wall-img" style="background-image: url('https://images.ygoprodeck.com/images/cards/38033121.jpg')"></div>
                    <div class="wall-img" style="background-image: url('https://cards.scryfall.io/large/front/2/3/2398892d-28e9-4009-81ec-0d544af79d2b.jpg')"></div>
                </div>
                <!-- Columna 6 ↓ -->
                <div class="wall-column col-down">
                    <div class="wall-img" style="background-image: url('https://images.pokemontcg.io/base1/9_hires.png')"></div>
                    <div class="wall-img" style="background-image: url('https://images.ygoprodeck.com/images/cards/83764718.jpg')"></div>
                    <div class="wall-img" style="background-image: url('https://cards.scryfall.io/large/front/b/d/bd8fa327-dd41-4737-8f19-2cf5eb1f7cdd.jpg')"></div>
                    <div class="wall-img" style="background-image: url('https://images.pokemontcg.io/base1/2_hires.png')"></div>
                    <div class="wall-img" style="background-image: url('https://images.ygoprodeck.com/images/cards/4031928.jpg')"></div>
                    <div class="wall-img" style="background-image: url('https://cards.scryfall.io/large/front/e/3/e3285e6b-3e79-4d7c-bf96-d920f973b122.jpg')"></div>
                    <!-- repeat -->
                    <div class="wall-img" style="background-image: url('https://images.pokemontcg.io/base1/9_hires.png')"></div>
                    <div class="wall-img" style="background-image: url('https://images.ygoprodeck.com/images/cards/83764718.jpg')"></div>
                    <div class="wall-img" style="background-image: url('https://cards.scryfall.io/large/front/b/d/bd8fa327-dd41-4737-8f19-2cf5eb1f7cdd.jpg')"></div>
                    <div class="wall-img" style="background-image: url('https://images.pokemontcg.io/base1/2_hires.png')"></div>
                    <div class="wall-img" style="background-image: url('https://images.ygoprodeck.com/images/cards/4031928.jpg')"></div>
                    <div class="wall-img" style="background-image: url('https://cards.scryfall.io/large/front/e/3/e3285e6b-3e79-4d7c-bf96-d920f973b122.jpg')"></div>
                </div>
                <!-- Columna 7 ↑ -->
                <div class="wall-column col-up">
                    <div class="wall-img" style="background-image: url('https://images.pokemontcg.io/swsh7/215_hires.png')"></div>
                    <div class="wall-img" style="background-image: url('https://images.ygoprodeck.com/images/cards/33396948.jpg')"></div>
                    <div class="wall-img" style="background-image: url('https://cards.scryfall.io/large/front/7/0/70901356-3266-4bd9-aacc-f06c27271de5.jpg')"></div>
                    <div class="wall-img" style="background-image: url('https://images.pokemontcg.io/base1/15_hires.png')"></div>
                    <div class="wall-img" style="background-image: url('https://images.ygoprodeck.com/images/cards/10000020.jpg')"></div>
                    <div class="wall-img" style="background-image: url('https://cards.scryfall.io/large/front/f/2/f2bc06cb-2f22-4313-82a2-a7e7b2564f4d.jpg')"></div>
                    <!-- repeat -->
                    <div class="wall-img" style="background-image: url('https://images.pokemontcg.io/swsh7/215_hires.png')"></div>
                    <div class="wall-img" style="background-image: url('https://images.ygoprodeck.com/images/cards/33396948.jpg')"></div>
                    <div class="wall-img" style="background-image: url('https://cards.scryfall.io/large/front/7/0/70901356-3266-4bd9-aacc-f06c27271de5.jpg')"></div>
                    <div class="wall-img" style="background-image: url('https://images.pokemontcg.io/base1/15_hires.png')"></div>
                    <div class="wall-img" style="background-image: url('https://images.ygoprodeck.com/images/cards/10000020.jpg')"></div>
                    <div class="wall-img" style="background-image: url('https://cards.scryfall.io/large/front/f/2/f2bc06cb-2f22-4313-82a2-a7e7b2564f4d.jpg')"></div>
                </div>
                <!-- Columna 8 ↓ -->
                <div class="wall-column col-down">
                    <div class="wall-img" style="background-image: url('https://images.pokemontcg.io/swsh11/186_hires.png')"></div>
                    <div class="wall-img" style="background-image: url('https://images.ygoprodeck.com/images/cards/89631139.jpg')"></div>
                    <div class="wall-img" style="background-image: url('https://cards.scryfall.io/large/front/2/3/2398892d-28e9-4009-81ec-0d544af79d2b.jpg')"></div>
                    <div class="wall-img" style="background-image: url('https://images.pokemontcg.io/base1/6_hires.png')"></div>
                    <div class="wall-img" style="background-image: url('https://images.ygoprodeck.com/images/cards/83764718.jpg')"></div>
                    <div class="wall-img" style="background-image: url('https://cards.scryfall.io/large/front/b/d/bd8fa327-dd41-4737-8f19-2cf5eb1f7cdd.jpg')"></div>
                    <!-- repeat -->
                    <div class="wall-img" style="background-image: url('https://images.pokemontcg.io/swsh11/186_hires.png')"></div>
                    <div class="wall-img" style="background-image: url('https://images.ygoprodeck.com/images/cards/89631139.jpg')"></div>
                    <div class="wall-img" style="background-image: url('https://cards.scryfall.io/large/front/2/3/2398892d-28e9-4009-81ec-0d544af79d2b.jpg')"></div>
                    <div class="wall-img" style="background-image: url('https://images.pokemontcg.io/base1/6_hires.png')"></div>
                    <div class="wall-img" style="background-image: url('https://images.ygoprodeck.com/images/cards/83764718.jpg')"></div>
                    <div class="wall-img" style="background-image: url('https://cards.scryfall.io/large/front/b/d/bd8fa327-dd41-4737-8f19-2cf5eb1f7cdd.jpg')"></div>
                </div>
                <!-- Columna 9 ↑ -->
                <div class="wall-column col-up">
                    <div class="wall-img" style="background-image: url('https://images.pokemontcg.io/neo1/9_hires.png')"></div>
                    <div class="wall-img" style="background-image: url('https://images.ygoprodeck.com/images/cards/46986414.jpg')"></div>
                    <div class="wall-img" style="background-image: url('https://cards.scryfall.io/large/front/e/3/e3285e6b-3e79-4d7c-bf96-d920f973b122.jpg')"></div>
                    <div class="wall-img" style="background-image: url('https://images.pokemontcg.io/base1/1_hires.png')"></div>
                    <div class="wall-img" style="background-image: url('https://images.ygoprodeck.com/images/cards/38033121.jpg')"></div>
                    <div class="wall-img" style="background-image: url('https://cards.scryfall.io/large/front/7/0/70901356-3266-4bd9-aacc-f06c27271de5.jpg')"></div>
                    <!-- repeat -->
                    <div class="wall-img" style="background-image: url('https://images.pokemontcg.io/neo1/9_hires.png')"></div>
                    <div class="wall-img" style="background-image: url('https://images.ygoprodeck.com/images/cards/46986414.jpg')"></div>
                    <div class="wall-img" style="background-image: url('https://cards.scryfall.io/large/front/e/3/e3285e6b-3e79-4d7c-bf96-d920f973b122.jpg')"></div>
                    <div class="wall-img" style="background-image: url('https://images.pokemontcg.io/base1/1_hires.png')"></div>
                    <div class="wall-img" style="background-image: url('https://images.ygoprodeck.com/images/cards/38033121.jpg')"></div>
                    <div class="wall-img" style="background-image: url('https://cards.scryfall.io/large/front/7/0/70901356-3266-4bd9-aacc-f06c27271de5.jpg')"></div>
                </div>
                <!-- Columna 10 ↓ -->
                <div class="wall-column col-down">
                    <div class="wall-img" style="background-image: url('https://images.pokemontcg.io/base1/4_hires.png')"></div>
                    <div class="wall-img" style="background-image: url('https://images.ygoprodeck.com/images/cards/4031928.jpg')"></div>
                    <div class="wall-img" style="background-image: url('https://cards.scryfall.io/large/front/f/2/f2bc06cb-2f22-4313-82a2-a7e7b2564f4d.jpg')"></div>
                    <div class="wall-img" style="background-image: url('https://images.pokemontcg.io/base1/9_hires.png')"></div>
                    <div class="wall-img" style="background-image: url('https://images.ygoprodeck.com/images/cards/10000020.jpg')"></div>
                    <div class="wall-img" style="background-image: url('https://cards.scryfall.io/large/front/2/3/2398892d-28e9-4009-81ec-0d544af79d2b.jpg')"></div>
                    <!-- repeat -->
                    <div class="wall-img" style="background-image: url('https://images.pokemontcg.io/base1/4_hires.png')"></div>
                    <div class="wall-img" style="background-image: url('https://images.ygoprodeck.com/images/cards/4031928.jpg')"></div>
                    <div class="wall-img" style="background-image: url('https://cards.scryfall.io/large/front/f/2/f2bc06cb-2f22-4313-82a2-a7e7b2564f4d.jpg')"></div>
                    <div class="wall-img" style="background-image: url('https://images.pokemontcg.io/base1/9_hires.png')"></div>
                    <div class="wall-img" style="background-image: url('https://images.ygoprodeck.com/images/cards/10000020.jpg')"></div>
                    <div class="wall-img" style="background-image: url('https://cards.scryfall.io/large/front/2/3/2398892d-28e9-4009-81ec-0d544af79d2b.jpg')"></div>
                </div>
                <!-- Columna 11 ↑ -->
                <div class="wall-column col-up">
                    <div class="wall-img" style="background-image: url('https://images.pokemontcg.io/base1/5_hires.png')"></div>
                    <div class="wall-img" style="background-image: url('https://images.ygoprodeck.com/images/cards/33396948.jpg')"></div>
                    <div class="wall-img" style="background-image: url('https://cards.scryfall.io/large/front/e/3/e3285e6b-3e79-4d7c-bf96-d920f973b122.jpg')"></div>
                    <div class="wall-img" style="background-image: url('https://images.pokemontcg.io/swsh7/215_hires.png')"></div>
                    <div class="wall-img" style="background-image: url('https://images.ygoprodeck.com/images/cards/83764718.jpg')"></div>
                    <div class="wall-img" style="background-image: url('https://cards.scryfall.io/large/front/b/d/bd8fa327-dd41-4737-8f19-2cf5eb1f7cdd.jpg')"></div>
                    <!-- repeat -->
                    <div class="wall-img" style="background-image: url('https://images.pokemontcg.io/base1/5_hires.png')"></div>
                    <div class="wall-img" style="background-image: url('https://images.ygoprodeck.com/images/cards/33396948.jpg')"></div>
                    <div class="wall-img" style="background-image: url('https://cards.scryfall.io/large/front/e/3/e3285e6b-3e79-4d7c-bf96-d920f973b122.jpg')"></div>
                    <div class="wall-img" style="background-image: url('https://images.pokemontcg.io/swsh7/215_hires.png')"></div>
                    <div class="wall-img" style="background-image: url('https://images.ygoprodeck.com/images/cards/83764718.jpg')"></div>
                    <div class="wall-img" style="background-image: url('https://cards.scryfall.io/large/front/b/d/bd8fa327-dd41-4737-8f19-2cf5eb1f7cdd.jpg')"></div>
                </div>
            </div>

            <div class="hero-content" style="margin: 0 auto;">
                <div class="hero-text">
                    <?php if ($nombre_usuario): ?>
                        <p class="welcome-text">¡Hola de nuevo, <?php echo htmlspecialchars($nombre_usuario); ?>!</p>
                    <?php endif; ?>
                    <h1>Loot&Trading<br><span>Marketplace.</span></h1>
                    <p data-i18n="home.subtitle">El mercado definitivo de TCGs con precios en tiempo real.</p>
                    <div style="display: flex; gap: 20px; justify-content: center; align-items: center; margin-top: 30px; flex-wrap: wrap; width: 100%;">
                        <a href="#section-pokemon" class="btn-main" style="margin-top: 0;" data-i18n="home.explore">Explorar Colecciones</a>
                        <a href="mercado.php?game=pokemon" class="btn-main" style="margin-top: 0; background: linear-gradient(135deg, #3b82f6 0%, #06b6d4 100%); color: white; box-shadow: 0 10px 25px rgba(59,130,246,0.3);" data-i18n="home.search_market">🔍 Buscar en el Mercado</a>
                        <a href="apuestas.php" class="btn-main" style="margin-top: 0; background: linear-gradient(135deg, #8b5cf6 0%, #d946ef 100%); color: white; box-shadow: 0 10px 25px rgba(139, 92, 246, 0.3);" data-i18n="home.auctions">🏷️ Subastas en vivo</a>
                    </div>
                </div>
            </div>
        </header>

        <!-- ===== CARTA DEL DÍA ===== -->
        <section class="cod-section">
            <div class="cod-inner">
                <div class="cod-left">
                    <div class="cod-eyebrow" data-i18n="home.card_of_day">Carta del día</div>
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
                        <div class="cod-opinions-label" data-i18n="home.community_says">Lo que dice la comunidad</div>
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
                    <h2 data-i18n="home.trending">🔥 Lo más buscado esta semana</h2>
                    <a href="mercado.php?game=pokemon" class="trending-see-all" data-i18n="home.see_all">Ver todo el mercado →</a>
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
                    <div class="trending-card" id="trending-op-luffy" onclick="openModal({card_id:'OP05-119',id:'OP05-119',name:'Luffy Manga Alt Art',img:'',badge:'One Piece',color:'#f97316',price:'2100.00'})">
                        <img src="" data-sb-search="Luffy" data-sb-game="onepiece" alt="Luffy Manga" style="background:#f1f5f9;">
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
                    <div class="trending-card" id="trending-op-zoro" onclick="openModal({card_id:'OP06-118',id:'OP06-118',name:'Zoro Manga Alt Art',img:'',badge:'One Piece',color:'#f97316',price:'980.00'})">
                        <img src="" data-sb-search="Zoro" data-sb-game="onepiece" alt="Zoro Manga" style="background:#f1f5f9;">
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
        <section class="stats-banner" id="stats-banner">
            <div class="stats-inner">
                <div class="stat-item stat-animate">
                    <span class="stat-num" data-count="12000" data-suffix="+" data-format="es">12.000+</span>
                    <span class="stat-label" data-i18n="home.cards_available">Cartas disponibles</span>
                </div>
                <div class="stat-divider"></div>
                <div class="stat-item stat-animate">
                    <span class="stat-num" data-count="4">4</span>
                    <span class="stat-label" data-i18n="home.tcgs_supported">TCGs soportados</span>
                </div>
                <div class="stat-divider"></div>
                <div class="stat-item stat-animate">
                    <span class="stat-num">Real-time</span>
                    <span class="stat-label" data-i18n="home.prices_updated">Precios actualizados</span>
                </div>
                <div class="stat-divider"></div>
                <div class="stat-item stat-animate">
                    <span class="stat-num" data-count="100" data-suffix="%">100%</span>
                    <span class="stat-label" data-i18n="home.free_collectors">Gratis para coleccionistas</span>
                </div>
            </div>
        </section>

        <!-- ===== EVENTOS / QUEDADAS ===== -->
        <section class="events-section">
            <div class="events-inner">
                <div class="events-header">
                    <div>
                        <h2>📅 Próximos Eventos</h2>
                        <p class="events-subtitle">Quedadas y torneos donde participamos</p>
                    </div>
                    <span class="events-we-badge">🎯 Participamos</span>
                </div>

                <div class="events-grid">

                <?php if (empty($events_db)): ?>
                    <p style="color:#94a3b8;grid-column:1/-1;text-align:center;padding:40px 0;">No hay eventos próximos.</p>
                <?php else: ?>
                <?php
                $gameColors = ['pokemon'=>'#e63329','yugioh'=>'#a855f7','magic'=>'#8b5cf6','onepiece'=>'#f97316','multi'=>'#3b82f6'];
                foreach ($events_db as $ev):
                    $d = new DateTime($ev['event_date']);
                    $featured = $ev['we_participate'] ? ' event-featured' : '';
                    $color = $gameColors[$ev['game_type']] ?? '#3b82f6';
                ?>
                    <div class="event-card<?php echo $featured; ?>">
                        <div class="event-date-block" style="background:<?php echo $color; ?>">
                            <span class="event-day"><?php echo $d->format('d'); ?></span>
                            <span class="event-month"><?php echo strtoupper($d->format('M')); ?></span>
                        </div>
                        <div class="event-info">
                            <div class="event-tags">
                                <span class="event-tag <?php echo htmlspecialchars($ev['game_type']); ?>"><?php echo ucfirst(htmlspecialchars($ev['game_type'])); ?></span>
                                <span class="event-tag <?php echo htmlspecialchars($ev['event_type']); ?>"><?php echo ucfirst(htmlspecialchars($ev['event_type'])); ?></span>
                                <?php if ($ev['we_participate']): ?><span class="event-tag nosotros">⭐ Nosotros</span><?php endif; ?>
                            </div>
                            <h3 class="event-name"><?php echo htmlspecialchars($ev['title']); ?></h3>
                            <p class="event-desc"><?php echo htmlspecialchars($ev['description']); ?></p>
                            <div class="event-meta">
                                <?php if ($ev['location']): ?><span>📍 <?php echo htmlspecialchars($ev['location']); ?></span><?php endif; ?>
                                <?php if ($ev['event_time']): ?><span>🕙 <?php echo htmlspecialchars($ev['event_time']); ?></span><?php endif; ?>
                                <?php if ($ev['participants']): ?><span>👥 <?php echo htmlspecialchars($ev['participants']); ?></span><?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
                <?php endif; ?>

                </div>
            </div>
        </section>

        <main id="main-content">
            <div id="section-pokemon" class="category-section">
                <div class="section-head">
                    <img src="../img/pokemon.png" alt="Pokémon" style="height:60px;display:block;margin:0 auto 10px;">
                </div>
                <div id="pokemon-grid" class="cards-grid"></div>
                <div class="load-more-container">
                    <a href="mercado.php?game=pokemon" class="btn-load" data-i18n="market.load_catalog">Ver todo el catálogo y filtros</a>
                </div>
            </div>

            <div id="section-yugioh" class="category-section">
                <div class="section-head">
                    <img src="../img/yugioh.png" alt="Yu-Gi-Oh!" style="height:60px;display:block;margin:0 auto 10px;">
                </div>
                <div id="yugioh-grid" class="cards-grid"></div>
                <div class="load-more-container">
                    <a href="mercado.php?game=yugioh" class="btn-load" data-i18n="market.load_catalog">Ver todo el catálogo y filtros</a>
                </div>
            </div>

            <div id="section-magic" class="category-section">
                <div class="section-head">
                    <img src="../img/magic.png" alt="Magic: The Gathering" style="height:60px;display:block;margin:0 auto 10px;">
                </div>
                <div id="magic-grid" class="cards-grid"></div>
                <div class="load-more-container">
                    <a href="mercado.php?game=magic" class="btn-load" data-i18n="market.load_catalog">Ver todo el catálogo y filtros</a>
                </div>
            </div>

            <div id="section-onepiece" class="category-section">
                <div class="section-head">
                    <img src="../img/onepiece.png" alt="One Piece" style="height:60px;display:block;margin:0 auto 10px;">
                </div>
                <div id="onepiece-grid" class="cards-grid"></div>
                <div class="load-more-container">
                    <a href="mercado.php?game=onepiece" class="btn-load" data-i18n="market.load_catalog">Ver todo el catálogo y filtros</a>
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
                    <button id="modal-toggle-fav" class="btn-cart modal-fav-btn" type="button" data-i18n="modal.add_fav">⭐ Añadir a favoritos</button>
                </div>
                <div class="modal-right">
                    <div class="modal-header">
                        <span id="modal-badge" class="card-badge"></span>
                        <h2 id="modal-title"></h2>
                        <h3 id="modal-price" class="price-big"></h3>
                    </div>
                    <div class="price-chart-section">
                        <div class="price-chart-header">
                            <span data-i18n="modal.price_history">📈 Histórico de Precios (30 días)</span>
                            <span id="price-chart-change" class="price-change-badge"></span>
                        </div>
                        <div style="position:relative;height:140px;">
                            <canvas id="price-chart"></canvas>
                        </div>
                    </div>
                    <div class="market-table-container">
                        <h4 data-i18n="modal.sellers">🛒 Ofertas de Vendedores</h4>
                        <table class="market-table">
                            <thead>
                                <tr><th data-i18n="modal.seller">Vendedor</th><th data-i18n="modal.condition">Estado</th><th data-i18n="modal.price">Precio</th><th data-i18n="modal.action">Acción</th></tr>
                            </thead>
                            <tbody id="market-list"></tbody>
                        </table>
                    </div>
                    <button id="modal-add-best" class="btn-main full-width" data-i18n="modal.add_best">Añadir mejor oferta al Carrito</button>
                </div>
            </div>
        </div>
        <div class="related-section">
            <h4 data-i18n="modal.related">Cartas Relacionadas</h4>
            <div id="related-grid" class="cards-grid mini-grid"></div>
        </div>
    </div>
</div>

<?php include dirname(__DIR__) . '/includes/footer.php'; ?>

<script src="../assets/js/csrf.js?v=<?php echo time(); ?>"></script>
    <script src="../assets/js/script.js?v=<?php echo time(); ?>"></script>
    <script>
    // Cargar imágenes One Piece del trending desde Supabase
    window.addEventListener('load', function() {
        const SB_URL = 'https://twnpxipxtmgdohjckbjn.supabase.co';
        const SB_KEY = 'sb_publishable_lu1sQo0v_aKaXvNQCh6SOw_ENj5JTPu';
        const OP_TCG = '3e7c1156-bfdd-4583-98fa-6564a8ea5f35';
        const headers = { 'apikey': SB_KEY, 'Authorization': 'Bearer ' + SB_KEY };

        function applyImg(img, card) {
            const url = card.image_large || card.image_small;
            if (!url) return;
            img.src = url;
            img.style.background = '';
            const parent = img.closest('.trending-card');
            if (parent) {
                const oc = parent.getAttribute('onclick') || '';
                parent.setAttribute('onclick', oc.replace("img:''", "img:'" + url.replace(/'/g, "\\'") + "'"));
            }
        }

        function fallbackRandom(img, offset) {
            fetch(`${SB_URL}/rest/v1/cards?select=name,image_large,image_small&tcg_id=eq.${OP_TCG}&image_large=not.is.null&limit=1&offset=${offset}`, { headers })
                .then(r => r.json())
                .then(function(d) { if (d && d.length) applyImg(img, d[0]); })
                .catch(function() {});
        }

        let fallbackOffset = 0;
        document.querySelectorAll('img[data-sb-search][data-sb-game="onepiece"]').forEach(function(img) {
            const term = img.dataset.sbSearch;
            fetch(`${SB_URL}/rest/v1/cards?select=name,image_large,image_small&tcg_id=eq.${OP_TCG}&name=ilike.*${term}*&image_large=not.is.null&limit=3`, { headers })
                .then(r => r.json())
                .then(function(data) {
                    if (data && data.length) {
                        applyImg(img, data[0]);
                    } else {
                        fallbackRandom(img, fallbackOffset++ * 10);
                    }
                })
                .catch(function() { fallbackRandom(img, fallbackOffset++ * 10); });
        });
    });

    // Parallax suave en las tarjetas de los grids
    (function() {
        let ticking = false;
        function applyParallax() {
            const cards = document.querySelectorAll('.tcg-item');
            const cy = window.innerHeight / 2;
            cards.forEach(function(card) {
                const rect = card.getBoundingClientRect();
                if (rect.bottom < -200 || rect.top > window.innerHeight + 200) return;
                const cardCY = rect.top + rect.height / 2;
                const dist   = (cardCY - cy) / window.innerHeight;
                card.style.transform = 'translateY(' + (dist * 20) + 'px)';
            });
        }
        window.addEventListener('scroll', function() {
            if (ticking) return;
            ticking = true;
            requestAnimationFrame(function() { applyParallax(); ticking = false; });
        }, { passive: true });

        // Reaplicar cuando las cartas se carguen del API
        const observer = new MutationObserver(applyParallax);
        ['pokemon-grid','yugioh-grid','magic-grid','onepiece-grid'].forEach(function(id) {
            const el = document.getElementById(id);
            if (el) observer.observe(el, { childList: true });
        });
    })();

    (function() {
        function countUp(el, target, suffix, format, duration) {
            const start = performance.now();
            function step(now) {
                const progress = Math.min((now - start) / duration, 1);
                const ease = 1 - Math.pow(1 - progress, 3);
                const value = Math.round(target * ease);
                if (format === 'es') {
                    el.textContent = value.toLocaleString('es-ES') + suffix;
                } else {
                    el.textContent = value + suffix;
                }
                if (progress < 1) requestAnimationFrame(step);
            }
            requestAnimationFrame(step);
        }

        const banner = document.getElementById('stats-banner');
        if (!banner) return;

        const observer = new IntersectionObserver(function(entries) {
            if (!entries[0].isIntersecting) return;
            observer.disconnect();

            banner.querySelectorAll('.stat-animate').forEach(function(item) {
                item.classList.add('visible');
                const num = item.querySelector('.stat-num[data-count]');
                if (!num) return;
                const target = parseInt(num.dataset.count);
                const suffix = num.dataset.suffix || '';
                const format = num.dataset.format || '';
                const duration = target >= 1000 ? 1800 : 900;
                countUp(num, target, suffix, format, duration);
            });
        }, { threshold: 0.3 });

        observer.observe(banner);
    })();
    </script>
</body>
</html>
