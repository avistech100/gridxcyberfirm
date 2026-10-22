<?php
require_once __DIR__ . '/config.php';

if (is_authenticated()) redirect('/dashboard.php');
$allow_reg  = get_site_setting('allow_self_registration', '0');
$reg_closed = isset($_GET['reg']) && $_GET['reg'] === 'closed';

$error = '';
$next  = $_GET['next'] ?? '/dashboard.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username && $password) {
        $db = get_db();
        $st = $db->prepare('SELECT * FROM user_gridxuser WHERE (username = ? OR email = ?) AND is_active = 1');
        $st->execute([$username, $username]);
        $user = $st->fetch();

        if ($user && check_password($password, $user['password'])) {
            $_SESSION['user_id']      = $user['id'];
            $_SESSION['username']     = $user['username'];
            $_SESSION['is_staff']     = (bool)$user['is_staff'];
            $_SESSION['is_superuser'] = (bool)$user['is_superuser'];
            $db->prepare('UPDATE user_gridxuser SET last_login=NOW() WHERE id=?')->execute([$user['id']]);

            // Users from /locate who haven't been approved yet → signup status page
            if (empty($user['dashboard_access']) && !empty($user['from_locate']) && empty($user['signup_paid'])) {
                $stx = $db->prepare('SELECT id FROM user_trasaction WHERE user_id=? ORDER BY id DESC LIMIT 1');
                $stx->execute([$user['id']]);
                $ltx = $stx->fetch();
                $qs  = $ltx ? '?tx_id=' . (int)$ltx['id'] : '';
                redirect('/signup_status.php' . $qs);
            }

            redirect($next);
        } else {
            $error = 'Please enter a correct username and password.';
        }
    } else {
        $error = 'This field is required.';
    }
}
?>
<?php // ---- exact copy of Django login template with template tags replaced ---- ?>
<head>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Rubik:ital@0;1&display=optional" rel="stylesheet">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | GridX</title>
    <link rel="stylesheet" href="<?= asset_url('/static/styles/form.css') ?>">
</head>
<div class="auth-container">
    <a href="/"> «Dashboard</a>
    <h2>Login to Your Dashboard</h2>

    <?php if ($error): ?>
    <p style="color:red"><?= e($error) ?></p>
    <?php endif; ?>

    <form method="post">
        <?= csrf_field() ?>

        <div class="form-group">
            <label for="id_username">Username:</label>
            <input type="text" name="username" id="id_username" autofocus required
                   value="<?= e($_POST['username'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label for="id_password">Password:</label>
            <input type="password" name="password" id="id_password" required>
        </div>

        <div>
            <button type="submit" class="btn">Login</button>
            <a href="/password_reset.php">Forgotten password</a>
        </div>
    </form>

    <?php if ($reg_closed): ?>
    <p style="color:#e53935; font-size:0.88rem;">Account registration is currently closed. Please contact an administrator.</p>
    <?php elseif ($allow_reg === '1'): ?>
    <p>Don't have an account? <a href="/signup.php">Sign up here</a></p>
    <?php endif; ?>
</div>
