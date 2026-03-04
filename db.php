<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "loot_and_trading";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Fallo en la conexión: " . $conn->connect_error);
}
?>