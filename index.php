<?php
session_start();
require_once __DIR__ . '/config/pdo.php';

// Include PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'src/Exception.php';
require 'src/PHPMailer.php';
require 'src/SMTP.php';

$error = "";
$success = "";

// --- Redirect already logged-in users ---
if (isset($_SESSION['username']) && isset($_SESSION['role']) && !isset($_GET['logout'])) {

    if ($_SESSION['role'] === 'admin') {
        header("Location: views/admin/dashboard.php");
    } else if ($_SESSION['role'] === 'student') {
        header("Location: views/student/dashboard.php");
    }
    exit;
}

// --- Handle login ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'login') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $role = $_POST['role'];

    if (empty($username) || empty($password)) {
        $error = "Please enter both username and password.";
    } else {
        if ($role === 'admin') {
            $stmt = $pdo->prepare("SELECT * FROM admin WHERE username = ?");
        } else {
            $stmt = $pdo->prepare("SELECT user_id, username, password, email, role FROM users WHERE username = ?");
        }

        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            $validPassword = ($role === 'admin')
                ? ($password === $user['password'])
                : password_verify($password, $user['password']);

            if ($validPassword) {
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $role;
                 // ✅ Store user_id only for students (since 'admin' table may not have user_id)
                if ($role === 'student') {
                   $_SESSION['user_id'] = $user['user_id'];  // make sure 'user_id' exists in 'users' table
                }

                if ($role === 'admin') {
                    header("Location: views/admin/dashboard.php");
                } else {
                    header("Location: views/student/dashboard.php");
                }
                exit;
            } else {
                $error = "Invalid username or password.";
            }
        } else {
            $error = "User not found.";
        }
    }
}

// --- Handle forgot password (students only) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'forgot') {
    $email = trim($_POST['email']);
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $otp = rand(100000, 999999);
        $_SESSION['otp'] = $otp;
        $_SESSION['otp_email'] = $email;
        $_SESSION['otp_expiry'] = time() + 300; // 5 min

        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'nandupanakanti@gmail.com'; // your gmail
            $mail->Password = 'bcpi gwuq muah dixu'; // Gmail App Password
            $mail->SMTPSecure = 'tls';
            $mail->Port = 587;

            $mail->setFrom('nandupanakanti@gmail.com', 'StudyMate');
            $mail->addAddress($email);
            $mail->Subject = 'StudyMate Password Reset OTP';
            $mail->Body = "Your OTP for password reset is: $otp\nValid for 5 minutes.";

            $mail->send();
            $success = "OTP sent to your email.";
        } catch (Exception $e) {
            $error = "Failed to send OTP. Error: {$mail->ErrorInfo}";
        }
    } else {
        $error = "Email not found.";
    }
}

// --- Handle reset password ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'reset') {
    $entered_otp = trim($_POST['otp']);
    $new_password = trim($_POST['new_password']);
    $confirm_password = trim($_POST['confirm_password']);

    if (!isset($_SESSION['otp'], $_SESSION['otp_email'], $_SESSION['otp_expiry'])) {
        $error = "OTP session expired. Try again.";
    } elseif ($_SESSION['otp_expiry'] < time()) {
        $error = "OTP expired. Request again.";
        unset($_SESSION['otp'], $_SESSION['otp_email'], $_SESSION['otp_expiry']);
    } elseif ($entered_otp != $_SESSION['otp']) {
        $error = "Invalid OTP.";
    } elseif ($new_password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE email = ?");
        $stmt->execute([$hashed_password, $_SESSION['otp_email']]);
        $success = "Password reset successfully. You can now log in.";
        unset($_SESSION['otp'], $_SESSION['otp_email'], $_SESSION['otp_expiry']);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>StudyMate | Login</title>
    <link rel="stylesheet" href="assests/css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="d-flex align-items-center justify-content-center vh-100">

<div class="text-center mb-4">
    <h1 class="fw-bold">Welcome to StudyMate</h1>
    <p class="text-muted">Connect, Learn, and Grow Together</p>
</div>

<div class="container d-flex justify-content-center gap-4">

    <?php if (!empty($error)) echo "<div class='alert alert-danger text-center w-100'>$error</div>"; ?>
    <?php if (!empty($success)) echo "<div class='alert alert-success text-center w-100'>$success</div>"; ?>

    <!-- Admin Login -->
    <div class="card shadow p-4" style="width:300px;">
        <h4 class="text-center mb-3">Admin Login</h4>
        <form method="POST">
            <input type="hidden" name="action" value="login">
            <input type="hidden" name="role" value="admin">
            <input type="text" class="form-control mb-2" name="username" placeholder="Admin Username" required>
            <input type="password" class="form-control mb-3" name="password" placeholder="Password" required>
            <button class="btn btn-primary w-100">Login</button>
        </form>
    </div>

    <!-- Student Login -->
    <div class="card shadow p-4" style="width:300px;">
        <h4 class="text-center mb-3">Student Login</h4>

        <?php if (!isset($success) || strpos($success, 'OTP sent') === false): ?>
        <form method="POST">
            <input type="hidden" name="action" value="login">
            <input type="hidden" name="role" value="student">
            <input type="text" class="form-control mb-2" name="username" placeholder="Student Username" required>
            <input type="password" class="form-control mb-3" name="password" placeholder="Password" required>
            <button class="btn btn-success w-100">Login</button>
        </form>

        <p class="mt-2 text-center small">
            <a href="#" onclick="document.getElementById('forgot').style.display='block'; return false;">Forgot Password?</a>
        </p>

        <!-- Forgot Password -->
        <div id="forgot" style="display:none;">
            <form method="POST" class="mt-2">
                <input type="hidden" name="action" value="forgot">
                <input type="email" name="email" class="form-control mb-2" placeholder="Enter your email" required>
                <button class="btn btn-warning w-100">Send OTP</button>
            </form>
        </div>

        <?php endif; ?>

        <!-- Reset Password -->
        <?php if (isset($success) && strpos($success, 'OTP sent') !== false): ?>
        <div id="reset" class="mt-3">
            <form method="POST">
                <input type="hidden" name="action" value="reset">
                <input type="text" name="otp" class="form-control mb-2" placeholder="Enter OTP" required>
                <input type="password" name="new_password" class="form-control mb-2" placeholder="New Password" required>
                <input type="password" name="confirm_password" class="form-control mb-3" placeholder="Confirm Password" required>
                <button class="btn btn-info w-100">Reset Password</button>
            </form>
        </div>
        <?php endif; ?>

        <p class="mt-3 text-center small">
            Don’t have an account? <a href="views/student/register.php">Register here</a>
        </p>
    </div>
</div>

</body>
</html>
