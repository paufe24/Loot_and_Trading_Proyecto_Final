<?php
session_start();
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/csrf.php';
header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') csrf_verify();

// Migración: añadir seller_id si la tabla ya existía sin esa columna
@$conn->query("ALTER TABLE auctions ADD COLUMN IF NOT EXISTS seller_id INT NULL");

// Resolver subastas expiradas antes de cualquier acción
resolveEnded($conn);

switch ($action) {
    case 'list':           listAuctions($conn);           break;
    case 'bid':            placeBid($conn);               break;
    case 'bids':           getAuctionBids($conn);         break;
    case 'balance':        getBalance($conn);             break;
    case 'create':         createAuction($conn);          break;
    case 'my_bids':        getMyBids($conn);              break;
    case 'my_wins':        getMyWins($conn);              break;
    case 'claim_win':      claimWin($conn);               break;
    default: echo json_encode(['ok' => false, 'message' => 'Acción desconocida']);
}

/* ── Resolver subastas terminadas ─────── */
function resolveEnded($conn) {
    $ended = $conn->query(
        "SELECT id, current_winner_id, current_bid, card_name, card_image, card_game, badge_color, base_price
         FROM auctions
         WHERE status = 'active' AND ends_at <= NOW()"
    )->fetch_all(MYSQLI_ASSOC);

    foreach ($ended as $a) {
        $endId = (int)$a['id'];
        $stEnd = $conn->prepare("UPDATE auctions SET status='ended' WHERE id=?");
        $stEnd->bind_param("i", $endId);
        $stEnd->execute();

        // Notificar al ganador en su actividad de perfil
        if (!empty($a['current_winner_id']) && $a['current_bid'] > 0) {
            $aId   = (int)$a['id'];
            $wId   = (int)$a['current_winner_id'];
            $bid   = (int)$a['current_bid'];

            $cardName = $a['card_name'] ?? 'Carta';

            $type  = 'auction_win';
            $title = 'Subasta ganada';
            $desc  = "¡Ganaste \"$cardName\" por $bid Lujanitos!";

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

            $ins = $conn->prepare(
                "INSERT INTO user_activity (user_id, activity_type, title, description, ref_id, amount)
                 VALUES (?, ?, ?, ?, ?, ?)"
            );
            $ins->bind_param("isssid", $wId, $type, $title, $desc, $aId, $bid);
            $ins->execute();
        }

        // Crear nueva subasta automática para reemplazar la terminada
        $duration_h  = rand(1, 6);
        $new_ends_at = date('Y-m-d H:i:s', strtotime("+{$duration_h} hours"));
        $card_name   = $a['card_name'];
        $card_image  = $a['card_image'];
        $card_game   = $a['card_game'];
        $badge_color = $a['badge_color'];
        $base_price  = (int)$a['base_price'];
        $stIns = $conn->prepare(
            "INSERT INTO auctions (card_name, card_image, card_game, badge_color, base_price, ends_at, status)
             VALUES (?, ?, ?, ?, ?, ?, 'active')"
        );
        $stIns->bind_param("ssssis", $card_name, $card_image, $card_game, $badge_color, $base_price, $new_ends_at);
        $stIns->execute();
    }

    // Si no hay ninguna subasta activa, generar subastas desde el catálogo predefinido
    $activeCount = (int)$conn->query("SELECT COUNT(*) FROM auctions WHERE status='active'")->fetch_row()[0];
    if ($activeCount === 0) {
        spawnDefaultAuctions($conn);
    }
}

