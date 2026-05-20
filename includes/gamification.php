<?php
// includes/gamification.php — Sistema de niveles, logros y alertas de precio

/* ──────────────────────────────────────
   NIVELES
   fórmula: level = 1 + floor(sqrt(xp / 50))
   Lv1=0, Lv2=50, Lv3=200, Lv4=450, Lv5=800, Lv6=1250, Lv7=1800, Lv8=2450, Lv9=3200, Lv10=4050
────────────────────────────────────── */
function getLevelInfo(int $xp): array {
    $level = 1 + (int)floor(sqrt($xp / 50));
    $level = max(1, $level);

    $titles = [
        1=>'Novato', 2=>'Aprendiz', 3=>'Coleccionista', 4=>'Experto', 5=>'Maestro',
        6=>'Gran Maestro', 7=>'Leyenda', 8=>'Élite', 9=>'Campeón', 10=>'Dios del Mazo'
    ];
    $title = $titles[min($level, 10)] ?? "Nv.$level";

    // XP de umbral para un nivel concreto: (lv-1)^2 * 50
    $xp_for = fn(int $lv) => ($lv - 1) * ($lv - 1) * 50;
    $xp_current = $xp_for($level);
    $xp_next    = $xp_for($level + 1);
    $xp_range   = $xp_next - $xp_current;
    $xp_in_lvl  = $xp - $xp_current;
    $progress   = $xp_range > 0 ? min(100, (int)(($xp_in_lvl / $xp_range) * 100)) : 100;

    return [
        'level'      => $level,
        'title'      => $title,
        'xp'         => $xp,
        'xp_current' => $xp_current,
        'xp_next'    => $xp_next,
        'xp_in_level'=> $xp_in_lvl,
        'xp_range'   => $xp_range,
        'progress'   => $progress,
    ];
}

