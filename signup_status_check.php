<?php
require_once __DIR__ . '/config.php';
login_required();

header('Content-Type: application/json');

$db      = get_db();
$user_id = $_SESSION['user_id'];
$tx_id   = (int)($_GET['tx_id'] ?? 0);

// Fetch fresh user row
$st = $db->prepare('SELECT dashboard_access, signup_paid, from_locate FROM user_gridxuser WHERE id=?');
$st->execute([$user_id]);
$user = $st->fetch();

if (!$user) {
    echo json_encode(['error' => 'user_not_found']);
    exit;
}

// Fully approved → redirect to dashboard
if (!empty($user['dashboard_access'])) {
    echo json_encode(['redirect' => '/dashboard.php']);
    exit;
}

// signup_paid=1 → signup fully completed; dashboard_access decides where to go
// (this endpoint is only valid while signup_paid=0; after that it should never be polled)
if (!empty($user['signup_paid'])) {
    $dest = !empty($user['dashboard_access']) ? '/dashboard.php' : '/404.php';
    echo json_encode(['redirect' => $dest]);
    exit;
}

// Fetch TX status
$tx = null;
if ($tx_id) {
    $st2 = $db->prepare('SELECT is_approved FROM user_trasaction WHERE id=? AND user_id=?');
    $st2->execute([$tx_id, $user_id]);
    $tx = $st2->fetch();
}

$tx_status = $tx['is_approved'] ?? null;

// If TX moved to failed or success (payment confirmed but signup pending) → reload page to show new UI
if ($tx_status === 'failed') {
    echo json_encode(['reload' => true]);
    exit;
}

if ($tx_status === 'success' && empty($user['signup_paid'])) {
    // Payment approved by admin, show "payment confirmed, waiting for account activation"
    echo json_encode(['reload' => true]);
    exit;
}

// Still pending — tell JS to keep polling
echo json_encode(['status' => $tx_status, 'waiting' => true]);
