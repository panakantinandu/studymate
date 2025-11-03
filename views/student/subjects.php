<?php
session_start();
require_once __DIR__ . '/../../config/pdo.php';

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'student') {
    header('Location: ../../index.php');
    exit;
}

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) die('user_id missing');

$success = '';
$error = '';

// Handle subscribe/unsubscribe
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['subscribe']) && isset($_POST['subject_id'])) {
        $sid = intval($_POST['subject_id']);
        try {
            $ins = $pdo->prepare("INSERT IGNORE INTO user_subjects (user_id, subject_id) VALUES (?, ?)");
            $ins->execute([$user_id, $sid]);
            $success = 'Subscribed to subject.';
        } catch (Exception $e) {
            $error = 'Error subscribing: ' . $e->getMessage();
        }
    } elseif (isset($_POST['unsubscribe']) && isset($_POST['subject_id'])) {
        $sid = intval($_POST['subject_id']);
        try {
            $del = $pdo->prepare("DELETE FROM user_subjects WHERE user_id = ? AND subject_id = ?");
            $del->execute([$user_id, $sid]);
            $success = 'Unsubscribed from subject.';
        } catch (Exception $e) {
            $error = 'Error unsubscribing: ' . $e->getMessage();
        }
    }
}

// Fetch all subjects
$subjects = $pdo->query("SELECT * FROM subjects ORDER BY subject_name ASC")->fetchAll(PDO::FETCH_ASSOC);
// Fetch user's subscriptions
$user_subs = [];
try {
    $stmt = $pdo->prepare("SELECT subject_id FROM user_subjects WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $user_subs = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'subject_id');
} catch (Exception $e) {
    // table missing
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>My Subjects</title>
    <style>
        body{font-family:'Segoe UI',sans-serif;background:#7ace99ff;margin:0;padding:20px}
        .container{width:85%;margin:0 auto;background:#cceed0ff;padding:20px;border-radius:10px}
        table{width:100%;border-collapse:collapse}
        th,td{padding:10px;border:1px solid #333;text-align:left}
        th{background:#e50a0a;color:#fff}
        .btn{padding:6px 10px;background:#08a112;color:#fff;border:none;border-radius:4px;cursor:pointer}
        .btn.danger{background:#dc3545}
        .back{display:inline-block;margin-bottom:12px;padding:8px 12px;background:#e50a0a;color:#fff;text-decoration:none;border-radius:6px}
        .msg{padding:8px;margin-bottom:12px;border-radius:6px}
        .success{background:#e8f5e9;color:#256029}
        .error{background:#fdecea;color:#a10b0b}
    </style>
</head>
<body>
<div class="container">
    <a class="back" href="profile.php">&larr; Back to Profile</a>
    <h2>Available Subjects</h2>
    <?php if ($success): ?><div class="msg success"><?=htmlspecialchars($success)?></div><?php endif; ?>
    <?php if ($error): ?><div class="msg error"><?=htmlspecialchars($error)?></div><?php endif; ?>

    <?php if (empty($subjects)): ?>
        <p>No subjects available. Please contact admin.</p>
    <?php else: ?>
        <table>
            <thead><tr><th>Subject</th><th>Action</th></tr></thead>
            <tbody>
            <?php foreach($subjects as $s): ?>
                <tr>
                    <td><?=htmlspecialchars($s['subject_name'])?></td>
                    <td>
                        <?php if (in_array($s['subject_id'],$user_subs)): ?>
                            <form method="post" style="display:inline">
                                <input type="hidden" name="subject_id" value="<?=intval($s['subject_id'])?>">
                                <button class="btn danger" type="submit" name="unsubscribe">Unsubscribe</button>
                            </form>
                        <?php else: ?>
                            <form method="post" style="display:inline">
                                <input type="hidden" name="subject_id" value="<?=intval($s['subject_id'])?>">
                                <button class="btn" type="submit" name="subscribe">Subscribe</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
</body>
</html>
