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

// Fully approved — go to dashboard
if (!empty($user['dashboard_access'])) {
    redirect('/dashboard.php');
}

// signup_paid=1 means signup was fully completed — if dashboard_access is now 0, admin revoked it → 404
if (!empty($user['signup_paid'])) {
    redirect('/404.php');
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

$tx_status        = $tx['is_approved'] ?? null;
$signup_paid      = (int)($user['signup_paid'] ?? 0);
$dashboard_access = (int)($user['dashboard_access'] ?? 0);

// Handle retry: create a new TX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['retry'])) {
    verify_csrf();
    $fee_amount = (float)get_site_setting('signup_fee_amount', '50');
    $db->prepare('INSERT INTO user_trasaction (user_id, amount, is_approved) VALUES (?, ?, "initialize")')
       ->execute([$user_id, $fee_amount]);
    $new_tx = (int)$db->lastInsertId();
    redirect('/checkout.php?id=' . $new_tx . '&signup=1');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Status | GridX Cyber Solutions</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&family=JetBrains+Mono:wght@500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-base: #0a0d14;
            --card-surface: #121722;
            --card-border: rgba(255, 255, 255, 0.08);
            --brand-primary: #2563eb;
            --brand-hover: #1d4ed8;
            --text-heading: #f8fafc;
            --text-body: #94a3b8;
            --text-muted: #64748b;
            --state-pending: #f59e0b;
            --state-pending-bg: rgba(245, 158, 11, 0.1);
            --state-pending-border: rgba(245, 158, 11, 0.25);
            --state-success: #10b981;
            --state-success-bg: rgba(16, 185, 129, 0.1);
            --state-success-border: rgba(16, 185, 129, 0.25);
            --state-failed: #ef4444;
            --state-failed-bg: rgba(239, 68, 68, 0.1);
            --state-failed-border: rgba(239, 68, 68, 0.25);
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
            background-color: var(--bg-base);
            background-image: 
                radial-gradient(at 0% 0%, rgba(37, 99, 235, 0.12) 0px, transparent 50%),
                radial-gradient(at 100% 100%, rgba(16, 185, 129, 0.08) 0px, transparent 50%);
            color: var(--text-body);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 24px 16px;
            -webkit-font-smoothing: antialiased;
        }

        .status-container {
            width: 100%;
            max-width: 480px;
        }

        .status-brand {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-bottom: 24px;
        }

        .status-brand svg {
            width: 24px;
            height: 24px;
            color: #38bdf8;
        }

        .status-brand span {
            font-size: 0.95rem;
            font-weight: 700;
            letter-spacing: 0.04em;
            color: var(--text-heading);
            text-transform: uppercase;
        }

        .status-card {
            background: var(--card-surface);
            border: 1px solid var(--card-border);
            border-radius: 20px;
            padding: 36px 32px;
            text-align: center;
            box-shadow: 0 20px 40px -15px rgba(0, 0, 0, 0.5), 0 0 0 1px rgba(255, 255, 255, 0.04) inset;
            backdrop-filter: blur(12px);
            position: relative;
            overflow: hidden;
        }

        .status-icon-wrapper {
            width: 68px;
            height: 68px;
            margin: 0 auto 20px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }

        .status-icon-wrapper.pending {
            background: var(--state-pending-bg);
            border: 1px solid var(--state-pending-border);
            color: var(--state-pending);
        }

        .status-icon-wrapper.success {
            background: var(--state-success-bg);
            border: 1px solid var(--state-success-border);
            color: var(--state-success);
        }

        .status-icon-wrapper.failed {
            background: var(--state-failed-bg);
            border: 1px solid var(--state-failed-border);
            color: var(--state-failed);
        }

        .status-icon-wrapper svg {
            width: 32px;
            height: 32px;
        }

        .status-card h2 {
            font-size: 1.35rem;
            font-weight: 700;
            color: var(--text-heading);
            margin-bottom: 10px;
            letter-spacing: -0.01em;
        }

        .status-card p.description {
            font-size: 0.92rem;
            line-height: 1.6;
            color: var(--text-body);
            margin-bottom: 24px;
        }

        /* Technical summary card */
        .spec-sheet {
            background: rgba(255, 255, 255, 0.025);
            border: 1px solid rgba(255, 255, 255, 0.06);
            border-radius: 12px;
            padding: 14px 18px;
            margin-bottom: 24px;
            text-align: left;
        }

        .spec-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 6px 0;
            font-size: 0.84rem;
        }

        .spec-item:not(:last-child) {
            border-bottom: 1px dashed rgba(255, 255, 255, 0.06);
        }

        .spec-label {
            color: var(--text-muted);
            font-weight: 500;
        }

        .spec-value {
            color: #cbd5e1;
            font-family: 'JetBrains Mono', monospace;
            font-weight: 500;
        }

        .spec-badge {
            font-size: 0.75rem;
            font-family: inherit;
            padding: 2px 8px;
            border-radius: 6px;
            font-weight: 600;
        }

        .spec-badge.pending { background: var(--state-pending-bg); color: var(--state-pending); border: 1px solid var(--state-pending-border); }
        .spec-badge.success { background: var(--state-success-bg); color: var(--state-success); border: 1px solid var(--state-success-border); }
        .spec-badge.failed  { background: var(--state-failed-bg); color: var(--state-failed); border: 1px solid var(--state-failed-border); }

        /* Step Timeline */
        .step-rail {
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: relative;
            margin: 20px 8px 24px;
        }

        .step-rail::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 12px;
            right: 12px;
            height: 2px;
            background: rgba(255, 255, 255, 0.08);
            transform: translateY(-50%);
            z-index: 1;
        }

        .step-point {
            position: relative;
            z-index: 2;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 6px;
        }

        .step-dot {
            width: 22px;
            height: 22px;
            border-radius: 50%;
            background: var(--card-surface);
            border: 2px solid var(--text-muted);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            color: var(--text-muted);
        }

        .step-point.active .step-dot {
            border-color: #38bdf8;
            background: #0369a1;
            color: #fff;
            box-shadow: 0 0 12px rgba(56, 189, 248, 0.4);
        }

        .step-point.completed .step-dot {
            border-color: var(--state-success);
            background: #065f46;
            color: #fff;
        }

        .step-text {
            font-size: 0.72rem;
            color: var(--text-muted);
            font-weight: 500;
        }

        .step-point.active .step-text {
            color: #38bdf8;
            font-weight: 600;
        }

        /* Live status polling indicator */
        .pulse-status {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 14px;
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 9999px;
            font-size: 0.8rem;
            color: #cbd5e1;
        }

        .pulse-orb {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background-color: #38bdf8;
            animation: radarPulse 1.6s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }

        @keyframes radarPulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.4; transform: scale(0.85); }
        }

        /* Actions */
        .btn-action {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            padding: 12px 20px;
            font-size: 0.92rem;
            font-weight: 600;
            border-radius: 10px;
            border: none;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.15s ease;
        }

        .btn-action.primary {
            background: var(--brand-primary);
            color: #fff;
            box-shadow: 0 4px 14px rgba(37, 99, 235, 0.35);
        }

        .btn-action.primary:hover {
            background: var(--brand-hover);
        }

        .btn-action.secondary {
            background: transparent;
            color: var(--text-muted);
            border: 1px solid rgba(255, 255, 255, 0.1);
            margin-top: 10px;
        }

        .btn-action.secondary:hover {
            color: #e2e8f0;
            background: rgba(255, 255, 255, 0.04);
        }

        .status-footer {
            margin-top: 18px;
            font-size: 0.78rem;
            color: var(--text-muted);
        }
    </style>
