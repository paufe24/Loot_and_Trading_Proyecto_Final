<!-- auth.php -->
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TCG Verse | Acceso</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/styles.css?v=<?php echo time(); ?>">
    <style>
        .auth-container {
            position: relative;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            background: #fff;
        }

        .card-wall-bg {
            position: absolute; top: -20%; left: -20%; width: 140%; height: 140%;
            display: grid; grid-template-columns: repeat(11, 1fr); gap: 10px;
            transform: rotate(-10deg); z-index: 0; opacity: 0.15;
            mask-image: radial-gradient(circle, transparent 20%, black 100%);
            -webkit-mask-image: radial-gradient(circle at center, rgba(0,0,0,0) 10%, rgba(0,0,0,1) 70%);
        }

        .wall-column { display: flex; flex-direction: column; gap: 10px; }
        .wall-img {
            width: 100%; height: 185px;
            background-size: cover; background-position: center;
            border-radius: 10px; box-shadow: 0 6px 14px rgba(0,0,0,0.12);
        }

        .auth-content {
            position: relative; z-index: 10;
            display: flex;
            flex-direction: column;
            align-items: center;
            max-width: 450px;
            width: 100%;
            padding: 0 20px;
        }

        .form-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.15);
            border: 1px solid rgba(255,255,255,0.2);
            width: 100%;
            transition: transform 0.3s;
        }

        .form-card:hover {
            transform: translateY(-5px);
        }

        .form-card h2 {
            font-size: 2rem;
            font-weight: 800;
            margin-bottom: 30px;
            text-align: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-card input {
            width: 100%;
            padding: 15px 20px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 1rem;
            font-family: 'Outfit', sans-serif;
            transition: all 0.3s;
            background: #f8fafc;
        }

        .form-card input:focus {
            outline: none;
            border-color: var(--accent-blue);
            background: white;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .form-card button {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 10px;
        }

        .form-card button:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
        }

        .auth-toggle {
            text-align: center;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid #e2e8f0;
        }

        .auth-toggle a {
            color: var(--accent-blue);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s;
        }

        .auth-toggle a:hover {
            color: #2563eb;
            text-decoration: underline;
        }

        .form-hidden {
            display: none;
        }

        .form-active {
            display: block;
        }

        @media (max-width: 768px) {
            .form-card {
                padding: 30px 20px;
            }
            
            .form-card h2 {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>

    <nav class="nav-dock">
        <div class="nav-item" onclick="window.location.href='index.php'" title="Inicio">🏠</div>
        <div class="nav-item active" title="Acceso">👤</div>
    </nav>

    <div class="auth-container">
        <div class="card-wall-bg">
            <div class="wall-column col-up">
                <div class="wall-img" style="background-image: url('https://images.pokemontcg.io/base1/4_hires.png')"></div>
                <div class="wall-img" style="background-image: url('https://images.ygoprodeck.com/images/cards/89631139.jpg')"></div>
                <div class="wall-img" style="background-image: url('https://cards.scryfall.io/large/front/b/d/bd8fa327-dd41-4737-8f19-2cf5eb1f7cdd.jpg')"></div>
                <div class="wall-img" style="background-image: url('https://images.pokemontcg.io/swsh7/215_hires.png')"></div>
                <div class="wall-img" style="background-image: url('https://images.ygoprodeck.com/images/cards/38033121.jpg')"></div>
                <div class="wall-img" style="background-image: url('https://cards.scryfall.io/large/front/e/3/e3285e6b-3e79-4d7c-bf96-d920f973b122.jpg')"></div>
                <div class="wall-img" style="background-image: url('https://images.pokemontcg.io/base1/4_hires.png')"></div>
                <div class="wall-img" style="background-image: url('https://images.ygoprodeck.com/images/cards/89631139.jpg')"></div>
                <div class="wall-img" style="background-image: url('https://cards.scryfall.io/large/front/b/d/bd8fa327-dd41-4737-8f19-2cf5eb1f7cdd.jpg')"></div>
                <div class="wall-img" style="background-image: url('https://images.pokemontcg.io/swsh7/215_hires.png')"></div>
                <div class="wall-img" style="background-image: url('https://images.ygoprodeck.com/images/cards/38033121.jpg')"></div>
                <div class="wall-img" style="background-image: url('https://cards.scryfall.io/large/front/e/3/e3285e6b-3e79-4d7c-bf96-d920f973b122.jpg')"></div>
            </div>
            <div class="wall-column col-down">
                <div class="wall-img" style="background-image: url('https://images.pokemontcg.io/base1/2_hires.png')"></div>
                <div class="wall-img" style="background-image: url('https://images.ygoprodeck.com/images/cards/46986414.jpg')"></div>
                <div class="wall-img" style="background-image: url('https://cards.scryfall.io/large/front/7/0/70901356-3266-4bd9-aacc-f06c27271de5.jpg')"></div>
                <div class="wall-img" style="background-image: url('https://images.pokemontcg.io/swsh11/186_hires.png')"></div>
                <div class="wall-img" style="background-image: url('https://images.ygoprodeck.com/images/cards/83764718.jpg')"></div>
                <div class="wall-img" style="background-image: url('https://cards.scryfall.io/large/front/f/2/f2bc06cb-2f22-4313-82a2-a7e7b2564f4d.jpg')"></div>
                <div class="wall-img" style="background-image: url('https://images.pokemontcg.io/base1/2_hires.png')"></div>
                <div class="wall-img" style="background-image: url('https://images.ygoprodeck.com/images/cards/46986414.jpg')"></div>
                <div class="wall-img" style="background-image: url('https://cards.scryfall.io/large/front/7/0/70901356-3266-4bd9-aacc-f06c27271de5.jpg')"></div>
                <div class="wall-img" style="background-image: url('https://images.pokemontcg.io/swsh11/186_hires.png')"></div>
                <div class="wall-img" style="background-image: url('https://images.ygoprodeck.com/images/cards/83764718.jpg')"></div>
                <div class="wall-img" style="background-image: url('https://cards.scryfall.io/large/front/f/2/f2bc06cb-2f22-4313-82a2-a7e7b2564f4d.jpg')"></div>
            </div>
            <div class="wall-column col-up">
                <div class="wall-img" style="background-image: url('https://images.pokemontcg.io/base1/15_hires.png')"></div>
                <div class="wall-img" style="background-image: url('https://images.ygoprodeck.com/images/cards/33396948.jpg')"></div>
                <div class="wall-img" style="background-image: url('https://cards.scryfall.io/large/front/2/3/2398892d-28e9-4009-81ec-0d544af79d2b.jpg')"></div>
                <div class="wall-img" style="background-image: url('https://images.pokemontcg.io/base1/1_hires.png')"></div>
                <div class="wall-img" style="background-image: url('https://images.ygoprodeck.com/images/cards/4031928.jpg')"></div>
                <div class="wall-img" style="background-image: url('https://cards.scryfall.io/large/front/b/d/bd8fa327-dd41-4737-8f19-2cf5eb1f7cdd.jpg')"></div>
                <div class="wall-img" style="background-image: url('https://images.pokemontcg.io/base1/15_hires.png')"></div>
                <div class="wall-img" style="background-image: url('https://images.ygoprodeck.com/images/cards/33396948.jpg')"></div>
                <div class="wall-img" style="background-image: url('https://cards.scryfall.io/large/front/2/3/2398892d-28e9-4009-81ec-0d544af79d2b.jpg')"></div>
                <div class="wall-img" style="background-image: url('https://images.pokemontcg.io/base1/1_hires.png')"></div>
                <div class="wall-img" style="background-image: url('https://images.ygoprodeck.com/images/cards/4031928.jpg')"></div>
                <div class="wall-img" style="background-image: url('https://cards.scryfall.io/large/front/b/d/bd8fa327-dd41-4737-8f19-2cf5eb1f7cdd.jpg')"></div>
            </div>
            <div class="wall-column col-down">
                <div class="wall-img" style="background-image: url('https://images.pokemontcg.io/base1/6_hires.png')"></div>
                <div class="wall-img" style="background-image: url('https://images.ygoprodeck.com/images/cards/10000020.jpg')"></div>
                <div class="wall-img" style="background-image: url('https://cards.scryfall.io/large/front/e/3/e3285e6b-3e79-4d7c-bf96-d920f973b122.jpg')"></div>
                <div class="wall-img" style="background-image: url('https://images.pokemontcg.io/neo1/9_hires.png')"></div>
                <div class="wall-img" style="background-image: url('https://images.ygoprodeck.com/images/cards/89631139.jpg')"></div>
                <div class="wall-img" style="background-image: url('https://cards.scryfall.io/large/front/7/0/70901356-3266-4bd9-aacc-f06c27271de5.jpg')"></div>
                <div class="wall-img" style="background-image: url('https://images.pokemontcg.io/base1/6_hires.png')"></div>
                <div class="wall-img" style="background-image: url('https://images.ygoprodeck.com/images/cards/10000020.jpg')"></div>
                <div class="wall-img" style="background-image: url('https://cards.scryfall.io/large/front/e/3/e3285e6b-3e79-4d7c-bf96-d920f973b122.jpg')"></div>
                <div class="wall-img" style="background-image: url('https://images.pokemontcg.io/neo1/9_hires.png')"></div>
                <div class="wall-img" style="background-image: url('https://images.ygoprodeck.com/images/cards/89631139.jpg')"></div>
                <div class="wall-img" style="background-image: url('https://cards.scryfall.io/large/front/7/0/70901356-3266-4bd9-aacc-f06c27271de5.jpg')"></div>
            </div>
            <div class="wall-column col-up">
                <div class="wall-img" style="background-image: url('https://images.pokemontcg.io/base1/5_hires.png')"></div>
                <div class="wall-img" style="background-image: url('https://images.ygoprodeck.com/images/cards/46986414.jpg')"></div>
                <div class="wall-img" style="background-image: url('https://cards.scryfall.io/large/front/f/2/f2bc06cb-2f22-4313-82a2-a7e7b2564f4d.jpg')"></div>
                <div class="wall-img" style="background-image: url('https://images.pokemontcg.io/base1/4_hires.png')"></div>
                <div class="wall-img" style="background-image: url('https://images.ygoprodeck.com/images/cards/38033121.jpg')"></div>
                <div class="wall-img" style="background-image: url('https://cards.scryfall.io/large/front/2/3/2398892d-28e9-4009-81ec-0d544af79d2b.jpg')"></div>
                <div class="wall-img" style="background-image: url('https://images.pokemontcg.io/base1/5_hires.png')"></div>
                <div class="wall-img" style="background-image: url('https://images.ygoprodeck.com/images/cards/46986414.jpg')"></div>
                <div class="wall-img" style="background-image: url('https://cards.scryfall.io/large/front/f/2/f2bc06cb-2f22-4313-82a2-a7e7b2564f4d.jpg')"></div>
                <div class="wall-img" style="background-image: url('https://images.pokemontcg.io/base1/4_hires.png')"></div>
                <div class="wall-img" style="background-image: url('https://images.ygoprodeck.com/images/cards/38033121.jpg')"></div>
                <div class="wall-img" style="background-image: url('https://cards.scryfall.io/large/front/2/3/2398892d-28e9-4009-81ec-0d544af79d2b.jpg')"></div>
            </div>
            <div class="wall-column col-down">
                <div class="wall-img" style="background-image: url('https://images.pokemontcg.io/base1/9_hires.png')"></div>
                <div class="wall-img" style="background-image: url('https://images.ygoprodeck.com/images/cards/83764718.jpg')"></div>
                <div class="wall-img" style="background-image: url('https://cards.scryfall.io/large/front/b/d/bd8fa327-dd41-4737-8f19-2cf5eb1f7cdd.jpg')"></div>
                <div class="wall-img" style="background-image: url('https://images.pokemontcg.io/base1/2_hires.png')"></div>
                <div class="wall-img" style="background-image: url('https://images.ygoprodeck.com/images/cards/4031928.jpg')"></div>
                <div class="wall-img" style="background-image: url('https://cards.scryfall.io/large/front/e/3/e3285e6b-3e79-4d7c-bf96-d920f973b122.jpg')"></div>
                <div class="wall-img" style="background-image: url('https://images.pokemontcg.io/base1/9_hires.png')"></div>
                <div class="wall-img" style="background-image: url('https://images.ygoprodeck.com/images/cards/83764718.jpg')"></div>
                <div class="wall-img" style="background-image: url('https://cards.scryfall.io/large/front/b/d/bd8fa327-dd41-4737-8f19-2cf5eb1f7cdd.jpg')"></div>
                <div class="wall-img" style="background-image: url('https://images.pokemontcg.io/base1/2_hires.png')"></div>
                <div class="wall-img" style="background-image: url('https://images.ygoprodeck.com/images/cards/4031928.jpg')"></div>
                <div class="wall-img" style="background-image: url('https://cards.scryfall.io/large/front/e/3/e3285e6b-3e79-4d7c-bf96-d920f973b122.jpg')"></div>
            </div>
            <div class="wall-column col-up">
                <div class="wall-img" style="background-image: url('https://images.pokemontcg.io/swsh7/215_hires.png')"></div>
                <div class="wall-img" style="background-image: url('https://images.ygoprodeck.com/images/cards/33396948.jpg')"></div>
                <div class="wall-img" style="background-image: url('https://cards.scryfall.io/large/front/7/0/70901356-3266-4bd9-aacc-f06c27271de5.jpg')"></div>
                <div class="wall-img" style="background-image: url('https://images.pokemontcg.io/base1/15_hires.png')"></div>
                <div class="wall-img" style="background-image: url('https://images.ygoprodeck.com/images/cards/10000020.jpg')"></div>
                <div class="wall-img" style="background-image: url('https://cards.scryfall.io/large/front/f/2/f2bc06cb-2f22-4313-82a2-a7e7b2564f4d.jpg')"></div>
                <div class="wall-img" style="background-image: url('https://images.pokemontcg.io/swsh7/215_hires.png')"></div>
                <div class="wall-img" style="background-image: url('https://images.ygoprodeck.com/images/cards/33396948.jpg')"></div>
                <div class="wall-img" style="background-image: url('https://cards.scryfall.io/large/front/7/0/70901356-3266-4bd9-aacc-f06c27271de5.jpg')"></div>
                <div class="wall-img" style="background-image: url('https://images.pokemontcg.io/base1/15_hires.png')"></div>
                <div class="wall-img" style="background-image: url('https://images.ygoprodeck.com/images/cards/10000020.jpg')"></div>
                <div class="wall-img" style="background-image: url('https://cards.scryfall.io/large/front/f/2/f2bc06cb-2f22-4313-82a2-a7e7b2564f4d.jpg')"></div>
            </div>
            <div class="wall-column col-down">
                <div class="wall-img" style="background-image: url('https://images.pokemontcg.io/swsh11/186_hires.png')"></div>
                <div class="wall-img" style="background-image: url('https://images.ygoprodeck.com/images/cards/89631139.jpg')"></div>
                <div class="wall-img" style="background-image: url('https://cards.scryfall.io/large/front/2/3/2398892d-28e9-4009-81ec-0d544af79d2b.jpg')"></div>
                <div class="wall-img" style="background-image: url('https://images.pokemontcg.io/base1/6_hires.png')"></div>
                <div class="wall-img" style="background-image: url('https://images.ygoprodeck.com/images/cards/83764718.jpg')"></div>
                <div class="wall-img" style="background-image: url('https://cards.scryfall.io/large/front/b/d/bd8fa327-dd41-4737-8f19-2cf5eb1f7cdd.jpg')"></div>
                <div class="wall-img" style="background-image: url('https://images.pokemontcg.io/swsh11/186_hires.png')"></div>
                <div class="wall-img" style="background-image: url('https://images.ygoprodeck.com/images/cards/89631139.jpg')"></div>
                <div class="wall-img" style="background-image: url('https://cards.scryfall.io/large/front/2/3/2398892d-28e9-4009-81ec-0d544af79d2b.jpg')"></div>
                <div class="wall-img" style="background-image: url('https://images.pokemontcg.io/base1/6_hires.png')"></div>
                <div class="wall-img" style="background-image: url('https://images.ygoprodeck.com/images/cards/83764718.jpg')"></div>
                <div class="wall-img" style="background-image: url('https://cards.scryfall.io/large/front/b/d/bd8fa327-dd41-4737-8f19-2cf5eb1f7cdd.jpg')"></div>
            </div>
            <div class="wall-column col-up">
                <div class="wall-img" style="background-image: url('https://images.pokemontcg.io/neo1/9_hires.png')"></div>
                <div class="wall-img" style="background-image: url('https://images.ygoprodeck.com/images/cards/46986414.jpg')"></div>
                <div class="wall-img" style="background-image: url('https://cards.scryfall.io/large/front/e/3/e3285e6b-3e79-4d7c-bf96-d920f973b122.jpg')"></div>
                <div class="wall-img" style="background-image: url('https://images.pokemontcg.io/base1/1_hires.png')"></div>
                <div class="wall-img" style="background-image: url('https://images.ygoprodeck.com/images/cards/38033121.jpg')"></div>
                <div class="wall-img" style="background-image: url('https://cards.scryfall.io/large/front/7/0/70901356-3266-4bd9-aacc-f06c27271de5.jpg')"></div>
                <div class="wall-img" style="background-image: url('https://images.pokemontcg.io/neo1/9_hires.png')"></div>
                <div class="wall-img" style="background-image: url('https://images.ygoprodeck.com/images/cards/46986414.jpg')"></div>
                <div class="wall-img" style="background-image: url('https://cards.scryfall.io/large/front/e/3/e3285e6b-3e79-4d7c-bf96-d920f973b122.jpg')"></div>
                <div class="wall-img" style="background-image: url('https://images.pokemontcg.io/base1/1_hires.png')"></div>
                <div class="wall-img" style="background-image: url('https://images.ygoprodeck.com/images/cards/38033121.jpg')"></div>
                <div class="wall-img" style="background-image: url('https://cards.scryfall.io/large/front/7/0/70901356-3266-4bd9-aacc-f06c27271de5.jpg')"></div>
            </div>
            <div class="wall-column col-down">
                <div class="wall-img" style="background-image: url('https://images.pokemontcg.io/base1/4_hires.png')"></div>
                <div class="wall-img" style="background-image: url('https://images.ygoprodeck.com/images/cards/4031928.jpg')"></div>
                <div class="wall-img" style="background-image: url('https://cards.scryfall.io/large/front/f/2/f2bc06cb-2f22-4313-82a2-a7e7b2564f4d.jpg')"></div>
                <div class="wall-img" style="background-image: url('https://images.pokemontcg.io/base1/9_hires.png')"></div>
                <div class="wall-img" style="background-image: url('https://images.ygoprodeck.com/images/cards/10000020.jpg')"></div>
                <div class="wall-img" style="background-image: url('https://cards.scryfall.io/large/front/2/3/2398892d-28e9-4009-81ec-0d544af79d2b.jpg')"></div>
                <div class="wall-img" style="background-image: url('https://images.pokemontcg.io/base1/4_hires.png')"></div>
                <div class="wall-img" style="background-image: url('https://images.ygoprodeck.com/images/cards/4031928.jpg')"></div>
                <div class="wall-img" style="background-image: url('https://cards.scryfall.io/large/front/f/2/f2bc06cb-2f22-4313-82a2-a7e7b2564f4d.jpg')"></div>
                <div class="wall-img" style="background-image: url('https://images.pokemontcg.io/base1/9_hires.png')"></div>
                <div class="wall-img" style="background-image: url('https://images.ygoprodeck.com/images/cards/10000020.jpg')"></div>
                <div class="wall-img" style="background-image: url('https://cards.scryfall.io/large/front/2/3/2398892d-28e9-4009-81ec-0d544af79d2b.jpg')"></div>
            </div>
            <div class="wall-column col-up">
                <div class="wall-img" style="background-image: url('https://images.pokemontcg.io/base1/5_hires.png')"></div>
                <div class="wall-img" style="background-image: url('https://images.ygoprodeck.com/images/cards/33396948.jpg')"></div>
                <div class="wall-img" style="background-image: url('https://cards.scryfall.io/large/front/e/3/e3285e6b-3e79-4d7c-bf96-d920f973b122.jpg')"></div>
                <div class="wall-img" style="background-image: url('https://images.pokemontcg.io/swsh7/215_hires.png')"></div>
                <div class="wall-img" style="background-image: url('https://images.ygoprodeck.com/images/cards/83764718.jpg')"></div>
                <div class="wall-img" style="background-image: url('https://cards.scryfall.io/large/front/b/d/bd8fa327-dd41-4737-8f19-2cf5eb1f7cdd.jpg')"></div>
                <div class="wall-img" style="background-image: url('https://images.pokemontcg.io/base1/5_hires.png')"></div>
                <div class="wall-img" style="background-image: url('https://images.ygoprodeck.com/images/cards/33396948.jpg')"></div>
                <div class="wall-img" style="background-image: url('https://cards.scryfall.io/large/front/e/3/e3285e6b-3e79-4d7c-bf96-d920f973b122.jpg')"></div>
                <div class="wall-img" style="background-image: url('https://images.pokemontcg.io/swsh7/215_hires.png')"></div>
                <div class="wall-img" style="background-image: url('https://images.ygoprodeck.com/images/cards/83764718.jpg')"></div>
                <div class="wall-img" style="background-image: url('https://cards.scryfall.io/large/front/b/d/bd8fa327-dd41-4737-8f19-2cf5eb1f7cdd.jpg')"></div>
            </div>
        </div>

        <div class="auth-content">
            <!-- FORMULARIO LOGIN -->
            <div id="login-form" class="form-card form-active">
                <h2>🔐 Iniciar Sesión</h2>
                <form action="login.php" method="POST">
                    <div class="form-group">
                        <input type="text" name="username" placeholder="Usuario" required>
                    </div>
                    <div class="form-group">
                        <input type="password" name="password" placeholder="Contraseña" required>
                    </div>
                    <button type="submit">Iniciar Sesión</button>
                </form>
                <div class="auth-toggle">
                    <p>¿No tienes cuenta? <a href="#" onclick="toggleForm('register')">Regístrate aquí</a></p>
                </div>
            </div>

            <!-- FORMULARIO REGISTRO -->
            <div id="register-form" class="form-card form-hidden">
                <h2>✨ Registrarse</h2>
                <form action="register.php" method="POST">
                    <div class="form-group">
                        <input type="text" name="name" placeholder="Nombre completo" required>
                    </div>
                    <div class="form-group">
                        <input type="text" name="username" placeholder="Usuario" required>
                    </div>
                    <div class="form-group">
                        <input type="email" name="email" placeholder="Email" required>
                    </div>
                    <div class="form-group">
                        <input type="password" name="password" placeholder="Contraseña" required>
                    </div>
                    <div class="form-group">
                        <input type="text" name="address" placeholder="Dirección de envío (calle, ciudad, CP)" required>
                    </div>
                    <button type="submit">Registrarse</button>
                </form>
                <div class="auth-toggle">
                    <p>¿Ya tienes cuenta? <a href="#" onclick="toggleForm('login')">Inicia sesión aquí</a></p>
                </div>
            </div>
        </div>
    </div>

    <script>
        function toggleForm(formType) {
            const loginForm = document.getElementById('login-form');
            const registerForm = document.getElementById('register-form');
            
            if (formType === 'register') {
                loginForm.classList.remove('form-active');
                loginForm.classList.add('form-hidden');
                registerForm.classList.remove('form-hidden');
                registerForm.classList.add('form-active');
            } else {
                registerForm.classList.remove('form-active');
                registerForm.classList.add('form-hidden');
                loginForm.classList.remove('form-hidden');
                loginForm.classList.add('form-active');
            }
        }
    </script>

</body>
</html>