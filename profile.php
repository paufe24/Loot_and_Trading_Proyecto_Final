<?php
session_start();

// Verificar si el usuario está logueado
if (!isset($_SESSION['user_id'])) {
    header("Location: auth.php");
    exit;
}

include 'db.php';

// Obtener datos del usuario
$stmt = $conn->prepare("SELECT name, email, username, created_at, avatar_url FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

// Actividad reciente
$conn->query("CREATE TABLE IF NOT EXISTS user_activity (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    activity_type VARCHAR(30) NOT NULL,
    title VARCHAR(255) NOT NULL,
    description VARCHAR(500) NOT NULL,
    ref_id INT NULL,
    amount DECIMAL(10,2) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_created (user_id, created_at),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)");

$recentActivity = [];
$activityStmt = $conn->prepare("SELECT activity_type, title, description, ref_id, created_at FROM user_activity WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
$activityStmt->bind_param("i", $_SESSION['user_id']);
$activityStmt->execute();
$activityRes = $activityStmt->get_result();
while ($row = $activityRes->fetch_assoc()) {
    $recentActivity[] = $row;
}
$activityStmt->close();

$orderPreviewById = [];
$orderIds = [];
foreach ($recentActivity as $act) {
    if (($act['activity_type'] ?? '') === 'order' && !empty($act['ref_id'])) {
        $orderIds[] = (int)$act['ref_id'];
    }
}

$orderIds = array_values(array_unique($orderIds));
if (count($orderIds) > 0) {
    $placeholders = implode(',', array_fill(0, count($orderIds), '?'));
    $types = str_repeat('i', count($orderIds));

    $sql = "SELECT i.order_id, i.card_name, i.card_image
            FROM cart_order_items i
            JOIN (
                SELECT order_id, MIN(id) AS min_id
                FROM cart_order_items
                WHERE order_id IN ($placeholders)
                GROUP BY order_id
            ) x ON x.min_id = i.id";

    $stmtPrev = $conn->prepare($sql);
    if ($stmtPrev) {
        $stmtPrev->bind_param($types, ...$orderIds);
        $stmtPrev->execute();
        $resPrev = $stmtPrev->get_result();
        while ($row = $resPrev->fetch_assoc()) {
            $orderPreviewById[(int)$row['order_id']] = [
                'card_name' => (string)$row['card_name'],
                'card_image' => (string)$row['card_image']
            ];
        }
        $stmtPrev->close();
    }
}

// Favoritos
$conn->query("CREATE TABLE IF NOT EXISTS user_favorites (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    card_id VARCHAR(255) NOT NULL,
    card_name VARCHAR(255) NOT NULL,
    card_image VARCHAR(500) NOT NULL,
    card_game VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_user_card (user_id, card_id),
    INDEX idx_user_created (user_id, created_at),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)");

$favorites = [];
$favStmt = $conn->prepare('SELECT card_id, card_name, card_image, card_game, created_at FROM user_favorites WHERE user_id = ? ORDER BY created_at DESC LIMIT 24');
if ($favStmt) {
    $favStmt->bind_param('i', $_SESSION['user_id']);
    $favStmt->execute();
    $favRes = $favStmt->get_result();
    while ($row = $favRes->fetch_assoc()) {
        $favorites[] = $row;
    }
    $favStmt->close();
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TCG Verse | Mi Perfil</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
    <style>
        .profile-container {
            position: relative;
            min-height: 100vh;
            background: var(--bg-main);
        }

        .profile-hero {
            position: relative;
            min-height: 60vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            padding: 0 20px;
            background: #fff;
        }

        .profile-content {
            position: relative;
            z-index: 10;
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px 60px 20px;
        }

        .activity-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
            margin-top: 10px;
        }

        .activity-item {
            background: rgba(255,255,255,0.75);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 14px 16px;
            text-align: left;
            display: grid;
            grid-template-columns: 48px 1fr;
            gap: 12px;
            align-items: start;
        }

        body.dark .activity-item {
            background: rgba(30,41,59,0.75);
            border-color: #334155;
        }

        .activity-title {
            font-weight: 900;
            margin-bottom: 4px;
        }

        .activity-thumb {
            width: 48px;
            height: 64px;
            border-radius: 12px;
            object-fit: cover;
            border: 1px solid rgba(0,0,0,0.06);
            background: #fff;
        }

        body.dark .activity-thumb {
            border-color: rgba(255,255,255,0.12);
            background: #0f172a;
        }

        .activity-desc {
            color: var(--text-secondary);
            font-weight: 600;
            font-size: 0.95rem;
            margin-bottom: 8px;
        }

        .activity-date {
            color: var(--text-secondary);
            font-weight: 700;
            font-size: 0.85rem;
        }

        .favorites-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 14px;
            margin-top: 10px;
        }

        .favorite-card {
            background: rgba(255,255,255,0.75);
            border: 1px solid var(--border-color);
            border-radius: 18px;
            padding: 12px;
            display: grid;
            grid-template-columns: 52px 1fr;
            gap: 12px;
            align-items: center;
        }

        body.dark .favorite-card {
            background: rgba(30,41,59,0.75);
            border-color: #334155;
        }

        .favorite-thumb {
            width: 52px;
            height: 72px;
            border-radius: 12px;
            object-fit: cover;
            border: 1px solid rgba(0,0,0,0.06);
            background: #fff;
        }

        body.dark .favorite-thumb {
            border-color: rgba(255,255,255,0.12);
            background: #0f172a;
        }

        .favorite-name {
            font-weight: 900;
            margin-bottom: 4px;
            line-height: 1.2;
        }

        .favorite-game {
            color: var(--text-secondary);
            font-weight: 700;
            font-size: 0.9rem;
        }

        .favorite-link { text-decoration: none; color: inherit; }
        .favorite-link:hover { transform: translateY(-2px); box-shadow: 0 10px 22px rgba(0,0,0,0.10); transition: 0.2s; }

        .profile-header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.15);
            border: 1px solid rgba(255,255,255,0.2);
            text-align: center;
            margin-bottom: 40px;
            transition: transform 0.3s;
        }

        .profile-header:hover {
            transform: translateY(-5px);
        }

        .avatar-container {
            width: 120px;
            height: 120px;
            margin: 0 auto 20px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            color: white;
            font-weight: 800;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
        }

        .profile-name {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 10px;
            color: #0f172a;
            letter-spacing: -1px;
        }

        .profile-username {
            font-size: 1.2rem;
            color: var(--text-secondary);
            margin-bottom: 30px;
        }

        .profile-stats {
            display: flex;
            justify-content: center;
            gap: 60px;
            margin-top: 30px;
        }

        .stat-item {
            text-align: center;
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 800;
            color: var(--accent-blue);
        }

        .stat-label {
            font-size: 0.9rem;
            color: var(--text-secondary);
            margin-top: 5px;
            font-weight: 600;
        }

        .profile-sections {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 30px;
        }

        .profile-section {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.15);
            border: 1px solid rgba(255,255,255,0.2);
            transition: transform 0.3s;
        }

        .profile-section:hover {
            transform: translateY(-5px);
        }

        .section-title {
            font-size: 1.5rem;
            font-weight: 800;
            margin-bottom: 25px;
            color: #0f172a;
            letter-spacing: -0.5px;
        }

        .info-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid var(--border-color);
        }

        .info-item:last-child {
            border-bottom: none;
        }

        .info-label {
            font-weight: 600;
            color: var(--text-secondary);
        }

        .info-value {
            color: var(--text-primary);
            font-weight: 500;
        }

        .btn-edit {
            background: #0f172a;
            color: white;
            border: none;
            padding: 16px 32px;
            border-radius: 50px;
            font-weight: 700;
            cursor: pointer;
            transition: 0.3s;
            margin-top: 25px;
            font-size: 1rem;
        }

        .btn-edit:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--text-secondary);
        }

        .empty-state-icon {
            font-size: 4rem;
            margin-bottom: 20px;
            opacity: 0.6;
        }

        .empty-state p {
            font-size: 1.1rem;
            margin-bottom: 10px;
        }

        .empty-state small {
            font-size: 0.9rem;
            opacity: 0.8;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1001;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(15, 23, 42, 0.8);
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(8px);
        }

        .modal-content {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(20px);
            margin: auto;
            padding: 28px 32px;
            border-radius: 20px;
            width: 90%;
            max-width: 420px;
            max-height: 85vh;
            overflow-y: auto;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            border: 1px solid rgba(255, 255, 255, 0.18);
            position: relative;
            animation: modalSlideIn 0.3s ease-out;
        }

        @keyframes modalSlideIn {
            from {
                opacity: 0;
                transform: translateY(-20px) scale(0.95);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .close-button {
            position: absolute;
            top: 12px;
            right: 12px;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: var(--bg-main);
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
            color: var(--text-secondary);
            font-size: 16px;
            font-weight: 600;
        }

        .close-button:hover {
            background: #f1f5f9;
            color: var(--text-primary);
            transform: scale(1.1);
        }

        .modal h2 {
            font-size: 1.4rem;
            font-weight: 800;
            margin-bottom: 20px;
            color: #0f172a;
            letter-spacing: -0.025em;
            text-align: center;
            padding-right: 20px;
        }

        .form-group {
            margin-bottom: 16px;
        }

        .form-group label {
            display: block;
            margin-bottom: 6px;
            font-weight: 600;
            color: var(--text-secondary);
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.025em;
        }

        .form-group input {
            width: 100%;
            padding: 12px 14px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 0.95rem;
            font-family: 'Outfit', sans-serif;
            transition: all 0.2s ease;
            background: #f8fafc;
            color: var(--text-primary);
            box-sizing: border-box;
            font-weight: 500;
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--accent-blue);
            background: white;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
            transform: translateY(-1px);
        }

        .form-group input::placeholder {
            color: #94a3b8;
        }

        .modal .btn-main {
            width: 100%;
            margin-top: 4px;
            padding: 12px 20px;
            font-size: 0.95rem;
            font-weight: 700;
            border-radius: 10px;
            letter-spacing: 0.025em;
            transition: all 0.2s ease;
        }

        .modal .btn-main:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(15, 23, 42, 0.2);
        }

        .modal.show {
            display: flex;
        }

        @media (max-width: 768px) {
            .modal-content {
                padding: 20px 16px;
                margin: 16px;
                max-width: calc(100% - 32px);
            }
            
            .modal h2 {
                font-size: 1.25rem;
                margin-bottom: 16px;
                padding-right: 15px;
            }
            
            .form-group {
                margin-bottom: 14px;
            }
            
            .form-group input {
                padding: 10px 12px;
                font-size: 0.9rem;
            }
            
            .modal .btn-main {
                padding: 10px 16px;
                font-size: 0.9rem;
                margin-top: 2px;
            }
        }

        @media (max-width: 768px) {
            .profile-content {
                padding: 20px 15px 40px 15px;
            }
            
            .profile-header {
                padding: 30px 20px;
            }
            
            .profile-stats {
                gap: 30px;
            }
            
            .stat-number {
                font-size: 2rem;
            }
            
            .profile-name {
                font-size: 2rem;
            }
            
            .profile-sections {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            
            .profile-section {
                padding: 30px 20px;
            }
        }
        /* Dark mode overrides for profile */
        body.dark .profile-hero { background: #0f172a; }
        body.dark .profile-header { background: rgba(30,41,59,0.95); border-color: rgba(51,65,85,0.3); }
        body.dark .profile-name { color: #e2e8f0; }
        body.dark .section-title { color: #e2e8f0; }
        body.dark .profile-section { background: rgba(30,41,59,0.95); border-color: rgba(51,65,85,0.3); }
        body.dark .modal h2 { color: #e2e8f0; }
        body.dark .modal .modal-content { background: rgba(30,41,59,0.98); border-color: rgba(51,65,85,0.3); }
        body.dark .close-button { background: #334155; color: #e2e8f0; }
        body.dark .close-button:hover { background: #475569; }
        body.dark .form-group input { background: #0f172a; border-color: #334155; color: #e2e8f0; }
        body.dark .form-group input:focus { background: #1e293b; }
        body.dark .btn-edit { background: #e2e8f0; color: #0f172a; }
    </style>
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

        <a href="profile.php" class="nav-item user-active" title="Mi Perfil">
            <svg class="nav-icon" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z"/></svg>
            Perfil
        </a>
        <a href="logout.php" class="nav-item logout-btn" title="Cerrar Sesión">
            <svg class="nav-icon" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9"/></svg>
        </a>
    </nav>

    <div class="profile-container">
        <div class="profile-hero">
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
                <div class="profile-header">
                    <div class="avatar-container">
                        <?php echo strtoupper(substr($user['username'], 0, 1)); ?>
                    </div>
                    <h1 class="profile-name"><?php echo htmlspecialchars($user['name']); ?></h1>
                    <p class="profile-username">@<?php echo htmlspecialchars($user['username']); ?></p>
                    
                    <div class="profile-stats">
                        <div class="stat-item">
                            <div class="stat-number">0</div>
                            <div class="stat-label">Cartas</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number">0</div>
                            <div class="stat-label">Colecciones</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number">0</div>
                            <div class="stat-label">Intercambios</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="profile-content">
            <div class="profile-sections">
                <div class="profile-section">
                    <h3 class="section-title">📋 Información Personal</h3>
                    <div class="info-item">
                        <span class="info-label">Nombre</span>
                        <span class="info-value"><?php echo htmlspecialchars($user['name']); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Usuario</span>
                        <span class="info-value">@<?php echo htmlspecialchars($user['username']); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Email</span>
                        <span class="info-value"><?php echo htmlspecialchars($user['email']); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Miembro desde</span>
                        <span class="info-value"><?php echo date('d/m/Y', strtotime($user['created_at'])); ?></span>
                    </div>
                    <button class="btn-edit" onclick="openEditModal()">✏️ Editar Perfil</button>
                </div>

                <div class="profile-section">
                    <h3 class="section-title">🎯 Actividad Reciente</h3>
                    <?php if (count($recentActivity) === 0): ?>
                        <div class="empty-state">
                            <div class="empty-state-icon">📚</div>
                            <p>Aún no tienes actividad</p>
                            <small>Comienza explorando el catálogo de cartas</small>
                        </div>
                    <?php else: ?>
                        <div class="activity-list">
                            <?php foreach ($recentActivity as $act): ?>
                                <?php
                                    $thumb = null;
                                    $cardName = null;
                                    if (($act['activity_type'] ?? '') === 'order' && !empty($act['ref_id'])) {
                                        $oid = (int)$act['ref_id'];
                                        if (isset($orderPreviewById[$oid])) {
                                            $thumb = $orderPreviewById[$oid]['card_image'] ?? null;
                                            $cardName = $orderPreviewById[$oid]['card_name'] ?? null;
                                        }
                                    }
                                ?>
                                <div class="activity-item">
                                    <?php if ($thumb): ?>
                                        <img class="activity-thumb" src="<?php echo htmlspecialchars($thumb); ?>" alt="">
                                    <?php else: ?>
                                        <div></div>
                                    <?php endif; ?>
                                    <div>
                                        <div class="activity-title"><?php echo htmlspecialchars($act['title']); ?></div>
                                        <?php if ($cardName): ?>
                                            <div class="activity-desc"><?php echo htmlspecialchars($cardName); ?></div>
                                        <?php endif; ?>
                                        <div class="activity-desc"><?php echo htmlspecialchars($act['description']); ?></div>
                                        <div class="activity-date"><?php echo date('d/m/Y H:i', strtotime($act['created_at'])); ?></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="profile-section">
                    <h3 class="section-title">⭐ Favoritos</h3>
                    <?php if (count($favorites) === 0): ?>
                        <div class="empty-state">
                            <div class="empty-state-icon">💝</div>
                            <p>No tienes cartas favoritas</p>
                            <small>Añade cartas a tus favoritos para verlas aquí</small>
                        </div>
                    <?php else: ?>
                        <div class="favorites-grid">
                            <?php foreach ($favorites as $fav): ?>
                                <?php
                                    $gameKey = 'pokemon';
                                    $g = strtolower((string)$fav['card_game']);
                                    if (strpos($g, 'yugioh') !== false || strpos($g, 'yu-gi-oh') !== false) $gameKey = 'yugioh';
                                    else if (strpos($g, 'magic') !== false) $gameKey = 'magic';
                                    else if (strpos($g, 'one') !== false && strpos($g, 'piece') !== false) $gameKey = 'onepiece';
                                    else if (strpos($g, 'pokemon') !== false || strpos($g, 'pok') !== false) $gameKey = 'pokemon';
                                ?>
                                <a class="favorite-card favorite-link" href="mercado.php?game=<?php echo urlencode($gameKey); ?>&open_fav=1&card_id=<?php echo urlencode($fav['card_id']); ?>&card_name=<?php echo urlencode($fav['card_name']); ?>&card_image=<?php echo urlencode($fav['card_image']); ?>&card_game=<?php echo urlencode($fav['card_game']); ?>">
                                    <img class="favorite-thumb" src="<?php echo htmlspecialchars($fav['card_image']); ?>" alt="">
                                    <div>
                                        <div class="favorite-name"><?php echo htmlspecialchars($fav['card_name']); ?></div>
                                        <div class="favorite-game"><?php echo htmlspecialchars($fav['card_game']); ?></div>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Edición -->
    <div id="editProfileModal" class="modal">
        <div class="modal-content">
            <button class="close-button" onclick="closeEditModal()" aria-label="Cerrar">×</button>
            <h2>Editar Perfil</h2>
            <form id="editProfileForm">
                <div class="form-group">
                    <label for="editName">Nombre</label>
                    <input type="text" id="editName" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="editUsername">Usuario</label>
                    <input type="text" id="editUsername" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="editEmail">Email</label>
                    <input type="email" id="editEmail" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                </div>
                <button type="submit" class="btn-main full-width">Guardar Cambios</button>
            </form>
        </div>
    </div>

    <script>
        function openEditModal() {
            document.getElementById('editProfileModal').classList.add('show');
        }

        function closeEditModal() {
            document.getElementById('editProfileModal').classList.remove('show');
        }

        // Cerrar modal al hacer clic fuera
        window.onclick = function(event) {
            const modal = document.getElementById('editProfileModal');
            if (event.target == modal) {
                closeEditModal();
            }
        }

        // Manejar envío del formulario
        document.getElementById('editProfileForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const data = Object.fromEntries(formData);
            
            fetch('update_profile.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams(data)
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    alert(result.message);
                    closeEditModal();
                    // Recargar la página para mostrar los cambios
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    alert(result.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al actualizar el perfil. Inténtalo de nuevo.');
            });
        });
    </script>

</body>
</html>
