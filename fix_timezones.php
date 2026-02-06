<?php
// Path to your scheduled emails file
$scheduledFile = __DIR__ . '/scheduled_emails.json';

if (!file_exists($scheduledFile)) {
    exit("No scheduled_emails.json found.\n");
}

$scheduled = json_decode(file_get_contents($scheduledFile), true);

// Loop through all entries and add timezone if missing
foreach ($scheduled as &$email) {
    if (!isset($email['timezone']) || empty($email['timezone'])) {
        $email['timezone'] = 'Asia/Manila'; // default fallback
        echo "Added timezone Asia/Manila to email for {$email['to']}\n";
    }
}

// Save back to file
file_put_contents($scheduledFile, json_encode($scheduled, JSON_PRETTY_PRINT));

echo "All entries updated with timezone.\n";
?>