/* Migraciones: crear tablas y columnas necesarias */
function runGamificationMigrations($conn): void {
    try { $conn->query("ALTER TABLE users ADD COLUMN xp INT NOT NULL DEFAULT 0"); } catch (Exception $e) {}

    $conn->query("CREATE TABLE IF NOT EXISTS achievements (
        id INT AUTO_INCREMENT PRIMARY KEY,
        `key` VARCHAR(50) NOT NULL UNIQUE,
        name VARCHAR(100) NOT NULL,
        description VARCHAR(255) NOT NULL,
        icon VARCHAR(10) NOT NULL DEFAULT '🏆',
        xp_reward INT NOT NULL DEFAULT 0
    )");

    $conn->query("CREATE TABLE IF NOT EXISTS user_achievements (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        achievement_id INT NOT NULL,
        unlocked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_user_ach (user_id, achievement_id),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )");

    // Catálogo de logros (INSERT IGNORE — agrega nuevos sin duplicar existentes)
    {
        $catalog = [
            ['bienvenido',          '¡Bienvenido!',          'Únete a Loot & Trading',                       '🎉', 20],
            ['primera_puja',        'Primera Puja',          'Realiza tu primera puja en una subasta',        '🔨', 10],
            ['pujador_pro',         'Pujador Pro',           'Realiza 10 pujas en subastas',                  '💪', 30],
            ['primera_victoria',    'Primera Victoria',      'Gana tu primera subasta',                       '🏆', 50],
            ['dominador',           'Dominador',             'Gana 3 subastas',                               '👑', 100],
            ['primer_compra',       'Comprador',             'Realiza tu primera compra en el mercado',       '🛒', 15],
            ['coleccionista',       'Coleccionista',         'Añade 5 cartas a favoritos',                    '⭐', 20],
            ['gran_coleccionista',  'Gran Coleccionista',    'Añade 20 cartas a favoritos',                   '💫', 50],
            ['nivel_5',             'Experto',               'Alcanza el nivel 5',                            '🚀', 0],
            ['nivel_10',            'Veterano',              'Alcanza el nivel 10',                           '🌟', 0],
            // Insignias de colección por juego
            ['col_pokemon_5',       'Entrenador Pokémon',        'Consigue 5 cartas de Pokémon',              '🔴', 25],
            ['col_pokemon_10',      'Maestro Pokémon',           'Consigue 10 cartas de Pokémon',             '⚡', 60],
            ['col_pokemon_20',      'Leyenda Pokémon',           'Consigue 20 cartas de Pokémon',             '🌟', 120],
            ['col_yugioh_5',        'Duelista Yu-Gi-Oh!',        'Consigue 5 cartas de Yu-Gi-Oh!',            '🃏', 25],
            ['col_yugioh_10',       'Rey de los Duelos',         'Consigue 10 cartas de Yu-Gi-Oh!',           '👁️', 60],
            ['col_yugioh_20',       'Faraón Legendario',         'Consigue 20 cartas de Yu-Gi-Oh!',           '🏺', 120],
            ['col_magic_5',         'Hechicero Aprendiz',        'Consigue 5 cartas de Magic',                '🔮', 25],
            ['col_magic_10',        'Planeswalker',              'Consigue 10 cartas de Magic',               '✨', 60],
            ['col_magic_20',        'Archimago',                 'Consigue 20 cartas de Magic',               '🧙', 120],
            ['col_onepiece_5',      'Grumete',                   'Consigue 5 cartas de One Piece',            '⚓', 25],
            ['col_onepiece_10',     'Capitán Pirata',            'Consigue 10 cartas de One Piece',           '🏴‍☠️', 60],
            ['col_onepiece_20',     'Rey de los Piratas',        'Consigue 20 cartas de One Piece',           '👒', 120],
            // Insignias de colección general
            ['col_total_10',        'Colección Iniciada',        'Consigue 10 cartas en total',               '📦', 30],
            ['col_total_25',        'Coleccionista Avanzado',    'Consigue 25 cartas en total',               '🗂️', 75],
            ['col_total_50',        'Coleccionista Supremo',     'Consigue 50 cartas en total',               '💎', 150],
        ];
        $st = $conn->prepare("INSERT IGNORE INTO achievements (`key`, name, description, icon, xp_reward) VALUES (?,?,?,?,?)");
        foreach ($catalog as [$k, $n, $d, $ic, $xpR]) {
            $st->bind_param("ssssi", $k, $n, $d, $ic, $xpR);
            $st->execute();
        }
    }

    $conn->query("CREATE TABLE IF NOT EXISTS price_alerts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        card_name VARCHAR(255) NOT NULL,
        card_game VARCHAR(50) NOT NULL,
        target_price INT NOT NULL,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        triggered_at TIMESTAMP NULL DEFAULT NULL,
        read_at TIMESTAMP NULL DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_active_card (is_active, card_name),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )");
    try { $conn->query("ALTER TABLE price_alerts ADD COLUMN read_at TIMESTAMP NULL DEFAULT NULL"); } catch (Exception $e) {}
}

/* Suma XP a un usuario; registra actividad si sube de nivel. */
function addXP($conn, int $user_id, int $amount): void {
    try { $conn->query("ALTER TABLE users ADD COLUMN xp INT NOT NULL DEFAULT 0"); } catch (Exception $e) {}

    $s = $conn->prepare("SELECT xp FROM users WHERE id=?");
    $s->bind_param("i", $user_id);
    $s->execute();
    $row     = $s->get_result()->fetch_assoc();
    $old_xp  = (int)($row['xp'] ?? 0);
    $new_xp  = $old_xp + $amount;

    $old_level = getLevelInfo($old_xp)['level'];
    $new_level = getLevelInfo($new_xp)['level'];

    $u = $conn->prepare("UPDATE users SET xp=? WHERE id=?");
    $u->bind_param("ii", $new_xp, $user_id);
    $u->execute();

    if ($new_level > $old_level) {
        $info  = getLevelInfo($new_xp);
        $type  = 'level_up';
        $title = "¡Subiste al nivel {$new_level}!";
        $desc  = "Ahora eres {$info['title']} — ¡sigue así!";
        $ins = $conn->prepare(
            "INSERT INTO user_activity (user_id, activity_type, title, description) VALUES (?,?,?,?)"
        );
        if ($ins) { $ins->bind_param("isss", $user_id, $type, $title, $desc); $ins->execute(); }
    }
}

