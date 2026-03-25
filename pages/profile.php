<?php
session_start();

// Verificar si el usuario está logueado
if (!isset($_SESSION['user_id'])) {
    header("Location: auth.php");
    exit;
}

require_once dirname(__DIR__) . '/includes/db.php';

// Migración: añadir columna address si no existe
try { $conn->query("ALTER TABLE users ADD COLUMN address TEXT NULL"); } catch (Exception $e) {}

// Obtener datos del usuario
$stmt = $conn->prepare("SELECT name, email, username, created_at, avatar_url, address FROM users WHERE id = ?");
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
$activityStmt = $conn->prepare("SELECT activity_type, title, description, ref_id, created_at FROM user_activity WHERE user_id = ? ORDER BY created_at DESC LIMIT 20");
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

// Estadísticas reales
$statsCartas = 0;
$statsColecciones = 0;
$statsIntercambios = 0;

// Cartas: cartas compradas (items pedidos) + subastas ganadas
$stC = $conn->prepare("SELECT COUNT(*) AS n FROM cart_order_items oi JOIN cart_orders o ON o.id = oi.order_id WHERE o.user_id = ?");
if ($stC) { $stC->bind_param("i",$_SESSION['user_id']); $stC->execute(); $statsCartas += (int)$stC->get_result()->fetch_assoc()['n']; $stC->close(); }
$stA = $conn->prepare("SELECT COUNT(*) AS n FROM auctions WHERE current_winner_id = ? AND status = 'ended'");
if ($stA) { $stA->bind_param("i",$_SESSION['user_id']); $stA->execute(); $statsCartas += (int)$stA->get_result()->fetch_assoc()['n']; $stA->close(); }

// Colecciones: juegos distintos de cartas compradas/ganadas
$stG = $conn->prepare("SELECT COUNT(DISTINCT oi.card_game) AS n FROM cart_order_items oi JOIN cart_orders o ON o.id=oi.order_id WHERE o.user_id=?");
if ($stG) { $stG->bind_param("i",$_SESSION['user_id']); $stG->execute(); $statsColecciones = (int)$stG->get_result()->fetch_assoc()['n']; $stG->close(); }

// Intercambios: subastas donde ha pujado
$stI = $conn->prepare("SELECT COUNT(DISTINCT auction_id) AS n FROM auction_bids WHERE user_id=?");
if ($stI) { $stI->bind_param("i",$_SESSION['user_id']); $stI->execute(); $statsIntercambios = (int)$stI->get_result()->fetch_assoc()['n']; $stI->close(); }

