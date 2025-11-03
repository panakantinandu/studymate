<?php
session_start();
require_once __DIR__ . '/../../config/pdo.php';

// ✅ Ensure student login
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'student') {
    header("Location: ../../index.php");
    exit;
}

$user_id = $_SESSION['user_id'] ?? null;
$username = $_SESSION['username'] ?? 'Unknown';
if (!$user_id) die("Error: user_id not found in session.");

// -------------------- 🔹 Handle AJAX POST (Send message) --------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (isset($input['receiver_id'], $input['message'])) {
        $receiver_id = intval($input['receiver_id']);
        $message = trim($input['message']);

        if ($receiver_id && $message) {
            // 💬 Store message
            $stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, message, sent_at) VALUES (?, ?, ?, NOW())");
            $stmt->execute([$user_id, $receiver_id, $message]);

            // 🔔 Check if a similar unread message notification already exists
            $notifCheck = $pdo->prepare("
                SELECT COUNT(*) FROM notifications 
                WHERE user_id = ? 
                  AND type = 'message'
                  AND message LIKE ?
                  AND is_read = 0
            ");
            $notifCheck->execute([$receiver_id, "%New message from $username%"]);

            if ($notifCheck->fetchColumn() == 0) {
                // 📨 Create notification
                $notif = $pdo->prepare("
                    INSERT INTO notifications (user_id, message, is_read, created_at, type, link_url)
                    VALUES (?, ?, 0, NOW(), 'message', ?)
                ");
                $notif->execute([
                    $receiver_id,
                    "💬 New message from $username",
                    'student/messages.php?user_id=' . $user_id
                ]);
            }

            echo json_encode(['success' => true]);
            exit;
        }
    }
}

