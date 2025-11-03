<?php
require_once '../../includes/auth.php';
require_role(['student']);
require_once '../../config/pdo.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $stmt = $pdo->prepare("INSERT INTO tasks (user_id,title,due_date) VALUES (?,?,?)");
  $stmt->execute([$_SESSION['user_id'], $_POST['title'], $_POST['due_date'] ?: null]);
}

$tasks = $pdo->prepare("SELECT * FROM tasks WHERE user_id=? ORDER BY is_done, due_date");
$tasks->execute([$_SESSION['user_id']]);
$tasks = $tasks->fetchAll(PDO::FETCH_ASSOC);
?>
<?php include '../../includes/header_admin.php'; ?>
<h2>My Planner</h2>
<form method="POST">
  <input name="title" placeholder="Task name" required>
  <input type="date" name="due_date">
  <button>Add</button>
</form>

<table border="1" width="100%" style="margin-top:10px;">
<tr><th>Task</th><th>Due</th><th>Status</th></tr>
<?php foreach($tasks as $t): ?>
<tr>
  <td><?= htmlspecialchars($t['title']) ?></td>
  <td><?= htmlspecialchars($t['due_date']) ?></td>
  <td><?= $t['is_done'] ? 'Done' : 'Pending' ?></td>
</tr>
<?php endforeach; ?>
</table>
<?php include '../../includes/footer.php'; ?>
