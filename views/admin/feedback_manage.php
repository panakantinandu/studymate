<?php
session_start();
require_once __DIR__ . '/../../config/pdo.php';
//include __DIR__ . '/../../includes/header_admin.php';

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../index.php");
    exit;
}

$message = "";

// ✅ Update ticket status
if (isset($_POST['update_status'])) {
    $id = $_POST['ticket_id'];
    $status = $_POST['status'];
    $stmt = $pdo->prepare("UPDATE feedback_tickets SET status=? WHERE id=?");
    $stmt->execute([$status, $id]);
    header("Location: feedback_manage.php?updated=1");
    exit;
}

// ✅ Delete ticket
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM feedback_tickets WHERE id=?");
    $stmt->execute([$id]);
    $message = "❌ Feedback deleted successfully.";
}

// ✅ Fetch all feedback
$tickets = $pdo->query("
    SELECT t.*, u.username 
    FROM feedback_tickets t
    JOIN users u ON t.user_id = u.user_id
    ORDER BY t.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

if (isset($_GET['updated'])) $message = "✅ Feedback status updated successfully.";
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Feedback Tickets</title>
<style>
body { font-family:'Segoe UI',sans-serif;background-color:#7ace99;margin:0;padding:0;}
.container{margin:30px auto;width:85%;background:#cceed0;border-radius:12px;box-shadow:0 4px 10px rgba(0,0,0,0.1);padding:30px;}
h2{text-align:center;color:#f21010;font-size:45px;margin-bottom:25px;}
.back-link{display:inline-block;margin-bottom:20px;padding:8px 15px;background-color:#e50a0a;color:#fff;text-decoration:none;border-radius:5px;font-weight:bold;}
.back-link:hover{background-color:#c40808;}
.message{background-color:#08a112;color:#fff;text-align:center;padding:10px;border-radius:6px;margin-bottom:20px;display:<?= $message ? 'block' : 'none' ?>;}
table{width:100%;border-collapse:collapse;text-align:center;}
th,td{padding:12px;border:1px solid #310303;}
th{background-color:#e50a0a;color:#fff;font-size:18px;text-transform:uppercase;}
tr:hover{background-color:#ea7676;}
select{padding:5px;border-radius:5px;}
.action-btn{padding:5px 12px;border-radius:5px;border:none;cursor:pointer;color:white;font-size:14px;text-decoration:none;}
.update-btn{background-color:#08a112;} .update-btn:hover{background-color:#067d0f;}
.delete-btn{background-color:#d91a1a;} .delete-btn:hover{background-color:#b01515;}
.no-data{text-align:center;padding:15px;color:#444;}
</style>
</head>
<body>
<div class="container">
<a href="dashboard.php" class="back-link">&larr; Back to Dashboard</a>
<h2>Feedback Tickets</h2>

<?php if ($message): ?><div class="message"><?= htmlspecialchars($message) ?></div><?php endif; ?>

<table>
<thead>
<tr><th>ID</th><th>User</th><th>Title</th><th>Category</th><th>Status</th><th>Created</th><th>Actions</th></tr>
</thead>
<tbody>
<?php if(empty($tickets)): ?>
<tr><td colspan="7" class="no-data">No feedback tickets found.</td></tr>
<?php else: ?>
<?php foreach($tickets as $t): ?>
<tr>
<td><?= $t['id'] ?></td>
<td><?= htmlspecialchars($t['username']) ?></td>
<td><?= htmlspecialchars($t['title']) ?></td>
<td><?= htmlspecialchars($t['category']) ?></td>
<td>
<form method="post" style="display:inline;">
<input type="hidden" name="ticket_id" value="<?= $t['id'] ?>">
<select name="status">
<?php foreach(['open','in_progress','resolved','closed'] as $s): ?>
<option value="<?= $s ?>" <?= $t['status']==$s?'selected':'' ?>><?= ucfirst($s) ?></option>
<?php endforeach; ?>
</select>
<button type="submit" name="update_status" class="action-btn update-btn">Update</button>
</form>
</td>
<td><?= $t['created_at'] ?></td>
<td><a href="?delete=<?= $t['id'] ?>" onclick="return confirm('Delete this feedback?')" class="action-btn delete-btn">Delete</a></td>
</tr>
<?php endforeach; ?>
<?php endif; ?>
</tbody>
</table>
</div>
<script>
setTimeout(()=>{const msg=document.querySelector('.message');if(msg)msg.style.display='none';},3000);
</script>
</body>
</html>
