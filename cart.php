<?php
session_start();

$isFetch = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'fetch';

if (!isset($_SESSION['user_id'])) {
    if ($isFetch) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(401);
        echo json_encode(['ok' => false, 'message' => 'Debes iniciar sesión para usar el carrito']);
        exit;
    }
    header('Location: auth.php');
    exit;
}

include 'db.php';

$userId = (int)$_SESSION['user_id'];
$lastCartError = '';

function ensureCartTable() {
    global $conn;
    $sql = "CREATE TABLE IF NOT EXISTS cart (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        card_id VARCHAR(255) NOT NULL,
        card_name VARCHAR(255) NOT NULL,
        card_image VARCHAR(500) NOT NULL,
        card_price DECIMAL(10,2) NOT NULL,
        card_game VARCHAR(50) NOT NULL,
        quantity INT NOT NULL DEFAULT 1,
        `condition` VARCHAR(50) DEFAULT 'Near Mint',
        seller VARCHAR(100) DEFAULT 'TCGVerse',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_user_card (user_id, card_id),
        INDEX idx_user_id (user_id),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    $conn->query($sql);
}

function ensureCheckoutTables() {
    global $conn;

    $conn->query("CREATE TABLE IF NOT EXISTS cart_orders (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        order_number VARCHAR(50) UNIQUE NOT NULL,
        total_amount DECIMAL(10,2) NOT NULL,
        status ENUM('pending','paid','cancelled') DEFAULT 'paid',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )");

    $conn->query("CREATE TABLE IF NOT EXISTS cart_order_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        order_id INT NOT NULL,
        card_id VARCHAR(255) NOT NULL,
        card_name VARCHAR(255) NOT NULL,
        card_image VARCHAR(500) NOT NULL,
        card_price DECIMAL(10,2) NOT NULL,
        card_game VARCHAR(50) NOT NULL,
        quantity INT NOT NULL,
        subtotal DECIMAL(10,2) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (order_id) REFERENCES cart_orders(id) ON DELETE CASCADE,
        INDEX idx_order_id (order_id)
    )");
}

function ensureActivityTable() {
    global $conn;
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
}

function logUserActivity($userId, $type, $title, $description, $refId = null, $amount = null) {
    global $conn;
    $stmt = $conn->prepare('INSERT INTO user_activity (user_id, activity_type, title, description, ref_id, amount) VALUES (?, ?, ?, ?, ?, ?)');
    if (!$stmt) {
        return false;
    }
    $stmt->bind_param('isssid', $userId, $type, $title, $description, $refId, $amount);
    return $stmt->execute();
}

function getCartItems($userId) {
    global $conn;
    $stmt = $conn->prepare('SELECT * FROM cart WHERE user_id = ? ORDER BY created_at DESC');
    if (!$stmt) {
        return false;
    }
    $stmt->bind_param('i', $userId);
    if (!$stmt->execute()) {
        return false;
    }
    return $stmt->get_result();
}

function addToCart($userId, $cardData) {
    global $conn;
    global $lastCartError;
    $lastCartError = '';

    $checkStmt = $conn->prepare('SELECT quantity FROM cart WHERE user_id = ? AND card_id = ?');
    if (!$checkStmt) {
        $lastCartError = $conn->error;
        return false;
    }
    $checkStmt->bind_param('is', $userId, $cardData['card_id']);
    if (!$checkStmt->execute()) {
        $lastCartError = $checkStmt->error;
        return false;
    }
    $existing = $checkStmt->get_result()->fetch_assoc();

    if ($existing) {
        $newQuantity = (int)$existing['quantity'] + 1;
        $updateStmt = $conn->prepare('UPDATE cart SET quantity = ? WHERE user_id = ? AND card_id = ?');
        if (!$updateStmt) {
            $lastCartError = $conn->error;
            return false;
        }
        $updateStmt->bind_param('iis', $newQuantity, $userId, $cardData['card_id']);
        $ok = $updateStmt->execute();
        if (!$ok) {
            $lastCartError = $updateStmt->error;
        }
        return $ok;
    }

    $insertStmt = $conn->prepare('INSERT INTO cart (user_id, card_id, card_name, card_image, card_price, card_game, quantity, `condition`, seller) VALUES (?, ?, ?, ?, ?, ?, 1, ?, ?)');
    if (!$insertStmt) {
        $lastCartError = $conn->error;
        return false;
    }

    $condition = $cardData['condition'] ?? 'Near Mint';
    $seller = $cardData['seller'] ?? 'TCGVerse';

    $insertStmt->bind_param(
        'isssdsss',
        $userId,
        $cardData['card_id'],
        $cardData['card_name'],
        $cardData['card_image'],
        $cardData['card_price'],
        $cardData['card_game'],
        $condition,
        $seller
    );

    $ok = $insertStmt->execute();
    if (!$ok) {
        $lastCartError = $insertStmt->error;
    }
    return $ok;
}

