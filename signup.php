<?php
require_once __DIR__ . '/config.php';

if (is_authenticated()) redirect('/dashboard.php');

// Check if self-registration is allowed
$allow_reg = get_site_setting('allow_self_registration', '0');
if ($allow_reg !== '1') {
    // Redirect to login with a notice
    redirect('/login.php?reg=closed');
}

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $username  = trim($_POST['username'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $password1 = $_POST['password1'] ?? '';
    $password2 = $_POST['password2'] ?? '';

    if (!$username) $errors[] = 'Username is required.';
    if (!$email)    $errors[] = 'Email is required.';
    if (!$password1) $errors[] = 'Password is required.';
    if ($password1 !== $password2) $errors[] = 'The two password fields didn\'t match.';
    if (strlen($password1) < 8) $errors[] = 'Password must be at least 8 characters.';

    if (!$errors) {
        $db = get_db();
        $chk = $db->prepare('SELECT id FROM user_gridxuser WHERE username=? OR email=?');
        $chk->execute([$username,$email]);
        if ($chk->fetch()) {
            $errors[] = 'A user with that username or email already exists.';
        } else {
            $hash = hash_password($password1);
            $db->prepare('INSERT INTO user_gridxuser (username,email,password,date_joined,is_active)
                          VALUES (?,?,?,NOW(),1)')
               ->execute([$username,$email,$hash]);
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
    <link rel="stylesheet" href="/static/styles/form.css">
</head>
<div class="auth-container">
    <a href="/"> «Dashboard</a>
    <h2>Create Your Account</h2>

    <?php if ($errors): ?>
    <div style="color:red">
        <ul><?php foreach($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul>
    </div>
    <?php endif; ?>

    <form method="post">
        <?= csrf_field() ?>

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