/* Evalúa logros y desbloquea los que se cumplan. */
function checkAchievements($conn, int $user_id): void {
    // Necesitamos las tablas (llamar solo si ya se hicieron las migraciones,
    // pero ponemos guards por si se llama suelto)
    try { $conn->query("ALTER TABLE users ADD COLUMN xp INT NOT NULL DEFAULT 0"); } catch (Exception $e) {}

    $s = $conn->prepare("SELECT xp FROM users WHERE id=?");
    $s->bind_param("i", $user_id);
    $s->execute();
    $row   = $s->get_result()->fetch_assoc();
    $xp    = (int)($row['xp'] ?? 0);
    $level = getLevelInfo($xp)['level'];

    $stB = $conn->prepare("SELECT COUNT(*) FROM auction_bids WHERE user_id=?");
    $stB->bind_param("i", $user_id); $stB->execute(); $bids = (int)$stB->get_result()->fetch_row()[0]; $stB->close();

    $stW = $conn->prepare("SELECT COUNT(*) FROM auctions WHERE current_winner_id=? AND status='ended'");
    $stW->bind_param("i", $user_id); $stW->execute(); $wins = (int)$stW->get_result()->fetch_row()[0]; $stW->close();

    $stF = $conn->prepare("SELECT COUNT(*) FROM user_favorites WHERE user_id=?");
    $stF->bind_param("i", $user_id); $stF->execute(); $favs = (int)$stF->get_result()->fetch_row()[0]; $stF->close();

    $stP = $conn->prepare("SELECT COUNT(*) FROM cart_orders WHERE user_id=? AND status='paid'");
    $stP->bind_param("i", $user_id); $stP->execute(); $purchases = (int)$stP->get_result()->fetch_row()[0]; $stP->close();

    // Contar cartas en colección por juego (compras + subastas ganadas con claim)
    $cardsByGame = ['pokemon' => 0, 'yugioh' => 0, 'magic' => 0, 'onepiece' => 0];
    $totalCards  = 0;

    // Cartas de compras
    $stCards = $conn->prepare(
        "SELECT oi.card_game, SUM(oi.quantity) AS cnt
         FROM cart_order_items oi
         JOIN cart_orders o ON o.id = oi.order_id
         WHERE o.user_id = ? AND o.status = 'paid'
         GROUP BY oi.card_game"
    );
    if ($stCards) {
        $stCards->bind_param("i", $user_id);
        $stCards->execute();
        $resCards = $stCards->get_result();
        while ($r = $resCards->fetch_assoc()) {
            $g = strtolower(trim($r['card_game']));
            $c = (int)$r['cnt'];
            if (isset($cardsByGame[$g])) $cardsByGame[$g] += $c;
            $totalCards += $c;
        }
        $stCards->close();
    }

    // Cartas de subastas ganadas (claimed)
    $stAuc = $conn->prepare(
        "SELECT a.card_game, COUNT(*) AS cnt
         FROM auctions a
         JOIN auction_claims cl ON cl.auction_id = a.id AND cl.user_id = ?
         WHERE a.current_winner_id = ?
         GROUP BY a.card_game"
    );
    if ($stAuc) {
        $stAuc->bind_param("ii", $user_id, $user_id);
        $stAuc->execute();
        $resAuc = $stAuc->get_result();
        while ($r = $resAuc->fetch_assoc()) {
            $g = strtolower(trim($r['card_game']));
            $c = (int)$r['cnt'];
            if (isset($cardsByGame[$g])) $cardsByGame[$g] += $c;
            $totalCards += $c;
        }
        $stAuc->close();
    }

    $conditions = [
        'bienvenido'         => true,
        'primera_puja'       => $bids      >= 1,
        'pujador_pro'        => $bids      >= 10,
        'primera_victoria'   => $wins      >= 1,
        'dominador'          => $wins      >= 3,
        'primer_compra'      => $purchases >= 1,
        'coleccionista'      => $favs      >= 5,
        'gran_coleccionista' => $favs      >= 20,
        'nivel_5'            => $level     >= 5,
        'nivel_10'           => $level     >= 10,
        // Colección por juego
        'col_pokemon_5'      => $cardsByGame['pokemon']  >= 5,
        'col_pokemon_10'     => $cardsByGame['pokemon']  >= 10,
        'col_pokemon_20'     => $cardsByGame['pokemon']  >= 20,
        'col_yugioh_5'       => $cardsByGame['yugioh']   >= 5,
        'col_yugioh_10'      => $cardsByGame['yugioh']   >= 10,
        'col_yugioh_20'      => $cardsByGame['yugioh']   >= 20,
        'col_magic_5'        => $cardsByGame['magic']    >= 5,
        'col_magic_10'       => $cardsByGame['magic']    >= 10,
        'col_magic_20'       => $cardsByGame['magic']    >= 20,
        'col_onepiece_5'     => $cardsByGame['onepiece'] >= 5,
        'col_onepiece_10'    => $cardsByGame['onepiece'] >= 10,
        'col_onepiece_20'    => $cardsByGame['onepiece'] >= 20,
        // Colección total
        'col_total_10'       => $totalCards >= 10,
        'col_total_25'       => $totalCards >= 25,
        'col_total_50'       => $totalCards >= 50,
    ];

    foreach ($conditions as $key => $met) {
        if (!$met) continue;

        $stA = $conn->prepare("SELECT id, name, xp_reward FROM achievements WHERE `key`=?");
        $stA->bind_param("s", $key);
        $stA->execute();
        $ach = $stA->get_result()->fetch_assoc();
        if (!$ach) continue;

        $ach_id = (int)$ach['id'];
        $stI    = $conn->prepare("INSERT IGNORE INTO user_achievements (user_id, achievement_id) VALUES (?,?)");
        $stI->bind_param("ii", $user_id, $ach_id);
        $stI->execute();

        if ($conn->affected_rows > 0) {
            // XP bonus del logro
            if ((int)$ach['xp_reward'] > 0) {
                addXP($conn, $user_id, (int)$ach['xp_reward']);
            }
            // Registrar en actividad
            $type  = 'achievement';
            $title = "Logro desbloqueado: {$ach['name']}";
            $desc  = '';
            $ins   = $conn->prepare(
                "INSERT INTO user_activity (user_id, activity_type, title, description, ref_id) VALUES (?,?,?,?,?)"
            );
            if ($ins) { $ins->bind_param("isssi", $user_id, $type, $title, $desc, $ach_id); $ins->execute(); }
        }
    }
}

