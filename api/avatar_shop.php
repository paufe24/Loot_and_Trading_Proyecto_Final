<?php
require_once dirname(__DIR__) . '/includes/session.php';
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['ok' => false, 'message' => 'Sesión requerida']); exit;
}

require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/csrf.php';

$user_id = (int)$_SESSION['user_id'];

// ── Migraciones ──────────────────────────────────────────────────────────
$conn->query("CREATE TABLE IF NOT EXISTS shop_avatars (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    category VARCHAR(50) NOT NULL DEFAULT 'general',
    image_url VARCHAR(1000) NOT NULL,
    price INT NOT NULL DEFAULT 0,
    rarity VARCHAR(20) NOT NULL DEFAULT 'common',
    sort_order INT NOT NULL DEFAULT 0,
    active TINYINT(1) NOT NULL DEFAULT 1
)");

$conn->query("CREATE TABLE IF NOT EXISTS user_avatars (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    avatar_id INT NOT NULL,
    purchased_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_user_avatar (user_id, avatar_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (avatar_id) REFERENCES shop_avatars(id) ON DELETE CASCADE
)");

// ── Seed avatares estándar si la tabla está vacía ────────────────────────
$cnt = (int)$conn->query("SELECT COUNT(*) c FROM shop_avatars")->fetch_assoc()['c'];
if ($cnt === 0) {
    $avatars = [
        // ── Gratis ──
        ['Explorador',    'basico',   'explorer',      0,   'common',  1],
        ['Novato',        'basico',   'rookie',        0,   'common',  2],
        ['Viajero',       'basico',   'traveler',      0,   'common',  3],

        // ── Comunes ──
        ['Guerrero',      'fantasia', 'warrior',       150, 'common',  10],
        ['Mago Oscuro',   'fantasia', 'dark_mage',     150, 'common',  11],
        ['Arquera',       'fantasia', 'archer',        150, 'common',  12],
        ['Pirata',        'aventura', 'pirate',        200, 'common',  13],
        ['Astronauta',    'aventura', 'astronaut',     200, 'common',  14],

        // ── Raros ──
        ['Dragón Dorado', 'fantasia', 'golden_dragon', 500, 'rare',    20],
        ['Ninja Sombra',  'fantasia', 'shadow_ninja',  500, 'rare',    21],
        ['Fénix',         'fantasia', 'phoenix',       600, 'rare',    22],
        ['Samurái',       'aventura', 'samurai',       600, 'rare',    23],
        ['Hacker',        'tech',     'hacker',        500, 'rare',    24],

        // ── Épicos ──
        ['Rey Celestial', 'fantasia', 'celestial_king',1200,'epic',    30],
        ['Valquiria',     'fantasia', 'valkyrie',      1200,'epic',    31],
        ['Cyborg',        'tech',     'cyborg',        1500,'epic',    32],
        ['Espíritu Zorro','fantasia', 'fox_spirit',    1500,'epic',    33],

        // ── Legendarios ──
        ['Dios del Mazo', 'legendario','deck_god',     3000,'legendary',40],
        ['Fénix Cósmico', 'legendario','cosmic_phoenix',3500,'legendary',41],
        ['Titán Ancestral','legendario','ancient_titan',4000,'legendary',42],
    ];

    $ins = $conn->prepare("INSERT INTO shop_avatars (name, category, image_url, price, rarity, sort_order) VALUES (?,?,?,?,?,?)");
    foreach ($avatars as $a) {
        $ins->bind_param("sssisi", $a[0], $a[1], $a[2], $a[3], $a[4], $a[5]);
        $ins->execute();
    }
    $ins->close();
}

// ── Router ───────────────────────────────────────────────────────────────
$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {

    // ── Listar catálogo ──────────────────────────────────────────────────
    case 'list':
        $lootcoins = (int)$conn->query("SELECT lootcoins FROM users WHERE id=$user_id")->fetch_assoc()['lootcoins'];
        $currentAv = $conn->query("SELECT avatar_url FROM users WHERE id=$user_id")->fetch_assoc()['avatar_url'] ?? '';

        $rows = $conn->query("
            SELECT sa.*, IF(ua.id IS NOT NULL, 1, 0) AS owned
            FROM shop_avatars sa
            LEFT JOIN user_avatars ua ON ua.avatar_id = sa.id AND ua.user_id = $user_id
            WHERE sa.active = 1
            ORDER BY sa.sort_order ASC
        ");

        $items = [];
        while ($r = $rows->fetch_assoc()) {
            $items[] = [
                'id'        => (int)$r['id'],
                'name'      => $r['name'],
                'category'  => $r['category'],
                'image_url' => $r['image_url'],
                'price'     => (int)$r['price'],
                'rarity'    => $r['rarity'],
                'owned'     => (bool)$r['owned'],
                'equipped'  => ($currentAv === 'avatar_shop:' . $r['image_url']),
            ];
        }

        echo json_encode(['ok' => true, 'items' => $items, 'lootcoins' => $lootcoins, 'current_avatar' => $currentAv]);
        break;

    // ── Comprar avatar ───────────────────────────────────────────────────
    case 'buy':
        csrf_verify();
        $avatar_id = (int)($_POST['avatar_id'] ?? 0);
        if ($avatar_id < 1) { echo json_encode(['ok' => false, 'message' => 'Avatar inválido']); exit; }

        // Comprobar si ya lo tiene
        $chk = $conn->prepare("SELECT id FROM user_avatars WHERE user_id=? AND avatar_id=?");
        $chk->bind_param("ii", $user_id, $avatar_id);
        $chk->execute();
        if ($chk->get_result()->num_rows > 0) {
            echo json_encode(['ok' => false, 'message' => 'Ya tienes este avatar']); exit;
        }

        // Obtener precio
        $av = $conn->prepare("SELECT price, name FROM shop_avatars WHERE id=? AND active=1");
        $av->bind_param("i", $avatar_id);
        $av->execute();
        $avData = $av->get_result()->fetch_assoc();
        if (!$avData) { echo json_encode(['ok' => false, 'message' => 'Avatar no encontrado']); exit; }

        $price = (int)$avData['price'];
        $lootcoins = (int)$conn->query("SELECT lootcoins FROM users WHERE id=$user_id")->fetch_assoc()['lootcoins'];

        if ($lootcoins < $price) {
            echo json_encode(['ok' => false, 'message' => 'No tienes suficientes Lujanitos. Necesitas ' . $price . ' LJ']); exit;
        }

        // Descontar y registrar compra
        $conn->begin_transaction();
        try {
            $conn->query("UPDATE users SET lootcoins = lootcoins - $price WHERE id = $user_id");
            $ins = $conn->prepare("INSERT INTO user_avatars (user_id, avatar_id) VALUES (?, ?)");
            $ins->bind_param("ii", $user_id, $avatar_id);
            $ins->execute();
            $conn->commit();

            $newBalance = (int)$conn->query("SELECT lootcoins FROM users WHERE id=$user_id")->fetch_assoc()['lootcoins'];
            echo json_encode(['ok' => true, 'message' => '¡Avatar "' . $avData['name'] . '" comprado!', 'lootcoins' => $newBalance]);
        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode(['ok' => false, 'message' => 'Error al procesar la compra']);
        }
        break;

    // ── Equipar avatar ───────────────────────────────────────────────────
    case 'equip':
        csrf_verify();
        $avatar_id = (int)($_POST['avatar_id'] ?? 0);
        if ($avatar_id < 1) { echo json_encode(['ok' => false, 'message' => 'Avatar inválido']); exit; }

        // Verificar que el usuario lo posee
        $chk = $conn->prepare("SELECT id FROM user_avatars WHERE user_id=? AND avatar_id=?");
        $chk->bind_param("ii", $user_id, $avatar_id);
        $chk->execute();
        if ($chk->get_result()->num_rows === 0) {
            echo json_encode(['ok' => false, 'message' => 'No posees este avatar']); exit;
        }

        // Obtener image_url del avatar
        $av = $conn->prepare("SELECT image_url, name FROM shop_avatars WHERE id=?");
        $av->bind_param("i", $avatar_id);
        $av->execute();
        $avData = $av->get_result()->fetch_assoc();
        if (!$avData) { echo json_encode(['ok' => false, 'message' => 'Avatar no encontrado']); exit; }

        // Guardar como avatar_url del usuario con prefijo especial
        $avatarUrl = 'avatar_shop:' . $avData['image_url'];
        $upd = $conn->prepare("UPDATE users SET avatar_url = ? WHERE id = ?");
        $upd->bind_param("si", $avatarUrl, $user_id);
        $upd->execute();

        echo json_encode(['ok' => true, 'message' => '¡Avatar "' . $avData['name'] . '" equipado!', 'avatar_url' => $avatarUrl]);
        break;

    // ── Desequipar (volver a sin avatar) ─────────────────────────────────
    case 'unequip':
        csrf_verify();
        $upd = $conn->prepare("UPDATE users SET avatar_url = '' WHERE id = ?");
        $upd->bind_param("i", $user_id);
        $upd->execute();
        echo json_encode(['ok' => true, 'message' => 'Avatar retirado']);
        break;

    default:
        echo json_encode(['ok' => false, 'message' => 'Acción no válida']);
}