function updateCartItemQuantity($userId, $cardId, $quantity) {
    global $conn;

    if ($quantity <= 0) {
        return removeFromCart($userId, $cardId);
    }

    $stmt = $conn->prepare('UPDATE cart SET quantity = ? WHERE user_id = ? AND card_id = ?');
    if (!$stmt) {
        return false;
    }
    $stmt->bind_param('iis', $quantity, $userId, $cardId);
    return $stmt->execute();
}

function removeFromCart($userId, $cardId) {
    global $conn;
    $stmt = $conn->prepare('DELETE FROM cart WHERE user_id = ? AND card_id = ?');
    if (!$stmt) {
        return false;
    }
    $stmt->bind_param('is', $userId, $cardId);
    return $stmt->execute();
}

function clearCart($userId) {
    global $conn;
    $stmt = $conn->prepare('DELETE FROM cart WHERE user_id = ?');
    if (!$stmt) {
        return false;
    }
    $stmt->bind_param('i', $userId);
    return $stmt->execute();
}

function checkout($userId) {
    global $conn;

    $itemsRes = getCartItems($userId);
    if (!$itemsRes) {
        return ['ok' => false, 'message' => 'No se pudo leer el carrito'];
    }

    $items = [];
    $total = 0.0;
    while ($row = $itemsRes->fetch_assoc()) {
        $qty = (int)$row['quantity'];
        $price = (float)$row['card_price'];
        $subtotal = $qty * $price;
        $row['_subtotal'] = $subtotal;
        $items[] = $row;
        $total += $subtotal;
    }

    if (count($items) === 0) {
        return ['ok' => false, 'message' => 'El carrito está vacío'];
    }

    $conn->begin_transaction();
    try {
        ensureCheckoutTables();
        ensureActivityTable();

        $orderNumber = 'ORD-' . date('Ymd-His') . '-' . $userId;
        $orderStmt = $conn->prepare('INSERT INTO cart_orders (user_id, order_number, total_amount, status) VALUES (?, ?, ?, \'paid\')');
        if (!$orderStmt) {
            throw new Exception($conn->error);
        }
        $orderStmt->bind_param('isd', $userId, $orderNumber, $total);
        if (!$orderStmt->execute()) {
            throw new Exception($orderStmt->error);
        }

        $orderId = (int)$conn->insert_id;

        $itemStmt = $conn->prepare('INSERT INTO cart_order_items (order_id, card_id, card_name, card_image, card_price, card_game, quantity, subtotal) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
        if (!$itemStmt) {
            throw new Exception($conn->error);
        }

        foreach ($items as $it) {
            $qty = (int)$it['quantity'];
            $price = (float)$it['card_price'];
            $subtotal = (float)$it['_subtotal'];
            $itemStmt->bind_param('isssdsid', $orderId, $it['card_id'], $it['card_name'], $it['card_image'], $price, $it['card_game'], $qty, $subtotal);
            if (!$itemStmt->execute()) {
                throw new Exception($itemStmt->error);
            }
        }

        clearCart($userId);

        logUserActivity(
            $userId,
            'order',
            'Pedido realizado',
            'Pedido ' . $orderNumber . ' · Total $' . number_format($total, 2),
            $orderId,
            $total
        );

        $conn->commit();
        return ['ok' => true, 'message' => 'Pedido completado', 'order_number' => $orderNumber];
    } catch (Throwable $e) {
        $conn->rollback();
        return ['ok' => false, 'message' => $e->getMessage()];
    }
}

ensureCartTable();

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action !== '') {
    if ($action === 'add') {
        $cardData = [
            'card_id' => (string)($_POST['card_id'] ?? ''),
            'card_name' => (string)($_POST['card_name'] ?? ''),
            'card_image' => (string)($_POST['card_image'] ?? ''),
            'card_price' => (float)($_POST['card_price'] ?? 0),
            'card_game' => (string)($_POST['card_game'] ?? ''),
            'condition' => (string)($_POST['condition'] ?? 'Near Mint'),
            'seller' => (string)($_POST['seller'] ?? 'TCGVerse')
        ];

        $ok = addToCart($userId, $cardData);
        if ($isFetch) {
            header('Content-Type: application/json; charset=utf-8');
            http_response_code($ok ? 200 : 400);
            echo json_encode([
                'ok' => (bool)$ok,
                'message' => $ok ? 'Añadido al carrito' : ($GLOBALS['lastCartError'] ?: 'No se pudo añadir al carrito')
            ]);
            exit;
        }

        header('Location: cart.php');
        exit;
    }

    if ($action === 'update') {
        $cardId = (string)($_POST['card_id'] ?? '');
        $qty = (int)($_POST['quantity'] ?? 1);
        updateCartItemQuantity($userId, $cardId, $qty);
        header('Location: cart.php');
        exit;
    }

    if ($action === 'remove') {
        $cardId = (string)($_POST['card_id'] ?? '');
        removeFromCart($userId, $cardId);
        header('Location: cart.php');
        exit;
    }

    if ($action === 'clear') {
        clearCart($userId);
        header('Location: cart.php');
        exit;
    }

    if ($action === 'checkout') {
        $result = checkout($userId);
        if ($isFetch) {
            header('Content-Type: application/json; charset=utf-8');
            http_response_code($result['ok'] ? 200 : 400);
            echo json_encode($result);
            exit;
        }

        $msg = $result['ok'] ? 'Checkout realizado: ' . ($result['order_number'] ?? '') : ('Error: ' . ($result['message'] ?? ''));
        header('Location: cart.php?msg=' . urlencode($msg));
        exit;
    }
}