/* ── Generar subastas predefinidas si no hay ninguna activa ── */
function spawnDefaultAuctions($conn) {
    $catalog = [
        ['Charizard ex', 'https://images.pokemontcg.io/sv3pt5/54_hires.png',       'pokemon',  '#ef4444', 500],
        ['Blue-Eyes White Dragon', 'https://images.ygoprodeck.com/images/cards/89631139.jpg', 'yugioh', '#3b82f6', 400],
        ['Black Lotus', 'https://cards.scryfall.io/large/front/b/d/bd8fa327-dd41-4737-8f19-2cf5eb1f7cdd.jpg', 'magic', '#8b5cf6', 800],
        ['Monkey D. Luffy OP01', 'https://en.onepiece-cardgame.com/images/cardlist/card/OP01-060.png', 'onepiece', '#f97316', 350],
        ['Pikachu V', 'https://images.pokemontcg.io/swsh45sv/44_hires.png',        'pokemon',  '#facc15', 300],
        ['Dark Magician', 'https://images.ygoprodeck.com/images/cards/46986414.jpg', 'yugioh',  '#6366f1', 250],
        ['Roronoa Zoro OP01', 'https://en.onepiece-cardgame.com/images/cardlist/card/OP01-001.png', 'onepiece', '#10b981', 300],
        ['Mewtwo ex', 'https://images.pokemontcg.io/sv3pt5/205_hires.png',         'pokemon',  '#a855f7', 600],
    ];

    $badgeMap = [
        'pokemon'  => '#ef4444',
        'yugioh'   => '#3b82f6',
        'magic'    => '#8b5cf6',
        'onepiece' => '#f97316',
    ];

    $stSpawn = $conn->prepare(
        "INSERT INTO auctions (card_name, card_image, card_game, badge_color, base_price, ends_at, status)
         VALUES (?, ?, ?, ?, ?, ?, 'active')"
    );
    foreach ($catalog as $card) {
        [$name, $image, $game, $badge, $price] = $card;
        $duration_h  = rand(1, 6);
        $new_ends_at = date('Y-m-d H:i:s', strtotime("+{$duration_h} hours"));
        $stSpawn->bind_param("ssssis", $name, $image, $game, $badge, $price, $new_ends_at);
        $stSpawn->execute();
    }
}

/* ── Listar subastas activas ──────────── */
function listAuctions($conn) {
    $user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
    $result  = $conn->query(
        "SELECT id, card_name, card_image, card_game, badge_color,
                base_price, current_bid, current_winner_id, current_winner_name,
                ends_at, status
         FROM auctions
         ORDER BY ends_at ASC"
    );
    $auctions = $result->fetch_all(MYSQLI_ASSOC);
    echo json_encode(['ok' => true, 'auctions' => $auctions, 'user_id' => $user_id]);
}

/* ── Pujar ───────────────────────────── */
function placeBid($conn) {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['ok' => false, 'message' => 'Debes iniciar sesión para pujar']);
        return;
    }

    $user_id    = (int)$_SESSION['user_id'];
    $auction_id = (int)($_POST['auction_id'] ?? 0);
    $amount     = (int)($_POST['amount']     ?? 0);

    // Cargar subasta
    $stmt = $conn->prepare("SELECT * FROM auctions WHERE id = ? AND status = 'active' FOR UPDATE");
    $stmt->bind_param("i", $auction_id);
    $stmt->execute();
    $auction = $stmt->get_result()->fetch_assoc();

    if (!$auction) {
        echo json_encode(['ok' => false, 'message' => 'Subasta no encontrada o ya terminada']);
        return;
    }

    if (strtotime($auction['ends_at']) <= time()) {
        echo json_encode(['ok' => false, 'message' => 'Esta subasta ya ha terminado']);
        return;
    }

    $min_bid = max($auction['base_price'], $auction['current_bid'] + 10);
    if ($amount < $min_bid) {
        echo json_encode(['ok' => false, 'message' => "La puja mínima es {$min_bid} LC"]);
        return;
    }

    if ($auction['current_winner_id'] == $user_id) {
        echo json_encode(['ok' => false, 'message' => 'Ya eres el mejor postor']);
        return;
    }

    // Comprobar balance del pujador
    $balStmt = $conn->prepare("SELECT lootcoins, username FROM users WHERE id = ?");
    $balStmt->bind_param("i", $user_id);
    $balStmt->execute();
    $user = $balStmt->get_result()->fetch_assoc();

    if ($user['lootcoins'] < $amount) {
        echo json_encode(['ok' => false, 'message' => 'No tienes suficientes LootCoins']);
        return;
    }

    $conn->begin_transaction();
    try {
        // Descontar Lujanitos al nuevo pujador
        $deduct = $conn->prepare("UPDATE users SET lootcoins = lootcoins - ? WHERE id = ?");
        $deduct->bind_param("ii", $amount, $user_id);
        $deduct->execute();

        // Devolver al pujador anterior si existe
        if ($auction['current_winner_id'] && $auction['current_bid'] > 0) {
            $prev_amount = (int)$auction['current_bid'];
            $prev_id     = (int)$auction['current_winner_id'];
            $refund = $conn->prepare("UPDATE users SET lootcoins = lootcoins + ? WHERE id = ?");
            $refund->bind_param("ii", $prev_amount, $prev_id);
            $refund->execute();
        }

        // Actualizar subasta
        $upd = $conn->prepare(
            "UPDATE auctions SET current_bid=?, current_winner_id=?, current_winner_name=? WHERE id=?"
        );
        $upd->bind_param("iisi", $amount, $user_id, $user['username'], $auction_id);
        $upd->execute();

        // Registrar puja en historial
        $ins = $conn->prepare(
            "INSERT INTO auction_bids (auction_id, user_id, username, amount) VALUES (?,?,?,?)"
        );
        $ins->bind_param("iisi", $auction_id, $user_id, $user['username'], $amount);
        $ins->execute();

        $conn->commit();

        // Nuevo balance
        $newBal = $user['lootcoins'] - $amount;
        echo json_encode([
            'ok'          => true,
            'new_balance' => $newBal,
            'current_bid' => $amount,
            'winner'      => $user['username']
        ]);

    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['ok' => false, 'message' => 'Error interno']);
    }
}

