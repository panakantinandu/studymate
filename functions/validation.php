<?php
function sanitize_input($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

function is_valid_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function is_strong_password($password) {
    return strlen($password) >= 8 && preg_match('/[A-Z]/', $password) && preg_match('/[0-9]/', $password);
}
?>
