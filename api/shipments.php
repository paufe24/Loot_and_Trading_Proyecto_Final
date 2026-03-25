<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
if (!isset($_SESSION['user_id'])) { echo json_encode(['ok'=>false]); exit; }
require_once dirname(__DIR__) . '/includes/db.php';
$uid = (int)$_SESSION['user_id'];

$result = ['ok'=>true, 'cart'=>[], 'auctions'=>[]];

$s1 = $conn->prepare("SELECT id AS order_id, shipping_status FROM cart_orders WHERE user_id = ?");
if ($s1) { $s1->bind_param("i",$uid); $s1->execute(); $result['cart'] = $s1->get_result()->fetch_all(MYSQLI_ASSOC); }

$s2 = $conn->prepare("SELECT auction_id, status AS shipping_status FROM auction_claims WHERE user_id = ? AND choice='delivery'");
if ($s2) { $s2->bind_param("i",$uid); $s2->execute(); $result['auctions'] = $s2->get_result()->fetch_all(MYSQLI_ASSOC); }

echo json_encode($result);
