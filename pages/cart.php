<?php
require_once dirname(__DIR__) . '/includes/session.php';

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

require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/csrf.php';
require_once dirname(__DIR__) . '/includes/gamification.php';

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

    $items    = [];
    $total    = 0.0;
    $totalLJ  = 0;
    while ($row = $itemsRes->fetch_assoc()) {
        $qty      = (int)$row['quantity'];
        $price    = (float)$row['card_price'];
        $priceLJ  = max(1, (int)round($price)); // 1€ = 1 LJ, mínimo 1
        $subtotal = $qty * $price;
        $row['_subtotal']  = $subtotal;
        $row['_price_lj']  = $priceLJ;
        $items[] = $row;
        $total   += $subtotal;
        $totalLJ += $qty * $priceLJ;
    }

    if (count($items) === 0) {
        return ['ok' => false, 'message' => 'El carrito está vacío'];
    }

    // Verificar saldo de Lujanitos
    try { $conn->query("ALTER TABLE users ADD COLUMN lootcoins INT NOT NULL DEFAULT 1000"); } catch (Exception $e) {}
    $balSt = $conn->prepare("SELECT lootcoins FROM users WHERE id=?");
    $balSt->bind_param("i", $userId); $balSt->execute();
    $balRes = $balSt->get_result();
    $userCoins = $balRes ? (int)$balRes->fetch_assoc()['lootcoins'] : 0;
    $balSt->close();
    if ($userCoins < $totalLJ) {
        return ['ok' => false, 'message' => "Lujanitos insuficientes. Necesitas {$totalLJ} LJ pero tienes {$userCoins} LJ. <a href='lujanitos.php' style='color:#f59e0b;font-weight:700;'>Consigue más →</a>"];
    }

    $conn->begin_transaction();
    try {
        ensureCheckoutTables();
        ensureActivityTable();

        // Descontar Lujanitos del saldo
        $updCoins = $conn->prepare("UPDATE users SET lootcoins = lootcoins - ? WHERE id = ?");
        $updCoins->bind_param("ii", $totalLJ, $userId);
        $updCoins->execute();
        $updCoins->close();

        $orderNumber = 'ORD-' . date('Ymd-His') . '-' . $userId;
        $orderStmt = $conn->prepare('INSERT INTO cart_orders (user_id, order_number, total_amount, status) VALUES (?, ?, ?, \'paid\')');
        if (!$orderStmt) throw new Exception($conn->error);
        $totalLJFloat = (float)$totalLJ;
        $orderStmt->bind_param('isd', $userId, $orderNumber, $totalLJFloat);
        if (!$orderStmt->execute()) throw new Exception($orderStmt->error);

        $orderId  = (int)$conn->insert_id;
        $itemStmt = $conn->prepare('INSERT INTO cart_order_items (order_id, card_id, card_name, card_image, card_price, card_game, quantity, subtotal) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
        if (!$itemStmt) throw new Exception($conn->error);

        foreach ($items as $it) {
            $qty      = (int)$it['quantity'];
            $priceLJ  = (float)$it['_price_lj'];
            $subtotal = (float)($qty * $it['_price_lj']);
            $itemStmt->bind_param('isssdsid', $orderId, $it['card_id'], $it['card_name'], $it['card_image'], $priceLJ, $it['card_game'], $qty, $subtotal);
            if (!$itemStmt->execute()) throw new Exception($itemStmt->error);
        }

        clearCart($userId);

        logUserActivity(
            $userId,
            'order',
            'Pedido realizado',
            'Pedido ' . $orderNumber . ' · -' . $totalLJ . ' LJ',
            $orderId,
            $totalLJ
        );

        $conn->commit();

        // XP por compra + verificar logros de colección
        addXP($conn, $userId, 10);
        checkAchievements($conn, $userId);

        return ['ok' => true, 'message' => 'Pedido completado', 'order_number' => $orderNumber, 'lj_spent' => $totalLJ];
    } catch (Throwable $e) {
        $conn->rollback();
        return ['ok' => false, 'message' => $e->getMessage()];
    }
}

