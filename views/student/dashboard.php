<?php

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../config/pdo.php';

// ✅ Ensure student is logged in
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'student') {
    header("Location: ../index.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// ✅ Dashboard summary counts
$totalSubjects = $pdo->prepare("SELECT COUNT(*) FROM user_subjects WHERE user_id = ?");
$totalSubjects->execute([$user_id]);
$totalSubjects = $totalSubjects->fetchColumn();

$totalSessions = $pdo->prepare("SELECT COUNT(*) FROM session_requests WHERE requester_id = ?");
$totalSessions->execute([$user_id]);
$totalSessions = $totalSessions->fetchColumn();

$completedSessions = $pdo->prepare("SELECT COUNT(*) FROM session_requests WHERE requester_id = ? AND status = 'completed'");
$completedSessions->execute([$user_id]);
$completedSessions = $completedSessions->fetchColumn();

$avgRating = $pdo->prepare("SELECT ROUND(AVG(rating),1) FROM ratings WHERE rated_user_id = ?");
$avgRating->execute([$user_id]);
$avgRating = $avgRating->fetchColumn() ?: 0;

$unread = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
$unread->execute([$user_id]);
$unread = $unread->fetchColumn();

// 🔔 Upcoming session reminder (within next 30 minutes)
$reminderStmt = $pdo->prepare("
    SELECT sr.session_date, sr.time_slot, sr.meeting_link,
           u.username AS peer_name
    FROM session_requests sr
    JOIN users u 
      ON (u.user_id = CASE WHEN sr.requester_id = ? THEN sr.receiver_id ELSE sr.requester_id END)
    WHERE (sr.requester_id = ? OR sr.receiver_id = ?)
      AND sr.status = 'accepted'
      AND sr.session_date = CURDATE()
      AND TIMESTAMPDIFF(
            MINUTE,
            NOW(),
            STR_TO_DATE(CONCAT(sr.session_date, ' ', TRIM(SUBSTRING_INDEX(sr.time_slot, '-', 1))), '%Y-%m-%d %h:%i %p')
          ) BETWEEN 0 AND 30
");
$reminderStmt->execute([$user_id, $user_id, $user_id]);
$reminders = $reminderStmt->fetchAll(PDO::FETCH_ASSOC);

// ✅ Automatically mark past sessions as completed
$autoComplete = $pdo->prepare("
    UPDATE session_requests
    SET status = 'completed'
    WHERE status = 'accepted'
      AND session_date IS NOT NULL
      AND STR_TO_DATE(CONCAT(session_date, ' ', TRIM(SUBSTRING_INDEX(time_slot, '-', -1))), '%Y-%m-%d %h:%i %p') < NOW()
");
$autoComplete->execute();

// ✅ Recent sessions
$recentSessions = $pdo->prepare("
    SELECT sr.request_id, sr.session_date, sr.time_slot, sr.status, u.username AS peer_name
    FROM session_requests sr
    JOIN users u ON u.user_id = sr.receiver_id
    WHERE sr.requester_id = ?
    ORDER BY sr.created_at DESC
    LIMIT 3
");
$recentSessions->execute([$user_id]);
$recentSessions = $recentSessions->fetchAll(PDO::FETCH_ASSOC);

// ✅ Upcoming sessions
$upcomingSessions = $pdo->prepare("
    SELECT sr.session_date, sr.time_slot, sr.meeting_link, sr.status, u.username AS peer_name
    FROM session_requests sr
    JOIN users u ON u.user_id = sr.receiver_id
    WHERE sr.requester_id = ? AND sr.status = 'accepted'
    ORDER BY sr.session_date ASC
    LIMIT 3
");
$upcomingSessions->execute([$user_id]);
$upcomingSessions = $upcomingSessions->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>StudyMate - Student Dashboard</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<style>
body {
    margin: 0;
    font-family: 'Segoe UI', sans-serif;
    background: url('../../assests/images/sd2.png') no-repeat center center fixed;
    background-size: cover;
    color: #fff;
}
.bg-overlay {
    background: rgba(0, 0, 0, 0.25);
    backdrop-filter: blur(3px);
    position: fixed; inset: 0;
}
.top-nav {
    background: rgba(255, 255, 255, 0.15);
    backdrop-filter: blur(10px);
    padding: 10px 40px;
    display: flex; flex-wrap: wrap; gap: 20px; align-items: center;
}
.top-nav a { color: #fff; text-decoration: none; transition: .3s; }
.top-nav a:hover { color: #ffdd57; }
.dashboard-container {
    text-align: center;
    padding: 100px 20px;
    position: relative;
    z-index: 1;
}
h1 { font-size: 2.5rem; text-shadow: 2px 2px 10px #000; }
.cards {
    display: flex; flex-wrap: wrap; justify-content: center; gap: 20px; margin-top: 40px;
}
.card-box {
    background: rgba(255, 255, 255, 0.12);
    border-radius: 10px;
    padding: 20px;
    width: 200px;
    box-shadow: 0 5px 10px rgba(0,0,0,0.3);
}
.card-box i { font-size: 28px; color: #ffdd57; margin-bottom: 10px; }
.card-box h3 { font-size: 1.2rem; margin: 0; }
.btn-custom {
    margin-top: 30px;
    background: #ffdd57;
    border: none;
    padding: 12px 25px;
    border-radius: 6px;
    color: #000;
    font-weight: bold;
}
.dashboard-lists {
    display: flex; flex-wrap: wrap; justify-content: space-between;
    gap: 20px; margin: 60px auto; width: 85%;
}
.card-section {
    background: rgba(255, 255, 255, 0.08);
    border-radius: 10px;
    flex: 1;
    padding: 20px;
    min-width: 45%;
}
.section-header {
    font-weight: 600;
    border-bottom: 1px solid rgba(255,255,255,0.2);
    margin-bottom: 10px;
    color: #FFD43B;
}
.session-list { list-style: none; padding: 0; }
.session-list li {
    background: rgba(0,0,0,0.3);
    padding: 10px;
    border-radius: 6px;
    margin-bottom: 8px;
}
.status.accepted { color: #4caf50; }
.status.pending { color: #ff9800; }
.status.completed { color: #2196f3; }
.empty-text { text-align: center; color: #bbb; margin-top: 50px; }
.logout-btn {
    margin-left: auto;
    background-color: #e74c3c;
    color: white;
    font-weight: bold;
    text-align: center;
    padding: 8px 15px;
    border-radius: 5px;
    text-decoration: none;
}
.badge.bg-secondary {
    background-color: #6c757d !important;
    color: #fff;
    font-size: 0.8rem;
    margin-left: 5px;
}
</style>
</head>
<body>
<div class="bg-overlay"></div>

<!-- ✅ Navigation -->
<div class="top-nav">
    <a href="availability.php"><i class="fas fa-calendar-check"></i> Availability</a>
    <a href="messages.php"><i class="fas fa-envelope"></i> Messages</a>
    <a href="notifications.php"><i class="fas fa-bell"></i> Notifications (<?= $unread ?>)</a>
    <a href="profile.php"><i class="fas fa-user"></i> Profile</a>
    <a href="ratings.php"><i class="fas fa-star"></i> Ratings</a>
    <a href="session_requests.php"><i class="fas fa-handshake"></i> Sessions</a>
    <a href="../../logout1.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
</div>

<!-- ✅ Dashboard -->
<div class="dashboard-container">
    <h1>Welcome, <?= htmlspecialchars($_SESSION['username']) ?> 👋</h1>
    <p>Your personalized study dashboard</p>

    <!-- 🔔 Reminder -->
    <?php if (!empty($reminders)): ?>
        <div class="alert alert-warning text-start mx-auto" style="max-width:800px;">
            <i class="fas fa-bell me-2"></i>
            <strong>Upcoming Session!</strong><br>
            <?php foreach ($reminders as $r): ?>
                You have a session with <strong><?= htmlspecialchars($r['peer_name']) ?></strong>
                at <?= htmlspecialchars($r['time_slot']) ?> today.
                <?php if (!empty($r['meeting_link'])): ?>
                    <a href="<?= htmlspecialchars($r['meeting_link']) ?>" target="_blank" class="btn btn-sm btn-success ms-2">
                        <i class="fas fa-video"></i> Join
                    </a>
                <?php endif; ?>
                <br>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="cards">
        <div class="card-box"><i class="fas fa-book"></i><h3>Subjects</h3><p><?= $totalSubjects ?></p></div>
        <div class="card-box"><i class="fas fa-handshake"></i><h3>Sessions</h3><p><?= $totalSessions ?></p></div>
        <div class="card-box"><i class="fas fa-check-circle"></i><h3>Completed</h3><p><?= $completedSessions ?></p></div>
        <div class="card-box"><i class="fas fa-star"></i><h3>Avg Rating</h3><p><?= $avgRating ?>/5</p></div>
        <div class="card-box"><i class="fas fa-bell"></i><h3>Unread</h3><p><?= $unread ?></p></div>
    </div>

    <a href="session_requests.php" class="btn-custom"><i class="fas fa-plus"></i> Request New Session</a>

    <!-- ✅ Activity Section -->
    <div class="dashboard-lists">
        <!-- Recent Activity -->
        <div class="card-section">
            <div class="section-header"><i class="fas fa-clock"></i> Recent Activity</div>
            <div class="section-content">
                <?php if (empty($recentSessions)): ?>
                    <p class="empty-text">No recent activity yet.</p>
                <?php else: ?>
                    <ul class="session-list">
                        <?php foreach ($recentSessions as $session): ?>
                            <li>
                                <strong><?= htmlspecialchars($session['peer_name']) ?></strong>
                                on <?= date("F j, Y", strtotime($session['session_date'])) ?>
                                at <?= htmlspecialchars($session['time_slot']) ?>
                                — <span class="status <?= strtolower($session['status']) ?>"><?= ucfirst($session['status']) ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>

        <!-- Upcoming Sessions -->
        <div class="card-section">
            <div class="section-header"><i class="fas fa-calendar-day"></i> Upcoming Sessions</div>
            <div class="section-content">
                <?php if (empty($upcomingSessions)): ?>
                    <p class="empty-text">No upcoming sessions scheduled.</p>
                <?php else: ?>
                    <ul class="session-list">
                        <?php foreach ($upcomingSessions as $session): ?>
                            <li>
                                <strong><?= date("F j, Y", strtotime($session['session_date'])) ?></strong>
                                — <?= htmlspecialchars($session['time_slot']) ?> with
                                <strong><?= htmlspecialchars($session['peer_name']) ?></strong>
                                <span class="status <?= strtolower($session['status']) ?>">(<?= ucfirst($session['status']) ?>)</span>

                                <?php
                                // 🕓 Show 'Session Over' badge if the scheduled time has passed
                                if ($session['status'] === 'accepted' && !empty($session['session_date'])) {
                                    $endTime = explode('-', $session['time_slot'])[1] ?? '';
                                    $endTimestamp = strtotime($session['session_date'] . ' ' . trim($endTime));
                                    if ($endTimestamp < time()) {
                                        echo "<span class='badge bg-secondary ms-2'>Session Over</span>";
                                    }
                                }
                                ?>

                                <?php if (!empty($session['meeting_link'])): ?>
                                    <a href="<?= htmlspecialchars($session['meeting_link']) ?>" target="_blank" class="btn btn-success btn-sm mt-2">
                                        <i class="fas fa-video"></i> Join
                                    </a>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
</body>
</html>
