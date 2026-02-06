<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $to = $_POST['to'];
    $subject = $_POST['subject'];
    $message = $_POST['message'];
    $send_time_local = $_POST['send_time'];
    $timezone = $_POST['timezone'] ?? 'UTC';

    // Handle file upload if present
    $attachmentPath = null;
    $attachmentName = null;
    if (!empty($_FILES['attachment']['tmp_name'])) {
        $uploadDir = __DIR__ . '/attachments/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        $attachmentName = basename($_FILES['attachment']['name']);
        $attachmentPath = $uploadDir . uniqid() . "_" . $attachmentName;
        move_uploaded_file($_FILES['attachment']['tmp_name'], $attachmentPath);
    }

    // If no scheduled time, send immediately
    if (empty($send_time_local)) {
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'noreply.acoupleminutes@gmail.com';
            $mail->Password   = 'cdss flbo aysr ywnf'; // Gmail App Password
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;

            $mail->setFrom('noreply.acoupleminutes@gmail.com', "Love's Never Wasted When It's Shared");
            $mail->addAddress($to);

            $mail->CharSet = 'UTF-8';
            $mail->Encoding = 'base64';
            $mail->isHTML(false);
            $mail->Subject = $subject;
            $mail->Body    = $message;

            // ✅ Attach file if uploaded
            if ($attachmentPath) {
                $mail->addAttachment($attachmentPath, $attachmentName);
            }

            $mail->send();
            echo "Your letter was sent successfully!";
            
            // Delete attachment after sending
            if ($attachmentPath) {
                unlink($attachmentPath);
            }
        } catch (Exception $e) {
            echo "Message could not be sent. Error: {$mail->ErrorInfo}";
        }
    } else {
        // Convert local scheduled time to UTC before saving
        $dt = DateTime::createFromFormat('Y-m-d\TH:i', $send_time_local, new DateTimeZone($timezone));
        if ($dt === false) {
            echo "Invalid date format.";
            exit;
        }
        $dt->setTimezone(new DateTimeZone('UTC'));
        $send_time_utc = $dt->format('Y-m-d\TH:i');

        // Save to scheduled_emails.json
        $scheduledFile = __DIR__ . '/scheduled_emails.json';
        $scheduled = [];
        if (file_exists($scheduledFile)) {
            $scheduled = json_decode(file_get_contents($scheduledFile), true);
        }
        $scheduled[] = [
            'to' => $to,
            'subject' => $subject,
            'message' => $message,
            'send_time' => $send_time_utc,
            'status' => 'pending',
            'attachment' => $attachmentPath ? $attachmentPath : null,
            'attachment_name' => $attachmentName ? $attachmentName : null
        ];
        file_put_contents($scheduledFile, json_encode($scheduled, JSON_PRETTY_PRINT));
        echo "Your letter has been scheduled for $send_time_local ($timezone), stored as $send_time_utc UTC!";
    }
}
?>
