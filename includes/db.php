<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "loot_and_trading";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    error_log("DB connection error: " . $conn->connect_error);
    http_response_code(500);
    die(json_encode(['ok' => false, 'message' => 'Error de servidor. Inténtalo más tarde.']));
}
?>
