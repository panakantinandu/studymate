<?php
session_start();
require_once __DIR__ . '/../../config/pdo.php';
//include_once '../../includes/header_admin.php';
include_once '../../includes/navbar_admin.php';

// --- ACCESS CONTROL ---
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../index.php");
    exit;
}

// --- SUMMARY STATISTICS ---
$totalUsers      = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalStudents   = $pdo->query("SELECT COUNT(*) FROM users WHERE role='student'")->fetchColumn();
$totalTeachers   = $pdo->query("SELECT COUNT(*) FROM users WHERE role='teacher'")->fetchColumn();
$totalCourses    = $pdo->query("SELECT COUNT(*) FROM courses")->fetchColumn();
$totalSubjects   = $pdo->query("SELECT COUNT(*) FROM subjects")->fetchColumn();
$totalSessions   = $pdo->query("SELECT COUNT(*) FROM session_requests")->fetchColumn();
$totalRatings    = $pdo->query("SELECT COUNT(*) FROM ratings")->fetchColumn();
$totalTickets    = $pdo->query("SELECT COUNT(*) FROM feedback_tickets")->fetchColumn();
$totalQuizzes    = $pdo->query("SELECT COUNT(*) FROM quizzes")->fetchColumn();

// --- TOP 5 RATED COURSES ---
$topCourses = $pdo->query("
    SELECT c.title, ROUND(AVG(r.rating),2) AS avg_rating, COUNT(r.rating_id) AS total_reviews
    FROM courses c
    LEFT JOIN ratings r ON r.target_type='course' AND r.target_id=c.id
    GROUP BY c.id
    ORDER BY avg_rating DESC
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

// --- RECENT FEEDBACK TICKETS ---
$recentTickets = $pdo->query("
    SELECT t.id, u.username, t.title, t.category, t.status, t.created_at
    FROM feedback_tickets t
    JOIN users u ON u.user_id = t.user_id
    ORDER BY t.created_at DESC
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

// --- RECENT SESSION REQUESTS ---
$recentSessions = $pdo->query("
    SELECT 
        sr.request_id,
        st.username AS requester,
        t.username AS receiver,
        s.subject_name AS subject,
        sr.day_of_week,
        sr.time_slot,
        sr.status,
        sr.created_at
    FROM session_requests sr
    JOIN users st ON sr.requester_id = st.user_id
    JOIN users t  ON sr.receiver_id = t.user_id
    LEFT JOIN subjects s ON sr.subject_id = s.subject_id
    ORDER BY sr.created_at DESC
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Dashboard</title>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
    body {
        font-family: 'Segoe UI', sans-serif;
        background-color: #7ace99ff;
        margin: 0;
        padding: 0;
    }
    
    .container {
        margin: 30px auto;
        width: 85%;
        background: #cceed0ff;
        border-radius: 12px;
        box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        padding: 30px;
    }
    .main-content {
        margin-left: 220px; /* Width of the sidebar */
        padding: 20px;
        background-color: #7ace99ff;
    }
    h2 {
        text-align: center;
        color: #f21010ff;
        font-size: 50px;
        margin-bottom: 25px;
    }
    h4 { color: #210101ff; margin-top: 30px; }
    table {
        width: 100%;
        border-collapse: collapse;
        text-align: center;
        margin-top: 10px;
    }
    th, td {
        padding: 12px;
        border: 1px solid #310303ff;
    }
    th {
        background-color: #e50a0aff;
        color: #0b0b0bff;
        font-size: 20px;
        text-transform: uppercase;
    }
    tr:hover { background-color: #ea7676ff; }
    td { font-size: 14px; color: #210101ff; }
    .summary-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit,minmax(150px,1fr));
        gap: 15px;
        text-align: center;
    }
    .stat-box {
        background: #f9f9f9;
        padding: 15px;
        border-radius: 8px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }
    .stat-box h3 { margin: 0; color: #e50a0a; }
    .stat-box p { margin: 5px 0 0; font-size: 14px; color: #444; }
    canvas { margin-top: 25px; }
    .no-data {
        text-align: center;
        color: #1b1a1a;
        padding: 20px;
    }
</style>
</head>
<body>
<div class="container">
    <h2>Admin Dashboard</h2>
    <p style="font-size: 22px; font-weight: bold;">Welcome, <?= htmlspecialchars($_SESSION['username']) ?> 👋</p>
     

    <!-- Quick Actions -->
<div style="margin: 18px 0; display:grid; grid-template-columns: repeat(auto-fit,minmax(220px,1fr)); gap:10px;">
  <a href="/views/admin/manage_courses.php" style="text-decoration:none;">
    <div class="stat-box"><h3>📚</h3><p>Manage Courses</p></div>
  </a>
  <a href="/views/admin/manage_users.php" style="text-decoration:none;">
    <div class="stat-box"><h3>👥</h3><p>Manage Users</p></div>
  </a>
  <a href="/views/admin/feedback_manage.php" style="text-decoration:none;">
    <div class="stat-box"><h3>🎫</h3><p>Feedback Tickets</p></div>
  </a>
  <a href="/views/admin/ratings.php" style="text-decoration:none;">
    <div class="stat-box"><h3>⭐</h3><p>Ratings Analytics</p></div>
  </a>
  <a href="/views/admin/session_requests.php" style="text-decoration:none;">
    <div class="stat-box"><h3>📆</h3><p>Sessions</p></div>
  </a>
</div>



    <!-- Summary Statistics -->
    <div class="summary-grid">
        <div class="stat-box"><h3><?= $totalUsers ?></h3><p>Total Users</p></div>
        <div class="stat-box"><h3><?= $totalStudents ?></h3><p>Students</p></div>
        <div class="stat-box"><h3><?= $totalTeachers ?></h3><p>Teachers</p></div>
        <div class="stat-box"><h3><?= $totalCourses ?></h3><p>Courses</p></div>
        <div class="stat-box"><h3><?= $totalSubjects ?></h3><p>Subjects</p></div>
        <div class="stat-box"><h3><?= $totalSessions ?></h3><p>Sessions</p></div>
        <div class="stat-box"><h3><?= $totalRatings ?></h3><p>Ratings</p></div>
        <div class="stat-box"><h3><?= $totalTickets ?></h3><p>Feedback Tickets</p></div>
    </div>

    <!-- Chart Section -->
    <canvas id="courseChart"></canvas>

    <!-- Top Courses -->
    <h4>Top Rated Courses</h4>
    <?php if (count($topCourses)): ?>
        <table>
            <tr><th>Course</th><th>Average Rating</th><th>Total Reviews</th></tr>
            <?php foreach ($topCourses as $c): ?>
                <tr>
                    <td><?= htmlspecialchars($c['title']) ?></td>
                    <td><?= $c['avg_rating'] ?: 'N/A' ?></td>
                    <td><?= $c['total_reviews'] ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php else: ?>
        <p class="no-data">No course ratings yet.</p>
    <?php endif; ?>

    <!-- Recent Feedback -->
    <h4>Recent Feedback Tickets</h4>
    <?php if (count($recentTickets)): ?>
        <table>
            <tr><th>User</th><th>Title</th><th>Category</th><th>Status</th><th>Created</th></tr>
            <?php foreach ($recentTickets as $t): ?>
                <tr>
                    <td><?= htmlspecialchars($t['username']) ?></td>
                    <td><?= htmlspecialchars($t['title']) ?></td>
                    <td><?= htmlspecialchars($t['category']) ?></td>
                    <td><?= htmlspecialchars($t['status']) ?></td>
                    <td><?= $t['created_at'] ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php else: ?>
        <p class="no-data">No feedback tickets available.</p>
    <?php endif; ?>

    <!-- Recent Sessions -->
    <h4>Recent Session Requests</h4>
    <?php if (count($recentSessions)): ?>
        <table>
            <tr><th>ID</th><th>Requester</th><th>Receiver</th><th>Subject</th><th>Day</th><th>Time</th><th>Status</th><th>Created</th></tr>
            <?php foreach ($recentSessions as $s): ?>
                <tr>
                    <td><?= $s['request_id'] ?></td>
                    <td><?= htmlspecialchars($s['requester']) ?></td>
                    <td><?= htmlspecialchars($s['receiver']) ?></td>
                    <td><?= htmlspecialchars($s['subject']) ?></td>
                    <td><?= htmlspecialchars($s['day_of_week']) ?></td>
                    <td><?= htmlspecialchars($s['time_slot']) ?></td>
                    <td><?= ucfirst($s['status']) ?></td>
                    <td><?= $s['created_at'] ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php else: ?>
        <p class="no-data">No session requests found.</p>
    <?php endif; ?>
</div>

<script>
// ChartJS - visualize courses and ratings
const ctx = document.getElementById('courseChart').getContext('2d');
const courseData = <?= json_encode(array_column($topCourses,'title')) ?>;
const ratingData = <?= json_encode(array_column($topCourses,'avg_rating')) ?>;
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: courseData,
        datasets: [{
            label: 'Average Rating',
            data: ratingData,
            borderWidth: 1,
            backgroundColor: 'rgba(229,10,10,0.7)',
            borderColor: '#310303ff'
        }]
    },
    options: {
        scales: { y: { beginAtZero: true, max: 5 } }
    }
});
</script>
</body>
</html>
