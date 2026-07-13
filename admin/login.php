<?php
require_once __DIR__ . '/../config.php';

// Already logged in as admin?
if (is_authenticated() && !empty($_SESSION['is_staff'])) {
    redirect('/admin/');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username && $password) {
        $db = get_db();
        $st = $db->prepare('SELECT * FROM user_gridxuser WHERE username = ? AND is_active = 1');
        $st->execute([$username]);
        $user = $st->fetch();

        if ($user && check_password($password, $user['password'])) {
            if ($user['is_staff'] || $user['is_superuser']) {
                $_SESSION['user_id']      = $user['id'];
                $_SESSION['username']     = $user['username'];
                $_SESSION['is_staff']     = (bool)$user['is_staff'];
                $_SESSION['is_superuser'] = (bool)$user['is_superuser'];

                // Update last_login
                $db->prepare('UPDATE user_gridxuser SET last_login=NOW() WHERE id=?')
                   ->execute([$user['id']]);

                redirect('/admin/');
            } else {
                $error = 'You do not have permission to access the admin panel.';
            }
        } else {
            $error = 'Please enter the correct username and password for a staff account. Note that both fields may be case-sensitive.';
        }
    } else {
        $error = 'This field is required.';
    }
}
?>
<!doctype html>
<html lang="en-us" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <meta name="robots" content="none, noarchive">
    <script>
        (function() {
            var mode = localStorage.getItem('jazzmin-theme-mode') || 'light';
            if (mode === 'auto') {
                mode = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
            }
            document.documentElement.setAttribute('data-bs-theme', mode);
        })();
    </script>
    <title>Log in | <?= SITE_TITLE ?></title>
    <link rel="stylesheet" href="/static/vendor/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="/static/vendor/adminlte/css/adminlte.min.css" id="adminlte-css">
    <link rel="stylesheet" id="jazzmin-theme" data-theme-base="/static/vendor/bootswatch">
    <link rel="stylesheet" href="/static/jazzmin/css/main.css">
    <link rel="shortcut icon" href="/static/vendor/adminlte/img/AdminLTELogo.png" type="image/png">
    <link href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700" rel="stylesheet">
</head>
<body class="login-page bg-body-secondary">
<div class="login-box">
    <div class="login-logo">
        <a href="/admin/">
            <img src="/static/vendor/adminlte/img/AdminLTELogo.png" alt="GridX Admin" width="60">
            <br>
            <b><?= APP_NAME ?></b>
        </a>
    </div>

    <div class="card">
        <div class="card-body login-card-body">
            <p class="login-box-msg">Sign in to start your session</p>

            <?php if ($error): ?>
            <div class="alert alert-danger"><?= e($error) ?></div>
            <?php endif; ?>

            <form method="post" action="/admin/login.php">
                <?= csrf_field() ?>
                <div class="input-group mb-3">
                    <input type="text" name="username" class="form-control" placeholder="Username"
                           value="<?= e($_POST['username'] ?? '') ?>" autofocus required>
                    <div class="input-group-text"><span class="fas fa-envelope"></span></div>
                </div>
                <div class="input-group mb-3">
                    <input type="password" name="password" class="form-control" placeholder="Password" required>
                    <div class="input-group-text"><span class="fas fa-lock"></span></div>
                </div>
                <div class="row">
                    <div class="col-8">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="remember" id="remember">
                            <label class="form-check-label" for="remember">Remember Me</label>
                        </div>
                    </div>
                    <div class="col-4">
                        <button type="submit" class="btn btn-primary btn-block w-100">Sign In</button>
                    </div>
                </div>
            </form>

            <p class="mb-1 mt-3">
                <a href="/admin/change_password.php">I forgot my password</a>
            </p>
        </div>
    </div>
</div>
<script src="/static/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="/static/vendor/adminlte/js/adminlte.min.js"></script>
</body>
</html>
