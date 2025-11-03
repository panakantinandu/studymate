<?php
require_once '../../config/pdo.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $username = trim($_POST['username']);
  $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
  $email    = trim($_POST['email']);

  $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, 'student')");
  $stmt->execute([$username, $email, $password]);
  header("Location: ../../index.php");
  exit;
}
?>
<!DOCTYPE html>
<html>
<head>
  <title>Register | StudyMate</title>
  <link rel="stylesheet" href="../../assets/css/style.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head>
<body class="d-flex justify-content-center align-items-center vh-100 bg-light">
  <div class="card p-4 shadow" style="width:350px;">
    <h4 class="text-center mb-3">Student Registration</h4>
    <form method="POST">
      <input class="form-control mb-2" name="username" placeholder="Username" required>
      <input type="email" class="form-control mb-2" name="email" placeholder="Email" required>
      <input type="password" class="form-control mb-3" name="password" placeholder="Password" required>
      <button class="btn btn-primary w-100">Register</button>
      <p class="mt-2 text-center"><a href="../../index.php">Back to login</a></p>
    </form>
  </div>
</body>
</html>