ensureCartTable();

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action !== '') {
    csrf_verify();
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
        $ok = updateCartItemQuantity($userId, $cardId, $qty);
        if ($isFetch) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => (bool)$ok]);
            exit;
        }
        header('Location: cart.php');
        exit;
    }

    if ($action === 'remove') {
        $cardId = (string)($_POST['card_id'] ?? '');
        $ok = removeFromCart($userId, $cardId);
        if ($isFetch) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => (bool)$ok]);
            exit;
        }
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

        $msg = $result['ok']
            ? '✅ Pedido ' . ($result['order_number'] ?? '') . ' · -' . ($result['lj_spent'] ?? 0) . ' LJ'
            : ('âŒ ' . ($result['message'] ?? ''));
        header('Location: cart.php?msg=' . urlencode($msg));
        exit;
    }
}

// Saldo de Lujanitos del usuario
try { $conn->query("ALTER TABLE users ADD COLUMN lootcoins INT NOT NULL DEFAULT 1000"); } catch (Exception $e) {}
$userCoinsDisplay = 0;
$coinsRow = $conn->query("SELECT lootcoins FROM users WHERE id=$userId");
if ($coinsRow) $userCoinsDisplay = (int)$coinsRow->fetch_assoc()['lootcoins'];

$itemsRes = getCartItems($userId);
$items   = [];
$total   = 0.0;
$totalLJ = 0;
if ($itemsRes) {
    while ($row = $itemsRes->fetch_assoc()) {
        $qty     = (int)$row['quantity'];
        $price   = (float)$row['card_price'];
        $priceLJ = max(1, (int)round($price));
        $subtotal = $qty * $price;
        $row['_subtotal'] = $subtotal;
        $row['_price_lj'] = $priceLJ;
        $items[]  = $row;
        $total   += $subtotal;
        $totalLJ += $qty * $priceLJ;
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
    <link rel="stylesheet" href="../assets/css/styles.css?v=<?php echo time(); ?>">
</head>
<body>
    <script>(function(){ if(localStorage.getItem('theme')==='dark') document.body.classList.add('dark'); })();</script>

    <?php include dirname(__DIR__) . '/includes/navbar.php'; ?>

    <div class="main-wrapper">
        <div class="cart-container">
            <div class="cart-header">
                <div class="cart-title">
                    <h1 data-i18n="cart.title">Tu Carrito</h1>
                </div>
                <div class="cart-actions">
                    <a class="btn-main" href="index.php" data-i18n="cart.continue">Seguir comprando</a>
                    <form method="post" action="cart.php" class="cart-inline-form">
                        <input type="hidden" name="action" value="clear">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
                        <button class="btn-cart" type="submit" data-i18n="cart.clear">Vaciar</button>
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
                    <div class="cart-empty-title" data-i18n="cart.empty">Tu carrito está vacío</div>
                    <div class="cart-empty-subtitle" data-i18n="cart.empty_sub">Explora el marketplace y añade tu primera carta.</div>
                    <a class="btn-main" href="index.php" data-i18n="cart.explore">Explorar cartas</a>
                </div>
            <?php else: ?>
                <div class="cart-grid">
                    <div class="cart-items">
                        <?php foreach ($items as $it): ?>
                            <div class="cart-item"
                                 data-card-id="<?php echo htmlspecialchars($it['card_id']); ?>"
                                 data-price="<?php echo number_format((float)$it['card_price'], 4, '.', ''); ?>">
                                <img class="cart-item-image" src="<?php echo htmlspecialchars($it['card_image']); ?>" alt="">
                                <div class="cart-item-main">
                                    <div class="cart-item-name"><?php echo htmlspecialchars($it['card_name']); ?></div>
                                    <div class="cart-item-meta">
                                        <?php echo htmlspecialchars($it['card_game']); ?> · <?php echo htmlspecialchars($it['condition']); ?> · <?php echo htmlspecialchars($it['seller']); ?>
                                    </div>
                                    <div class="cart-item-price"><?php echo $it['_price_lj']; ?> LJ</div>
                                </div>

                                <div class="cart-item-controls">
                                    <div class="cart-qty-form">
                                        <input class="cart-qty" type="number" min="1"
                                               value="<?php echo (int)$it['quantity']; ?>">
                                        <button class="btn-cart" type="button" onclick="cartUpdate(this)" data-i18n="cart.update">Actualizar</button>
                                    </div>

                                    <div class="cart-item-subtotal">
                                        <div class="cart-item-subtotal-value"><?php echo (int)$it['quantity'] * $it['_price_lj']; ?> LJ</div>
                                        <button class="btn-cart danger" type="button" onclick="cartRemove(this)" data-i18n="cart.remove">Eliminar</button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <aside class="cart-summary">
                        <div class="cart-summary-header" data-i18n="cart.summary">Resumen</div>
                        <div class="cart-summary-row">
                            <span data-i18n="cart.your_balance">Tu saldo</span>
                            <span id="user-coins-display" style="color:#f59e0b;font-weight:800;">💰 <?php echo number_format($userCoinsDisplay); ?> LJ</span>
                        </div>
                        <div class="cart-summary-row">
                            <span data-i18n="cart.total_pay">Total a pagar</span>
                            <strong id="cart-total" style="color:#0f172a;"><?php echo number_format($totalLJ); ?> LJ</strong>
                        </div>
                        <?php if ($userCoinsDisplay < $totalLJ): ?>
                        <div style="background:#fef2f2;border-radius:12px;padding:10px 14px;font-size:.82rem;color:#dc2626;font-weight:700;margin-bottom:12px;">
                            ⚠️ Saldo insuficiente. <a href="lujanitos.php" style="color:#f59e0b;">Conseguir Lujanitos →</a>
                        </div>
                        <?php endif; ?>
                        <form method="post" action="cart.php" class="cart-summary-actions">
                            <input type="hidden" name="action" value="checkout">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
                            <button class="btn-main full-width" type="submit" <?php echo $userCoinsDisplay < $totalLJ ? 'disabled' : ''; ?> data-i18n="cart.finish">Finalizar compra</button>
                        </form>
                        <div class="cart-summary-note" data-i18n="cart.note">Se descuentan Lujanitos de tu saldo.</div>
                    </aside>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="../assets/js/csrf.js?v=<?php echo time(); ?>"></script>
    <script src="../assets/js/script.js?v=<?php echo time(); ?>"></script>
    <script>
    function priceLJ(euroPrice) {
        return Math.max(1, Math.round(parseFloat(euroPrice) || 0));
    }

    function cartRecalcTotal() {
        let totalLJ = 0;
        document.querySelectorAll('.cart-item').forEach(row => {
            const lj  = priceLJ(row.dataset.price);
            const qty = parseInt(row.querySelector('.cart-qty').value) || 0;
            totalLJ  += lj * qty;
        });
        const el = document.getElementById('cart-total');
        if (el) el.textContent = totalLJ.toLocaleString('es-ES') + ' LJ';
    }

    async function cartUpdate(btn) {
        const row    = btn.closest('.cart-item');
        const cardId = row.dataset.cardId;
        const price  = parseFloat(row.dataset.price) || 0;
        const qtyEl  = row.querySelector('.cart-qty');
        const qty    = parseInt(qtyEl.value) || 0;

        if (qty <= 0) { cartRemove(btn); return; }

        btn.disabled = true;
        const fd = new FormData();
        fd.append('action',   'update');
        fd.append('card_id',  cardId);
        fd.append('quantity', qty);

        try {
            const res  = await fetch('cart.php', {
                method: 'POST',
                headers: { 'X-Requested-With': 'fetch' },
                body: fd
            });
            const data = await res.json();
            if (data.ok) {
                row.querySelector('.cart-item-subtotal-value').textContent =
                    (priceLJ(price) * qty).toLocaleString('es-ES') + ' LJ';
                cartRecalcTotal();
                if (typeof showToast === 'function') showToast('Cantidad actualizada', 'success');
            }
        } finally {
            btn.disabled = false;
        }
    }

    async function cartRemove(btn) {
        const row    = btn.closest('.cart-item');
        const cardId = row.dataset.cardId;

        btn.disabled = true;
        const fd = new FormData();
        fd.append('action',  'remove');
        fd.append('card_id', cardId);

        try {
            const res  = await fetch('cart.php', {
                method: 'POST',
                headers: { 'X-Requested-With': 'fetch' },
                body: fd
            });
            const data = await res.json();
            if (data.ok) {
                row.remove();
                cartRecalcTotal();
                if (typeof showToast === 'function') showToast('Carta eliminada', 'success');
                if (!document.querySelector('.cart-item')) location.reload();
            }
        } finally {
            btn.disabled = false;
        }
    }
    </script>

<?php include dirname(__DIR__) . '/includes/footer.php'; ?>
</body>
</html>
