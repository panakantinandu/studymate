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
$success = "";

// ✅ Automatically mark past sessions as completed
$autoComplete = $pdo->prepare("
    UPDATE session_requests
    SET status = 'completed'
    WHERE status = 'accepted'
      AND session_date IS NOT NULL
      AND CONCAT(session_date, ' ', SUBSTRING_INDEX(time_slot, '-', -1)) < NOW()
");
$autoComplete->execute();

// ----------------------------------------
// Handle POST actions
// ----------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // 1️⃣ Create a new session request
    if (isset($_POST['receiver_id'], $_POST['subject_id'], $_POST['session_date'], $_POST['time_slot'])) {
        $stmt = $pdo->prepare("
            INSERT INTO session_requests (requester_id, receiver_id, subject_id, session_date, time_slot, status, created_at)
            VALUES (?, ?, ?, ?, ?, 'pending', NOW())
        ");
        $stmt->execute([$user_id, $_POST['receiver_id'], $_POST['subject_id'], $_POST['session_date'], $_POST['time_slot']]);

        // Notify receiver
        $receiver = intval($_POST['receiver_id']);
        $subjectId = intval($_POST['subject_id']);
        $subName = $pdo->query("SELECT subject_name FROM subjects WHERE subject_id = $subjectId")->fetchColumn();
        $sessionDate = date("F j, Y", strtotime($_POST['session_date']));
        $message = "$username requested a study session for $subName on $sessionDate at {$_POST['time_slot']}.";

        $pdo->prepare("INSERT INTO notifications (user_id, message, is_read, created_at) VALUES (?, ?, 0, NOW())")
            ->execute([$receiver, $message]);

        $success = "✅ Session request sent successfully!";
    }

    // 2️⃣ Cancel sent request
    if (isset($_POST['cancel_request_id'])) {
        $cancel_id = intval($_POST['cancel_request_id']);
        $pdo->prepare("DELETE FROM session_requests WHERE request_id = ? AND requester_id = ?")
            ->execute([$cancel_id, $user_id]);
        $success = "❌ Session request cancelled.";
    }

    // 3️⃣ Accept / Reject request
    if (isset($_POST['request_id'], $_POST['action'])) {
        $request_id = intval($_POST['request_id']);
        $action = $_POST['action'];

        if (in_array($action, ['accepted', 'rejected'])) {
            $meeting_link = null;
            if ($action === 'accepted') {
                $meeting_link = "https://meet.jit.si/StudyMate-" . substr(md5(uniqid()), 0, 8);
            }

            // Update request
            $stmt = $pdo->prepare("UPDATE session_requests SET status = ?, meeting_link = ? WHERE request_id = ? AND receiver_id = ?");
            $stmt->execute([$action, $meeting_link, $request_id, $user_id]);

            // Fetch requester info
            $fetch = $pdo->prepare("SELECT requester_id, session_date, time_slot, subject_id FROM session_requests WHERE request_id = ?");
            $fetch->execute([$request_id]);
            $req = $fetch->fetch(PDO::FETCH_ASSOC);

            if ($req) {
                $req_id = $req['requester_id'];
                $sessionDate = date("F j, Y", strtotime($req['session_date']));
                $msg = $action === 'accepted'
                    ? "✅ Your session request for $sessionDate at {$req['time_slot']} was accepted."
                    : "❌ Your session request for $sessionDate at {$req['time_slot']} was rejected.";

                if ($action === 'accepted' && $meeting_link) {
                    $msg .= " Join here: $meeting_link";
                }

                $notif = $pdo->prepare("INSERT INTO notifications (user_id, message, is_read, created_at) VALUES (?, ?, 0, NOW())");
                $notif->execute([$req_id, $msg]);
            }

            $success = ($action === 'accepted') ? "✅ Session accepted and meeting link created." : "❌ Session rejected.";
        }
    }

    // 4️⃣ Mark session as completed
    if (isset($_POST['complete_id'])) {
        $req_id = intval($_POST['complete_id']);

        $stmt = $pdo->prepare("SELECT requester_id, receiver_id, status FROM session_requests WHERE request_id = ?");
        $stmt->execute([$req_id]);
        $session = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($session && $session['status'] === 'accepted' && ($session['requester_id'] == $user_id || $session['receiver_id'] == $user_id)) {
            $pdo->prepare("UPDATE session_requests SET status = 'completed' WHERE request_id = ?")->execute([$req_id]);

            $other_user = ($session['requester_id'] == $user_id) ? $session['receiver_id'] : $session['requester_id'];
            $pdo->prepare("INSERT INTO notifications (user_id, message, is_read, created_at) VALUES (?, ?, 0, NOW())")
                ->execute([$other_user, "$username marked the session as completed."]);

            $success = "✅ Session marked as completed.";
        } else {
            $success = "⚠️ Unable to mark session as completed.";
        }
    }
}

// ----------------------------------------
// Fetch dropdown data
// ----------------------------------------
$peers = $pdo->query("SELECT user_id, username FROM users WHERE role IN ('student', 'teacher') AND user_id != $user_id")->fetchAll();
$subjects = $pdo->query("SELECT * FROM subjects ORDER BY subject_name ASC")->fetchAll();

// ----------------------------------------
// Fetch sent & received requests
// ----------------------------------------
$sent = $pdo->prepare("
    SELECT sr.request_id, sr.session_date, sr.time_slot, sr.status, sr.meeting_link,
           u.username AS peer_name, s.subject_name
    FROM session_requests sr
    JOIN users u ON sr.receiver_id = u.user_id
    LEFT JOIN subjects s ON s.subject_id = sr.subject_id
    WHERE sr.requester_id = ?
    ORDER BY sr.created_at DESC
");
$sent->execute([$user_id]);
$sent = $sent->fetchAll(PDO::FETCH_ASSOC);

$received = $pdo->prepare("
    SELECT sr.request_id, sr.session_date, sr.time_slot, sr.status, sr.meeting_link,
           u.username AS peer_name, s.subject_name
    FROM session_requests sr
    JOIN users u ON sr.requester_id = u.user_id
    LEFT JOIN subjects s ON s.subject_id = sr.subject_id
    WHERE sr.receiver_id = ?
    ORDER BY sr.created_at DESC
");
$received->execute([$user_id]);
$received = $received->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Session Requests</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background: #f4f7f6; font-family: 'Segoe UI', sans-serif; }
        .container {
            background: white; margin-top: 40px; padding: 30px; border-radius: 15px;
            box-shadow: 0 6px 15px rgba(0,0,0,0.1);
        }
        h2 { color: #2c3e50; text-align: center; font-weight: 700; }
        .status {
            padding: 5px 10px; border-radius: 8px; color: white; font-weight: 600;
        }
        .pending { background: #f1c40f; }
        .accepted { background: #27ae60; }
        .rejected { background: #e74c3c; }
        .completed { background: #17a2b8; }
        table td, th { vertical-align: middle !important; }
        .btn-sm i { margin-right: 5px; }
    </style>
</head>
<body>
<div class="container">
    <a href="dashboard.php" class="btn btn-outline-danger mb-3">
        <i class="fas fa-arrow-left"></i> Back to Dashboard
    </a>
    <h2><i class="fas fa-handshake"></i> Session Requests</h2>

    <?php if ($success): ?>
        <div class="alert alert-success text-center mt-3"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <!-- Create New Session -->
    <div class="border rounded p-4 mb-4 bg-light">
        <h5><i class="fas fa-plus-circle"></i> Request New Session</h5>
        <form method="post" class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Select Peer</label>
                <select name="receiver_id" class="form-select" required>
                    <option value="">Choose...</option>
                    <?php foreach ($peers as $p): ?>
                        <option value="<?= $p['user_id'] ?>"><?= htmlspecialchars($p['username']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Subject</label>
                <select name="subject_id" class="form-select" required>
                    <option value="">Select subject...</option>
                    <?php foreach ($subjects as $s): ?>
                        <option value="<?= $s['subject_id'] ?>"><?= htmlspecialchars($s['subject_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Session Date</label>
                <input type="date" name="session_date" class="form-control" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Time Slot</label>
                <input type="text" name="time_slot" class="form-control" placeholder="e.g. 4PM - 5PM" required>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-success w-100">
                    <i class="fas fa-paper-plane"></i> Send
                </button>
            </div>
        </form>
    </div>

    <!-- Tabs -->
    <ul class="nav nav-tabs" id="myTab">
        <li class="nav-item"><button class="nav-link active" id="sent-tab" data-bs-toggle="tab" data-bs-target="#sent">Sent</button></li>
        <li class="nav-item"><button class="nav-link" id="received-tab" data-bs-toggle="tab" data-bs-target="#received">Received</button></li>
    </ul>

    <div class="tab-content">
        <!-- Sent Tab -->
        <div class="tab-pane fade show active" id="sent">
            <?php if (empty($sent)): ?>
                <p class="text-center mt-3">You haven’t sent any session requests yet.</p>
            <?php else: ?>
                <table class="table table-striped table-hover mt-3">
                    <thead><tr><th>Peer</th><th>Subject</th><th>Date</th><th>Time</th><th>Status</th><th>Action</th></tr></thead>
                    <tbody>
                        <?php foreach ($sent as $r): ?>
                            <tr>
                                <td><?= htmlspecialchars($r['peer_name']) ?></td>
                                <td><?= htmlspecialchars($r['subject_name'] ?? '—') ?></td>
                                <td><?= date("F j, Y", strtotime($r['session_date'])) ?></td>
                                <td><?= htmlspecialchars($r['time_slot']) ?></td>
                                <td><span class="status <?= htmlspecialchars($r['status']) ?>"><?= ucfirst($r['status']) ?></span></td>
                                <td>
                                    <?php if ($r['status'] === 'pending'): ?>
                                        <form method="post" style="display:inline;">
                                            <input type="hidden" name="cancel_request_id" value="<?= $r['request_id'] ?>">
                                            <button type="submit" class="btn btn-warning btn-sm">
                                                <i class="fas fa-times"></i> Cancel
                                            </button>
                                        </form>

                                    <?php elseif ($r['status'] === 'accepted'): ?>
                                        <?php if (!empty($r['meeting_link'])): ?>
                                            <a href="<?= htmlspecialchars($r['meeting_link']) ?>" target="_blank" 
                                               class="btn btn-primary btn-sm me-1">
                                                <i class="fas fa-video"></i> Join
                                            </a>
                                        <?php endif; ?>
                                        <form method="post" style="display:inline;">
                                            <input type="hidden" name="complete_id" value="<?= $r['request_id'] ?>">
                                            <button type="submit" class="btn btn-success btn-sm">
                                                <i class="fas fa-check-circle"></i> Mark Completed
                                            </button>
                                        </form>

                                    <?php elseif ($r['status'] === 'completed'): ?>
                                        <span class="badge bg-success">Completed</span>

                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <!-- Received Tab -->
        <div class="tab-pane fade" id="received">
            <?php if (empty($received)): ?>
                <p class="text-center mt-3">No incoming requests right now.</p>
            <?php else: ?>
                <table class="table table-striped table-hover mt-3">
                    <thead><tr><th>Peer</th><th>Subject</th><th>Date</th><th>Time</th><th>Status</th><th>Action</th></tr></thead>
                    <tbody>
                        <?php foreach ($received as $r): ?>
                            <tr>
                                <td><?= htmlspecialchars($r['peer_name']) ?></td>
                                <td><?= htmlspecialchars($r['subject_name'] ?? '—') ?></td>
                                <td><?= date("F j, Y", strtotime($r['session_date'])) ?></td>
                                <td><?= htmlspecialchars($r['time_slot']) ?></td>
                                <td><span class="status <?= htmlspecialchars($r['status']) ?>"><?= ucfirst($r['status']) ?></span></td>
                                <td>
                                    <?php if ($r['status'] === 'pending'): ?>
                                        <form method="post" class="d-inline">
                                            <input type="hidden" name="request_id" value="<?= $r['request_id'] ?>">
                                            <input type="hidden" name="action" value="accepted">
                                            <button class="btn btn-success btn-sm">
                                                <i class="fas fa-check"></i> Accept
                                            </button>
                                        </form>
                                        <form method="post" class="d-inline">
                                            <input type="hidden" name="request_id" value="<?= $r['request_id'] ?>">
                                            <input type="hidden" name="action" value="rejected">
                                            <button class="btn btn-danger btn-sm">
                                                <i class="fas fa-times"></i> Reject
                                            </button>
                                        </form>

                                    <?php elseif ($r['status'] === 'accepted' && !empty($r['meeting_link'])): ?>
                                        <a href="<?= htmlspecialchars($r['meeting_link']) ?>" target="_blank" 
                                           class="btn btn-primary btn-sm">
                                            <i class="fas fa-video"></i> Join
                                        </a>

                                    <?php elseif ($r['status'] === 'completed'): ?>
                                        <span class="badge bg-success">Completed</span>

                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