// Subastas ganadas + claims
$wonAuctions = [];
$conn->query("CREATE TABLE IF NOT EXISTS auction_claims (
    id INT AUTO_INCREMENT PRIMARY KEY,
    auction_id INT NOT NULL,
    user_id INT NOT NULL,
    choice ENUM('delivery','exchange') NOT NULL,
    address TEXT NULL,
    lujanitos_awarded INT DEFAULT 0,
    status ENUM('pending','processing','shipped','delivered','done') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user (user_id)
)");
$stW = $conn->prepare(
    "SELECT a.id, a.card_name, a.card_image, a.card_game, a.current_bid, a.ends_at,
            cl.choice, cl.status AS claim_status, cl.address, cl.lujanitos_awarded
     FROM auctions a
     LEFT JOIN auction_claims cl ON cl.auction_id = a.id AND cl.user_id = ?
     WHERE a.current_winner_id = ? AND a.status = 'ended'
     ORDER BY a.ends_at DESC LIMIT 10"
);
if ($stW) {
    $stW->bind_param("ii", $_SESSION['user_id'], $_SESSION['user_id']);
    $stW->execute();
    $wonRes = $stW->get_result();
    while ($row = $wonRes->fetch_assoc()) { $wonAuctions[] = $row; }
    $stW->close();
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
    <link rel="stylesheet" href="../assets/css/styles.css">
    <style>
        .profile-container {
            position: relative;
            min-height: 100vh;
            background: var(--bg-main);
        }

        .profile-hero {
            position: relative;
            min-height: 70vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            padding: 0 20px;
            background: #fff;
        }
        body.dark .profile-hero { background: #0f172a; }
        .profile-hero .card-wall-bg {
            grid-template-columns: repeat(5, 1fr);
            mask-image: radial-gradient(circle at center, rgba(0,0,0,0) 0%, rgba(0,0,0,0.9) 70%);
            -webkit-mask-image: radial-gradient(circle at center, rgba(0,0,0,0) 0%, rgba(0,0,0,0.9) 70%);
        }
        .profile-hero::before, .profile-hero::after {
            content: ''; position: absolute; left: 0; width: 100%; height: 180px; z-index: 1; pointer-events: none;
        }
        .profile-hero::before { top: 0; background: linear-gradient(to bottom, #fff 0%, transparent 100%); }
        .profile-hero::after  { bottom: 0; background: linear-gradient(to top, #fff 0%, transparent 100%); }
        body.dark .profile-hero::before { background: linear-gradient(to bottom, #0f172a 0%, transparent 100%); }
        body.dark .profile-hero::after  { background: linear-gradient(to top, #0f172a 0%, transparent 100%); }

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
            cursor: pointer;
            position: relative;
            overflow: hidden;
            transition: box-shadow .25s;
        }
        .avatar-container:hover { box-shadow: 0 10px 30px rgba(59,130,246,.45); }
        .avatar-container img { width: 100%; height: 100%; object-fit: cover; border-radius: 50%; }
        .avatar-camera-overlay {
            position: absolute; inset: 0; border-radius: 50%;
            background: rgba(0,0,0,.45);
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            opacity: 0; transition: opacity .2s; font-size: .75rem; font-weight: 700; gap: 4px;
        }
        .avatar-container:hover .avatar-camera-overlay { opacity: 1; }
        #avatar-upload-input { display: none; }

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

    <?php include dirname(__DIR__) . '/includes/navbar.php'; ?>

    <div class="profile-container">
        <div class="profile-hero">
            <div class="card-wall-bg">
                <div class="wall-column col-up">
                    <div class="wall-img" style="background-image: url('https://images.pokemontcg.io/base1/4_hires.png')"></div>
                    <div class="wall-img" style="background-image: url('https://images.ygoprodeck.com/images/cards/89631139.jpg')"></div>
                    <div class="wall-img" style="background-image: url('https://cards.scryfall.io/large/front/b/d/bd8fa327-dd41-4737-8f19-2cf5eb1f7cdd.jpg')"></div>
                    <div class="wall-img" style="background-image: url('https://images.pokemontcg.io/swsh35/74_hires.png')"></div>
                    <div class="wall-img" style="background-image: url('https://images.ygoprodeck.com/images/cards/46986414.jpg')"></div>
                </div>
                <div class="wall-column col-down">
                    <div class="wall-img" style="background-image: url('https://asia-en.onepiece-cardgame.com/images/cardlist/card/OP05-060.png')"></div>
                    <div class="wall-img" style="background-image: url('https://images.pokemontcg.io/base1/2_hires.png')"></div>
                    <div class="wall-img" style="background-image: url('https://images.ygoprodeck.com/images/cards/33396948.jpg')"></div>
                    <div class="wall-img" style="background-image: url('https://asia-en.onepiece-cardgame.com/images/cardlist/card/OP01-016.png')"></div>
                    <div class="wall-img" style="background-image: url('https://images.pokemontcg.io/swsh7/215_hires.png')"></div>
                </div>
                <div class="wall-column col-up">
                    <div class="wall-img" style="background-image: url('https://cards.scryfall.io/large/front/7/0/70901356-3266-4bd9-aacc-f06c27271de5.jpg')"></div>
                    <div class="wall-img" style="background-image: url('https://asia-en.onepiece-cardgame.com/images/cardlist/card/OP01-025.png')"></div>
                    <div class="wall-img" style="background-image: url('https://images.pokemontcg.io/neo1/9_hires.png')"></div>
                    <div class="wall-img" style="background-image: url('https://cards.scryfall.io/large/front/e/3/e3285e6b-3e79-4d7c-bf96-d920f973b122.jpg')"></div>
                    <div class="wall-img" style="background-image: url('https://images.pokemontcg.io/swsh11/186_hires.png')"></div>
                </div>
                <div class="wall-column col-down">
                    <div class="wall-img" style="background-image: url('https://cards.scryfall.io/large/front/2/3/2398892d-28e9-4009-81ec-0d544af79d2b.jpg')"></div>
                    <div class="wall-img" style="background-image: url('https://asia-en.onepiece-cardgame.com/images/cardlist/card/OP01-120.png')"></div>
                    <div class="wall-img" style="background-image: url('https://images.pokemontcg.io/base1/15_hires.png')"></div>
                    <div class="wall-img" style="background-image: url('https://images.ygoprodeck.com/images/cards/4031928.jpg')"></div>
                    <div class="wall-img" style="background-image: url('https://images.pokemontcg.io/swsh4/43_hires.png')"></div>
                </div>
                <div class="wall-column col-up">
                    <div class="wall-img" style="background-image: url('https://cards.scryfall.io/large/front/f/2/f2bc06cb-2f22-4313-82a2-a7e7b2564f4d.jpg')"></div>
                    <div class="wall-img" style="background-image: url('https://asia-en.onepiece-cardgame.com/images/cardlist/card/OP01-121.png')"></div>
                    <div class="wall-img" style="background-image: url('https://images.pokemontcg.io/swsh1/1_hires.png')"></div>
                    <div class="wall-img" style="background-image: url('https://images.ygoprodeck.com/images/cards/10000020.jpg')"></div>
                    <div class="wall-img" style="background-image: url('https://images.pokemontcg.io/base1/2_hires.png')"></div>
                </div>
            </div>

            <div class="hero-content">
                <div class="profile-header">
                    <div class="avatar-container" onclick="document.getElementById('avatar-upload-input').click()" title="Cambiar foto">
                        <?php if (!empty($user['avatar_url'])): ?>
                            <?php $av = $user['avatar_url']; if (!str_starts_with($av,'/') && !str_starts_with($av,'http')) $av='../'.$av; ?>
                            <img src="<?php echo htmlspecialchars($av); ?>?v=<?php echo time(); ?>" alt="Avatar" id="avatar-img">
                        <?php else: ?>
                            <span id="avatar-letter"><?php echo strtoupper(substr($user['username'], 0, 1)); ?></span>
                        <?php endif; ?>
                        <div class="avatar-camera-overlay">
                            <span style="font-size:1.4rem;">📷</span>
                            <span>Cambiar foto</span>
                        </div>
                    </div>
                    <input type="file" id="avatar-upload-input" accept="image/jpeg,image/png,image/webp,image/gif">
                    <h1 class="profile-name"><?php echo htmlspecialchars($user['name']); ?></h1>
                    <p class="profile-username">@<?php echo htmlspecialchars($user['username']); ?></p>
                    
                    <div class="profile-stats">
                        <div class="stat-item">
                            <div class="stat-number"><?php echo $statsCartas; ?></div>
                            <div class="stat-label">Cartas</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number"><?php echo $statsColecciones; ?></div>
                            <div class="stat-label">Colecciones</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number"><?php echo $statsIntercambios; ?></div>
                            <div class="stat-label">Pujas</div>
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
                        <div class="activity-list" id="activity-list-short">
                            <?php foreach (array_slice($recentActivity, 0, 3) as $act): ?>
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
                                        <?php if ($cardName): ?><div class="activity-desc"><?php echo htmlspecialchars($cardName); ?></div><?php endif; ?>
                                        <div class="activity-desc"><?php echo htmlspecialchars($act['description']); ?></div>
                                        <div class="activity-date"><?php echo date('d/m/Y H:i', strtotime($act['created_at'])); ?></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <?php if (count($recentActivity) > 3): ?>
                        <div class="activity-list" id="activity-list-full" style="display:none;">
                            <?php foreach ($recentActivity as $act): ?>
                                <?php
                                    $thumb = null; $cardName = null;
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
                                        <?php if ($cardName): ?><div class="activity-desc"><?php echo htmlspecialchars($cardName); ?></div><?php endif; ?>
                                        <div class="activity-desc"><?php echo htmlspecialchars($act['description']); ?></div>
                                        <div class="activity-date"><?php echo date('d/m/Y H:i', strtotime($act['created_at'])); ?></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <button onclick="toggleActivity()" id="activity-toggle-btn"
                            style="margin-top:14px;background:none;border:2px solid var(--border-color);border-radius:50px;padding:8px 20px;font-weight:700;cursor:pointer;color:var(--text-secondary);width:100%;font-family:'Outfit',sans-serif;font-size:.9rem;">
                            Ver toda la actividad (<?php echo count($recentActivity); ?>) ▼
                        </button>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>

                <!-- Subastas ganadas + envíos -->
                <div class="profile-section">
                    <h3 class="section-title">🏆 Subastas Ganadas</h3>
                    <?php if (empty($wonAuctions)): ?>
                        <div class="empty-state">
                            <div class="empty-state-icon">🎯</div>
                            <p>Aún no has ganado ninguna subasta</p>
                            <small><a href="apuestas.php" style="color:var(--accent-blue);">Explorar subastas</a></small>
                        </div>
                    <?php else: ?>
                        <div style="display:flex;flex-direction:column;gap:12px;margin-top:10px;">
                            <?php foreach ($wonAuctions as $w): ?>
                                <?php
                                    $claimIcon = '';
                                    $claimLabel = '';
                                    if ($w['choice'] === 'exchange') { $claimIcon = '🪙'; $claimLabel = 'Canjeada por ' . number_format($w['lujanitos_awarded']) . ' LJ'; }
                                    elseif ($w['choice'] === 'delivery') {
                                        $statusMap = ['pending'=>'Pendiente','processing'=>'Procesando','shipped'=>'Enviada','delivered'=>'Entregada'];
                                        $claimIcon = '📦'; $claimLabel = $statusMap[$w['claim_status']] ?? 'En proceso';
                                    }
                                ?>
                                <div style="display:grid;grid-template-columns:52px 1fr;gap:12px;align-items:center;background:rgba(255,255,255,0.75);border:1px solid var(--border-color);border-radius:16px;padding:12px;">
                                    <img src="<?php echo htmlspecialchars($w['card_image']); ?>" alt="" style="width:52px;height:72px;border-radius:10px;object-fit:cover;">
                                    <div>
                                        <div style="font-weight:900;margin-bottom:4px;"><?php echo htmlspecialchars($w['card_name']); ?></div>
                                        <div style="color:var(--text-secondary);font-size:.85rem;margin-bottom:4px;"><?php echo htmlspecialchars($w['card_game']); ?> — <?php echo number_format((int)$w['current_bid']); ?> LJ</div>
                                        <?php if ($claimLabel): ?>
                                            <span style="font-size:.78rem;font-weight:800;padding:3px 10px;border-radius:20px;background:rgba(16,185,129,.15);color:#059669;">
                                                <?php echo $claimIcon . ' ' . htmlspecialchars($claimLabel); ?>
                                            </span>
                                        <?php else: ?>
                                            <a href="apuestas.php" style="font-size:.78rem;font-weight:800;padding:3px 10px;border-radius:20px;background:rgba(139,92,246,.15);color:#7c3aed;text-decoration:none;">
                                                Reclamar →
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Envíos -->
                <?php
                    $shipmentsWon = array_filter($wonAuctions, fn($w) => $w['choice'] === 'delivery');
                ?>
                <?php if (!empty($shipmentsWon)): ?>
                <div class="profile-section">
                    <h3 class="section-title">📦 Mis Envíos</h3>
                    <div style="display:flex;flex-direction:column;gap:14px;margin-top:10px;">
                        <?php foreach ($shipmentsWon as $s): ?>
                            <?php
                                $steps = ['pending'=>1,'processing'=>2,'shipped'=>3,'delivered'=>4,'done'=>4];
                                $step  = $steps[$s['claim_status']] ?? 1;
                                $labels = ['Recibido','Preparando','Enviado','Entregado'];
                                $statusColors = ['pending'=>'#f59e0b','processing'=>'#3b82f6','shipped'=>'#8b5cf6','delivered'=>'#10b981','done'=>'#10b981'];
                                $color = $statusColors[$s['claim_status']] ?? '#94a3b8';
                            ?>
                            <div style="background:rgba(255,255,255,0.75);border:1px solid var(--border-color);border-radius:16px;padding:16px;">
                                <div style="display:grid;grid-template-columns:52px 1fr;gap:12px;align-items:center;margin-bottom:16px;">
                                    <img src="<?php echo htmlspecialchars($s['card_image']); ?>" alt="" style="width:52px;height:72px;border-radius:10px;object-fit:cover;">
                                    <div>
                                        <div style="font-weight:900;margin-bottom:4px;"><?php echo htmlspecialchars($s['card_name']); ?></div>
                                        <div style="color:var(--text-secondary);font-size:.85rem;"><?php echo htmlspecialchars($s['address']); ?></div>
                                    </div>
                                </div>
                                <div style="display:flex;gap:0;position:relative;">
                                    <?php foreach ($labels as $i => $lbl): ?>
                                        <?php $done = ($i + 1) <= $step; ?>
                                        <div style="flex:1;text-align:center;position:relative;">
                                            <div style="width:28px;height:28px;border-radius:50%;margin:0 auto 6px;display:flex;align-items:center;justify-content:center;font-size:.75rem;font-weight:800;
                                                background:<?php echo $done ? $color : 'var(--border-color)'; ?>;
                                                color:<?php echo $done ? '#fff' : 'var(--text-secondary)'; ?>;">
                                                <?php echo $done ? '✓' : ($i+1); ?>
                                            </div>
                                            <div style="font-size:.72rem;font-weight:700;color:<?php echo $done ? 'var(--text-primary)' : 'var(--text-secondary)'; ?>">
                                                <?php echo $lbl; ?>
                                            </div>
                                            <?php if ($i < 3): ?>
                                            <div style="position:absolute;top:14px;left:calc(50% + 14px);right:calc(-50% + 14px);height:2px;background:<?php echo ($i+1 < $step) ? $color : 'var(--border-color)'; ?>;"></div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

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
                <div class="form-group">
                    <label for="editAddress">Dirección de envío</label>
                    <input type="text" id="editAddress" name="address" value="<?php echo htmlspecialchars($user['address'] ?? ''); ?>" placeholder="Calle, ciudad, código postal">
                </div>
                <button type="submit" class="btn-main full-width">Guardar Cambios</button>
            </form>
        </div>
    </div>

    <script>
        function toggleActivity() {
            const short = document.getElementById('activity-list-short');
            const full  = document.getElementById('activity-list-full');
            const btn   = document.getElementById('activity-toggle-btn');
            if (!full) return;
            const expanded = full.style.display !== 'none';
            full.style.display  = expanded ? 'none' : '';
            short.style.display = expanded ? ''     : 'none';
            btn.textContent = expanded
                ? `Ver toda la actividad ▼`
                : `Ver menos ▲`;
        }

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
            
            fetch('../api/update_profile.php', {
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

        // ── Subir foto de perfil ──────────────────────────
        document.getElementById('avatar-upload-input').addEventListener('change', async function() {
            const file = this.files[0];
            if (!file) return;
            const fd = new FormData();
            fd.append('avatar', file);
            const overlay = document.querySelector('.avatar-camera-overlay');
            if (overlay) overlay.innerHTML = '<span style="font-size:1.4rem">⏳</span><span>Subiendo...</span>';
            try {
                const res  = await fetch('../api/upload_avatar.php', { method: 'POST', body: fd });
                const data = await res.json();
                if (data.ok) {
                    // Mostrar imagen inmediatamente sin recargar
                    const container = document.querySelector('.avatar-container');
                    const letter = document.getElementById('avatar-letter');
                    if (letter) letter.remove();
                    let img = document.getElementById('avatar-img');
                    if (!img) {
                        img = document.createElement('img');
                        img.id = 'avatar-img';
                        img.style.cssText = 'width:100%;height:100%;object-fit:cover;border-radius:50%;';
                        container.insertBefore(img, container.firstChild);
                    }
                    const rawUrl = data.url || '';
                    img.src = (rawUrl && !rawUrl.startsWith('/') && !rawUrl.startsWith('http') ? '../' + rawUrl : rawUrl) + '?v=' + Date.now();
                } else {
                    alert(data.message || 'Error al subir la foto');
                }
            } catch(e) {
                alert('Error de conexión al subir la foto');
            } finally {
                const ov = document.querySelector('.avatar-camera-overlay');
                if (ov) ov.innerHTML = '<span style="font-size:1.4rem">📷</span><span>Cambiar foto</span>';
                this.value = '';
            }
        });
    </script>

<script src="../assets/js/csrf.js?v=<?php echo time(); ?>"></script>
</body>
</html>