/* ── Historial de pujas de una subasta ── */
function getAuctionBids($conn) {
    $auction_id = (int)($_GET['auction_id'] ?? 0);
    $stmt = $conn->prepare(
        "SELECT username, amount, created_at FROM auction_bids
         WHERE auction_id = ? ORDER BY amount DESC LIMIT 10"
    );
    $stmt->bind_param("i", $auction_id);
    $stmt->execute();
    $bids = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    echo json_encode(['ok' => true, 'bids' => $bids]);
}

/* ── Balance ──────────────────────────── */
function getBalance($conn) {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['ok' => false]);
        return;
    }
    $stmt = $conn->prepare("SELECT lootcoins FROM users WHERE id = ?");
    $stmt->bind_param("i", (int)$_SESSION['user_id']);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    echo json_encode(['ok' => true, 'balance' => (int)$row['lootcoins']]);
}

/* ── Crear subasta (vender) ───────────── */
function createAuction($conn) {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['ok' => false, 'message' => 'Debes iniciar sesión']);
        return;
    }
    $user_id      = (int)$_SESSION['user_id'];
    $card_name    = trim($_POST['card_name']    ?? '');
    $card_image   = trim($_POST['card_image']   ?? '');
    $card_game    = trim($_POST['card_game']    ?? '');
    $badge_color  = trim($_POST['badge_color']  ?? '#3b82f6');
    $base_price   = (int)($_POST['base_price']  ?? 0);
    $duration_h   = min(max((int)($_POST['duration_hours'] ?? 24), 1), 168);

    if (!$card_name || !$card_game || $base_price < 1) {
        echo json_encode(['ok' => false, 'message' => 'Datos incompletos']);
        return;
    }
    if (!$card_image) $card_image = 'https://upload.wikimedia.org/wikipedia/en/3/3b/Pokemon_Trading_Card_Game_cardback.jpg';

    // Verificar que el usuario tiene al menos base_price de Lujanitos para cubrir posibles garantías (opcional)
    $ends_at = date('Y-m-d H:i:s', strtotime("+{$duration_h} hours"));

    $conn->query("CREATE TABLE IF NOT EXISTS auctions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        card_name VARCHAR(255) NOT NULL,
        card_image VARCHAR(500) NOT NULL,
        card_game VARCHAR(50) NOT NULL,
        badge_color VARCHAR(20) DEFAULT '#3b82f6',
        base_price INT NOT NULL DEFAULT 100,
        current_bid INT NOT NULL DEFAULT 0,
        current_winner_id INT NULL,
        current_winner_name VARCHAR(100) NULL,
        seller_id INT NULL,
        ends_at DATETIME NOT NULL,
        status ENUM('active','ended') DEFAULT 'active',
        INDEX idx_status_ends (status, ends_at)
    )");

    $stmt = $conn->prepare(
        "INSERT INTO auctions (card_name, card_image, card_game, badge_color, base_price, seller_id, ends_at)
         VALUES (?, ?, ?, ?, ?, ?, ?)"
    );
    $stmt->bind_param("ssssiis", $card_name, $card_image, $card_game, $badge_color, $base_price, $user_id, $ends_at);
    $stmt->execute();
    $new_id = $conn->insert_id;

    echo json_encode(['ok' => true, 'auction_id' => $new_id, 'message' => 'Subasta creada correctamente']);
}

