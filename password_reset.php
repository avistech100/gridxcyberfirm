<?php
require_once __DIR__ . '/config.php';
$sent = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $email = trim($_POST['email'] ?? '');
    if (!$email) {
        $error = 'Email is required.';
    } else {
        // In production: generate token, send email
        // For now, just confirm the message (same UX as Django)
        $sent = true;
    }
}
?>
<head>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Rubik:ital@0;1&display=swap" rel="stylesheet">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Reset | GridX</title>
    <link rel="stylesheet" href="/static/styles/form.css">
</head>
<div class="auth-container">
    <a href="/login.php"> «Back to Login</a>
    <h2>Password Reset</h2>

    <?php if ($sent): ?>
    <p>We've emailed you instructions for setting your password. If you don't receive an email, please make sure you've entered the address you registered with, and check your spam folder.</p>
    <?php else: ?>
    <?php if ($error): ?><p style="color:red"><?= e($error) ?></p><?php endif; ?>
    <p>Forgotten your password? Enter your email address below, and we'll email instructions for setting a new one.</p>
    <form method="post">
        <?= csrf_field() ?>
        <div class="form-group">
            <label>Email address</label>
            <input type="email" name="email" required value="<?= e($_POST['email'] ?? '') ?>">
        </div>
        <button type="submit" class="btn">Reset my password</button>
    </form>
    <?php endif; ?>
</div>