$itemsRes = getCartItems($userId);
$items = [];
$total = 0.0;
if ($itemsRes) {
    while ($row = $itemsRes->fetch_assoc()) {
        $qty = (int)$row['quantity'];
        $price = (float)$row['card_price'];
        $subtotal = $qty * $price;
        $row['_subtotal'] = $subtotal;
        $items[] = $row;
        $total += $subtotal;
    }
}

$msg = isset($_GET['msg']) ? (string)$_GET['msg'] : '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carrito</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles.css?v=<?php echo time(); ?>">
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

        <a href="cart.php" class="nav-item" title="Carrito" style="color: var(--accent-blue);">
            🛒
        </a>

        <?php if (isset($_SESSION['username']) && $_SESSION['username']): ?>
            <a href="profile.php" class="nav-item user-active" title="Ver Perfil">
                <svg class="nav-icon" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z"/></svg>
                <?php echo htmlspecialchars($_SESSION['username']); ?>
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

    <div class="main-wrapper">
        <div class="cart-container">
            <div class="cart-header">
                <div class="cart-title">
                    <h1>Tu Carrito</h1>
                </div>
                <div class="cart-actions">
                    <a class="btn-main" href="index.php">Seguir comprando</a>
                    <form method="post" action="cart.php" class="cart-inline-form">
                        <input type="hidden" name="action" value="clear">
                        <button class="btn-cart" type="submit">Vaciar</button>
                    </form>
                </div>
            </div>

            <?php if ($msg): ?>
                <div class="cart-alert">
                    <?php echo htmlspecialchars($msg); ?>
                </div>
            <?php endif; ?>

            <?php if (count($items) === 0): ?>
                <div class="cart-empty">
                    <div class="cart-empty-icon">🛒</div>
                    <div class="cart-empty-title">Tu carrito está vacío</div>
                    <div class="cart-empty-subtitle">Explora el marketplace y añade tu primera carta.</div>
                    <a class="btn-main" href="index.php">Explorar cartas</a>
                </div>
            <?php else: ?>
                <div class="cart-grid">
                    <div class="cart-items">
                        <?php foreach ($items as $it): ?>
                            <div class="cart-item">
                                <img class="cart-item-image" src="<?php echo htmlspecialchars($it['card_image']); ?>" alt="">
                                <div class="cart-item-main">
                                    <div class="cart-item-name"><?php echo htmlspecialchars($it['card_name']); ?></div>
                                    <div class="cart-item-meta">
                                        <?php echo htmlspecialchars($it['card_game']); ?> · <?php echo htmlspecialchars($it['condition']); ?> · <?php echo htmlspecialchars($it['seller']); ?>
                                    </div>
                                    <div class="cart-item-price">$<?php echo number_format((float)$it['card_price'], 2); ?></div>
                                </div>

                                <div class="cart-item-controls">
                                    <form method="post" action="cart.php" class="cart-qty-form">
                                        <input type="hidden" name="action" value="update">
                                        <input type="hidden" name="card_id" value="<?php echo htmlspecialchars($it['card_id']); ?>">
                                        <input class="cart-qty" type="number" name="quantity" min="1" value="<?php echo (int)$it['quantity']; ?>">
                                        <button class="btn-cart" type="submit">Actualizar</button>
                                    </form>

                                    <div class="cart-item-subtotal">
                                        <div class="cart-item-subtotal-value">$<?php echo number_format((float)$it['_subtotal'], 2); ?></div>
                                        <form method="post" action="cart.php" class="cart-inline-form">
                                            <input type="hidden" name="action" value="remove">
                                            <input type="hidden" name="card_id" value="<?php echo htmlspecialchars($it['card_id']); ?>">
                                            <button class="btn-cart danger" type="submit">Eliminar</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <aside class="cart-summary">
                        <div class="cart-summary-header">Resumen</div>
                        <div class="cart-summary-row">
                            <span>Total</span>
                            <strong>$<?php echo number_format($total, 2); ?></strong>
                        </div>
                        <form method="post" action="cart.php" class="cart-summary-actions">
                            <input type="hidden" name="action" value="checkout">
                            <button class="btn-main full-width" type="submit">Finalizar compra</button>
                        </form>
                        <div class="cart-summary-note">Pago simulado. Se genera un pedido y se vacía el carrito.</div>
                    </aside>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="script.js?v=<?php echo time(); ?>"></script>
</body>
</html>
