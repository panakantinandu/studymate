<?php
session_start();
require_once __DIR__ . '/../../config/pdo.php';

// ✅ Ensure logged-in student
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'student') {
    header("Location: ../../index.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

// ✅ Mark all notifications as read once the user views this page
$pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?")->execute([$user_id]);

// ✅ Fetch all notifications (newest first)
$stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Notifications</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<style>
body {
    background: #f4f7f6;
    font-family: 'Segoe UI', sans-serif;
}
.container {
    background: #fff;
    margin-top: 40px;
    padding: 30px;
    border-radius: 15px;
    box-shadow: 0 6px 15px rgba(0,0,0,0.1);
    max-width: 800px;
}
h2 {
    color: #e50a0a;
    text-align: center;
    font-weight: 700;
    margin-bottom: 20px;
}
.alert {
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: space-between;
}
.icon {
    font-size: 1.4rem;
    margin-right: 10px;
}
.type-message { background-color: #e3f2fd; border-left: 5px solid #2196f3; }
.type-session { background-color: #e8f5e9; border-left: 5px solid #4caf50; }
.type-reminder { background-color: #fff3e0; border-left: 5px solid #ff9800; }
.type-general { background-color: #f3e5f5; border-left: 5px solid #9c27b0; }
small { color: #777; }
.btn-open {
    background: #e50a0a;
    color: #fff;
    border: none;
    padding: 6px 12px;
    border-radius: 6px;
    text-decoration: none;
}
.btn-open:hover {
    background: #c40808;
    color: #fff;
}
.empty-text {
    text-align: center;
    color: #777;
    font-style: italic;
    margin-top: 50px;
}
</style>
</head>
<body>
<div class="container">
    <a href="dashboard.php" class="btn btn-outline-danger mb-3">
        <i class="fas fa-arrow-left"></i> Back to Dashboard
    </a>
    <h2><i class="fas fa-bell"></i> Notifications</h2>

    <?php if (empty($notifications)): ?>
        <p class="empty-text">You don’t have any notifications yet.</p>
    <?php else: ?>
        <?php foreach ($notifications as $n): ?>
            <?php
            $type = htmlspecialchars($n['type']);
            $message = htmlspecialchars($n['message']);
            $time = date("M d, Y h:i A", strtotime($n['created_at']));
            $link = !empty($n['link_url']) ? htmlspecialchars($n['link_url']) : null;

            // Icon by type
            $icon = match($type) {
                'message'  => '<i class="fas fa-envelope icon text-primary"></i>',
                'session'  => '<i class="fas fa-handshake icon text-success"></i>',
                'reminder' => '<i class="fas fa-clock icon text-warning"></i>',
                default    => '<i class="fas fa-info-circle icon text-purple"></i>',
            };
            ?>
            <div class="alert type-<?= $type ?> mb-3">
                <div class="d-flex align-items-center">
                    <?= $icon ?>
                    <div>
                        <strong><?= $message ?></strong><br>
                        <small><?= $time ?></small>
                    </div>
                </div>
                <?php if ($link): ?>
                    <a href="http://localhost:8081/Advanced_Web_Application_Project/studymate/views/student/messages.php" class="btn-open">Open</a>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
</body>
</html>
