<?php
session_start();
require_once __DIR__ . '/../../config/pdo.php';

// Ensure student logged in
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'student') {
    header("Location: ../../index.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$success = "";

// -------------------------------
// Handle Rating Submission
// -------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_rating'])) {
    $rated_user_id = intval($_POST['rated_user_id']);
    $session_request_id = intval($_POST['session_request_id']);
    $rating = intval($_POST['rating']);
    $comment = trim($_POST['comment']);

    // Insert new rating
    $stmt = $pdo->prepare("
        INSERT INTO ratings (user_id, rated_user_id, session_request_id, rating, comment, created_at, target_type, target_id)
        VALUES (?, ?, ?, ?, ?, NOW(), 'session', ?)
    ");
    $stmt->execute([$user_id, $rated_user_id, $session_request_id, $rating, $comment, $session_request_id]);

    // -------------------------------
    // Send Notification to Rated User
    // -------------------------------
    $msg = "⭐ $username rated you $rating star" . ($rating > 1 ? 's' : '') . " for your recent session.";
    $notif = $pdo->prepare("
        INSERT INTO notifications (user_id, message, is_read, created_at)
        VALUES (?, ?, 0, NOW())
    ");
    $notif->execute([$rated_user_id, $msg]);

    $success = "✅ Rating submitted successfully, and the user has been notified!";
}

// -------------------------------
// Fetch Completed Sessions for “Rate Peer” Modal
// -------------------------------
$stmt = $pdo->prepare("
    SELECT sr.request_id, sr.requester_id, sr.receiver_id, sr.day_of_week, sr.time_slot,
           u.username AS peer_name
    FROM session_requests sr
    JOIN users u ON (u.user_id = CASE WHEN sr.requester_id = ? THEN sr.receiver_id ELSE sr.requester_id END)
    WHERE (sr.requester_id = ? OR sr.receiver_id = ?)
      AND sr.status = 'completed'
      AND sr.request_id NOT IN (SELECT session_request_id FROM ratings WHERE user_id = ?)
");
$stmt->execute([$user_id, $user_id, $user_id, $user_id]);
$completed_sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// -------------------------------
// Fetch Ratings Given
// -------------------------------
$stmt = $pdo->prepare("
    SELECT r.rating_id, u.username AS rated_user, r.rating, r.comment, r.created_at, r.target_type
    FROM ratings r
    LEFT JOIN users u ON r.rated_user_id = u.user_id
    WHERE r.user_id = ?
    ORDER BY r.created_at DESC
");
$stmt->execute([$user_id]);
$ratings_given = $stmt->fetchAll(PDO::FETCH_ASSOC);

// -------------------------------
// Fetch Ratings Received
// -------------------------------
$stmt = $pdo->prepare("
    SELECT r.rating_id, u.username AS rater, r.rating, r.comment, r.created_at, r.target_type
    FROM ratings r
    LEFT JOIN users u ON r.user_id = u.user_id
    WHERE r.rated_user_id = ?
    ORDER BY r.created_at DESC
");
$stmt->execute([$user_id]);
$ratings_received = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>My Ratings</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<style>
body { background: #f4f7f6; font-family: 'Segoe UI', sans-serif; }
.container {
    background: #fff; border-radius: 15px;
    box-shadow: 0 6px 15px rgba(0,0,0,0.1);
    padding: 30px; margin-top: 40px; max-width: 1000px;
}
h2 { text-align: center; color: #2c3e50; font-weight: 700; margin-bottom: 20px; }
.nav-tabs .nav-link.active { background-color: #FFD43B; font-weight: 600; color: #000 !important; }
.star { color: #FFD43B; cursor: pointer; }
.star:hover, .star.selected { color: #ffb400; }
.table th { background: #FFD43B; color: #000; }
.comment-box { font-style: italic; color: #555; }
.modal-header { background: #FFD43B; }
</style>
</head>
<body>
<div class="container">
    <a href="dashboard.php" class="btn btn-outline-danger mb-3"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
    <h2><i class="fas fa-star"></i> My Ratings</h2>

    <?php if ($success): ?>
        <div class="alert alert-success text-center"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <!-- Give Rating Button -->
    <div class="text-end mb-3">
        <?php if (!empty($completed_sessions)): ?>
            <button class="btn btn-warning fw-bold" data-bs-toggle="modal" data-bs-target="#rateModal">
                <i class="fas fa-pen"></i> Give Rating
            </button>
        <?php else: ?>
            <button class="btn btn-secondary" disabled>No completed sessions to rate</button>
        <?php endif; ?>
    </div>

    <ul class="nav nav-tabs" id="ratingTabs">
        <li class="nav-item">
            <button class="nav-link active" id="given-tab" data-bs-toggle="tab" data-bs-target="#given">Ratings I Gave</button>
        </li>
        <li class="nav-item">
            <button class="nav-link" id="received-tab" data-bs-toggle="tab" data-bs-target="#received">Ratings I Received</button>
        </li>
    </ul>

    <div class="tab-content mt-4">
        <!-- Ratings Given -->
        <div class="tab-pane fade show active" id="given">
            <?php if (empty($ratings_given)): ?>
                <p class="text-center text-muted">You haven’t rated anyone yet.</p>
            <?php else: ?>
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Rated User</th>
                            <th>Rating</th>
                            <th>Comment</th>
                            <th>Target</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($ratings_given as $r): ?>
                        <tr>
                            <td><?= htmlspecialchars($r['rated_user'] ?? 'Unknown') ?></td>
                            <td>
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <i class="fa-star <?= $i <= $r['rating'] ? 'fas star' : 'far star' ?>"></i>
                                <?php endfor; ?>
                            </td>
                            <td class="comment-box"><?= htmlspecialchars($r['comment'] ?? '—') ?></td>
                            <td><?= htmlspecialchars(ucfirst($r['target_type'] ?? 'Session')) ?></td>
                            <td><?= htmlspecialchars(date('M d, Y', strtotime($r['created_at']))) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <!-- Ratings Received -->
        <div class="tab-pane fade" id="received">
            <?php if (empty($ratings_received)): ?>
                <p class="text-center text-muted">You haven’t received any ratings yet.</p>
            <?php else: ?>
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Rater</th>
                            <th>Rating</th>
                            <th>Comment</th>
                            <th>Target</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($ratings_received as $r): ?>
                        <tr>
                            <td><?= htmlspecialchars($r['rater'] ?? 'Unknown') ?></td>
                            <td>
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <i class="fa-star <?= $i <= $r['rating'] ? 'fas star' : 'far star' ?>"></i>
                                <?php endfor; ?>
                            </td>
                            <td class="comment-box"><?= htmlspecialchars($r['comment'] ?? '—') ?></td>
                            <td><?= htmlspecialchars(ucfirst($r['target_type'] ?? 'Session')) ?></td>
                            <td><?= htmlspecialchars(date('M d, Y', strtotime($r['created_at']))) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Rating Modal -->
<div class="modal fade" id="rateModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form method="POST">
        <div class="modal-header">
          <h5 class="modal-title"><i class="fas fa-pen"></i> Give a Rating</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
            <div class="mb-3">
                <label class="form-label fw-bold">Select Completed Session</label>
                <select name="session_request_id" id="sessionSelect" class="form-select" required>
                    <option value="">-- Select Session --</option>
                    <?php foreach ($completed_sessions as $s): ?>
                        <option value="<?= $s['request_id'] ?>" data-peer="<?= $s['peer_name'] ?>" data-peerid="<?= ($s['requester_id'] == $user_id) ? $s['receiver_id'] : $s['requester_id'] ?>">
                            <?= htmlspecialchars($s['peer_name']) ?> (<?= htmlspecialchars($s['day_of_week']) ?> at <?= htmlspecialchars($s['time_slot']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                <input type="hidden" name="rated_user_id" id="ratedUserId">
            </div>
            <div class="mb-3 text-center">
                <label class="form-label fw-bold d-block">Rating</label>
                <div id="starContainer">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <i class="far fa-star star" data-value="<?= $i ?>"></i>
                    <?php endfor; ?>
                </div>
                <input type="hidden" name="rating" id="ratingInput" required>
            </div>
            <div class="mb-3">
                <label class="form-label fw-bold">Comment (optional)</label>
                <textarea name="comment" class="form-control" rows="3" placeholder="Write a short comment..."></textarea>
            </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" name="submit_rating" class="btn btn-warning fw-bold">Submit Rating</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Handle star selection
const stars = document.querySelectorAll('.star');
const ratingInput = document.getElementById('ratingInput');
stars.forEach(star => {
    star.addEventListener('click', () => {
        const val = parseInt(star.getAttribute('data-value'));
        ratingInput.value = val;
        stars.forEach(s => s.classList.remove('fas', 'selected'));
        stars.forEach(s => s.classList.add('far'));
        for (let i = 0; i < val; i++) {
            stars[i].classList.remove('far');
            stars[i].classList.add('fas', 'selected');
        }
    });
});

// Handle session selection (set rated_user_id)
document.getElementById('sessionSelect').addEventListener('change', function() {
    const selected = this.options[this.selectedIndex];
    document.getElementById('ratedUserId').value = selected.getAttribute('data-peerid') || '';
});
</script>
</body>
</html>
