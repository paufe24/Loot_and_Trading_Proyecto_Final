<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
if (!isset($_SESSION['user_id'])) { echo json_encode(['ok'=>false,'message'=>'No autenticado']); exit; }
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/csrf.php';
$uid    = (int)$_SESSION['user_id'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') csrf_verify();

$conn->query("CREATE TABLE IF NOT EXISTS wallapop_listings (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    seller_id    INT NOT NULL,
    card_name    VARCHAR(255) NOT NULL,
    card_game    VARCHAR(50)  DEFAULT '',
    card_set     VARCHAR(255) DEFAULT '',
    description  TEXT         DEFAULT '',
    price        DECIMAL(10,2) NOT NULL DEFAULT 0,
    image_url    TEXT          DEFAULT '',
    listing_type ENUM('digital','physical') DEFAULT 'physical',
    status       ENUM('active','sold','cancelled') DEFAULT 'active',
    buyer_id     INT DEFAULT NULL,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_status_game (status, card_game),
    INDEX idx_seller (seller_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

switch ($action) {

    /* ── Listar anuncios activos (sin los del propio usuario) ── */
    case 'list':
        $game = trim($_GET['game'] ?? '');
        $q    = trim($_GET['q']    ?? '');
        $sql  = "SELECT l.id, l.card_name, l.card_game, l.card_set, l.description,
                        l.price, l.image_url, l.listing_type, l.created_at,
                        u.username AS seller_name, u.avatar_url AS seller_avatar
                 FROM wallapop_listings l
                 JOIN users u ON u.id = l.seller_id
                 WHERE l.status='active' AND l.seller_id != ?";
        $params = [$uid]; $types = 'i';
        if ($game !== '') { $sql .= " AND l.card_game = ?"; $params[] = $game; $types .= 's'; }
        if ($q    !== '') { $sql .= " AND l.card_name LIKE ?"; $params[] = "%$q%"; $types .= 's'; }
        $sql .= " ORDER BY l.created_at DESC LIMIT 60";
        $st = $conn->prepare($sql);
        $st->bind_param($types, ...$params);
        $st->execute();
        $rows = $st->get_result()->fetch_all(MYSQLI_ASSOC);
        echo json_encode(['ok'=>true,'listings'=>$rows]);
        break;

    /* ── Mis anuncios ── */
    case 'my_listings':
        $st = $conn->prepare(
            "SELECT l.*, u.username AS buyer_name
             FROM wallapop_listings l
             LEFT JOIN users u ON u.id = l.buyer_id
             WHERE l.seller_id = ?
             ORDER BY l.created_at DESC"
        );
        $st->bind_param("i", $uid);
        $st->execute();
        echo json_encode(['ok'=>true,'listings'=>$st->get_result()->fetch_all(MYSQLI_ASSOC)]);
        break;

    /* ── Cartas compradas del usuario (para pre-rellenar el formulario) ── */
    case 'my_cards':
        $st = $conn->prepare(
            "SELECT DISTINCT oi.card_name, oi.card_image, oi.card_game, oi.card_price
             FROM cart_order_items oi
             JOIN cart_orders o ON o.id = oi.order_id
             WHERE o.user_id = ?
             ORDER BY oi.id DESC LIMIT 30"
        );
        $st->bind_param("i", $uid);
        $st->execute();
        echo json_encode(['ok'=>true,'cards'=>$st->get_result()->fetch_all(MYSQLI_ASSOC)]);
        break;

    /* ── Publicar anuncio ── */
    case 'create':
        $card_name = trim($_POST['card_name']    ?? '');
        $card_game = trim($_POST['card_game']    ?? '');
        $card_set  = trim($_POST['card_set']     ?? '');
        $desc      = trim($_POST['description']  ?? '');
        $price     = floatval($_POST['price']    ?? 0);
        $img_url   = trim($_POST['image_url']    ?? '');
        $type      = in_array($_POST['listing_type'] ?? '', ['digital','physical'])
                     ? $_POST['listing_type'] : 'physical';

        if ($card_name === '' || $price <= 0) {
            echo json_encode(['ok'=>false,'message'=>'Nombre y precio son obligatorios']); exit;
        }

        // Subida de imagen
        if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
            $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, ['jpg','jpeg','png','webp','gif'])) {
                echo json_encode(['ok'=>false,'message'=>'Formato de imagen no permitido']); exit;
            }
            $finfo    = new finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->file($_FILES['image']['tmp_name']);
            if (!in_array($mimeType, ['image/jpeg','image/png','image/webp','image/gif'])) {
                echo json_encode(['ok'=>false,'message'=>'Formato de imagen no permitido']); exit;
            }
            $dir = dirname(__DIR__) . '/img/wallapop/';
            if (!is_dir($dir)) mkdir($dir, 0755, true);
            $fname = 'card_' . $uid . '_' . time() . '_' . rand(1000,9999) . '.' . $ext;
            if (move_uploaded_file($_FILES['image']['tmp_name'], $dir . $fname)) {
                $img_url = 'img/wallapop/' . $fname;
            }
        }

        $st = $conn->prepare(
            "INSERT INTO wallapop_listings
             (seller_id, card_name, card_game, card_set, description, price, image_url, listing_type)
             VALUES (?,?,?,?,?,?,?,?)"
        );
        $st->bind_param("issssdss", $uid, $card_name, $card_game, $card_set, $desc, $price, $img_url, $type);
        $st->execute();
        echo json_encode(['ok'=>true,'id'=>$conn->insert_id]);
        break;

    /* ── Comprar un anuncio ── */
    case 'buy':
        $lid = (int)($_POST['listing_id'] ?? 0);
        if (!$lid) { echo json_encode(['ok'=>false,'message'=>'Anuncio inválido']); exit; }

        $conn->begin_transaction();
        try {
            $st = $conn->prepare("SELECT * FROM wallapop_listings WHERE id=? AND status='active' FOR UPDATE");
            $st->bind_param("i", $lid);
            $st->execute();
            $listing = $st->get_result()->fetch_assoc();
            if (!$listing) { $conn->rollback(); echo json_encode(['ok'=>false,'message'=>'Anuncio no disponible']); exit; }
            if ($listing['seller_id'] == $uid) { $conn->rollback(); echo json_encode(['ok'=>false,'message'=>'No puedes comprarte tu propio anuncio']); exit; }

            $price     = floatval($listing['price']);
            $seller_id = (int)$listing['seller_id'];
            $stBal = $conn->prepare("SELECT lootcoins FROM users WHERE id=? FOR UPDATE");
            $stBal->bind_param("i", $uid);
            $stBal->execute();
            $buyer = $stBal->get_result()->fetch_assoc();
            if ($buyer['lootcoins'] < $price) {
                $conn->rollback();
                echo json_encode(['ok'=>false,'message'=>'LootCoins insuficientes']); exit;
            }

            $stDeduct = $conn->prepare("UPDATE users SET lootcoins = lootcoins - ? WHERE id=?");
            $stDeduct->bind_param("di", $price, $uid);
            $stDeduct->execute();
            $stCredit = $conn->prepare("UPDATE users SET lootcoins = lootcoins + ? WHERE id=?");
            $stCredit->bind_param("di", $price, $seller_id);
            $stCredit->execute();
            $st2 = $conn->prepare("UPDATE wallapop_listings SET status='sold', buyer_id=? WHERE id=?");
            $st2->bind_param("ii", $uid, $lid);
            $st2->execute();

            $conn->commit();
            echo json_encode(['ok'=>true,'message'=>"¡Compra realizada! -$price LootCoins"]);
        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode(['ok'=>false,'message'=>'Error en la transacción']);
        }
        break;

    /* ── Cancelar anuncio propio ── */
    case 'cancel':
        $lid = (int)($_POST['listing_id'] ?? 0);
        $st  = $conn->prepare("UPDATE wallapop_listings SET status='cancelled' WHERE id=? AND seller_id=?");
        $st->bind_param("ii", $lid, $uid);
        $st->execute();
        echo json_encode(['ok'=>true]);
        break;

    default:
        echo json_encode(['ok'=>false,'message'=>'Acción no válida']);
}