/* ── Mis pujas activas ────────────────── */
function getMyBids($conn) {
    if (!isset($_SESSION['user_id'])) { echo json_encode(['ok'=>false]); return; }
    $user_id = (int)$_SESSION['user_id'];

    $stmt = $conn->prepare(
        "SELECT a.id, a.card_name, a.card_image, a.card_game, a.badge_color,
                a.base_price, a.current_bid, a.current_winner_id, a.current_winner_name,
                a.ends_at, a.status,
                MAX(b.amount) AS my_best_bid
         FROM auction_bids b
         JOIN auctions a ON a.id = b.auction_id
         WHERE b.user_id = ?
         GROUP BY a.id
         ORDER BY a.ends_at ASC"
    );
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    echo json_encode(['ok' => true, 'bids' => $rows]);
}

/* ── Mis subastas ganadas ─────────────── */
function getMyWins($conn) {
    if (!isset($_SESSION['user_id'])) { echo json_encode(['ok'=>false]); return; }
    $user_id = (int)$_SESSION['user_id'];

    // Crear tabla claims si no existe
    $conn->query("CREATE TABLE IF NOT EXISTS auction_claims (
        id INT AUTO_INCREMENT PRIMARY KEY,
        auction_id INT NOT NULL,
        user_id INT NOT NULL,
        choice ENUM('delivery','exchange') NOT NULL,
        address TEXT NULL,
        lujanitos_awarded INT DEFAULT 0,
        status ENUM('pending','processing','shipped','delivered','done') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_user (user_id),
        FOREIGN KEY (auction_id) REFERENCES auctions(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )");

    $stmt = $conn->prepare(
        "SELECT a.id, a.card_name, a.card_image, a.card_game, a.badge_color,
                a.current_bid, a.ends_at, a.status,
                cl.choice, cl.status AS claim_status, cl.id AS claim_id, cl.address
         FROM auctions a
         LEFT JOIN auction_claims cl ON cl.auction_id = a.id AND cl.user_id = ?
         WHERE a.current_winner_id = ? AND a.status = 'ended'
         ORDER BY a.ends_at DESC"
    );
    $stmt->bind_param("ii", $user_id, $user_id);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    echo json_encode(['ok' => true, 'wins' => $rows]);
}

/* ── Reclamar subasta ganada ──────────── */
function claimWin($conn) {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['ok' => false, 'message' => 'Sesión requerida']);
        return;
    }
    $user_id    = (int)$_SESSION['user_id'];
    $auction_id = (int)($_POST['auction_id'] ?? 0);
    $choice     = $_POST['choice'] ?? '';
    $address    = trim($_POST['address'] ?? '');

    if (!in_array($choice, ['delivery', 'exchange'])) {
        echo json_encode(['ok' => false, 'message' => 'Opción inválida']);
        return;
    }
    if ($choice === 'delivery' && trim($address) === '') {
        echo json_encode(['ok' => false, 'message' => 'Introduce tu dirección de envío']);
        return;
    }

    // Crear tabla si no existe
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

    // Verificar que el usuario ganó esa subasta y está terminada
    $check = $conn->prepare(
        "SELECT current_bid, seller_id FROM auctions WHERE id = ? AND current_winner_id = ? AND status = 'ended'"
    );
    $check->bind_param("ii", $auction_id, $user_id);
    $check->execute();
    $auction = $check->get_result()->fetch_assoc();

    if (!$auction) {
        echo json_encode(['ok' => false, 'message' => 'Subasta no encontrada o no ganada']);
        return;
    }

    // Verificar que no haya sido reclamada ya
    $dup = $conn->prepare("SELECT id FROM auction_claims WHERE auction_id = ? AND user_id = ?");
    $dup->bind_param("ii", $auction_id, $user_id);
    $dup->execute();
    if ($dup->get_result()->num_rows > 0) {
        echo json_encode(['ok' => false, 'message' => 'Ya has reclamado esta subasta']);
        return;
    }

    $conn->begin_transaction();
    try {
        $bid_amount  = (int)$auction['current_bid'];
        $seller_id   = (int)$auction['seller_id'];
        $lujanitos_bonus = 0;

        // Siempre: descontar la puja al ganador
        $deduct = $conn->prepare("UPDATE users SET lootcoins = lootcoins - ? WHERE id = ?");
        $deduct->bind_param("ii", $bid_amount, $user_id);
        $deduct->execute();

        // Siempre: dar los coins al vendedor
        if ($seller_id > 0) {
            $pay = $conn->prepare("UPDATE users SET lootcoins = lootcoins + ? WHERE id = ?");
            $pay->bind_param("ii", $bid_amount, $seller_id);
            $pay->execute();
        }

        if ($choice === 'exchange') {
            // La plataforma se queda la carta y da un 20% de bonus al ganador
            $lujanitos_bonus = (int)round($bid_amount * 0.2);
            $bonus = $conn->prepare("UPDATE users SET lootcoins = lootcoins + ? WHERE id = ?");
            $bonus->bind_param("ii", $lujanitos_bonus, $user_id);
            $bonus->execute();
            $status = 'done';
        } else {
            $status = 'pending';
        }

        $ins = $conn->prepare(
            "INSERT INTO auction_claims (auction_id, user_id, choice, address, lujanitos_awarded, status)
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        $ins->bind_param("iissis", $auction_id, $user_id, $choice, $address, $lujanitos_bonus, $status);
        $ins->execute();

        // Registrar en actividad
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

        $stName = $conn->prepare("SELECT card_name FROM auctions WHERE id=?");
        $stName->bind_param("i", $auction_id);
        $stName->execute();
        $row_name = $stName->get_result()->fetch_assoc();
        $cardName = $row_name['card_name'] ?? 'Carta';
        $act_title = $choice === 'exchange' ? 'Canjeado por Lujanitos' : 'Carta en camino';
        $act_desc  = $choice === 'exchange'
            ? "Canjeaste \"$cardName\" por $lujanitos_bonus Lujanitos"
            : "\"$cardName\" será enviada a tu dirección";

        $ins2 = $conn->prepare(
            "INSERT INTO user_activity (user_id, activity_type, title, description, ref_id, amount)
             VALUES (?, 'auction_claim', ?, ?, ?, ?)"
        );
        $ins2->bind_param("issiid", $user_id, $act_title, $act_desc, $auction_id, $lujanitos_bonus);
        $ins2->execute();

        $conn->commit();

        $b = $conn->prepare("SELECT lootcoins FROM users WHERE id=?");
        $b->bind_param("i", $user_id);
        $b->execute();
        $new_balance = (int)$b->get_result()->fetch_assoc()['lootcoins'];

        echo json_encode([
            'ok'           => true,
            'choice'       => $choice,
            'lujanitos'    => $lujanitos_bonus,
            'new_balance'  => $new_balance,
            'message'      => $choice === 'exchange'
                ? "¡Canjeaste! Se han añadido +{$lujanitos_bonus} LJ de bonus a tu cuenta."
                : '¡Perfecto! Tu carta será enviada a tu dirección.'
        ]);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['ok' => false, 'message' => 'Error interno']);
    }
}
