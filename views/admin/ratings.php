<?php
session_start();
require_once __DIR__ . '/../../config/pdo.php';
//include __DIR__ . '/../../includes/header_admin.php';

// ✅ Restrict access to admins only
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../index.php");
    exit;
}

// ✅ Fetch all ratings joined with users and session requests
$ratings = $pdo->query("
    SELECT r.*, 
           u1.username AS rated_by, 
           u2.username AS rated_user,
           s.day_of_week, s.time_slot
    FROM ratings r
    LEFT JOIN users u1 ON r.user_id = u1.user_id
    LEFT JOIN users u2 ON r.rated_user_id = u2.user_id
    LEFT JOIN session_requests s ON r.session_request_id = s.request_id
    ORDER BY r.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

// ✅ Average rating per teacher
$teacher_avg = $pdo->query("
    SELECT rated_user_id, u.username AS teacher_name, 
           ROUND(AVG(rating),1) AS avg_rating, COUNT(*) AS total_reviews
    FROM ratings r
    JOIN users u ON r.rated_user_id = u.user_id
    WHERE r.target_type = 'teacher'
    GROUP BY rated_user_id
    ORDER BY avg_rating DESC
")->fetchAll(PDO::FETCH_ASSOC);

// ✅ Average rating per session
$session_avg = $pdo->query("
    SELECT s.request_id, s.day_of_week, s.time_slot, 
           ROUND(AVG(r.rating),1) AS avg_rating, COUNT(*) AS total_reviews
    FROM ratings r
    JOIN session_requests s ON r.session_request_id = s.request_id
    WHERE r.target_type = 'session'
    GROUP BY s.request_id
    ORDER BY avg_rating DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Ratings Analytics</title>
<style>
body { font-family: 'Segoe UI', sans-serif; background-color: #7ace99; margin: 0; padding: 0; }
.container { margin: 30px auto; width: 85%; background: #cceed0; border-radius: 12px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); padding: 30px; }
h2 { text-align: center; color: #f21010; font-size: 45px; margin-bottom: 25px; }
.back-link { display:inline-block;margin-bottom:20px;padding:8px 15px;background-color:#e50a0a;color:#fff;text-decoration:none;border-radius:5px;font-weight:bold; }
.back-link:hover { background-color:#c40808; }

.section-title { font-size:28px; color:#0b0b0b; margin:30px 0 10px; border-bottom:3px solid #f21010; display:inline-block; }
.table-wrap { overflow-x:auto; margin-top:10px; }
table { width:100%; border-collapse:collapse; text-align:center; }
th, td { padding:12px; border:1px solid #310303; }
th { background-color:#e50a0a; color:white; text-transform:uppercase; font-size:16px; }
tr:hover { background-color:#ea7676; }
.no-data { text-align:center; padding:15px; color:#444; }

.star { color:gold; font-size:18px; }
.progress-bar { height:10px; border-radius:5px; background-color:#eee; position:relative; }
.progress-fill { height:100%; border-radius:5px; background-color:#f21010; }

.rating-badge { background:#f21010; color:#fff; padding:4px 8px; border-radius:5px; font-weight:bold; }

.comment-box { text-align:left; background:#fff; border-radius:8px; padding:8px 10px; box-shadow:0 1px 3px rgba(0,0,0,0.1); }

</style>
</head>
<body>
<div class="container">
    <a href="dashboard.php" class="back-link">&larr; Back to Dashboard</a>
    <h2>Ratings Analytics</h2>

    <!-- Teacher Ratings -->
    <h3 class="section-title">👨‍🏫 Teacher Ratings Overview</h3>
    <div class="table-wrap">
        <table>
            <thead>
                <tr><th>Teacher</th><th>Average Rating</th><th>Total Reviews</th><th>Visual</th></tr>
            </thead>
            <tbody>
            <?php if (empty($teacher_avg)): ?>
                <tr><td colspan="4" class="no-data">No teacher ratings yet.</td></tr>
            <?php else: ?>
                <?php foreach($teacher_avg as $t): ?>
                    <tr>
                        <td><?= htmlspecialchars($t['teacher_name']) ?></td>
                        <td>
                            <span class="rating-badge"><?= $t['avg_rating'] ?></span>
                            <?= str_repeat('⭐', (int) round($t['avg_rating'])) ?>
                        </td>
                        <td><?= $t['total_reviews'] ?></td>
                        <td>
                            <div class="progress-bar">
                                <div class="progress-fill" style="width:<?= ($t['avg_rating']/5)*100 ?>%"></div>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Session Ratings -->
    <h3 class="section-title">📆 Session Ratings Overview</h3>
    <div class="table-wrap">
        <table>
            <thead>
                <tr><th>Session (Day / Time)</th><th>Average Rating</th><th>Total Reviews</th><th>Visual</th></tr>
            </thead>
            <tbody>
            <?php if (empty($session_avg)): ?>
                <tr><td colspan="4" class="no-data">No session ratings yet.</td></tr>
            <?php else: ?>
                <?php foreach($session_avg as $s): ?>
                    <tr>
                        <td><?= htmlspecialchars($s['day_of_week'] . ' - ' . $s['time_slot']) ?></td>
                        <td>
                            <span class="rating-badge"><?= $s['avg_rating'] ?></span>
                            <?= str_repeat('⭐', (int) round($s['avg_rating'])) ?>
                        </td>
                        <td><?= $s['total_reviews'] ?></td>
                        <td>
                            <div class="progress-bar">
                                <div class="progress-fill" style="width:<?= ($s['avg_rating']/5)*100 ?>%"></div>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Individual Ratings -->
    <h3 class="section-title">🧾 All Ratings & Comments</h3>
    <div class="table-wrap">
        <table>
            <thead>
                <tr><th>ID</th><th>Rated By</th><th>Rated User</th><th>Target Type</th><th>Rating</th><th>Comment</th><th>Date</th></tr>
            </thead>
            <tbody>
            <?php if (empty($ratings)): ?>
                <tr><td colspan="7" class="no-data">No ratings found.</td></tr>
            <?php else: ?>
                <?php foreach($ratings as $r): ?>
                    <tr>
                        <td><?= $r['rating_id'] ?></td>
                        <td><?= htmlspecialchars($r['rated_by'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($r['rated_user'] ?? '-') ?></td>
                        <td><?= htmlspecialchars(ucfirst($r['target_type'])) ?></td>
                        <td>
                            <span class="rating-badge"><?= $r['rating'] ?></span>
                            <?= str_repeat('⭐', (int)$r['rating']) ?>
                        </td>
                        <td><div class="comment-box"><?= htmlspecialchars($r['comment'] ?? '-') ?></div></td>
                        <td><?= htmlspecialchars($r['created_at']) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
