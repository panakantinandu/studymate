<?php
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'student') {
    header("Location: ../../index.php");
    exit;
}
if ($_SESSION['role'] !== 'student') {
    // destroy session and redirect cleanly
    session_unset();
    session_destroy();
    header("Location: ../../index.php");
    exit;
}

?>
