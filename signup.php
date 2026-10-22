<?php
require_once __DIR__ . '/config.php';

if (is_authenticated()) redirect('/dashboard.php');

// Check if self-registration is allowed
$allow_reg = get_site_setting('allow_self_registration', '0');
if ($allow_reg !== '1') {
    redirect('/login.php?reg=closed');
}

// ── Ensure user_services table exists ───────────────────────
$db = get_db();
try {
    $db->exec("CREATE TABLE IF NOT EXISTS user_services (
        id         INT AUTO_INCREMENT PRIMARY KEY,
        user_id    INT NOT NULL,
        service    VARCHAR(50) NOT NULL,
        UNIQUE KEY uq_user_service (user_id, service),
        FOREIGN KEY (user_id) REFERENCES user_gridxuser(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
} catch (Exception $e) {}

// ── Valid service keys ───────────────────────────────────────
const VALID_SERVICES = [
    'phone_files','location','social_apps','video_apps','live_recording',
    'remote_control','data_export','permissions_check',
    'whatsapp','facebook','calls','messages','gallery','browser','deleted'
];

// ── Carry services through GET → POST via hidden field ──────
$services_param = $_GET['services'] ?? $_POST['selected_services'] ?? '';
$services_raw   = array_filter(
    array_map('trim', explode(',', $services_param)),
    fn($s) => in_array($s, VALID_SERVICES, true)
);
$services_clean = array_values(array_unique($services_raw));

$errors  = [];
$success = false;

// ── Signup fee settings ──────────────────────────────────────
$fee_enabled = get_site_setting('signup_fee_enabled', '0') === '1';
$fee_amount  = (float)get_site_setting('signup_fee_amount', '50');

// ── Ensure signup columns exist ──────────────────────────────
try { $db->exec("ALTER TABLE user_gridxuser ADD COLUMN signup_paid  TINYINT(1) NOT NULL DEFAULT 0"); } catch (Exception $e) {}
try { $db->exec("ALTER TABLE user_gridxuser ADD COLUMN from_locate  TINYINT(1) NOT NULL DEFAULT 0"); } catch (Exception $e) {}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $username  = trim($_POST['username']  ?? '');
    $email     = trim($_POST['email']     ?? '');
    $password1 = $_POST['password1'] ?? '';
    $password2 = $_POST['password2'] ?? '';

    if (!$username)  $errors[] = 'Username is required.';
    if (!$email)     $errors[] = 'Email is required.';
    if (!$password1) $errors[] = 'Password is required.';
    if ($password1 !== $password2) $errors[] = 'The two password fields didn\'t match.';
    if (strlen($password1) < 8)    $errors[] = 'Password must be at least 8 characters.';

    if (!$errors) {
        $chk = $db->prepare('SELECT id FROM user_gridxuser WHERE username=? OR email=?');
        $chk->execute([$username, $email]);
        if ($chk->fetch()) {
            $errors[] = 'A user with that username or email already exists. <a href="/login.php">Log in instead</a>.';
        } else {
            $hash = hash_password($password1);

            if ($fee_enabled) {
                // Account locked until fee paid + admin approval
                $db->prepare('INSERT INTO user_gridxuser
                               (username,email,password,date_joined,is_active,dashboard_access,signup_paid,from_locate)
                               VALUES (?,?,?,NOW(),1,0,0,1)')
                   ->execute([$username, $email, $hash]);
            } else {
                $db->prepare('INSERT INTO user_gridxuser
                               (username,email,password,date_joined,is_active)
                               VALUES (?,?,?,NOW(),1)')
                   ->execute([$username, $email, $hash]);
            }

            $new_id = (int)$db->lastInsertId();

            // ── Store selected services ──────────────────────
            if ($new_id && !empty($services_clean)) {
                $svc_stmt = $db->prepare(
                    'INSERT IGNORE INTO user_services (user_id, service) VALUES (?, ?)'
                );
                foreach ($services_clean as $svc) {
                    $svc_stmt->execute([$new_id, $svc]);
                }
            }

            if ($fee_enabled && $new_id) {
                // Log user in for checkout flow
                session_regenerate_id(true);
                $_SESSION['user_id'] = $new_id;

                // Create a signup-fee transaction
                $db->prepare('INSERT INTO user_trasaction
                               (user_id, amount, is_approved, date)
                               VALUES (?, ?, "initialize", NOW())')
                   ->execute([$new_id, $fee_amount]);
                $tx_id = (int)$db->lastInsertId();

                redirect('/checkout.php?id=' . $tx_id . '&signup=1');
            }

            redirect('/login.php');
        }
    }
}
?>
<head>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Rubik:ital@0;1&display=optional" rel="stylesheet">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up | GridX</title>
    <link rel="stylesheet" href="<?= asset_url('/static/styles/form.css') ?>">
</head>
<div class="auth-container">
    <a href="/"> «Dashboard</a>
    <h2>Create Your Account</h2>

    <?php if ($errors): ?>
    <div style="color:red">
        <ul><?php foreach($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul>
    </div>
    <?php endif; ?>

    <?php if (!empty($services_clean)): ?>
    <div style="background:rgba(79,142,247,.12);border:1px solid rgba(79,142,247,.3);border-radius:10px;padding:10px 14px;margin-bottom:18px;font-size:.85rem;color:#4f8ef7;">
        <strong>Selected services:</strong>
        <?= e(implode(', ', array_map(fn($s) => ucwords(str_replace('_',' ',$s)), $services_clean))) ?>
    </div>
    <?php endif; ?>

    <?php if ($fee_enabled): ?>
    <div style="background:rgba(255,193,7,.13);border:1px solid rgba(255,193,7,.5);border-radius:10px;padding:12px 16px;margin-bottom:20px;font-size:.9rem;color:#b8860b;">
        <strong>⚠ One-time Signup Fee: $<?= number_format($fee_amount, 2) ?></strong>
        <p style="margin:4px 0 0;font-size:.82rem;">After filling in your details below, you will be directed to complete a one-time payment of <strong>$<?= number_format($fee_amount, 2) ?></strong> to activate your account.</p>
    </div>
    <?php endif; ?>

    <form method="post">
        <?= csrf_field() ?>
        <!-- Carry selected services through POST -->
        <input type="hidden" name="selected_services" value="<?= e(implode(',', $services_clean)) ?>">

        <div class="form-group">
            <label>Username</label>
            <input type="text" name="username" required value="<?= e($_POST['username'] ?? '') ?>">
        </div>
        <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" required value="<?= e($_POST['email'] ?? '') ?>">
        </div>
        <div class="form-group">
            <label>Password</label>
            <input type="password" name="password1" required>
        </div>
        <div class="form-group">
            <label>Password confirmation</label>
            <input type="password" name="password2" required>
        </div>

        <button type="submit" class="btn">Sign Up</button>
    </form>

    <p>Already have an account? <a href="/login.php">Login here</a></p>
</div>
