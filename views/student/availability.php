<?php
session_start();
require_once __DIR__ . '/../../config/pdo.php';

// ✅ Ensure student is logged in
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'student') {
    header("Location: ../../index.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$error = "";
$success = "";

// ----------------------------------------
// DELETE Availability
// ----------------------------------------
if (isset($_GET['delete_id'])) {
    $del_id = intval($_GET['delete_id']);
    $pdo->prepare("DELETE FROM availability WHERE avail_id = ? AND user_id = ?")->execute([$del_id, $user_id]);
    $success = "Availability deleted successfully.";
}

// ----------------------------------------
// ADD or UPDATE Availability
// ----------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['day_of_week'], $_POST['start_time'], $_POST['end_time'])) {

    $day = $_POST['day_of_week'];
    $start = trim($_POST['start_time']);
    $end = trim($_POST['end_time']);

    // Convert input to 12-hour format with AM/PM
    $start_12 = date("h:i A", strtotime($start));
    $end_12   = date("h:i A", strtotime($end));
    $time_slot = "$start_12 - $end_12";

    // Avoid duplicates
    $check = $pdo->prepare("SELECT COUNT(*) FROM availability WHERE user_id = ? AND day_of_week = ? AND time_slot = ?");
    $check->execute([$user_id, $day, $time_slot]);
    if ($check->fetchColumn() > 0) {
        $error = "This availability slot already exists.";
    } else {
        $pdo->prepare("INSERT INTO availability (user_id, day_of_week, time_slot) VALUES (?, ?, ?)")
            ->execute([$user_id, $day, $time_slot]);
        $success = "Availability added for $day ($time_slot).";
    }
}

// ----------------------------------------
// FETCH Data
// ----------------------------------------
$availabilities = $pdo->prepare("
    SELECT * FROM availability 
    WHERE user_id=? 
    ORDER BY FIELD(day_of_week,'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday')
");
$availabilities->execute([$user_id]);
$availabilities = $availabilities->fetchAll(PDO::FETCH_ASSOC);

$my_subjects = $pdo->prepare("
    SELECT s.subject_id, s.subject_name 
    FROM subjects s 
    JOIN user_subjects us ON s.subject_id=us.subject_id 
    WHERE us.user_id=? 
    ORDER BY s.subject_name
");
$my_subjects->execute([$user_id]);
$my_subjects = $my_subjects->fetchAll(PDO::FETCH_ASSOC);

// ----------------------------------------
// FIND Matching Peers by Availability
// ----------------------------------------
$matches = [];
if ($availabilities) {
    $params = [$user_id];
    $conds = [];

    foreach ($availabilities as $a) {
        $conds[] = "(a.day_of_week=? AND a.time_slot=?)";
        $params[] = $a['day_of_week'];
        $params[] = $a['time_slot'];
    }

    $sql = "
        SELECT u.user_id, u.username, a.day_of_week, a.time_slot 
        FROM availability a 
        JOIN users u ON a.user_id=u.user_id
        WHERE a.user_id != ? AND (" . implode(" OR ", $conds) . ")
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $matches = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get subjects for matches
    if ($matches) {
        $userIds = array_unique(array_column($matches, 'user_id'));
        $placeholders = implode(',', array_fill(0, count($userIds), '?'));
        $q = $pdo->prepare("
            SELECT us.user_id, s.subject_id, s.subject_name 
            FROM user_subjects us 
            JOIN subjects s ON us.subject_id=s.subject_id 
            WHERE us.user_id IN ($placeholders)
        ");
        $q->execute($userIds);
        $subject_rows = $q->fetchAll(PDO::FETCH_ASSOC);

        $subject_map = [];
        foreach ($subject_rows as $r) $subject_map[$r['user_id']][] = $r;

        foreach ($matches as &$m) {
            $peerSubjects = $subject_map[$m['user_id']] ?? [];
            $shared = array_intersect(array_column($my_subjects, 'subject_id'), array_column($peerSubjects, 'subject_id'));
            $m['shared_subjects'] = array_filter($peerSubjects, fn($ps) => in_array($ps['subject_id'], $shared));
        }
        unset($m);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>My Availability</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<style>
body { background: #f4f7f6; font-family: 'Segoe UI', sans-serif; }
.container { background: white; margin-top: 40px; padding: 30px; border-radius: 15px; box-shadow: 0 6px 15px rgba(0,0,0,0.1); }
h2 { text-align: center; color: #2c3e50; font-weight: 700; }
.table th { background: #e50a0a; color: white; }
.btn-custom { border-radius: 6px; font-weight: 600; }
.badge-shared { background: #ffe08a; color: #111; }
</style>
</head>
<body>
<div class="container">
    <a href="dashboard.php" class="btn btn-outline-danger mb-3"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
    <h2><i class="fas fa-calendar-check"></i> Manage Weekly Availability</h2>

    <?php if ($success): ?><div class="alert alert-success text-center"><?= htmlspecialchars($success) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-danger text-center"><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <!-- Availability Form -->
    <form method="POST" class="row g-3 align-items-end mb-4">
        <div class="col-md-3">
            <label class="form-label">Day of Week</label>
            <select name="day_of_week" class="form-select" required>
                <option value="">Select</option>
                <?php foreach (['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'] as $d): ?>
                    <option><?= $d ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Start Time</label>
            <input type="time" name="start_time" class="form-control" required>
        </div>
        <div class="col-md-3">
            <label class="form-label">End Time</label>
            <input type="time" name="end_time" class="form-control" required>
        </div>
        <div class="col-md-3 d-grid">
            <button type="submit" class="btn btn-danger btn-custom"><i class="fas fa-plus"></i> Add Availability</button>
        </div>
    </form>

    <!-- Current Availability -->
    <h4><i class="fas fa-clock"></i> Your Current Availability</h4>
    <?php if (empty($availabilities)): ?>
        <p class="text-center text-muted">You haven’t added any availability yet.</p>
    <?php else: ?>
        <table class="table table-bordered table-striped">
            <thead><tr><th>Day</th><th>Time Slot</th><th>Action</th></tr></thead>
            <tbody>
            <?php foreach ($availabilities as $a): ?>
                <tr>
                    <td><?= htmlspecialchars($a['day_of_week']) ?></td>
                    <td><?= htmlspecialchars($a['time_slot']) ?></td>
                    <td>
                        <a href="?delete_id=<?= $a['avail_id'] ?>" class="btn btn-sm btn-danger"
                           onclick="return confirm('Delete this availability slot?')"><i class="fas fa-trash"></i></a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <!-- Matching Section -->
    <h4 class="mt-5"><i class="fas fa-users"></i> Matching Students </h4>
    <?php if (empty($matches)): ?>
        <p class="text-center text-muted">No matches found yet — try adding more time slots.</p>
    <?php else: ?>
        <table class="table table-hover mt-3">
            <thead><tr><th>User</th><th>Day</th><th>Time</th><th>Shared Subjects</th><th>Action</th></tr></thead>
            <tbody>
            <?php foreach ($matches as $m): ?>
                <tr>
                    <td><?= htmlspecialchars($m['username']) ?></td>
                    <td><?= htmlspecialchars($m['day_of_week']) ?></td>
                    <td><?= htmlspecialchars($m['time_slot']) ?></td>
                    <td>
                        <?php if (!empty($m['shared_subjects'])): ?>
                            <?php foreach ($m['shared_subjects'] as $s): ?>
                                <span class="badge badge-shared"><?= htmlspecialchars($s['subject_name']) ?></span>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <em>No common subjects</em>
                        <?php endif; ?>
                    </td>
                    <td>
                        <a href="session_requests.php" class="btn btn-success btn-sm">
                            <i class="fas fa-handshake"></i> Request Session
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
