<?php
require_once "../config/pdo.php";

if (!isset($_GET['username'])) {
    echo "empty";
    exit;
}

$username = trim($_GET['username']);

$stmt = $pdo->prepare("SELECT username FROM users WHERE username = ?");
$stmt->execute([$username]);

echo $stmt->rowCount() > 0 ? "taken" : "available";
