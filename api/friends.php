<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
if (!isset($_SESSION['user_id'])) { echo json_encode(['ok'=>false,'message'=>'Sesión requerida']); exit; }
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/csrf.php';
$uid    = (int)$_SESSION['user_id'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') csrf_verify();

// Crear tabla si no existe (sin FKs para compatibilidad con MyISAM)
$conn->query("CREATE TABLE IF NOT EXISTS friendships (
    id INT AUTO_INCREMENT PRIMARY KEY,
    requester_id INT NOT NULL,
    addressee_id INT NOT NULL,
    status ENUM('pending','accepted','blocked') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_pair (requester_id, addressee_id),
    INDEX idx_req  (requester_id),
    INDEX idx_addr (addressee_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

switch ($action) {

    /* ── Buscar usuarios por username o nombre ── */
    case 'search':
        $q = trim($_GET['q'] ?? '');
        if (strlen($q) < 2) { echo json_encode(['ok'=>true,'users'=>[]]); exit; }
        $like = '%' . $q . '%';
        $st = $conn->prepare(
            "SELECT u.id, u.username, u.name, u.avatar_url,
                    f.status AS friendship_status, f.requester_id
             FROM users u
             LEFT JOIN friendships f ON (
                 (f.requester_id = ? AND f.addressee_id = u.id) OR
                 (f.addressee_id = ? AND f.requester_id = u.id)
             )
             WHERE (u.username LIKE ? OR u.name LIKE ?) AND u.id != ?
             LIMIT 20"
        );
        $st->bind_param("iissi", $uid, $uid, $like, $like, $uid);
        $st->execute();
        echo json_encode(['ok'=>true,'users'=>$st->get_result()->fetch_all(MYSQLI_ASSOC)]);
        break;

    /* ── Lista de amigos aceptados ── */
    case 'list':
        $st = $conn->prepare(
            "SELECT u.id, u.username, u.name, u.avatar_url
             FROM friendships f
             JOIN users u ON u.id = IF(f.requester_id = ?, f.addressee_id, f.requester_id)
             WHERE (f.requester_id = ? OR f.addressee_id = ?) AND f.status = 'accepted'
             ORDER BY u.username ASC"
        );
        $st->bind_param("iii", $uid, $uid, $uid);
        $st->execute();
        echo json_encode(['ok'=>true,'friends'=>$st->get_result()->fetch_all(MYSQLI_ASSOC)]);
        break;

    /* ── Solicitudes pendientes recibidas ── */
    case 'requests':
        $st = $conn->prepare(
            "SELECT f.id AS friendship_id, u.id, u.username, u.name, u.avatar_url, f.created_at
             FROM friendships f
             JOIN users u ON u.id = f.requester_id
             WHERE f.addressee_id = ? AND f.status = 'pending'
             ORDER BY f.created_at DESC"
        );
        $st->bind_param("i", $uid);
        $st->execute();
        echo json_encode(['ok'=>true,'requests'=>$st->get_result()->fetch_all(MYSQLI_ASSOC)]);
        break;

    /* ── Enviar solicitud ── */
    case 'send':
        $target = (int)($_POST['user_id'] ?? 0);
        if (!$target || $target === $uid) { echo json_encode(['ok'=>false,'message'=>'Usuario inválido']); exit; }
        try {
            $st = $conn->prepare("INSERT INTO friendships (requester_id, addressee_id) VALUES (?,?)");
            $st->bind_param("ii", $uid, $target);
            $st->execute();
            echo json_encode(['ok'=>true,'message'=>'Solicitud enviada']);
        } catch(Exception $e) {
            echo json_encode(['ok'=>false,'message'=>'Ya existe una solicitud']);
        }
        break;

    /* ── Responder solicitud (accept/reject) ── */
    case 'respond':
        $fid    = (int)($_POST['friendship_id'] ?? 0);
        $answer = $_POST['answer'] ?? '';
        if (!in_array($answer, ['accepted','rejected'])) { echo json_encode(['ok'=>false]); exit; }
        if ($answer === 'rejected') {
            $st = $conn->prepare("DELETE FROM friendships WHERE id = ? AND addressee_id = ?");
            $st->bind_param("ii", $fid, $uid);
        } else {
            $st = $conn->prepare("UPDATE friendships SET status='accepted' WHERE id = ? AND addressee_id = ?");
            $st->bind_param("ii", $fid, $uid);
        }
        $st->execute();
        echo json_encode(['ok'=>true]);
        break;

    /* ── Eliminar amigo ── */
    case 'remove':
        $target = (int)($_POST['user_id'] ?? 0);
        $st = $conn->prepare(
            "DELETE FROM friendships WHERE
             (requester_id=? AND addressee_id=?) OR (requester_id=? AND addressee_id=?)"
        );
        $st->bind_param("iiii", $uid, $target, $target, $uid);
        $st->execute();
        echo json_encode(['ok'=>true]);
        break;

    /* ── Perfil público de un usuario ── */
    case 'profile':
        $target = (int)($_GET['user_id'] ?? 0);
        $st = $conn->prepare("SELECT id, username, name, avatar_url, created_at FROM users WHERE id = ?");
        $st->bind_param("i", $target);
        $st->execute();
        $profile = $st->get_result()->fetch_assoc();
        if (!$profile) { echo json_encode(['ok'=>false,'message'=>'Usuario no encontrado']); exit; }

        // Comprobar si son amigos
        $sf = $conn->prepare(
            "SELECT status, requester_id, id AS friendship_id FROM friendships WHERE
             (requester_id=? AND addressee_id=?) OR (requester_id=? AND addressee_id=?)"
        );
        $sf->bind_param("iiii", $uid, $target, $target, $uid);
        $sf->execute();
        $frow = $sf->get_result()->fetch_assoc();
        $profile['friendship_status']    = $frow['status'] ?? null;
        $profile['friendship_requester'] = (int)($frow['requester_id'] ?? 0);
        $profile['friendship_id']        = (int)($frow['friendship_id'] ?? 0);

        // Stats
        $statsCartas = 0;
        $stC = $conn->prepare("SELECT COUNT(*) AS n FROM cart_order_items oi JOIN cart_orders o ON o.id=oi.order_id WHERE o.user_id=?");
        $stC->bind_param("i", $target); $stC->execute();
        $statsCartas += (int)$stC->get_result()->fetch_assoc()['n'];

        $stA = $conn->prepare("SELECT COUNT(*) AS n FROM auctions WHERE current_winner_id=? AND status='ended'");
        $stA->bind_param("i", $target); $stA->execute();
        $statsCartas += (int)$stA->get_result()->fetch_assoc()['n'];

        $stG = $conn->prepare("SELECT COUNT(DISTINCT oi.card_game) AS n FROM cart_order_items oi JOIN cart_orders o ON o.id=oi.order_id WHERE o.user_id=?");
        $stG->bind_param("i", $target); $stG->execute();
        $statsColecciones = (int)$stG->get_result()->fetch_assoc()['n'];

        $stI = $conn->prepare("SELECT COUNT(DISTINCT auction_id) AS n FROM auction_bids WHERE user_id=?");
        $stI->bind_param("i", $target); $stI->execute();
        $statsPujas = (int)$stI->get_result()->fetch_assoc()['n'];

        $profile['stats'] = [
            'cartas'      => $statsCartas,
            'colecciones' => $statsColecciones,
            'pujas'       => $statsPujas,
        ];

        // Actividad reciente (pública)
        $activity = [];
        $stAct = $conn->prepare(
            "SELECT activity_type, title, description, created_at
             FROM user_activity WHERE user_id=? ORDER BY created_at DESC LIMIT 10"
        );
        $stAct->bind_param("i", $target); $stAct->execute();
        $activity = $stAct->get_result()->fetch_all(MYSQLI_ASSOC);
        $profile['activity'] = $activity;

        // Subastas ganadas
        $wonAuctions = [];
        $stW = $conn->prepare(
            "SELECT a.card_name, a.card_image, a.card_game, a.current_bid,
                    cl.choice, cl.lujanitos_awarded
             FROM auctions a
             LEFT JOIN auction_claims cl ON cl.auction_id=a.id AND cl.user_id=?
             WHERE a.current_winner_id=? AND a.status='ended'
             ORDER BY a.ends_at DESC LIMIT 10"
        );
        $stW->bind_param("ii", $target, $target); $stW->execute();
        $wonAuctions = $stW->get_result()->fetch_all(MYSQLI_ASSOC);
        $profile['won_auctions'] = $wonAuctions;

        // Favoritos
        $stFav = $conn->prepare("SELECT card_id, card_name, card_image, card_game FROM user_favorites WHERE user_id=? ORDER BY created_at DESC LIMIT 12");
        $stFav->bind_param("i", $target); $stFav->execute();
        $profile['favorites'] = $stFav->get_result()->fetch_all(MYSQLI_ASSOC);

        echo json_encode(['ok'=>true,'profile'=>$profile]);
        break;

    default:
        echo json_encode(['ok'=>false,'message'=>'Acción no válida']);
}