// -------------------- 🔹 Handle AJAX GET (Fetch messages) --------------------
if (isset($_GET['user_id'])) {
    $other_id = intval($_GET['user_id']);
    $stmt = $pdo->prepare("
        SELECT m.sender_id, m.receiver_id, m.message, 
               DATE_FORMAT(m.sent_at, '%b %d, %Y %h:%i %p') AS sent_at,
               u.username AS sender_name
        FROM messages m
        JOIN users u ON m.sender_id = u.user_id
        WHERE (m.sender_id=? AND m.receiver_id=?) 
           OR (m.sender_id=? AND m.receiver_id=?)
        ORDER BY m.sent_at ASC
    ");
    $stmt->execute([$user_id, $other_id, $other_id, $user_id]);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    exit;
}

// -------------------- 🔹 Fetch chat users --------------------
$stmt = $pdo->prepare("SELECT user_id, username FROM users WHERE role='student' AND user_id != ?");
$stmt->execute([$user_id]);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Messages</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<style>
body {
    background-color: #f4f7f6;
    font-family: 'Segoe UI', sans-serif;
}
.container {
    margin: 40px auto;
    width: 90%;
    max-width: 1100px;
    background: #fff;
    border-radius: 15px;
    box-shadow: 0 6px 15px rgba(0,0,0,0.1);
    display: flex;
    overflow: hidden;
}
.sidebar {
    width: 28%;
    background-color: #f9f9f9;
    border-right: 2px solid #eee;
    padding: 20px;
    overflow-y: auto;
}
.sidebar h3 {
    color: #e50a0a;
    text-align: center;
    font-weight: 600;
}
.sidebar button {
    width: 100%;
    text-align: left;
    margin: 6px 0;
    padding: 10px;
    border: none;
    border-radius: 8px;
    background: #eee;
    font-weight: 500;
    cursor: pointer;
}
.sidebar button:hover, .sidebar button.active {
    background-color: #e50a0a;
    color: white;
}
.chat-area {
    flex: 1;
    display: flex;
    flex-direction: column;
}
.chat-header {
    background: #e50a0a;
    color: white;
    padding: 15px;
    font-weight: 600;
    font-size: 1.1rem;
}
.chat-box {
    flex: 1;
    padding: 20px;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
    gap: 12px;
    background: #fafafa;
}
.msg {
    max-width: 70%;
    padding: 10px 15px;
    border-radius: 12px;
    line-height: 1.4;
    word-wrap: break-word;
}
.sent {
    background-color: #e50a0a;
    color: white;
    align-self: flex-end;
    border-bottom-right-radius: 0;
}
.received {
    background-color: #e0e0e0;
    align-self: flex-start;
    border-bottom-left-radius: 0;
}
.timestamp {
    font-size: 0.75rem;
    color: #4caf50;
    margin-top: 3px;
    text-align: right;
}
.chat-form {
    display: flex;
    padding: 15px;
    border-top: 1px solid #ccc;
    gap: 10px;
}
.chat-form input {
    flex: 1;
    border-radius: 8px;
    border: 1px solid #ccc;
    padding: 10px;
}
.chat-form button {
    border: none;
    background: #e50a0a;
    color: white;
    padding: 10px 20px;
    border-radius: 8px;
    font-weight: bold;
}
.chat-form button:hover {
    background: #c40808;
}
</style>
</head>
<body>
<div class="container">
    <!-- Sidebar -->
    <div class="sidebar">
        <a href="dashboard.php" class="btn btn-outline-danger mb-3">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
        <h3><i class="fas fa-users"></i> Students</h3>
        <hr>
        <?php foreach($users as $u): ?>
            <button class="user-btn" data-id="<?= $u['user_id'] ?>">
                <i class="fas fa-user"></i> <?= htmlspecialchars($u['username']) ?>
            </button>
        <?php endforeach; ?>
    </div>

    <!-- Chat area -->
    <div class="chat-area">
        <div class="chat-header" id="chat-header">Select a student to start chat</div>
        <div class="chat-box" id="chat-box">
            <p class="text-center text-muted mt-4">No conversation selected.</p>
        </div>
        <form id="chat-form" class="chat-form" style="display:none;">
            <input type="text" id="message" placeholder="Type your message..." required>
            <button type="submit"><i class="fas fa-paper-plane"></i> Send</button>
        </form>
    </div>
</div>

<script>
let selectedUser = null;
let chatBox = document.getElementById('chat-box');
let chatHeader = document.getElementById('chat-header');
let chatForm = document.getElementById('chat-form');
const messageInput = document.getElementById('message');

document.querySelectorAll('.user-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        document.querySelectorAll('.user-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        selectedUser = btn.dataset.id;
        chatHeader.innerText = "Chat with " + btn.innerText.trim();
        chatBox.innerHTML = '<p class="text-center text-muted mt-4">Loading messages...</p>';
        chatForm.style.display = 'flex';
        fetchMessages();
    });
});

chatForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    if (!selectedUser) return;
    const message = messageInput.value.trim();
    if (!message) return;

    const res = await fetch('messages.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ receiver_id: selectedUser, message })
    });
    const data = await res.json();
    if (data.success) {
        messageInput.value = '';
        fetchMessages();
    }
});

async function fetchMessages() {
    if (!selectedUser) return;
    const res = await fetch('messages.php?user_id=' + selectedUser);
    const data = await res.json();

    chatBox.innerHTML = '';
    if (data.length === 0) {
        chatBox.innerHTML = '<p class="text-center text-muted mt-4">No messages yet.</p>';
    } else {
        data.forEach(m => {
            const div = document.createElement('div');
            div.classList.add('msg', m.sender_id == <?= $user_id ?> ? 'sent' : 'received');
            div.innerHTML = `<div>${m.message}</div><div class="timestamp">${m.sent_at}</div>`;
            chatBox.appendChild(div);
        });
        chatBox.scrollTop = chatBox.scrollHeight;
    }
}

// Auto refresh every 3 seconds
setInterval(fetchMessages, 3000);
</script>
</body>
</html>
