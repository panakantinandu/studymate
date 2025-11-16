<?php
require_once '../config/pdo.php';

session_start();
$success = "";
$error = "";

// Handle POST submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email    = trim($_POST['email']);
    $password = trim($_POST['password']);

    // Check if email already exists
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $emailExists = $stmt->fetch();

    if ($emailExists) {
        $error = "Email is already registered.";
    }

    // Check if username exists
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $userExists = $stmt->fetch();

    if ($userExists) {
        $error = "Username already taken.";
    }

    // Insert if no errors
    if ($error === "") {
        $hashedPass = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role) 
                               VALUES (?, ?, ?, 'student')");
        $stmt->execute([$username, $email, $hashedPass]);

        // Redirect with success message
        $_SESSION['success'] = "Account created successfully! Please login.";
        header("Location: ../index.php");
        exit;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
  <title>Register | StudyMate</title>
  <link rel="stylesheet" href="../../assets/css/style.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">

  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>

<body class="d-flex justify-content-center align-items-center vh-100 bg-light">

  <div class="card p-4 shadow" style="width:350px;">
    <h4 class="text-center mb-3">Student Registration</h4>

    <!-- Display PHP error -->
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger p-2"><?= $error ?></div>
    <?php endif; ?>

    <form method="POST" id="registerForm">

      <input type="text" 
             name="username" 
             id="reg_username" 
             class="form-control mb-1"
             placeholder="Username"
             required>
      <small id="username-status" class="text-danger"></small>

      <input type="email" 
             class="form-control mb-2" 
             name="email" 
             placeholder="Email"
             required>

      <input type="password" 
             class="form-control mb-3" 
             name="password" 
             placeholder="Password"
             required>

      <button class="btn btn-primary w-100">Register</button>

      <p class="mt-2 text-center">
        <a href="../index.php">Back to login</a>
      </p>
    </form>
  </div>

  <script src="/Advanced_Web_Application_Project/studymate/assets/js/validation.js"></script>

</body>
</html>
