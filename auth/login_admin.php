 <?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/pdo.php';

$error = "";
$success = "";

// --- Redirect if already logged in ---
if (isset($_SESSION['username']) && $_SESSION['role'] === 'admin') {
    header("Location: ../views/admin/dashboard.php");
    exit;
}

// --- LOGIN HANDLER ---
//if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if ($username === "" || $password === "") {
        $error = "Please enter both username and password.";
    } else {

        // Fetch admin user
        $stmt = $pdo->prepare("SELECT id, username, password, email FROM admin WHERE username = ?");
        $stmt->execute([$username]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        // Compare password (plain text OR hashed)
        $password_match =
            password_verify($password, $admin['password']) ||
            $password === $admin['password'];

        if ($admin && $password_match) {

            regenerate_session_secure(); // security

            $_SESSION['id']       = $admin['id'];
            $_SESSION['username'] = $admin['username'];
            $_SESSION['role']     = "admin";

            // Update last login time
            $pdo->prepare("UPDATE admin SET last_login = NOW() WHERE id = ?")
                ->execute([$admin['id']]);

            header("Location: /Advanced_Web_Application_Project/studymate/views/admin/dashboard.php");
            exit;
        } else {
            $error = "Invalid username or password.";
        }
    }
}



?>




<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>StudyMate | Admin Login</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="d-flex align-items-center justify-content-center vh-100 bg-dark text-light">
<div class="card p-4 shadow" style="width:400px;background:rgba(188, 189, 126, 0.6);">
<h3 class="text-center mb-3">🛠 Admin Login</h3>
<?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<form id="loginForm" method="POST">
    <div id="error-box"></div>
<input type="text" name="username" class="form-control mb-2" placeholder="Username" required>
    <input type="password" name="password" class="form-control mb-3" placeholder="Password" required>
    <button type="submit" name="action" value="login" class="btn btn-primary">Login</button>
</form>

<script src="/Advanced_Web_Application_Project/studymate/assets/js/validation.js"></script>

<p class="text-center mt-3 small"><a href="login_student.php" class="text-light">Login as Student</a></p>
</div>
</body>
</html> 

