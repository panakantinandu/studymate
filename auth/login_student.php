<?php
require_once __DIR__ . '/../includes/auth.php';     // secure session + cookies
require_once __DIR__ . '/../config/pdo.php';        // DB connection
require_once __DIR__ . '/../functions/email_helper.php'; // centralized mail helper

$error = "";
$success = "";

// --- Redirect if already logged in ---
if (isset($_SESSION['username']) && $_SESSION['role'] === 'student') {
    header("Location: ../views/student/dashboard.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'login') {

    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if ($username === "" || $password === "") {
        $error = "Please enter both username and password.";
    } else {

        $stmt = $pdo->prepare("SELECT user_id, username, password, email FROM users WHERE username = ? AND role='student'");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {

            regenerate_session_secure();

            $_SESSION['user_id']  = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role']     = 'student';

            $pdo->prepare("UPDATE users SET last_login = NOW() WHERE user_id = ?")
                ->execute([$user['user_id']]);

            header("Location: /Advanced_Web_Application_Project/studymate/views/student/dashboard.php");
            exit;
        } else {
            $error = "Invalid username or password.";
        }
    }
}

// =============================
//     STUDENT FORGOT PASSWORD
// =============================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'forgot') {

    $email = trim($_POST['email']);

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND role='student'");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $otp = rand(100000, 999999);

        $_SESSION['otp']        = $otp;
        $_SESSION['otp_email']  = $email;
        $_SESSION['otp_expiry'] = time() + 300;

        $body = "Your OTP for StudyMate password reset is: $otp\nValid for 5 minutes.";
        $sent = send_email($email, "StudyMate Password Reset OTP", $body);

        $success = $sent ? "OTP sent to your email." : "Failed to send OTP!";
    } else {
        $error = "Email not found.";
    }
}

// =============================
//     STUDENT RESET PASSWORD
// =============================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'reset') {

    $entered_otp      = trim($_POST['otp']);
    $new_password     = trim($_POST['new_password']);
    $confirm_password = trim($_POST['confirm_password']);

    if (!isset($_SESSION['otp_email']) || $_SESSION['otp_expiry'] < time()) {
        $error = "OTP expired.";
    } elseif ($entered_otp != $_SESSION['otp']) {
        $error = "Invalid OTP.";
    } elseif ($new_password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        $hashed = password_hash($new_password, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("UPDATE users SET password=? WHERE email=?");
        $stmt->execute([$hashed, $_SESSION['otp_email']]);

        unset($_SESSION['otp'], $_SESSION['otp_email'], $_SESSION['otp_expiry']);

        $success = "Password reset successful! You can log in now.";
    }
}
?>



<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>StudyMate | Student Login</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center justify-content-center vh-100">
<div class="card shadow p-4" style="width: 400px;">
<h3 class="text-center mb-3">🎓 Student Login</h3>

<?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

<?php if (strpos($success, 'OTP sent') === false): ?>
<form method="POST">
    <input type="hidden" name="action" value="login">
    <div class="mb-3"><input type="text" class="form-control" name="username" placeholder="Username" required></div>
    <div class="mb-3"><input type="password" class="form-control" name="password" placeholder="Password" required></div>
    <button class="btn btn-danger w-100">Login</button>
</form>

<p class="text-center mt-3"><a href="#" onclick="document.getElementById('forgot').style.display='block';return false;">Forgot Password?</a></p>

<div id="forgot" style="display:none;">
<form method="POST">
    <input type="hidden" name="action" value="forgot">
    <input type="email" class="form-control mb-2" name="email" placeholder="Enter your email" required>
    <button class="btn btn-warning w-100">Send OTP</button>
</form>
</div>

<?php else: ?>
<form method="POST">
    <input type="hidden" name="action" value="reset">
    <input type="text" class="form-control mb-2" name="otp" placeholder="Enter OTP" required>
    <input type="password" class="form-control mb-2" name="new_password" placeholder="New Password" required>
    <input type="password" class="form-control mb-3" name="confirm_password" placeholder="Confirm Password" required>
    <button class="btn btn-success w-100">Reset Password</button>
</form>
<?php endif; ?>

<p class="mt-3 text-center small">Don’t have an account? <a href="register.php">Register here</a></p>
<p class="text-center small"><a href="login_admin.php">Login as Admin</a></p>
</div>
</body>
</html>
