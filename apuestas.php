<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: auth.php");
    exit;
}
$nombre_usuario = $_SESSION['username'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Loot&Trading | TCG Predictor</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles.css?v=<?php echo time(); ?>">
</head>
<body>
    <script>(function(){ if(localStorage.getItem('theme')==='dark') document.body.classList.add('dark'); })();</script>

    <nav class="nav-dock">
        <div class="spacer"></div>
        <a href="index.php" class="nav-item" title="Inicio">
            <svg class="nav-icon" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955a1.126 1.126 0 0 1 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25"/></svg>
        </a>
        <a href="mercado.php?game=pokemon" class="nav-item" title="Mercado"><svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 3v18h18"/><path d="M18.7 8l-5.1 5.2-2.8-2.7L7 14.3"/></svg></a>
        
        <a href="apuestas.php" class="nav-item active" title="TCG Predictor" style="color: #a855f7;">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
        </a>

        <div class="spacer"></div>
        <button class="nav-item dark-toggle" onclick="toggleDarkMode()" title="Cambiar tema">
            <svg class="nav-icon icon-sun" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><circle cx="12" cy="12" r="5"/><path stroke-linecap="round" d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"/></svg>
            <svg class="nav-icon icon-moon" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
        </button>
        <a href="profile.php" class="nav-item user-active" title="Ver Perfil">
            <svg class="nav-icon" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z"/></svg>
            <?php echo htmlspecialchars($nombre_usuario); ?>
        </a>
    </nav>

    <div class="main-wrapper">
        <header class="arena-hero">
            <div class="arena-content">
                <h1>TCG Predictor<span>.</span></h1>
                <p>Las cartas expiran y cambian en tiempo real. Predice si subirán o bajarán de precio y multiplica tus Lujanitos antes de que se acabe el tiempo.</p>
                
                <div class="balance-card">
                    <div class="balance-label">Tu Saldo Actual</div>
                    <div class="balance-amount"><span id="user-balance">1000</span> <span class="coin">🪙</span></div>
                    <p class="balance-sub">Lujanitos</p>
                </div>
            </div>
        </header>

        <main id="main-content" style="padding-top: 40px;">
            <div class="category-section" style="border: none;">
                <div class="section-head">
                    <h2>Mercados Express Activos</h2>
                    <p>¡Rápido! Elige una opción antes de que la carta desaparezca del tablero.</p>
                </div>
                
                <div id="arena-grid" class="cards-grid">
                    </div>
            </div>

            <div class="divider-line" style="margin: 40px auto; height: 1px; background: var(--border-color); max-width: 1200px;"></div>

            <div class="category-section" style="border: none;">
                <div class="section-head">
                    <h2>Tus Predicciones Pendientes</h2>
                </div>
                <div class="market-table-container" style="max-width: 1000px; margin: 0 auto;">
                    <table class="market-table">
                        <thead>
                            <tr><th>Carta</th><th>Predicción</th><th>Apostado</th><th>Posible Ganancia</th><th>Estado</th></tr>
                        </thead>
                        <tbody id="my-bets-list">
                            </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <div id="bet-modal" class="modal-overlay">
        <div class="modal-content" style="max-width: 450px;">
            <button class="close-modal" onclick="closeBetModal()">×</button>
            <div style="text-align: center;">
                <h2 id="bet-modal-title">Apostar</h2>
                <p id="bet-modal-desc" style="color: var(--text-secondary); margin-bottom: 20px;">Prediciendo mercado...</p>
                
                <div class="form-group" style="text-align: left;">
                    <label>Cantidad de Lujanitos:</label>
                    <input type="number" id="bet-amount" placeholder="Ej. 100" min="10" max="10000" style="font-size: 1.5rem; text-align: center; font-weight: 800;">
                </div>

                <div style="display: flex; gap: 15px; margin-top: 25px;">
                    <button id="btn-confirm-bet" class="btn-main full-width" style="margin-top: 0; background: #8b5cf6;">Confirmar Apuesta</button>
                </div>
            </div>
        </div>
    </div>

    <script src="script.js?v=<?php echo time(); ?>"></script>
</body>
</html>