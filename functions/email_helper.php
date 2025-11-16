<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../src/PHPMailer.php';
require_once __DIR__ . '/../src/SMTP.php';
require_once __DIR__ . '/../src/Exception.php';

function send_email($to, $subject, $body) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'nandupanakanti@gmail.com';   // <-- REPLACE
        $mail->Password = 'aayi pyeh dgbk uacw';    // <-- REPLACE
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->CharSet = 'UTF-8';

        $mail->setFrom('nandupanakanti@gmail.com', 'StudyMate');
        $mail->addAddress($to);
        $mail->isHTML(false); 
        $mail->Subject = $subject;
        $mail->Body = $body;

        $mail->send();
        return true;

    } catch (Exception $e) {
        error_log("Email failed: {$mail->ErrorInfo}");
        return false;
    }
}
?>
