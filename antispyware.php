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
    <style>
        :root {
            --primary: #2563eb;
            --primary-hover: #1d4ed8;
            --primary-light: #eff6ff;
            --primary-border: #bfdbfe;
            --dark: #0f172a;
            --navy: #1e293b;
            --text: #1e293b;
            --text-secondary: #475569;
            --text-muted: #64748b;
            --border: #e2e8f0;
            --border-hover: #cbd5e1;
            --bg: #f8fafc;
            --card-bg: #ffffff;
            --success-bg: #f0fdf4;
            --success-border: #bbf7d0;
            --success-text: #166534;
            --alert-bg: #fef2f2;
            --alert-border: #fecaca;
            --alert-text: #991b1b;
            --radius-sm: 8px;
            --radius-md: 12px;
            --radius-lg: 16px;
            --shadow-sm: 0 1px 2px rgba(0, 0, 0, 0.04);
            --shadow-md: 0 4px 12px rgba(15, 23, 42, 0.06);
            --shadow-hover: 0 8px 20px rgba(37, 99, 235, 0.12);
        }

        *, *::before, *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Rubik', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            line-height: 1.5;
            -webkit-font-smoothing: antialiased;
        }

        /* Top Navigation */
        .as-nav {
            background: var(--navy);
            height: 56px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 1.5rem;
            position: sticky;
            top: 0;
            z-index: 50;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        }

        .as-nav-back {
            color: #cbd5e1;
            text-decoration: none;
            font-size: 0.88rem;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 10px;
            border-radius: var(--radius-sm);
            transition: all 0.15s ease;
        }

        .as-nav-back:hover {
            background: rgba(255, 255, 255, 0.1);
            color: #ffffff;
        }

        .as-nav-title {
            color: #ffffff;
            font-size: 0.95rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
            letter-spacing: 0.01em;
        }

        .as-nav-title i {
            color: #60a5fa;
        }

        /* Main Container */
        .as-container {
            max-width: 960px;
            margin: 0 auto;
            padding: 1.5rem 1rem 3rem;
        }

        /* Compact Header / Security Banner */
        .as-header-card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-sm);
            padding: 1rem 1.25rem;
            margin-bottom: 1.25rem;
            display: grid;
            grid-template-columns: 1fr auto 1fr;
            align-items: center;
            gap: 1.25rem;
        }

        .as-alert-col {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .as-pill {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 0.72rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: var(--alert-text);
            background: var(--alert-bg);
            border: 1px solid var(--alert-border);
            padding: 2px 8px;
            border-radius: 20px;
            width: fit-content;
        }

        .as-title {
            font-size: 1.15rem;
            font-weight: 700;
            color: var(--dark);
            letter-spacing: -0.01em;
        }

        .as-desc {
            font-size: 0.85rem;
            color: var(--text-secondary);
            line-height: 1.4;
        }

        .as-header-divider {
            width: 1px;
            height: 100%;
            min-height: 48px;
            background: var(--border);
        }

        .as-refund-col {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            background: var(--success-bg);
            border: 1px solid var(--success-border);
            border-radius: var(--radius-sm);
            padding: 0.75rem 0.9rem;
        }

        .as-refund-icon {
            font-size: 1.1rem;
            color: var(--success-text);
            margin-top: 1px;
            flex-shrink: 0;
        }

        .as-refund-content {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .as-refund-title {
            font-size: 0.84rem;
            font-weight: 700;
            color: var(--success-text);
        }

        .as-refund-desc {
            font-size: 0.78rem;
            color: #15803d;
            line-height: 1.35;
        }

        /* Error message */
        .as-error-box {
            background: var(--alert-bg);
            border: 1px solid var(--alert-border);
            color: var(--alert-text);
            padding: 0.7rem 1rem;
            border-radius: var(--radius-sm);
            margin-bottom: 1rem;
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* Section Heading */
        .as-section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 0.85rem;
        }

        .as-section-title {
            font-size: 0.95rem;
            font-weight: 700;
            color: var(--dark);
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }

        .as-section-hint {
            font-size: 0.8rem;
            color: var(--text-muted);
        }

        /* Plan Cards Grid */
        .as-plans-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 0.75rem;
            margin-bottom: 1.25rem;
        }

        .as-plan-card {
            background: var(--card-bg);
            border: 1.5px solid var(--border);
            border-radius: var(--radius-md);
            padding: 1.15rem 0.85rem 1rem;
            text-align: center;
            cursor: pointer;
            position: relative;
            transition: border-color 0.15s ease, box-shadow 0.15s ease, transform 0.15s ease, background 0.15s ease;
            user-select: none;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            min-height: 180px;
        }

        .as-plan-card:hover {
            border-color: var(--border-hover);
            box-shadow: var(--shadow-sm);
            transform: translateY(-2px);
        }

        .as-plan-card.selected {
            border-color: var(--primary);
            background: var(--primary-light);
            box-shadow: var(--shadow-hover);
        }

        .as-card-badge {
            position: absolute;
            top: -10px;
            left: 50%;
            transform: translateX(-50%);
            font-size: 0.65rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            padding: 2px 8px;
            border-radius: 20px;
            white-space: nowrap;
        }

        .as-card-badge.popular {
            background: #2563eb;
            color: #ffffff;
        }

        .as-card-badge.best-deal {
            background: #166534;
            color: #ffffff;
        }

        .as-plan-radio {
            width: 18px;
            height: 18px;
            border-radius: 50%;
            border: 1.5px solid var(--border);
            margin: 0 auto 0.6rem;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.15s ease;
        }

        .as-plan-card.selected .as-plan-radio {
            border-color: var(--primary);
            background: var(--primary);
        }

        .as-plan-radio::after {
            content: '';
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: #ffffff;
            opacity: 0;
            transition: opacity 0.15s ease;
        }

        .as-plan-card.selected .as-plan-radio::after {
            opacity: 1;
        }

        .as-card-price {
            font-size: 2.1rem;
            font-weight: 800;
            color: var(--dark);
            line-height: 1;
            letter-spacing: -0.03em;
            margin-bottom: 0.35rem;
            display: flex;
            align-items: baseline;
            justify-content: center;
            gap: 2px;
        }

        .as-currency {
            font-size: 1.15rem;
            font-weight: 600;
            color: var(--text-secondary);
        }

        .as-plan-card.selected .as-card-price {
            color: var(--primary);
        }

        .as-plan-card.selected .as-currency {
            color: var(--primary);
        }

        .as-card-label {
            font-size: 0.8rem;
            font-weight: 500;
            color: var(--text-muted);
            margin-bottom: 0.65rem;
            text-transform: capitalize;
        }

        .as-plan-card.selected .as-card-label {
            color: var(--text-secondary);
        }

        .as-card-desc {
            font-size: 0.72rem;
            color: var(--text-muted);
            line-height: 1.35;
        }

        /* Action Button */
        .as-submit-btn {
            width: 100%;
            padding: 0.9rem 1.25rem;
            background: var(--primary);
            color: #ffffff;
            border: none;
            border-radius: var(--radius-sm);
            font-family: inherit;
            font-size: 0.98rem;
            font-weight: 600;
            cursor: pointer;
            box-shadow: var(--shadow-sm);
            transition: all 0.15s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-bottom: 1.25rem;
        }

        .as-submit-btn:not(:disabled):hover {
            background: var(--primary-hover);
            box-shadow: 0 4px 14px rgba(37, 99, 235, 0.3);
        }

        .as-submit-btn:disabled {
            background: #94a3b8;
            cursor: not-allowed;
            opacity: 0.7;
            box-shadow: none;
        }

        /* Trust Strip */
        .as-trust-strip {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 1.5rem;
            flex-wrap: wrap;
            padding: 0.5rem 0;
        }

        .as-trust-item {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 0.78rem;
            color: var(--text-muted);
            font-weight: 500;
        }

        .as-trust-item i {
            color: #10b981;
            font-size: 0.85rem;
        }

        /* Responsive Breakpoints */
        @media (max-width: 860px) {
            .as-header-card {
                grid-template-columns: 1fr;
                gap: 0.85rem;
            }
            .as-header-divider {
                display: none;
            }
            .as-plans-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        @media (max-width: 580px) {
            .as-container {
                padding: 1rem 0.85rem 2rem;
            }
            .as-plans-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 0.6rem;
            }
            .as-plan-card {
                padding: 1rem 0.65rem 0.85rem;
                min-height: 160px;
            }
            .as-card-price {
                font-size: 1.3rem;
            }
            .as-trust-strip {
                flex-direction: column;
                gap: 0.5rem;
                align-items: center;
            }
        }

        @media (max-width: 360px) {
            .as-plans-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

<nav class="as-nav">
    <a href="/dashboard.php" class="as-nav-back"><i class="fa-solid fa-arrow-left"></i> Dashboard</a>
    <span class="as-nav-title"><i class="fa-solid fa-shield-virus"></i> Antispyware Protection</span>
    <span style="width:70px;"></span>
</nav>

<div class="as-container">

    <!-- Compact Security Notice & Refund Guarantee Header -->
    <div class="as-header-card">
        <div class="as-alert-col">
            <div class="as-pill"><i class="fa-solid fa-triangle-exclamation"></i> Security Notice</div>
            <h1 class="as-title">Protect Your Device</h1>
            <p class="as-desc">Antispyware activity has been detected. Activate protection now.</p>
        </div>
        <div class="as-header-divider"></div>
        <div class="as-refund-col">
            <div class="as-refund-icon"><i class="fa-solid fa-shield-check"></i></div>
            <div class="as-refund-content">
                <span class="as-refund-title">Instant Refund Guarantee</span>
                <p class="as-refund-desc">Not satisfied with your subscription? You can request a full refund after your subscription is confirmed.</p>
            </div>
        </div>
    </div>

    <?php if (!empty($error)): ?>
    <div class="as-error-box"><i class="fa-solid fa-triangle-exclamation"></i> <?= e($error) ?></div>
    <?php endif; ?>

    <!-- Plans Form -->
    <form method="post" action="/antispyware.php" id="planForm">
        <?= csrf_field() ?>
        <input type="hidden" name="amount"     id="hidAmount"   value="">
        <input type="hidden" name="plan_label" id="hidLabel"    value="">
        <input type="hidden" name="duration"   id="hidDuration" value="">

        <div class="as-section-header">
            <h2 class="as-section-title">Select a Protection Plan</h2>
            <span class="as-section-hint">Choose duration to continue</span>
        </div>

        <div class="as-plans-grid">
            <?php foreach ($plans as $plan): ?>
            <div class="as-plan-card" onclick="selectPlan(<?= $plan['amount'] ?>, '<?= e($plan['label']) ?>', '<?= e($plan['duration']) ?>', this)">
                <?php if ($plan['badge']): ?>
                <span class="as-card-badge <?= strtolower(str_replace(' ', '-', $plan['badge'])) ?>">
                    <?= e($plan['badge']) ?>
                </span>
                <?php endif; ?>
                <div>
                    <div class="as-plan-radio"></div>
                    <div class="as-card-price"><span class="as-currency">$</span><?= number_format($plan['amount']) ?></div>
                    <div class="as-card-label"><?= e($plan['label']) ?></div>
                </div>
                <div class="as-card-desc"><?= e($plan['desc']) ?></div>
            </div>
            <?php endforeach; ?>
        </div>

        <button type="submit" class="as-submit-btn" id="subscribeBtn" disabled>
            <i class="fa-solid fa-lock"></i> Select a Plan to Continue
        </button>
    </form>

    <!-- Trust indicators -->
    <div class="as-trust-strip">
        <span class="as-trust-item"><i class="fa-solid fa-lock"></i> 256-Bit SSL Encrypted Payment</span>
        <span class="as-trust-item"><i class="fa-solid fa-bolt"></i> Instant Protection Activation</span>
        <span class="as-trust-item"><i class="fa-solid fa-rotate-left"></i> Full Refund Eligible</span>
    </div>

</div>

<script>
function selectPlan(amount, label, duration, el) {
    document.querySelectorAll('.as-plan-card').forEach(p => p.classList.remove('selected'));
    el.classList.add('selected');
    document.getElementById('hidAmount').value   = amount;
    document.getElementById('hidLabel').value    = label;
    document.getElementById('hidDuration').value = duration;
    const btn = document.getElementById('subscribeBtn');
    btn.disabled = false;
    btn.innerHTML = `<i class="fa-solid fa-shield-check"></i> Subscribe – $${amount.toLocaleString()} (${label})`;
}
</script>
</body>
</html>
