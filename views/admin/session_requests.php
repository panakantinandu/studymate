<?php
session_start();
require_once __DIR__ . '/../../config/pdo.php';
//include __DIR__ . '/../../includes/header_admin.php';

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../index.php");
    exit;
}

$sessions = $pdo->query("
    SELECT 
        sr.request_id,
        st.username AS requester,
        t.username AS receiver,
        sub.subject_name AS subject,
        sr.day_of_week,
        sr.time_slot,
        sr.status,
        sr.created_at
    FROM session_requests sr
    JOIN users st ON sr.requester_id = st.user_id
    JOIN users t ON sr.receiver_id = t.user_id
    JOIN subjects sub ON sr.subject_id = sub.subject_id
    ORDER BY sr.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

// NOTE: Your SQL query selects 'requester' and 'receiver', but your HTML
// loop expects 'student', 'tutor', 'session_id', and 'session_date'.
// I will adjust the loop to match the SQL output as closely as possible,
// assuming 'requester' is 'Student' and 'receiver' is 'Tutor'.

// I will create dummy values for 'session_id' and 'session_date' to make the HTML work.
// In a real application, you would need to adjust the SQL query to select these.
// For now, I'll map request_id to ID, and combine day_of_week and time_slot for Date.

// Reformat sessions data to match the HTML column names:
$formatted_sessions = [];
foreach ($sessions as $s) {
    $formatted_sessions[] = [
        'session_id'    => $s['request_id'], // Using request_id as the ID
        'student'       => $s['requester'],
        'tutor'         => $s['receiver'],
        'subject'       => $s['subject'],
        'session_date'  => $s['day_of_week'] . ' @ ' . $s['time_slot'], // Combine day & time
        'status'        => $s['status'],
    ];
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>All Tutoring Sessions</title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            /* Background color from your user page */
            background-color: #7ace99ff; 
            margin: 0;
            padding: 0;
        }

        .container {
            margin: 30px auto;
            width: 85%;
            /* Container background color from your user page */
            background: #cceed0ff; 
            border-radius: 12px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            padding: 30px;
        }

        h2 {
            text-align: center;
            /* Heading color from your user page */
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
            /* This was a problematic block in your original code. The colors were too light. 
               It is overridden by the th rule below anyway, so this block is mostly ignored. 
               Keeping it for fidelity to your style, but the th rule is what matters. */
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
            /* Header background from your user page */
            background-color: #e50a0aff; 
            
            font-size: 20px;
            text-transform: uppercase;
            
            /* Column Name Text Color - Dark/Black for clear visibility */
            color: #0b0b0bff; 
            
            letter-spacing: 0.5px;
            border-top: 1px solid #0f0e0eff;
        }

        tr:hover {
            background-color: #ea7676ff;
        }

        td {
            font-size: 14px;
            /* Row data color from your user page */
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

        /* --- Responsive Styles (Mobile View) --- */
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
                /* Row background color for mobile from your user page */
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
                /* Mobile label color from your user page */
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
        <h2>All Tutoring Sessions</h2>
        <table border="0" cellpadding="0">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Student</th>
                    <th>Tutor</th>
                    <th>Subject</th>
                    <th>Date</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($formatted_sessions)): ?>
                    <tr><td colspan="6" class="no-data">No sessions found.</td></tr>
                <?php else: ?>
                    <?php foreach ($formatted_sessions as $s): ?>
                        <tr>
                            <td data-label="ID"><?= $s['session_id'] ?></td>
                            <td data-label="Student"><?= htmlspecialchars($s['student']) ?></td>
                            <td data-label="Tutor"><?= htmlspecialchars($s['tutor']) ?></td>
                            <td data-label="Subject"><?= htmlspecialchars($s['subject']) ?></td>
                            <td data-label="Date"><?= htmlspecialchars($s['session_date']) ?></td>
                            <td data-label="Status"><?= htmlspecialchars($s['status']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>