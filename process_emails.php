<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

$scheduledFile = __DIR__ . '/scheduled_emails.json';
if (!file_exists($scheduledFile)) exit;

$scheduled = json_decode(file_get_contents($scheduledFile), true);

// Always work in UTC
date_default_timezone_set('UTC');
$now = new DateTime('now', new DateTimeZone('UTC'));

foreach ($scheduled as &$email) {
    // Parse scheduled time as UTC (all entries are stored in UTC by sendmail.php)
    $scheduledTime = DateTime::createFromFormat('Y-m-d\TH:i', $email['send_time'], new DateTimeZone('UTC'));

    echo "Checking {$scheduledTime->format('Y-m-d\TH:i')} UTC against {$now->format('Y-m-d\TH:i')} UTC\n";

    if ($email['status'] === 'pending' && $scheduledTime <= $now) {
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
            $mail->addAddress($email['to']);

            $mail->CharSet = 'UTF-8';
            $mail->Encoding = 'base64';
            $mail->isHTML(false);
            $mail->Subject = $email['subject'];
            $mail->Body    = $email['message'];

            // ✅ Attach file if scheduled entry has one
            if (!empty($email['attachment']) && file_exists($email['attachment'])) {
                $mail->addAttachment($email['attachment'], $email['attachment_name']);
            }

            $mail->send();
            $email['status'] = 'sent';
            echo "Sent email to {$email['to']} (subject: {$email['subject']})\n";

            // Delete attachment after sending
            if (!empty($email['attachment']) && file_exists($email['attachment'])) {
                unlink($email['attachment']);
                $email['attachment'] = null;
                $email['attachment_name'] = null;
                echo "Deleted attachment after sending\n";
            }
        } catch (Exception $e) {
            $email['status'] = 'error: ' . $mail->ErrorInfo;
            echo "Error sending to {$email['to']}: {$mail->ErrorInfo}\n";
        }
    }
}

// Save updated statuses back to JSON file
file_put_contents($scheduledFile, json_encode($scheduled, JSON_PRETTY_PRINT));
?>
