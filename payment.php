<?php
require_once __DIR__ . '/config.php';
login_required();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $amount = $_POST['amount'] ?? '';
    if (!$amount) {
        $error = 'Plan not selected';
    } else {
        $db = get_db();
        $user_id = $_SESSION['user_id'];

        // get_or_create transaction
        $st = $db->prepare('SELECT id FROM user_trasaction WHERE user_id=? AND amount=? AND is_approved="initialize" LIMIT 1');
        $st->execute([$user_id, $amount]);
        $existing = $st->fetch();

        if ($existing) {
            $tx_id = $existing['id'];
        } else {
            $db->prepare('INSERT INTO user_trasaction (user_id,amount,is_approved,gift_type) VALUES (?,?,"initialize","failed")')
               ->execute([$user_id, $amount]);
            $tx_id = $db->lastInsertId();
        }
        redirect('/checkout.php?id=' . $tx_id);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Rubik:ital@0;1&display=swap" rel="stylesheet">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Darkweb Data Upload</title>
    <link rel="stylesheet" href="/static/styles/payment.css">
</head>
<body>
<a href="/dashboard.php"> «Dashboard</a>
<div class="container">
    <h2>GRIDX DATA UPLOAD</h2>

    <?php if (!empty($error)): ?><p style="color:red"><?= e($error) ?></p><?php endif; ?>

    <form method="post" action="/payment.php">
        <?= csrf_field() ?>
        <table>
            <thead>
                <tr>
                    <th>Action</th>
                    <th>File Size (10GB–20GB)</th>
                    <th>Verification Amount</th>
                    <th>Verification Time</th>
                    <th>Data Upload Speed</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $plans = [
                    [50,'7–8 GB','50 USD','One Hour','2.00 mb/s'],
                    [100,'8–9 GB','100 USD','One Hour','2.80 mb/s'],
                    [200,'9–10 GB','200 USD','One Hour','3.00 mb/s'],
                    [500,'10–11 GB','500 USD','One Hour','3.06 mb/s'],
                    [600,'11–12 GB','600 USD','One Hour','3.33 mb/s'],
                    [700,'12–13 GB','700 USD','One Hour','3.61 mb/s'],
                    [800,'13–14 GB','800 USD','One Hour','3.89 mb/s'],
                    [900,'14–15 GB','900 USD','One Hour','4.17 mb/s'],
                    [1000,'15–16 GB','1000 USD','One Hour','4.44 mb/s'],
                    [1100,'16–17 GB','1100 USD','One Hour','4.72 mb/s'],
                    [1200,'17–18 GB','1200 USD','One Hour','5 mb/s'],
                    [1300,'18–19 GB','1300 USD','One Hour','5.28 mb/s'],
                ];
                foreach ($plans as $p): ?>
                <tr>
                    <td><input type="radio" name="amount" value="<?= $p[0] ?>"></td>
                    <td><?= $p[1] ?></td>
                    <td><?= $p[2] ?></td>
                    <td><?= $p[3] ?></td>
                    <td><?= $p[4] ?></td>
                </tr>
                <?php endforeach; ?>
                <tr class="disabled">
                    <td><input type="radio" disabled></td>
                    <td>19–20 GB</td>
                    <td>Identity verification required</td>
                    <td>—</td>
                    <td>—</td>
                </tr>
                <tr class="disabled">
                    <td><input type="radio" disabled></td>
                    <td>20 GB+</td>
                    <td>Identity verification required</td>
                    <td>—</td>
                    <td>—</td>
                </tr>
            </tbody>
        </table>
        <button type="submit">Proceed to checkout</button>
    </form>
</div>
</body>
</html>
