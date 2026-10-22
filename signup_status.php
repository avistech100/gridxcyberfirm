<?php
require_once __DIR__ . '/config.php';
login_required();

$db      = get_db();
$user_id = $_SESSION['user_id'];
$tx_id   = (int)($_GET['tx_id'] ?? 0);

// Ensure columns exist (safe if already added)
try { $db->exec("ALTER TABLE user_gridxuser ADD COLUMN signup_paid  TINYINT(1) NOT NULL DEFAULT 0"); } catch (Exception $e) {}
try { $db->exec("ALTER TABLE user_gridxuser ADD COLUMN from_locate  TINYINT(1) NOT NULL DEFAULT 0"); } catch (Exception $e) {}

$user = current_user();
if (!$user) { redirect('/login.php'); }

// If already approved — just go to dashboard
if (!empty($user['dashboard_access'])) {
    redirect('/dashboard.php');
}

// Fetch the TX (may be null if tx_id wasn't passed)
$tx = null;
if ($tx_id) {
    $st = $db->prepare('SELECT * FROM user_trasaction WHERE id=? AND user_id=?');
    $st->execute([$tx_id, $user_id]);
    $tx = $st->fetch();
}

// If no tx_id passed, find the latest one for this user
if (!$tx) {
    $st = $db->prepare('SELECT * FROM user_trasaction WHERE user_id=? ORDER BY id DESC LIMIT 1');
    $st->execute([$user_id]);
    $tx = $st->fetch();
    if ($tx) $tx_id = (int)$tx['id'];
}

