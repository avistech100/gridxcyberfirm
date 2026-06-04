<?php
require_once __DIR__ . '/config.php';
login_required();

$db      = get_db();
$user_id = $_SESSION['user_id'];
$errors  = [];

// Only show successful transactions for this user (same as Django queryset filter)
$st = $db->prepare('SELECT id, amount, is_approved FROM user_trasaction WHERE user_id=? AND is_approved="success"');
$st->execute([$user_id]);
$transactions = $st->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $tx_id  = (int)($_POST['transactions'] ?? 0);
    $amount = $_POST['amount'] ?? '';
    $reason = trim($_POST['reason'] ?? '');

    if (!$tx_id)  $errors[] = 'Transaction is required.';
    if (!$amount) $errors[] = 'Amount is required.';
    if (!$reason) $errors[] = 'Reason is required.';

    // Verify the transaction belongs to this user and is successful
    if ($tx_id) {
        $verify = $db->prepare('SELECT id FROM user_trasaction WHERE id=? AND user_id=? AND is_approved="success"');
        $verify->execute([$tx_id, $user_id]);
        if (!$verify->fetch()) $errors[] = 'Invalid transaction selected.';
    }

    if (!$errors) {
        $db->prepare('INSERT INTO refund_refund (user_id, transactions_id, amount, reason) VALUES (?,?,?,?)')
           ->execute([$user_id, $tx_id, $amount, $reason]);
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

            <!-- Agreement -->
            <div class="form-check">
                <input type="checkbox" required>
                <span>I confirm that the information provided is correct</span>
            </div>

            <button type="submit" class="btn-submit">Submit Refund Request</button>
        </form>
    </div>
</body>
</html>
