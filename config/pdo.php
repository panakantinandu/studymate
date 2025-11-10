<?php

$host = 'localhost';
$db   = '';//replace it with your database name
$user = '';// replace your user
$pass = '';// replace your password
$charset = 'utf8mb4';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=$charset", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
        // ✅ Sync timezone
        date_default_timezone_set('America/Chicago');
        $pdo->exec("SET time_zone = '-05:00'");
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>
