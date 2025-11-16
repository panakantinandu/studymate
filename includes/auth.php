<?php
// secure cookie settings
$is_https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');

session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => $is_https,
    'httponly' => true,
    'samesite' => 'Strict'
]);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// regenerate session securely
function regenerate_session_secure() {
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_regenerate_id(true);
    }
}

// require login
function require_login() {
    if (!isset($_SESSION['username']) || !isset($_SESSION['role'])) {
        header("Location: /studymate/index.php");
        exit;
    }
}

// require admin role
function require_admin() {
    require_login();
    if ($_SESSION['role'] !== 'admin') {
        header("Location: /studymate/index.php");
        exit;
    }
}

// require student role
function require_student() {
    require_login();
    if ($_SESSION['role'] !== 'student') {
        header("Location: /studymate/index.php");
        exit;
    }
}
?>
<!-- <?php
// ========================================================
// includes/auth.php - Secure Session + Cookie Management
// ========================================================

// // ✅ Define HTTPS flag first
// $is_https = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';

// // ✅ Apply session ini BEFORE starting session
// ini_set('session.use_strict_mode', 1);
// ini_set('session.use_only_cookies', 1);
// ini_set('session.cookie_httponly', 1);
// ini_set('session.cookie_secure', $is_https ? 1 : 0);
// ini_set('session.cookie_samesite', 'Strict');

// // ✅ Cookie config
// $cookieParams = [
//     'lifetime' => 0,
//     'path' => '/',
//     'domain' => '',
//     'secure' => false, 
//     'httponly' => true,
//     'samesite' => 'Lax'
// ];

// // ✅ Start session
// if (session_status() === PHP_SESSION_NONE) {
//     session_set_cookie_params($cookieParams);
//     session_start();
// }

// // ✅ Include DB connection
// if (!isset($pdo)) {
//     require_once __DIR__ . '/../config/pdo.php';
// }

// // ✅ Regenerate session safely
// function regenerate_session_secure() {
//     if (session_status() === PHP_SESSION_ACTIVE) {
//         session_regenerate_id(true);
//     }
// }

// // ✅ Require login
// function require_login() {
//     if (empty($_SESSION['user_id']) && empty($_SESSION['id'])) {
//         header("Location: ../../index.php?err=login_required");
//         exit;
//     }
// }

// // ✅ Require role
// function require_role($allowed_roles = []) {
//     require_login();
//     $role = $_SESSION['role'] ?? '';
//     if (!in_array($role, (array)$allowed_roles, true)) {
//         http_response_code(403);
//         echo "<h3>Access Denied</h3>";
//         exit;
//     }
// }

// // ✅ Visit tracking
// if (!empty($_SESSION['user_id']) || !empty($_SESSION['id'])) {
//     $visitCount = isset($_COOKIE['visits']) ? (int)$_COOKIE['visits'] + 1 : 1;

//     setcookie('visits', $visitCount, [
//         'expires' => time() + (86400 * 30),
//         'path' => '/',
//         'secure' => $cookieParams['secure'],
//         'httponly' => true,
//         'samesite' => 'Strict'
//     ]);

//     try {
//         if (!empty($_SESSION['user_id']) && $_SESSION['role'] === 'student') {
//             $stmt = $pdo->prepare("UPDATE users SET visit_count = ? WHERE user_id = ?");
//             $stmt->execute([$visitCount, $_SESSION['user_id']]);
//         } elseif (!empty($_SESSION['id']) && $_SESSION['role'] === 'admin') {
//             $stmt = $pdo->prepare("UPDATE admin SET visit_count = ? WHERE id = ?");
//             $stmt->execute([$visitCount, $_SESSION['id']]);
//         }
//     } catch (Exception $e) {}
// }

// // ✅ Auto logout after inactivity
// $timeout = 1800; // 30 mins
// if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > $timeout)) {
//     session_unset();
//     session_destroy();

//     if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
//         header("Location: ../../auth/login_admin.php?err=session_expired");
//     } else {
//         header("Location: ../../auth/login_student.php?err=session_expired");
//     }
//     exit;
// }
// $_SESSION['LAST_ACTIVITY'] = time();
?> -->
