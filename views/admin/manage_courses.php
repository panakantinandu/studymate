<?php
session_start();
require_once __DIR__ . '/../../config/pdo.php';
//include __DIR__ . '/../../includes/header_admin.php';

// ✅ Restrict access to admins only
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../index.php");
    exit;
}

$message = "";

// ✅ Fetch dropdown data
$subjects = $pdo->query("SELECT subject_id, subject_name FROM subjects ORDER BY subject_name")->fetchAll(PDO::FETCH_ASSOC);
$teachers = $pdo->query("SELECT user_id, username FROM users WHERE role='teacher' ORDER BY username")->fetchAll(PDO::FETCH_ASSOC);

// ✅ Add new course
if (isset($_POST['add_course'])) {
    $title = trim($_POST['course_title']);
    $description = trim($_POST['description']);
    $subject_id = $_POST['subject_id'] ?: null;
    $teacher_id = $_POST['teacher_id'] ?: null;

    if (!empty($title)) {
        $stmt = $pdo->prepare("INSERT INTO courses (title, description, subject_id, teacher_id) VALUES (?, ?, ?, ?)");
        $stmt->execute([$title, $description, $subject_id, $teacher_id]);
        $message = "✅ Course added successfully.";
    }
}

// ✅ Delete course
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM courses WHERE id = ?");
    $stmt->execute([$id]);
    $pdo->exec("ALTER TABLE courses AUTO_INCREMENT = 1");
    $message = "❌ Course deleted successfully.";
}

// ✅ Update course
if (isset($_POST['update_course'])) {
    $id = $_POST['course_id'];
    $title = trim($_POST['course_title']);
    $description = trim($_POST['description']);
    $subject_id = $_POST['subject_id'] ?: null;
    $teacher_id = $_POST['teacher_id'] ?: null;

    if (!empty($title)) {
        $stmt = $pdo->prepare("UPDATE courses SET title=?, description=?, subject_id=?, teacher_id=? WHERE id=?");
        $stmt->execute([$title, $description, $subject_id, $teacher_id, $id]);
        header("Location: manage_courses.php?updated=1");
        exit;
    }
}

