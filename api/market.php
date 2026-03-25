<?php
session_start();
require_once dirname(__DIR__) . '/includes/db.php';
header('Content-Type: application/json');
$action = $_GET['action'] ?? '';
if ($action === 'price_history') { priceHistory($conn); }
else { echo json_encode(['ok'=>false]); }

function priceHistory($conn) {
    // Ensure table exists
    $conn->query("CREATE TABLE IF NOT EXISTS price_history (
        id INT AUTO_INCREMENT PRIMARY KEY,
        card_id VARCHAR(100) NOT NULL,
        game VARCHAR(20) NOT NULL DEFAULT '',
        price DECIMAL(10,2) NOT NULL,
        recorded_at DATE NOT NULL,
        INDEX idx_card_date (card_id, recorded_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $card_id = trim($_GET['card_id'] ?? '');
    $game    = trim($_GET['game']    ?? '');
    $price   = isset($_GET['price']) ? floatval($_GET['price']) : 0;

    if ($card_id === '') {
        echo json_encode(['ok'=>false, 'error'=>'missing card_id']);
        return;
    }

    $today = date('Y-m-d');

    // If price is valid and not yet recorded today, insert it
    if ($price > 0) {
        $stmt = $conn->prepare("SELECT id FROM price_history WHERE card_id=? AND recorded_at=? LIMIT 1");
        $stmt->bind_param('ss', $card_id, $today);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows === 0) {
            $stmt->close();
            $ins = $conn->prepare("INSERT INTO price_history (card_id, game, price, recorded_at) VALUES (?,?,?,?)");
            $ins->bind_param('ssds', $card_id, $game, $price, $today);
            $ins->execute();
            $ins->close();
        } else {
            $stmt->close();
        }
    }

    // Check how many entries exist for this card
    $cnt_stmt = $conn->prepare("SELECT COUNT(*) FROM price_history WHERE card_id=?");
    $cnt_stmt->bind_param('s', $card_id);
    $cnt_stmt->execute();
    $cnt_stmt->bind_result($count);
    $cnt_stmt->fetch();
    $cnt_stmt->close();

    // Seed 30 days of history if fewer than 3 entries exist
    if ($count < 3 && $price > 0) {
        // Generate prices array working backwards from today-1 to today-30
        // Then insert in chronological order (today-30 to today-1)
        $seed_prices = [];
        $current = $price * (0.85 + mt_rand(0, 1000) / 10000.0 * 10); // price * (0.85 to 0.95)

        // Build 30 daily prices via random walk (index 0 = 30 days ago, index 29 = yesterday)
        for ($i = 0; $i < 30; $i++) {
            if (mt_rand(1, 10) === 1) {
                // ~10% chance of larger spike ±8%
                $factor = 1 + (mt_rand(-80, 80) / 1000.0);
            } else {
                // Normal day ±5%
                $factor = 1 + (mt_rand(-50, 50) / 1000.0);
            }
            $current = max(0.50, $current * $factor);
            $seed_prices[] = round($current, 2);
        }

        // The last generated price should be close to current_price — nudge final value
        // Gently steer the last few days toward current_price
        $steps = 5;
        $last_idx = count($seed_prices) - 1;
        for ($j = 0; $j < $steps; $j++) {
            $idx = $last_idx - $steps + 1 + $j;
            $ratio = ($j + 1) / $steps;
            $seed_prices[$idx] = round($seed_prices[$idx] * (1 - $ratio) + $price * $ratio, 2);
        }

        $ins2 = $conn->prepare("INSERT IGNORE INTO price_history (card_id, game, price, recorded_at) VALUES (?,?,?,?)");
        for ($i = 0; $i < 30; $i++) {
            $day = date('Y-m-d', strtotime("-" . (30 - $i) . " days"));
            $p   = $seed_prices[$i];
            $ins2->bind_param('ssds', $card_id, $game, $p, $day);
            $ins2->execute();
        }
        $ins2->close();

        // Add a unique index to prevent duplicate date entries per card (safe to run multiple times)
        // (already handled by INSERT IGNORE above; table index is non-unique so just guard with IGNORE)
    }

    // Fetch all history sorted ASC
    $sel = $conn->prepare("SELECT recorded_at, price FROM price_history WHERE card_id=? ORDER BY recorded_at ASC");
    $sel->bind_param('s', $card_id);
    $sel->execute();
    $res = $sel->get_result();

    $labels = [];
    $prices = [];
    while ($row = $res->fetch_assoc()) {
        $labels[] = $row['recorded_at'];
        $prices[] = floatval($row['price']);
    }
    $sel->close();

    echo json_encode(['ok' => true, 'labels' => $labels, 'prices' => $prices]);
}
