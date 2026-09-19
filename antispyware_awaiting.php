<?php
require_once __DIR__ . '/config.php';
login_required();
$db = get_db();
$id = (int)($_GET['id'] ?? 0);
$user_id = $_SESSION['user_id'];
$st = $db->prepare('SELECT * FROM antispyware_subscriptions WHERE id=? AND user_id=?');
$st->execute([$id, $user_id]);
$sub = $st->fetch();
if (!$sub) redirect('/antispyware.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Submitted | GridX</title>
    <link href="https://fonts.googleapis.com/css2?family=Rubik:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="<?= asset_url('/static/styles/antispyware.css') ?>">
    <style>
    .await-card{background:#fff;border-radius:20px;padding:2.5rem 2rem;max-width:460px;margin:3rem auto;text-align:center;box-shadow:0 6px 30px rgba(30,47,111,.13);}
    .await-icon{font-size:3.5rem;color:#f9a825;margin-bottom:1rem;animation:spin-wait 4s linear infinite;}
    @keyframes spin-wait{0%{transform:rotate(0);}100%{transform:rotate(360deg);}}
    .await-card h2{font-size:1.3rem;color:#1a1a2e;margin-bottom:.6rem;}
    .await-card p{font-size:.9rem;color:#666;line-height:1.65;margin-bottom:1.2rem;}
    .await-plan-badge{display:inline-block;padding:5px 18px;background:linear-gradient(135deg,#1e2f6f,#2d44a0);color:#fff;border-radius:99px;font-size:.85rem;font-weight:700;margin-bottom:1.2rem;}
    .await-refund{background:#e8f5e9;border:1px solid #a5d6a7;color:#2e7d32;border-radius:10px;padding:.75rem 1rem;font-size:.82rem;display:flex;align-items:center;gap:8px;margin-bottom:1.4rem;text-align:left;}
    .await-btn{display:inline-block;padding:.7rem 2rem;background:linear-gradient(135deg,#1e2f6f,#2d44a0);color:#fff;border-radius:10px;text-decoration:none;font-weight:700;font-size:.95rem;}
    </style>
</head>
<body style="background:#f0f2f9;font-family:'Rubik',sans-serif;">
<div class="await-card">
    <div class="await-icon"><i class="fa-solid fa-clock-rotate-left"></i></div>
    <div class="await-plan-badge"><i class="fa-solid fa-shield-check"></i> <?= e($sub['plan_label']) ?> — $<?= number_format((float)$sub['amount'],2) ?></div>
    <h2>Payment Submitted — Awaiting Confirmation</h2>
    <p>Your antispyware subscription payment has been received and is currently being reviewed by our team. You will be notified once your plan is activated.</p>
    <div class="await-refund">
        <i class="fa-solid fa-rotate-left"></i>
        <span>If your subscription is confirmed and you wish to cancel, a <strong>full refund</strong> will be processed promptly. Your payment is fully protected.</span>
    </div>
    <a href="/dashboard.php" class="await-btn"><i class="fa-solid fa-gauge"></i> Back to Dashboard</a>
</div>
</body>
</html>
