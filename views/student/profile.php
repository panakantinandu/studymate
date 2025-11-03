<?php
session_start();
require_once __DIR__ . '/../../config/pdo.php';

// Ensure student logged in
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'student') {
    header("Location: ../../index.php");
    exit;
}

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) die("Error: user_id not found in session.");

// Fetch user info
$stmt = $pdo->prepare("SELECT user_id, username, email, role, created_at, profile_image FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$user) die("Error: User not found.");

$success = "";

// Fetch subjects & user_subjects
$subjects = $pdo->query("SELECT * FROM subjects ORDER BY subject_name ASC")->fetchAll(PDO::FETCH_ASSOC);
$user_subject_ids = [];
try {
    $stmt = $pdo->prepare("SELECT subject_id FROM user_subjects WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $user_subject_ids = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'subject_id');
} catch (Exception $e) {}

// Handle POST (profile or subjects)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Delete photo
    if (isset($_POST['delete_photo'])) {
        if ($user['profile_image']) {
            $file = __DIR__ . '/../../uploads/profile_images/' . $user['profile_image'];
            if (file_exists($file)) unlink($file);
        }
        $pdo->prepare("UPDATE users SET profile_image = NULL WHERE user_id = ?")->execute([$user_id]);
        $user['profile_image'] = null;
        $success = "🗑️ Profile photo deleted.";
    }

    // Save profile
    elseif (isset($_POST['email'])) {
        $email = trim($_POST['email']);
        $image = $user['profile_image'];

        if (!empty($_FILES['profile_image']['name'])) {
            $dir = "../../uploads/profile_images/";
            if (!is_dir($dir)) mkdir($dir, 0777, true);
            $filename = "user_" . $user_id . "_" . basename($_FILES['profile_image']['name']);
            $path = $dir . $filename;
            $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

            if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) {
                move_uploaded_file($_FILES["profile_image"]["tmp_name"], $path);
                $image = $filename;
            }
        }

        $pdo->prepare("UPDATE users SET email = ?, profile_image = ? WHERE user_id = ?")
            ->execute([$email, $image, $user_id]);
        $user['email'] = $email;
        $user['profile_image'] = $image;
        $success = "✅ Profile updated successfully!";
    }

    // Save subjects
    if (isset($_POST['save_subjects'])) {
        $selected = $_POST['subjects'] ?? [];
        $pdo->beginTransaction();
        $pdo->prepare("DELETE FROM user_subjects WHERE user_id = ?")->execute([$user_id]);
        $ins = $pdo->prepare("INSERT INTO user_subjects (user_id, subject_id) VALUES (?, ?)");
        foreach ($selected as $sid) $ins->execute([$user_id, intval($sid)]);
        $pdo->commit();
        $user_subject_ids = array_map('intval', $selected);
        $success = "📚 Subjects updated.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>My Profile</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<style>
body {
    background: #d9d9d9;
    font-family: 'Segoe UI', sans-serif;
}
.container {
     background: white;
    margin-top: 40px;
    padding: 30px;
    border-radius: 15px;
    max-width: 900px;
    box-shadow: 0 6px 15px rgba(0,0,0,0.1);
}
.profile-card {
    background: #fff;
    border-radius: 15px;
    box-shadow: 0 6px 20px rgba(0,0,0,0.1);
    padding: 30px;
}
.profile-img {
    width: 140px;
    height: 140px;
    object-fit: cover;
    border-radius: 50%;
    border: 4px solid #FFD43B;
}
.username {
    font-size: 1.5rem;
    font-weight: 700;
    color: #333;
}
.email {
    color: #555;
}
.section-title {
    font-size: 1.2rem;
    font-weight: 600;
    margin-top: 25px;
    color: #444;
}
.subject-tag {
    display: inline-block;
    background: #f8f9fa;
    border: 1px solid #ddd;
    padding: 6px 10px;
    border-radius: 8px;
    margin: 4px;
}
.btn-yellow {
    background: #FFD43B;
    color: #000;
    font-weight: 600;
    border: none;
}
.btn-yellow:hover {
    background: #ffca2c;
}
</style>
</head>
<body>
<div class="container">
    <a href="dashboard.php" class="btn btn-outline-danger mb-3"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>

    <div class="profile-card">
        <div class="text-center mb-4">
            <img src="<?= $user['profile_image'] ? "../../uploads/profile_images/" . htmlspecialchars($user['profile_image']) : "../../assets/images/default.png" ?>"
                 alt="Profile Image" class="profile-img mb-3">
            <h3 class="username"><?= htmlspecialchars($user['username']) ?></h3>
            <p class="email"><i class="fas fa-envelope"></i> <?= htmlspecialchars($user['email']) ?></p>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success text-center"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <!-- Profile Info -->
        <form method="post" enctype="multipart/form-data" id="profileForm">
            <div class="row">
                <div class="col-md-6">
                    <label class="form-label">Email</label>
                    <input type="email" class="form-control" name="email" value="<?= htmlspecialchars($user['email']) ?>" readonly required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Role</label>
                    <input type="text" class="form-control" value="<?= htmlspecialchars($user['role']) ?>" readonly>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Joined</label>
                    <input type="text" class="form-control" value="<?= date('M d, Y', strtotime($user['created_at'])) ?>" readonly>
                </div>
            </div>

            <div class="mt-3">
                <label class="form-label">Profile Photo</label>
                <input type="file" name="profile_image" id="profileImage" class="form-control" disabled>
            </div>

            <!-- Subjects -->
            <div class="section-title"><i class="fas fa-book"></i> Subjects</div>
            <div class="d-flex flex-wrap mt-2">
                <?php foreach ($subjects as $sub): ?>
                    <label class="subject-tag">
                        <input type="checkbox" name="subjects[]" value="<?= $sub['subject_id'] ?>"
                               <?= in_array($sub['subject_id'], $user_subject_ids) ? 'checked' : '' ?> disabled>
                        <?= htmlspecialchars($sub['subject_name']) ?>
                    </label>
                <?php endforeach; ?>
            </div>

            <div class="text-center mt-4">
                <button type="button" id="editBtn" class="btn btn-yellow"><i class="fas fa-edit"></i> Edit</button>
                <button type="submit" id="saveBtn" class="btn btn-success" style="display:none;"><i class="fas fa-save"></i> Save</button>
                <button type="button" id="cancelBtn" class="btn btn-secondary" style="display:none;"><i class="fas fa-xmark"></i> Cancel</button>
                <?php if ($user['profile_image']): ?>
                    <button type="submit" name="delete_photo" class="btn btn-danger"><i class="fas fa-trash"></i> Delete Photo</button>
                <?php endif; ?>
                <button type="submit" name="save_subjects" class="btn btn-info text-white"><i class="fas fa-bookmark"></i> Save Subjects</button>
            </div>
        </form>
    </div>
</div>

<script>
const editBtn = document.getElementById('editBtn');
const saveBtn = document.getElementById('saveBtn');
const cancelBtn = document.getElementById('cancelBtn');
const form = document.getElementById('profileForm');

editBtn.addEventListener('click', () => {
    form.querySelectorAll('input').forEach(el => {
        if (el.type !== 'hidden') el.removeAttribute('readonly');
    });
    form.querySelectorAll('input[type="file"], input[type="checkbox"]').forEach(el => el.disabled = false);
    editBtn.style.display = 'none';
    saveBtn.style.display = 'inline-block';
    cancelBtn.style.display = 'inline-block';
});

cancelBtn.addEventListener('click', () => {
    form.querySelectorAll('input').forEach(el => el.setAttribute('readonly', true));
    form.querySelectorAll('input[type="file"], input[type="checkbox"]').forEach(el => el.disabled = true);
    editBtn.style.display = 'inline-block';
    saveBtn.style.display = 'none';
    cancelBtn.style.display = 'none';
});
</script>
</body>
</html>