// ✅ Fetch all courses
$courses = $pdo->query("
    SELECT c.*, s.subject_name, u.username AS teacher_name 
    FROM courses c
    LEFT JOIN subjects s ON c.subject_id = s.subject_id
    LEFT JOIN users u ON c.teacher_id = u.user_id
    ORDER BY c.id ASC
")->fetchAll(PDO::FETCH_ASSOC);

// ✅ Handle message after redirect
if (isset($_GET['updated'])) {
    $message = "✅ Course updated successfully.";
}

$edit_id = isset($_GET['edit']) ? $_GET['edit'] : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Courses</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background-color: #7ace99; margin: 0; padding: 0; }
        .container { margin: 30px auto; width: 85%; background: #cceed0; border-radius: 12px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); padding: 30px; }
        h2 { text-align: center; color: #f21010; font-size: 45px; margin-bottom: 25px; }
        .back-link { display: inline-block; margin-bottom: 20px; padding: 8px 15px; background-color: #e50a0a; color: #ffffff; text-decoration: none; border-radius: 5px; font-weight: bold; }
        .back-link:hover { background-color: #c40808; }
        .message { background-color: #08a112; color: #fff; text-align: center; padding: 10px; border-radius: 6px; margin-bottom: 20px; display: <?= $message ? 'block' : 'none' ?>; }
        form.add-form { display: flex; flex-wrap: wrap; gap: 10px; justify-content: center; margin-bottom: 25px; }
        form.add-form input[type="text"], form.add-form textarea, form.add-form select {
            padding: 8px; border-radius: 6px; border: 1px solid #aaa; width: 220px;
        }
        form.add-form button { background-color: #08a112; color: white; border: none; border-radius: 6px; padding: 8px 15px; cursor: pointer; font-weight: bold; }
        form.add-form button:hover { background-color: #067d0f; }
        table { width: 100%; border-collapse: collapse; text-align: center; }
        th, td { padding: 12px; border: 1px solid #310303; }
        th { background-color: #e50a0a; color: white; font-size: 18px; text-transform: uppercase; }
        tr:hover { background-color: #ea7676; }
        .action-btn { padding: 5px 12px; border-radius: 5px; border: none; cursor: pointer; color: white; font-size: 14px; text-decoration: none; }
        .edit-btn { background-color: #3498db; } .edit-btn:hover { background-color: #2176b5; }
        .update-btn { background-color: #08a112; } .update-btn:hover { background-color: #067d0f; }
        .cancel-btn { background-color: #777; } .cancel-btn:hover { background-color: #555; }
        .delete-btn { background-color: #d91a1a; } .delete-btn:hover { background-color: #b01515; }
        .no-data { text-align: center; padding: 15px; color: #444; }
    </style>
</head>
<body>
<div class="container">
    <a href="dashboard.php" class="back-link">&larr; Back to Dashboard</a>
    <h2>Manage Courses</h2>

    <?php if ($message): ?><div class="message"><?= htmlspecialchars($message) ?></div><?php endif; ?>

    <!-- Add Course Form -->
    <form method="post" class="add-form">
        <input type="text" name="course_title" placeholder="Course Title" required>
        <textarea name="description" placeholder="Course Description"></textarea>
        <select name="subject_id">
            <option value="">-- Subject (Optional) --</option>
            <?php foreach($subjects as $s): ?>
                <option value="<?= $s['subject_id'] ?>"><?= htmlspecialchars($s['subject_name']) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="teacher_id">
            <option value="">-- Assign Teacher (Optional) --</option>
            <?php foreach($teachers as $t): ?>
                <option value="<?= $t['user_id'] ?>"><?= htmlspecialchars($t['username']) ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" name="add_course">Add Course</button>
    </form>

    <!-- Courses Table -->
    <table>
        <thead>
            <tr><th>ID</th><th>Title</th><th>Description</th><th>Subject</th><th>Teacher</th><th>Actions</th></tr>
        </thead>
        <tbody>
        <?php if (empty($courses)): ?>
            <tr><td colspan="6" class="no-data">No courses found.</td></tr>
        <?php else: ?>
            <?php foreach ($courses as $c): ?>
                <tr>
                <?php if ($edit_id == $c['id']): ?>
                    <form method="post">
                        <td><?= $c['id'] ?></td>
                        <td><input type="text" name="course_title" value="<?= htmlspecialchars($c['title']) ?>" required></td>
                        <td><textarea name="description"><?= htmlspecialchars($c['description']) ?></textarea></td>
                        <td>
                            <select name="subject_id">
                                <option value="">-- None --</option>
                                <?php foreach($subjects as $s): ?>
                                    <option value="<?= $s['subject_id'] ?>" <?= ($c['subject_id']==$s['subject_id'])?'selected':'' ?>>
                                        <?= htmlspecialchars($s['subject_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td>
                            <select name="teacher_id">
                                <option value="">-- None --</option>
                                <?php foreach($teachers as $t): ?>
                                    <option value="<?= $t['user_id'] ?>" <?= ($c['teacher_id']==$t['user_id'])?'selected':'' ?>>
                                        <?= htmlspecialchars($t['username']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td>
                            <input type="hidden" name="course_id" value="<?= $c['id'] ?>">
                            <button type="submit" name="update_course" class="action-btn update-btn">Save</button>
                            <a href="manage_courses.php" class="action-btn cancel-btn">Cancel</a>
                        </td>
                    </form>
                <?php else: ?>
                    <td><?= $c['id'] ?></td>
                    <td><?= htmlspecialchars($c['title']) ?></td>
                    <td><?= htmlspecialchars($c['description']) ?></td>
                    <td><?= htmlspecialchars($c['subject_name'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($c['teacher_name'] ?? '-') ?></td>
                    <td>
                        <a href="?edit=<?= $c['id'] ?>" class="action-btn edit-btn">Edit</a>
                        <a href="?delete=<?= $c['id'] ?>" onclick="return confirm('Delete this course?')" class="action-btn delete-btn">Delete</a>
                    </td>
                <?php endif; ?>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
setTimeout(()=>{const msg=document.querySelector('.message'); if(msg) msg.style.display='none';},3000);
</script>
</body>
</html>
