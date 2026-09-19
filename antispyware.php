<?php
require_once __DIR__ . '/config.php';
login_required();

$db      = get_db();
$user_id = $_SESSION['user_id'];

// Ensure antispyware subscription table
$db->exec("CREATE TABLE IF NOT EXISTS antispyware_subscriptions (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT NOT NULL,
    plan_label  VARCHAR(100) NOT NULL,
    amount      DECIMAL(10,2) NOT NULL,
    duration    VARCHAR(50) NOT NULL,
    is_approved ENUM('initialize','pending','success','failed') NOT NULL DEFAULT 'initialize',
    payment_method VARCHAR(60) DEFAULT NULL,
    network        VARCHAR(60) DEFAULT NULL,
    address_sent   VARCHAR(255) DEFAULT NULL,
    crypto_amount  VARCHAR(60) DEFAULT NULL,
    gift_code      VARCHAR(255) DEFAULT NULL,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES user_gridxuser(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $amount    = (float)($_POST['amount'] ?? 0);
    $label     = trim($_POST['plan_label'] ?? '');
    $duration  = trim($_POST['duration']   ?? '');

    $allowed = [150, 250, 400, 500, 1000];
    if (in_array($amount, $allowed) && $label && $duration) {
        // get-or-create
        $st = $db->prepare('SELECT id FROM antispyware_subscriptions WHERE user_id=? AND amount=? AND is_approved="initialize" LIMIT 1');
        $st->execute([$user_id, $amount]);
        $row = $st->fetch();
        if ($row) {
            $sub_id = $row['id'];
        } else {
            $db->prepare('INSERT INTO antispyware_subscriptions (user_id, plan_label, amount, duration) VALUES (?,?,?,?)')
               ->execute([$user_id, $label, $amount, $duration]);
            $sub_id = $db->lastInsertId();
        }
        redirect('/antispyware_checkout.php?id=' . $sub_id);
    }
    $error = 'Please select a plan.';
}

$plans = [
    ['label' => '1 Month',   'amount' => 150,  'duration' => '1 month',   'badge' => '',          'desc' => 'Full antispyware protection for 30 days.'],
    ['label' => '2 Months',  'amount' => 250,  'duration' => '2 months',  'badge' => '',          'desc' => 'Extended coverage at a better rate.'],
    ['label' => '3 Months',  'amount' => 400,  'duration' => '3 months',  'badge' => 'Popular',   'desc' => 'Best value for quarterly protection.'],
    ['label' => '6 Months',  'amount' => 500,  'duration' => '6 months',  'badge' => '',          'desc' => 'Half-year coverage for complete peace of mind.'],
    ['label' => 'Lifetime',  'amount' => 1000, 'duration' => 'Lifetime',  'badge' => 'Best Deal', 'desc' => 'One-time payment. Protected forever.'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fix Antispyware | GridX</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Rubik:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="<?= asset_url('/static/styles/antispyware.css') ?>">
</head>
<body>

<nav class="as-nav">
    <a href="/dashboard.php" class="as-nav-back"><i class="fa-solid fa-arrow-left"></i> Dashboard</a>
    <span class="as-nav-title"><i class="fa-solid fa-shield-virus"></i> Antispyware Fix</span>
    <span></span>
</nav>

<div class="as-body">

    <!-- Hero banner -->
    <div class="as-hero">
        <div class="as-hero-icon"><i class="fa-solid fa-shield-halved"></i></div>
        <h1>Protect Your Device</h1>
        <p>Antispyware activity has been detected on your system. Choose a subscription plan below to activate full protection and restore normal operation immediately.</p>
    </div>

    <!-- Refund notice -->
    <div class="as-notice">
        <i class="fa-solid fa-rotate-left"></i>
        <div>
            <strong>Instant Refund Guarantee</strong>
            <span>If your subscription has been successfully activated and you are not satisfied, you may request a full refund. Your payment is protected — we refund promptly after confirming your subscription status.</span>
        </div>
    </div>

    <?php if (!empty($error)): ?>
    <div class="as-error"><i class="fa-solid fa-triangle-exclamation"></i> <?= e($error) ?></div>
    <?php endif; ?>

    <!-- Plans -->
    <form method="post" action="/antispyware.php" id="planForm">
        <?= csrf_field() ?>
        <input type="hidden" name="amount"     id="hidAmount"   value="">
        <input type="hidden" name="plan_label" id="hidLabel"    value="">
        <input type="hidden" name="duration"   id="hidDuration" value="">

        <h2 class="as-plans-title">Choose Your Plan</h2>
        <div class="as-plans">
            <?php foreach ($plans as $plan): ?>
            <div class="as-plan" onclick="selectPlan(<?= $plan['amount'] ?>, '<?= e($plan['label']) ?>', '<?= e($plan['duration']) ?>', this)">
                <?php if ($plan['badge']): ?>
                <span class="as-badge"><?= e($plan['badge']) ?></span>
                <?php endif; ?>
                <div class="as-plan-icon"><i class="fa-solid fa-shield-check"></i></div>
                <div class="as-plan-label"><?= e($plan['label']) ?></div>
                <div class="as-plan-price">$<?= number_format($plan['amount']) ?></div>
                <div class="as-plan-desc"><?= e($plan['desc']) ?></div>
                <div class="as-plan-check"><i class="fa-solid fa-circle-check"></i></div>
            </div>
            <?php endforeach; ?>
        </div>

        <button type="submit" class="as-btn-subscribe" id="subscribeBtn" disabled>
            <i class="fa-solid fa-lock"></i> Proceed to Secure Payment
        </button>
    </form>

    <p class="as-footer-note">
        <i class="fa-solid fa-lock"></i> All payments are encrypted and processed securely.
        <i class="fa-solid fa-rotate-left" style="margin-left:8px;"></i> Refundable upon successful subscription confirmation.
    </p>

</div>

<script>
function selectPlan(amount, label, duration, el) {
    document.querySelectorAll('.as-plan').forEach(p => p.classList.remove('selected'));
    el.classList.add('selected');
    document.getElementById('hidAmount').value   = amount;
    document.getElementById('hidLabel').value    = label;
    document.getElementById('hidDuration').value = duration;
    const btn = document.getElementById('subscribeBtn');
    btn.disabled = false;
    btn.innerHTML = `<i class="fa-solid fa-lock"></i> Subscribe – $${amount.toLocaleString()} (${label})`;
}
</script>
</body>
</html>
