<?php
session_start();
require_once __DIR__ . '/../../config/pdo.php';
//include __DIR__ . '/../../includes/header_admin.php';

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../index.php");
    exit;
}
// ✅ Handle Update Request
if (isset($_POST['update_user'])) {
    $id = $_POST['user_id'];
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $role = $_POST['role'];

    if (!empty($id) && !empty($username) && !empty($email)) {
        $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, role = ? WHERE user_id = ?");
        $stmt->execute([$username, $email, $role, $id]);
    }
}

// ✅ Fetch users (oldest to newest)
$users = $pdo->query("
    SELECT user_id, username, email, role, created_at 
    FROM users 
    ORDER BY created_at ASC
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Users</title>
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

        h2 {
            text-align: center;
            color: #f21010ff;
            font-size: 50px;
            margin-bottom: 25px;
        }
        /* --- NEW STYLE FOR BACK LINK --- */
         .back-link {
             display: inline-block;
             margin-bottom: 20px;
             padding: 8px 15px;
             /* Matches the table header background color */
             background-color: #e50a0aff; 
             color: #ffffff; /* White text for contrast */
             text-decoration: none;
             border-radius: 5px;
             font-weight: bold;
             transition: background-color 0.2s;
         }

         .back-link:hover {
             /* Darker shade on hover */
             background-color: #c40808ff; 
         }
         /* --- END NEW STYLE --- */


        table {
            width: 100%;
            border-collapse: collapse;
            text-align: center;
            margin-top: 10px;
        }

        thead {
            background-color: #04ec33ff;
            color: #07e11dff;
        }

        th, td {
            padding: 12px;
        
            border-bottom: 1px solid #310303ff;
            border-left: 1px solid #310303ff;
            border-right: 1px solid #310303ff;
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

        tr:hover {
            background-color: #ea7676ff;
        }

        

        td {
            font-size: 14px;
            color: #210101ff;
        }

        .no-data {
            text-align: center;
            color: #1b1a1aff;
            padding: 20px;
        }

        /* Optional subtle animation */
        tbody tr {
            transition: background-color 0.2s ease;
        }

        @media (max-width: 768px) {
            .container {
                width: 95%;
                padding: 20px;
            }

            table, thead, tbody, th, td, tr {
                display: block;
            }

            thead {
                display: none;
            }

            tr {
                background: #111010ff;
                margin-bottom: 15px;
                border-radius: 8px;
                box-shadow: 0 2px 6px rgba(0,0,0,0.05);
                padding: 10px;
            }

            td {
                text-align: right;
                padding-left: 50%;
                position: relative;
            }

            td::before {
                content: attr(data-label);
                position: absolute;
                left: 10px;
                width: 45%;
                text-align: left;
                font-weight: bold;
                color: #96d812ff;
            }
        }
    </style>
</head>
<body>
    
    <div class="container">
         <a href="dashboard.php" class="back-link">
    &larr; Back to Dashboard
    </a>
        <h2>Manage Users</h2>

        <table>
            <thead>
                <tr>
                    <th>User ID</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Joined</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($users)): ?>
                    <tr><td colspan="5" class="no-data">No users found.</td></tr>
                <?php else: ?>
                    <?php foreach ($users as $u): ?>
                        <tr>
                            <td data-label="User ID"><?= $u['user_id'] ?></td>
                            <td data-label="Username"><?= htmlspecialchars($u['username']) ?></td>
                            <td data-label="Email"><?= htmlspecialchars($u['email']) ?></td>
                            <td data-label="Role"><?= htmlspecialchars($u['role']) ?></td>
                            <td data-label="Joined"><?= htmlspecialchars($u['created_at']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
