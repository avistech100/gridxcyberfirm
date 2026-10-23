<?php
// admin/email_send.php — AJAX endpoint: send an email and log it
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/admin_layout.php';
admin_required();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['ok' => false, 'error' => 'POST only']); exit;
}

$to      = trim($_POST['to']      ?? '');
$subject = trim($_POST['subject'] ?? '');
$body    = trim($_POST['body']    ?? '');

if (!$to || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['ok' => false, 'error' => 'Invalid recipient email']); exit;
}
if (!$subject) {
    echo json_encode(['ok' => false, 'error' => 'Subject is required']); exit;
}
if (!$body) {
    echo json_encode(['ok' => false, 'error' => 'Message body is required']); exit;
}

$db = get_db();
_ensure_email_tables($db);

// Read SMTP settings
$smtp_host = get_site_setting('smtp_host', 'smtp.hostinger.com');
$smtp_port = (int) get_site_setting('smtp_port', '465');
$smtp_user = get_site_setting('smtp_user', 'app@gridxcyberfirm.com');
$smtp_pass = get_site_setting('smtp_pass', '');
$from_name = get_site_setting('smtp_from_name', 'GridX Cyber Solutions');
$from_addr = get_site_setting('smtp_from_email', 'app@gridxcyberfirm.com');

// Send via PHP mail() with custom headers (works on Hostinger without PHPMailer)
// For best delivery on Hostinger shared, we use mail() + Hostinger's SMTP relay
// Hostinger Premium supports PHP mail() natively via Exim sendmail
$admin_forward = get_site_setting('smtp_admin_forward', '');
$reply_to      = ($admin_forward && filter_var($admin_forward, FILTER_VALIDATE_EMAIL))
                 ? $admin_forward : $from_addr;

$headers  = "From: {$from_name} <{$from_addr}>\r\n";
$headers .= "Reply-To: {$reply_to}\r\n";
$headers .= "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: text/html; charset=UTF-8\r\n";
$headers .= "X-Mailer: GridX-Mailer/1.0\r\n";

$html_body = nl2br(htmlspecialchars($body, ENT_QUOTES, 'UTF-8'));
$full_body = <<<HTML
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"></head>
<body style="font-family:Arial,sans-serif;color:#222;padding:20px;">
<div style="max-width:600px;margin:auto;background:#fff;border:1px solid #e0e0e0;border-radius:8px;padding:30px;">
  <div style="border-bottom:2px solid #2c3e50;padding-bottom:12px;margin-bottom:20px;">
    <strong style="font-size:18px;color:#2c3e50;">GridX Cyber Solutions</strong>
  </div>
  <p>{$html_body}</p>
  <hr style="border:none;border-top:1px solid #eee;margin:24px 0;">
  <small style="color:#999;">This message was sent from GridX Cyber Solutions &mdash; gridxcyberfirm.com</small>
</div>
</body>
</html>
HTML;

$sent = mail($to, $subject, $full_body, $headers);

if (!$sent) {
    echo json_encode(['ok' => false, 'error' => 'Mail server rejected the message. Check Hostinger SMTP settings.']);
    exit;
}

// Log the sent message
$st = $db->prepare(
    "INSERT INTO email_messages (recipient_email, subject, body, direction, admin_seen)
     VALUES (?, ?, ?, 'outgoing', 1)"
);
$st->execute([$to, $subject, $body]);

echo json_encode(['ok' => true]);

// ── helpers ─────────────────────────────────────────────────────────────────
function _ensure_email_tables(PDO $db): void {
    $db->exec("CREATE TABLE IF NOT EXISTS locate_email_leads (
        id         INT AUTO_INCREMENT PRIMARY KEY,
        email      VARCHAR(255) NOT NULL,
        admin_seen TINYINT(1) NOT NULL DEFAULT 0,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    try { $db->exec("ALTER TABLE locate_email_leads ADD UNIQUE KEY uq_email (email)"); } catch(Exception $e){}

    $db->exec("CREATE TABLE IF NOT EXISTS email_messages (
        id               INT AUTO_INCREMENT PRIMARY KEY,
        recipient_email  VARCHAR(255) NOT NULL,
        subject          VARCHAR(500) NOT NULL DEFAULT '',
        body             TEXT NOT NULL,
        direction        ENUM('outgoing','incoming') NOT NULL DEFAULT 'outgoing',
        admin_seen       TINYINT(1) NOT NULL DEFAULT 0,
        sent_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}
