<?php
require_once '../../includes/auth.php';
require_role(['student']);
require_once '../../config/pdo.php';

if ($_SERVER['REQUEST_METHOD']==='POST') {
  $stmt = $pdo->prepare("INSERT INTO feedback_tickets (user_id,category,title,body) VALUES (?,?,?,?)");
  $stmt->execute([$_SESSION['user_id'], $_POST['category'], $_POST['title'], $_POST['body']]);
}
?>
<?php include '../../includes/header_admin.php'; ?>
<h2>Submit Feedback</h2>
<form method="POST">
  <select name="category">
    <option value="general">General</option>
    <option value="technical">Technical</option>
    <option value="academic">Academic</option>
  </select>
  <input name="title" placeholder="Title" required>
  <textarea name="body" placeholder="Describe the issue..." required></textarea>
  <button>Submit</button>
</form>
<?php include '../../includes/footer.php'; ?>
