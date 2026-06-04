<?php
/**
 * GridX Setup Script
 * Run this ONCE after uploading to Hostinger to create your admin account.
 * DELETE this file after running it!
 *
 * Access: https://yourdomain.com/setup.php
 */

// Simple guard - change this token before uploading
define('SETUP_TOKEN', 'qwertyuiop00');

require_once __DIR__ . '/config.php';

$done   = false;
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token    = $_POST['token'] ?? '';
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($token !== SETUP_TOKEN) $errors[] = 'Invalid setup token.';
    if (!$username) $errors[] = 'Username required.';
    if (!$email)    $errors[] = 'Email required.';
    if (strlen($password) < 8) $errors[] = 'Password must be at least 8 characters.';

    if (!$errors) {
        try {
            $db = get_db();

            // Create tables
            $db->exec(file_get_contents(__DIR__ . '/database.sql'));

            // Create superuser
            $hash = hash_password($password);
            $st = $db->prepare('INSERT INTO user_gridxuser
                (username,email,password,is_staff,is_superuser,is_active,date_joined,balance,first_name,last_name)
                VALUES (?,?,?,1,1,1,NOW(),0,"","")
                ON DUPLICATE KEY UPDATE password=VALUES(password)');
            $st->execute([$username, $email, $hash]);

            $done = true;
        } catch (Exception $e) {
            $errors[] = 'Database error: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>GridX Setup</title>
    <style>
        body { font-family: sans-serif; max-width: 500px; margin: 60px auto; padding: 20px; }
        input { display:block; width:100%; padding:8px; margin:8px 0 16px; border:1px solid #ccc; border-radius:4px; }
        button { background:#007bff; color:#fff; border:none; padding:10px 24px; border-radius:4px; cursor:pointer; font-size:15px; }
        .error { color:red; }
        .success { color:green; background:#e8f5e9; padding:12px; border-radius:4px; }
        .warning { background:#fff3cd; padding:10px; border-radius:4px; margin-bottom:16px; }
    </style>
</head>
<body>
    <h1>GridX Setup</h1>

    <?php if ($done): ?>
    <div class="success">
        <strong>✅ Setup complete!</strong><br>
        Admin account created. You can now <a href="/admin/login.php">log in to the admin panel</a>.<br><br>
        <strong>⚠️ DELETE this setup.php file immediately!</strong>
    </div>
    <?php else: ?>

    <div class="warning">
        ⚠️ Run this only once. Delete this file after setup.
    </div>

    <?php if ($errors): ?>
    <div class="error"><ul><?php foreach($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul></div>
    <?php endif; ?>

    <form method="post">
        <label>Setup Token (from setup.php line 10)</label>
        <input type="text" name="token" required>

        <label>Admin Username</label>
        <input type="text" name="username" required>

        <label>Admin Email</label>
        <input type="email" name="email" required>

        <label>Admin Password (min 8 chars)</label>
        <input type="password" name="password" required>

        <button type="submit">Run Setup</button>
    </form>
    <?php endif; ?>
</body>
</html>