$tx_status       = $tx['is_approved'] ?? null;
$signup_paid     = (int)($user['signup_paid'] ?? 0);
$dashboard_access = (int)($user['dashboard_access'] ?? 0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Status | GridX</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Rubik:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= asset_url('/static/styles/status.css') ?>">
    <style>
        body { font-family: 'Rubik', sans-serif; background: #0f111a; color: #e0e0e0; min-height: 100vh; display: flex; align-items: center; justify-content: center; margin: 0; }
        .status-wrap { max-width: 480px; width: 100%; margin: 0 auto; padding: 24px 16px; }
        .status-card { background: #1a1d2e; border-radius: 16px; padding: 36px 32px; text-align: center; box-shadow: 0 8px 40px rgba(0,0,0,.4); }
        .status-icon { font-size: 3rem; margin-bottom: 12px; }
        .status-card h2 { font-size: 1.4rem; font-weight: 600; margin: 0 0 8px; }
        .status-card p { color: #aaa; font-size: .92rem; margin: 0 0 20px; line-height: 1.6; }
        .tx-detail { background: rgba(255,255,255,.05); border-radius: 8px; padding: 10px 14px; margin-bottom: 20px; font-size: .82rem; color: #888; text-align: left; }
        .tx-detail span { color: #ccc; font-weight: 500; }
        .btn-primary { display: inline-block; background: #4f8ef7; color: #fff; border: none; border-radius: 8px; padding: 10px 28px; font-size: .95rem; font-weight: 500; cursor: pointer; text-decoration: none; }
        .btn-secondary { display: inline-block; background: rgba(255,255,255,.08); color: #ccc; border: none; border-radius: 8px; padding: 10px 28px; font-size: .9rem; cursor: pointer; text-decoration: none; margin-top: 10px; }
        .badge-pending  { color: #f7c948; }
        .badge-success  { color: #4caf7d; }
        .badge-failed   { color: #e05252; }
        .badge-init     { color: #aaa; }
        .spinner { display: inline-block; width: 14px; height: 14px; border: 2px solid rgba(255,255,255,.3); border-top-color: #4f8ef7; border-radius: 50%; animation: spin .8s linear infinite; vertical-align: middle; margin-right: 6px; }
        @keyframes spin { to { transform: rotate(360deg); } }
        #poll-note { font-size: .78rem; color: #555; margin-top: 14px; }
    </style>
</head>
<body>
<div class="status-wrap">
    <div class="status-card" id="status-card">

        <?php if (!$tx): ?>
            <!-- No transaction found — user never completed checkout -->
            <div class="status-icon">⚠️</div>
            <h2>Payment Not Completed</h2>
            <p>We couldn't find a payment for your account. Please complete the checkout to activate your account.</p>
            <a href="/payment.php" class="btn-primary">Go to Checkout</a>

        <?php elseif ($tx_status === 'initialize'): ?>
            <!-- TX exists but user hasn't submitted payment method yet -->
            <div class="status-icon">⚠️</div>
            <h2>Payment Not Started</h2>
            <p>You haven't completed your payment yet. Please go to checkout and submit your payment method to activate your account.</p>
            <?php if ($tx_id): ?>
            <a href="/checkout.php?id=<?= $tx_id ?>&signup=1" class="btn-primary">Complete Payment</a>
            <?php endif; ?>

        <?php elseif ($tx_status === 'pending'): ?>
            <!-- Payment submitted, waiting for admin -->
            <div class="status-icon">⏳</div>
            <h2 class="badge-pending">Payment Under Review</h2>
            <p>Your payment details have been received and are being reviewed. Your account will be activated once your payment is confirmed.</p>
            <?php if ($tx_id): ?>
            <div class="tx-detail">
                Transaction ID: <span>#<?= e($tx_id) ?></span><br>
                Amount: <span>$<?= e(number_format((float)$tx['amount'], 2)) ?></span><br>
                Method: <span><?= e($tx['payment_method'] ?? '—') ?></span>
            </div>
            <?php endif; ?>
            <p><span class="spinner"></span><span id="poll-label">Checking for updates…</span></p>
            <p id="poll-note">This page checks automatically every few seconds.</p>

        <?php elseif ($tx_status === 'success' && !$signup_paid): ?>
            <!-- Admin approved TX but hasn't approved the signup yet -->
            <div class="status-icon">✅</div>
            <h2 class="badge-success">Payment Confirmed!</h2>
            <p>Your payment was confirmed. Your account is being set up — this usually takes a few hours. You'll be redirected automatically once it's ready.</p>
            <?php if ($tx_id): ?>
            <div class="tx-detail">
                Transaction ID: <span>#<?= e($tx_id) ?></span><br>
                Amount: <span>$<?= e(number_format((float)$tx['amount'], 2)) ?></span>
            </div>
            <?php endif; ?>
            <p><span class="spinner"></span><span id="poll-label">Waiting for account activation…</span></p>
            <p id="poll-note">This page checks automatically every few seconds.</p>

        <?php elseif ($tx_status === 'failed'): ?>
            <!-- Admin rejected the TX -->
            <div class="status-icon">❌</div>
            <h2 class="badge-failed">Payment Not Confirmed</h2>
            <p>Your payment could not be verified by our team. Please try again with the correct details. If you believe this is a mistake, contact support.</p>
            <?php if ($tx_id): ?>
            <div class="tx-detail">
                Transaction ID: <span>#<?= e($tx_id) ?></span> — <span style="color:#e05252">Rejected</span>
            </div>
            <?php endif; ?>
            <!-- Create a new TX for retry -->
            <form method="post" action="/signup_status.php?retry=1">
                <?= csrf_field() ?>
                <button type="submit" class="btn-primary">Retry Payment</button>
            </form>
            <br><a href="/logout.php" class="btn-secondary">Log Out</a>

        <?php else: ?>
            <div class="status-icon">⏳</div>
            <h2>Account Pending</h2>
            <p>Your account is being created. This process will be done in a few hours.</p>
            <p><span class="spinner"></span><span id="poll-label">Checking status…</span></p>
        <?php endif; ?>

    </div>
</div>

<?php if (in_array($tx_status, ['pending', 'success', null]) || ($tx_status === 'success' && !$signup_paid)): ?>
<script>
(function() {
    var txId  = <?= (int)$tx_id ?>;
    var timer = null;

    function poll() {
        fetch('/signup_status_check.php?tx_id=' + txId, {cache: 'no-store'})
            .then(function(r){ return r.json(); })
            .then(function(d) {
                if (d.redirect) {
                    window.location.href = d.redirect;
                    return;
                }
                if (d.reload) {
                    window.location.reload();
                    return;
                }
                // Still waiting — schedule next poll
                timer = setTimeout(poll, 8000);
            })
            .catch(function() {
                timer = setTimeout(poll, 8000);
            });
    }

    timer = setTimeout(poll, 8000);
})();
</script>
<?php endif; ?>

<?php
// ── Handle retry: create a new TX ────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['retry'])) {
    verify_csrf();
    $fee_amount = (float)get_site_setting('signup_fee_amount', '50');
    $db->prepare('INSERT INTO user_trasaction (user_id, amount, is_approved, date) VALUES (?, ?, "initialize", NOW())')
       ->execute([$user_id, $fee_amount]);
    $new_tx = (int)$db->lastInsertId();
    redirect('/checkout.php?id=' . $new_tx . '&signup=1');
}
?>
</body>
</html>
