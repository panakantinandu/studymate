<?php
session_start();
require_once __DIR__ . '/../config/pdo.php';

function require_login() {
    if (empty($_SESSION['user_id'])) {
        header("Location: /index.php?err=login_required");
        exit;
    }
}

function require_role($allowed_roles = []) {
    require_login();
    $role = $_SESSION['role'] ?? 'student';
    if (!in_array($role, (array)$allowed_roles, true)) {
        http_response_code(403);
        echo "<h3>Access Denied</h3>";
        exit;
    }
}
?>
