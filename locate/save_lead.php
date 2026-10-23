<?php
// locate/save_lead.php — stores email leads from collect.html popup
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
    exit;
}

$email = trim($_POST['email'] ?? '');

if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Invalid email']);
    exit;
}

$db = get_db();

// Ensure table exists
$db->exec("CREATE TABLE IF NOT EXISTS locate_email_leads (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    email         VARCHAR(255) NOT NULL,
    admin_seen    TINYINT(1) NOT NULL DEFAULT 0,
    created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Upsert — if same email already exists just bump created_at so it stays visible
$st = $db->prepare(
    "INSERT INTO locate_email_leads (email) VALUES (?)
     ON DUPLICATE KEY UPDATE created_at = CURRENT_TIMESTAMP, admin_seen = 0"
);
// Add unique constraint safely
try {
    $db->exec("ALTER TABLE locate_email_leads ADD UNIQUE KEY uq_email (email)");
} catch (Exception $e) {}

$st->execute([$email]);

echo json_encode(['ok' => true]);
