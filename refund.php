<?php
require_once __DIR__ . '/config.php';
login_required();

$db      = get_db();
$user_id = $_SESSION['user_id'];
$errors  = [];

// Ensure refund_method_options table exists with defaults
$db->exec("CREATE TABLE IF NOT EXISTS refund_method_options (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    is_visible TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

// Seed defaults if empty
$cnt = $db->query("SELECT COUNT(*) FROM refund_method_options")->fetchColumn();
if ($cnt == 0) {
    $db->exec("INSERT INTO refund_method_options (name, is_visible) VALUES ('PayPal',1),('Cash App',1),('Venmo',1)");
}

// Fetch visible "Other" sub-options
$other_opts_st = $db->query("SELECT name FROM refund_method_options WHERE is_visible=1 ORDER BY id");
$other_options = $other_opts_st->fetchAll(PDO::FETCH_COLUMN);

// Only show successful transactions for this user
$st = $db->prepare('SELECT id, amount, is_approved FROM user_trasaction WHERE user_id=? AND is_approved="success"');
$st->execute([$user_id]);
$transactions = $st->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $tx_id         = (int)($_POST['transactions'] ?? 0);
    $amount        = $_POST['amount'] ?? '';
    $reason        = trim($_POST['reason'] ?? '');
    $refund_method = trim($_POST['refund_method'] ?? '');
    $other_method  = trim($_POST['other_method'] ?? '');

    if (!$tx_id)         $errors[] = 'Transaction is required.';
    if (!$amount)        $errors[] = 'Amount is required.';
    if (!$reason)        $errors[] = 'Reason is required.';
    if (!$refund_method) $errors[] = 'Preferred refund method is required.';

    if ($tx_id) {
        $verify = $db->prepare('SELECT id FROM user_trasaction WHERE id=? AND user_id=? AND is_approved="success"');
        $verify->execute([$tx_id, $user_id]);
        if (!$verify->fetch()) $errors[] = 'Invalid transaction selected.';
    }

    if (!$errors) {
        $final_method = ($refund_method === 'Other' && $other_method) ? 'Other: ' . $other_method : $refund_method;
        // Add refund_method column if not yet present
        try { $db->exec("ALTER TABLE refund_refund ADD COLUMN refund_method VARCHAR(100) DEFAULT NULL"); } catch(Exception $e){}
        $db->prepare('INSERT INTO refund_refund (user_id, transactions_id, amount, reason, refund_method) VALUES (?,?,?,?,?)')
           ->execute([$user_id, $tx_id, $amount, $reason, $final_method]);
        redirect('/');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Request Refund</title>
    <link rel="stylesheet" href="/static/styles/refund.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>
    <div class="refund-container">
        <a href="/"> «Dashboard</a>
        <h2>Refund Request</h2>
        <p class="subtitle">Submit a request to refund a completed transaction</p>

        <?php if ($errors): ?>
        <div style="color:red"><ul><?php foreach($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul></div>
        <?php endif; ?>

        <form method="post" action="/refund.php" class="refund-form">
            <?= csrf_field() ?>

            <!-- User (hidden - same as Django form auto-filling from logged-in user) -->
            <input type="hidden" name="user" value="<?= $user_id ?>">

            <!-- Transaction -->
            <p>
                <label for="id_transactions">Transactions:</label><br>
                <select name="transactions" id="id_transactions">
                    <option value="">---------</option>
                    <?php foreach ($transactions as $t): ?>
                    <option value="<?= $t['id'] ?>" <?= (($_POST['transactions']??'')==$t['id'])?'selected':'' ?>>
                        Transaction #<?= $t['id'] ?> — $<?= e($t['amount']) ?> (<?= e($t['is_approved']) ?>)
                    </option>
                    <?php endforeach; ?>
                </select>
            </p>

            <!-- Amount -->
            <p>
                <label for="id_amount">Amount:</label><br>
                <input type="number" name="amount" id="id_amount" step="0.01"
                       value="<?= e($_POST['amount'] ?? '') ?>">
            </p>

            <!-- Reason -->
            <p>
                <label for="id_reason">Reason:</label><br>
                <textarea name="reason" id="id_reason" maxlength="500" rows="4"><?= e($_POST['reason'] ?? '') ?></textarea>
            </p>

            <!-- Preferred Refund Method -->
            <p>
                <label for="id_refund_method">Preferred Refund Method:</label><br>
                <select name="refund_method" id="id_refund_method" onchange="toggleOtherMethod(this.value)" required>
                    <option value="">---------</option>
                    <option value="Bank" <?= (($_POST['refund_method']??'')==='Bank')?'selected':'' ?>>Bank</option>
                    <option value="Crypto" <?= (($_POST['refund_method']??'')==='Crypto')?'selected':'' ?>>Crypto</option>
                    <?php if ($other_options): ?>
                    <option value="Other" <?= (($_POST['refund_method']??'')==='Other')?'selected':'' ?>>Other</option>
                    <?php endif; ?>
                </select>
            </p>

            <!-- Other sub-options (shown when "Other" is selected) -->
            <?php if ($other_options): ?>
            <div id="other-method-wrap" style="display:<?= (($_POST['refund_method']??'')==='Other')?'block':'none' ?>; margin-top:8px;">
                <label for="id_other_method">Select Option:</label><br>
                <select name="other_method" id="id_other_method">
                    <option value="">---------</option>
                    <?php foreach ($other_options as $opt): ?>
                    <option value="<?= e($opt) ?>" <?= (($_POST['other_method']??'')===$opt)?'selected':'' ?>><?= e($opt) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>

            <!-- Agreement -->
            <div class="form-check">
                <input type="checkbox" required>
                <span>I confirm that the information provided is correct</span>
            </div>

            <button type="submit" class="btn-submit">Submit Refund Request</button>

            <script>
            function toggleOtherMethod(val) {
                var wrap = document.getElementById('other-method-wrap');
                if (wrap) wrap.style.display = (val === 'Other') ? 'block' : 'none';
            }
            </script>
        </form>
    </div>
</body>
</html>
