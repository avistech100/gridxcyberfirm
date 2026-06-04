<?php
require_once __DIR__ . '/config.php';
login_required();

$db      = get_db();
$tx_id   = (int)($_GET['tx_id'] ?? 0);
$user_id = $_SESSION['user_id'];

$st = $db->prepare('SELECT * FROM user_trasaction WHERE id=? AND user_id=?');
$st->execute([$tx_id, $user_id]);
$tx = $st->fetch();

if (!$tx) { redirect('/dashboard.php'); }
?>
<head>
    <meta charset="UTF-8" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Rubik:ital@0;1&display=swap" rel="stylesheet">
    <title>Payment Pending</title>
    <link rel="stylesheet" href="/static/styles/status.css">
</head>

<div class="status-container pending">
    <div class="status-card">
        <div class="icon pending">⏳</div>
        <h2>Payment Pending</h2>

        <div class="details">
            <p><strong>Transaction ID:</strong> <?= e($tx['id']) ?></p>
            <p><strong>Amount:</strong> $<?= e($tx['amount']) ?></p>
            <p><strong>Status:</strong> <?= e($tx['is_approved']) ?></p>
        </div>

        <button class="btn-secondary">View Transactions</button>
        <a href="/dashboard.php" class="btn-secondary">Back to Dashboard</a>
    </div>
</div>
