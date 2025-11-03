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

// ✅ Add new subject
if (isset($_POST['add_subject'])) {
    $name = trim($_POST['subject_name']);
    if (!empty($name)) {
        $stmt = $pdo->prepare("INSERT INTO subjects (subject_name) VALUES (?)");
        $stmt->execute([$name]);
        $message = "✅ Subject added successfully.";
    }
}

// ✅ Delete subject
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM subjects WHERE subject_id = ?");
    $stmt->execute([$id]);
    $pdo->exec("ALTER TABLE subjects AUTO_INCREMENT = 1"); // Optional
    $message = "❌ Subject deleted successfully.";
}

// ✅ Update subject
if (isset($_POST['update_subject'])) {
    $id = $_POST['subject_id'];
    $name = trim($_POST['subject_name']);
    if (!empty($name)) {
        $stmt = $pdo->prepare("UPDATE subjects SET subject_name = ? WHERE subject_id = ?");
        $stmt->execute([$name, $id]);
        // ✅ After update, go back to read-only mode
        header("Location: manage_subjects.php?updated=1");
        exit;
    }
}

// ✅ Fetch subjects
$subjects = $pdo->query("SELECT * FROM subjects ORDER BY subject_id ASC")->fetchAll(PDO::FETCH_ASSOC);

// ✅ Handle success message from redirect
if (isset($_GET['updated'])) {
    $message = "✅ Subject updated successfully.";
}

// ✅ Determine which subject is being edited
$edit_id = isset($_GET['edit']) ? $_GET['edit'] : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Subjects</title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background-color: #7ace99;
            margin: 0;
            padding: 0;
        }

        .container {
            margin: 30px auto;
            width: 85%;
            background: #cceed0;
            border-radius: 12px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            padding: 30px;
        }

        h2 {
            text-align: center;
            color: #f21010;
            font-size: 45px;
            margin-bottom: 25px;
        }

        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            padding: 8px 15px;
            background-color: #e50a0a;
            color: #ffffff;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            transition: background-color 0.2s;
        }

        .back-link:hover { background-color: #c40808; }

        .message {
            background-color: #08a112;
            color: #fff;
            text-align: center;
            padding: 10px;
            border-radius: 6px;
            margin-bottom: 20px;
            display: <?= $message ? 'block' : 'none' ?>;
        }

        form.add-form {
            display: flex;
            justify-content: center;
            margin-bottom: 25px;
        }

        form.add-form input[type="text"] {
            width: 300px;
            padding: 8px;
            border-radius: 6px;
            border: 1px solid #aaa;
            margin-right: 10px;
        }

        form.add-form button {
            background-color: #08a112;
            color: white;
            border: none;
            border-radius: 6px;
            padding: 8px 15px;
            cursor: pointer;
            font-weight: bold;
        }

        form.add-form button:hover { background-color: #067d0f; }

        table {
            width: 100%;
            border-collapse: collapse;
            text-align: center;
        }

        thead {
            background-color: #e50a0a;
            color: white;
        }

        th, td {
            padding: 12px;
            border: 1px solid #310303;
        }
        th {
            /* This is the key change: sets the background for the column name cells */
            background-color: #e50a0aff; /* Light gray background to match the image */
            font-size: 20px;
            text-transform: uppercase;
            color: #0b0b0bff; /* Dark gray text color for better readability */
            letter-spacing: 0.5px;
            /* Optional: Add a top border to frame the header */
            border-top: 1px solid #310303ff;
        }

        tr:hover { background-color: #ea7676; }

        .action-btn {
            padding: 5px 12px;
            border-radius: 5px;
            border: none;
            cursor: pointer;
            color: white;
            font-size: 14px;
            text-decoration: none;
        }

        .edit-btn { background-color: #3498db; }
        .edit-btn:hover { background-color: #2176b5; }

        .update-btn { background-color: #08a112; }
        .update-btn:hover { background-color: #067d0f; }

        .cancel-btn { background-color: #777; }
        .cancel-btn:hover { background-color: #555; }

        .delete-btn { background-color: #d91a1a; }
        .delete-btn:hover { background-color: #b01515; }

        td input[type="text"] {
            width: 90%;
            padding: 5px;
            border-radius: 5px;
            border: 1px solid #999;
        }

        .no-data {
            text-align: center;
            padding: 15px;
            color: #444;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="dashboard.php" class="back-link">&larr; Back to Dashboard</a>
        <h2>Manage Subjects</h2>

        <?php if ($message): ?>
            <div class="message"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <!-- Add Subject Form -->
        <form method="post" class="add-form">
            <input type="text" name="subject_name" placeholder="Enter subject name" required>
            <button type="submit" name="add_subject">Add Subject</button>
        </form>

        <!-- Subjects Table -->
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Subject Name</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($subjects)): ?>
                    <tr><td colspan="3" class="no-data">No subjects found.</td></tr>
                <?php else: ?>
                    <?php foreach ($subjects as $s): ?>
                        <tr>
                            <?php if ($edit_id == $s['subject_id']): ?>
                                <form method="post">
                                    <td><?= $s['subject_id'] ?></td>
                                    <td>
                                        <input type="text" name="subject_name" value="<?= htmlspecialchars($s['subject_name']) ?>" required>
                                    </td>
                                    <td>
                                        <input type="hidden" name="subject_id" value="<?= $s['subject_id'] ?>">
                                        <button type="submit" name="update_subject" class="action-btn update-btn">Save</button>
                                        <a href="manage_subjects.php" class="action-btn cancel-btn">Cancel</a>
                                    </td>
                                </form>
                            <?php else: ?>
                                <td><?= $s['subject_id'] ?></td>
                                <td><?= htmlspecialchars($s['subject_name']) ?></td>
                                <td>
                                    <a href="?edit=<?= $s['subject_id'] ?>" class="action-btn edit-btn">Edit</a>
                                    <a href="?delete=<?= $s['subject_id'] ?>" onclick="return confirm('Delete this subject?')" class="action-btn delete-btn">Delete</a>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <script>
        // Auto-hide success message after 3 seconds
        setTimeout(() => {
            const msg = document.querySelector('.message');
            if (msg) msg.style.display = 'none';
        }, 3000);
    </script>
</body>
</html>
