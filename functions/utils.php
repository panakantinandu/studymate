<?php
require_once __DIR__ . '/../config/pdo.php';

function create_notification($user_id, $message) {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO notifications (user_id, message, is_read, created_at) VALUES (?, ?, 0, NOW())");
    $stmt->execute([$user_id, $message]);
}
?>
