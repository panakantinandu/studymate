 <!-- <div class="sidebar">
    <h3>Admin Panel</h3>
    <a href="dashboard.php" class="<?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : '' ?>">Dashboard</a>
    <a href="manage_users.php" class="<?= basename($_SERVER['PHP_SELF']) == 'manage_users.php' ? 'active' : '' ?>">Manage Users</a>
    <a href="manage_subjects.php" class="<?= basename($_SERVER['PHP_SELF']) == 'manage_subjects.php' ? 'active' : '' ?>">Manage Subjects</a>
    <a href="session_requests.php" class="<?= basename($_SERVER['PHP_SELF']) == 'session_requests.php' ? 'active' : '' ?>">Session Requests</a>
    <a href="ratings.php" class="<?= basename($_SERVER['PHP_SELF']) == 'ratings.php' ? 'active' : '' ?>">Ratings</a>
    <a href="logout.php" class="logout-btn">Logout</a>
</div>

<div class="main-content"> 

 -->



 <?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../index.php");
    exit;
}
?>

<style>
    .sidebar {
        width: 250px;
        height: 100vh;
        position: fixed;
        background-color: #111010;
        color: white;
        padding-top: 25px;
        display: flex;
        flex-direction: column;
        align-items: flex-start;
    }

    .sidebar h3 {
        color: #ff6b6b;
        text-align: center;
        width: 100%;
        margin-bottom: 20px;
        font-size: 22px;
        letter-spacing: 1px;
    }

    .sidebar a {
        color: white;
        text-decoration: none;
        display: block;
        width: 100%;
        padding: 14px 0.5px;
        font-size: 16px;
        transition: all 0.3s ease;
    }

    .sidebar a:hover,
    .sidebar a.active {
        background-color: #f21010;
        color: #fff;
        font-weight: bold;
    }

    .logout-btn {
        margin-top: 25px;
        background-color: #e74c3c;
        color: white;
        font-weight: bold;
        text-align: center;
    }

    .logout-btn:hover {
        background-color: #c0392b;
    }

    .main-content {
        margin-left: 240px;
        padding: 20px;
        background-color: #cceed0ff;
        min-height: 100vh;
    }

    @media (max-width: 768px) {
        .sidebar {
            width: 100%;
            height: auto;
            flex-direction: row;
            justify-content: space-around;
            padding: 10px;
        }

        .main-content {
            margin-left: 0;
            padding: 15px;
        }
    }
</style>

<div class="sidebar">
    <h3>Admin Panel</h3>

    <a href="dashboard.php" class="<?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : '' ?>">🏠 Dashboard</a>

    <a href="manage_users.php" class="<?= basename($_SERVER['PHP_SELF']) == 'manage_users.php' ? 'active' : '' ?>">👥 Manage Users</a>

    <a href="manage_courses.php" class="<?= basename($_SERVER['PHP_SELF']) == 'manage_courses.php' ? 'active' : '' ?>">📚 Manage Courses</a>

    <!-- Optional: Keep if you want Subjects as Tags -->
    <a href="manage_subjects.php" class="<?= basename($_SERVER['PHP_SELF']) == 'manage_subjects.php' ? 'active' : '' ?>">🏷️ Subject Tags</a>

    <a href="session_requests.php" class="<?= basename($_SERVER['PHP_SELF']) == 'session_requests.php' ? 'active' : '' ?>">📆 Session Requests</a>

    <a href="ratings.php" class="<?= basename($_SERVER['PHP_SELF']) == 'ratings.php' ? 'active' : '' ?>">⭐ Ratings Analytics</a>

    <a href="feedback_manage.php" class="<?= basename($_SERVER['PHP_SELF']) == 'feedback_manage.php' ? 'active' : '' ?>">🎫 Feedback Tickets</a>

    <a href="../../logout1.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
</div>

<div class="main-content">