</head>
<body>

<div class="status-container">
    <div class="status-brand">
        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
        </svg>
        <span>GridX Security Verification</span>
    </div>

    <div class="status-card" id="status-card">

        <?php if (!$tx): ?>
            <!-- No transaction found -->
            <div class="status-icon-wrapper failed">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                </svg>
            </div>
            <h2>Checkout Incomplete</h2>
            <p class="description">We could not identify an initiated transaction record for your registration. Please finalize checkout to activate platform capabilities.</p>
            <a href="/payment.php" class="btn-action primary">Go to Payment</a>

        <?php elseif ($tx_status === 'initialize'): ?>
            <!-- TX exists but payment method not selected -->
            <div class="status-icon-wrapper pending">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <h2>Payment Pending Submission</h2>
            <p class="description">Your registration order is open. Please select your payment method to begin identity validation and system access.</p>
            <?php if ($tx_id): ?>
            <a href="/checkout.php?id=<?= $tx_id ?>&signup=1" class="btn-action primary">Complete Payment &rarr;</a>
            <?php endif; ?>

        <?php elseif ($tx_status === 'pending'): ?>
            <!-- Payment submitted, waiting for admin approval -->
            <div class="status-icon-wrapper pending">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <h2>Payment Under Review</h2>
            <p class="description">Your transaction has been securely submitted and is undergoing network confirmation. Access privileges will be enabled immediately upon verification.</p>

            <!-- Progress Step Rail -->
            <div class="step-rail">
                <div class="step-point completed">
                    <div class="step-dot">&#10003;</div>
                    <div class="step-text">Registered</div>
                </div>
                <div class="step-point active">
                    <div class="step-dot">&bull;</div>
                    <div class="step-text">Confirming</div>
                </div>
                <div class="step-point">
                    <div class="step-dot">3</div>
                    <div class="step-text">Activated</div>
                </div>
            </div>

            <?php if ($tx_id): ?>
            <div class="spec-sheet">
                <div class="spec-item">
                    <span class="spec-label">Transaction Reference</span>
                    <span class="spec-value">#<?= e($tx_id) ?></span>
                </div>
                <div class="spec-item">
                    <span class="spec-label">Settlement Amount</span>
                    <span class="spec-value">$<?= e(number_format((float)$tx['amount'], 2)) ?></span>
                </div>
                <div class="spec-item">
                    <span class="spec-label">Gateway Method</span>
                    <span class="spec-value"><?= e($tx['payment_method'] ?? 'Automated') ?></span>
                </div>
                <div class="spec-item">
                    <span class="spec-label">Network Verification</span>
                    <span class="spec-badge pending">Under Review</span>
                </div>
            </div>
            <?php endif; ?>

            <div class="pulse-status">
                <div class="pulse-orb"></div>
                <span id="poll-label">Syncing verification status with network…</span>
            </div>

        <?php elseif ($tx_status === 'success' && !$signup_paid): ?>
            <!-- Admin approved TX but hasn't finalized account setup yet -->
            <div class="status-icon-wrapper success">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <h2>Payment Confirmed!</h2>
            <p class="description">Your payment has been successfully validated. Your dedicated terminal is being provisioned. You will be redirected immediately once configuration completes.</p>

            <!-- Progress Step Rail -->
            <div class="step-rail">
                <div class="step-point completed">
                    <div class="step-dot">&#10003;</div>
                    <div class="step-text">Registered</div>
                </div>
                <div class="step-point completed">
                    <div class="step-dot">&#10003;</div>
                    <div class="step-text">Verified</div>
                </div>
                <div class="step-point active">
                    <div class="step-dot">&bull;</div>
                    <div class="step-text">Provisioning</div>
                </div>
            </div>

            <?php if ($tx_id): ?>
            <div class="spec-sheet">
                <div class="spec-item">
                    <span class="spec-label">Transaction Reference</span>
                    <span class="spec-value">#<?= e($tx_id) ?></span>
                </div>
                <div class="spec-item">
                    <span class="spec-label">Settlement Amount</span>
                    <span class="spec-value">$<?= e(number_format((float)$tx['amount'], 2)) ?></span>
                </div>
                <div class="spec-item">
                    <span class="spec-label">Verification State</span>
                    <span class="spec-badge success">Payment Confirmed</span>
                </div>
            </div>
            <?php endif; ?>

            <div class="pulse-status">
                <div class="pulse-orb" style="background-color: var(--state-success);"></div>
                <span id="poll-label">Waiting for account activation…</span>
            </div>

        <?php elseif ($tx_status === 'failed'): ?>
            <!-- Payment rejected -->
            <div class="status-icon-wrapper failed">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                </svg>
            </div>
            <h2>Payment Verification Failed</h2>
            <p class="description">We were unable to verify this transaction against the network details provided. You can resubmit payment details below or contact support.</p>

            <?php if ($tx_id): ?>
            <div class="spec-sheet">
                <div class="spec-item">
                    <span class="spec-label">Transaction Reference</span>
                    <span class="spec-value">#<?= e($tx_id) ?></span>
                </div>
                <div class="spec-item">
                    <span class="spec-label">Resolution</span>
                    <span class="spec-badge failed">Unverified</span>
                </div>
            </div>
            <?php endif; ?>

            <form method="post" action="/signup_status.php?retry=1">
                <?= csrf_field() ?>
                <button type="submit" class="btn-action primary">Retry Payment</button>
            </form>
            <a href="/logout.php" class="btn-action secondary">Sign Out</a>

        <?php else: ?>
            <div class="status-icon-wrapper pending">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <h2>Account Initializing</h2>
            <p class="description">Your account profile is currently being prepared by the system.</p>
            <div class="pulse-status">
                <div class="pulse-orb"></div>
                <span id="poll-label">Checking configuration…</span>
            </div>
        <?php endif; ?>

    </div>

    <div class="status-footer text-center" style="text-align: center;">
        Automated status monitoring active &bull; Auto-refreshes seamlessly
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

</body>
</html>
