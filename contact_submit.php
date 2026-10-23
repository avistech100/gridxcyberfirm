<?php
// contact_submit.php — Public endpoint: user sends message → stored as incoming in email_messages
require_once __DIR__ . '/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['ok' => false, 'error' => 'POST only']); exit;
}

$name    = trim($_POST['name']    ?? '');
$email   = trim($_POST['email']   ?? '');
$message = trim($_POST['message'] ?? '');

if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['ok' => false, 'error' => 'Valid email required']); exit;
}
if (!$message) {
    echo json_encode(['ok' => false, 'error' => 'Message required']); exit;
}

$subject = $name ? "Message from {$name}" : "Message from website visitor";

$db = get_db();

// Ensure tables exist
$db->exec("CREATE TABLE IF NOT EXISTS locate_email_leads (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    email      VARCHAR(255) NOT NULL,
    admin_seen TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
try { $db->exec("ALTER TABLE locate_email_leads ADD UNIQUE KEY uq_email (email)"); } catch(Exception $e){}

$db->exec("CREATE TABLE IF NOT EXISTS email_messages (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    recipient_email VARCHAR(255) NOT NULL,
    subject         VARCHAR(500) NOT NULL DEFAULT '',
    body            TEXT NOT NULL,
    direction       ENUM('outgoing','incoming') NOT NULL DEFAULT 'outgoing',
    admin_seen      TINYINT(1) NOT NULL DEFAULT 0,
    sent_at         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Store as incoming message (user → admin), recipient_email = sender's email
$st = $db->prepare(
    "INSERT INTO email_messages (recipient_email, subject, body, direction, admin_seen)
     VALUES (?, ?, ?, 'incoming', 0)"
);
$st->execute([$email, $subject, $message]);

// Also upsert as a lead so they appear in the Leads tab
$db->prepare(
    "INSERT IGNORE INTO locate_email_leads (email, admin_seen) VALUES (?, 0)"
)->execute([$email]);

echo json_encode(['ok' => true]);