/* Comprueba alertas activas para una carta recién subastada y notifica al usuario. */
function checkPriceAlerts($conn, string $card_name, int $base_price): void {
    $st = $conn->prepare(
        "SELECT pa.id, pa.user_id, pa.target_price
         FROM price_alerts pa
         WHERE pa.is_active = 1
           AND pa.card_name = ?
           AND pa.target_price >= ?"
    );
    $st->bind_param("si", $card_name, $base_price);
    $st->execute();
    $alerts = $st->get_result()->fetch_all(MYSQLI_ASSOC);

    foreach ($alerts as $alert) {
        $uid    = (int)$alert['user_id'];
        $alertId = (int)$alert['id'];

        // Marcar alerta como disparada
        $upd = $conn->prepare("UPDATE price_alerts SET is_active=0, triggered_at=NOW() WHERE id=?");
        $upd->bind_param("i", $alertId);
        $upd->execute();

        // Notificar al usuario en actividad
        $type  = 'price_alert';
        $title = "Alerta de precio: $card_name";
        $desc  = "¡Hay una subasta de \"$card_name\" por $base_price LJ o menos!";
        $ins   = $conn->prepare(
            "INSERT INTO user_activity (user_id, activity_type, title, description) VALUES (?,?,?,?)"
        );
        if ($ins) { $ins->bind_param("isss", $uid, $type, $title, $desc); $ins->execute(); }
    }
}
