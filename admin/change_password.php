<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/admin_layout.php';
admin_required();

$db     = get_db();
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $old_pass = $_POST['old_password'] ?? '';
    $new_pass = $_POST['new_password1'] ?? '';
    $confirm  = $_POST['new_password2'] ?? '';

    $user = $db->prepare('SELECT * FROM user_gridxuser WHERE id=?');
    $user->execute([$_SESSION['user_id']]);
    $user = $user->fetch();

    if (!check_password($old_pass, $user['password'])) {
        $errors[] = 'Your old password was entered incorrectly.';
    }
    if (strlen($new_pass) < 8) $errors[] = 'Password must be at least 8 characters.';
    if ($new_pass !== $confirm) $errors[] = 'The two password fields didn\'t match.';

    if (!$errors) {
        $db->prepare('UPDATE user_gridxuser SET password=? WHERE id=?')
           ->execute([hash_password($new_pass), $_SESSION['user_id']]);
        $success = true;
    }
}

admin_head('Change Password');
admin_body_open();
admin_content_header('Change Password', ['Change Password' => '']);
?>
<?= show_flash() ?>

<div class="card card-primary card-outline" style="max-width:500px">
    <div class="card-header"><h3 class="card-title">Change Password</h3></div>
    <div class="card-body">
        <?php if ($success): ?>
        <div class="alert alert-success">Your password has been changed successfully.</div>
        <?php endif; ?>
        <?php if ($errors): ?>
        <div class="alert alert-danger"><ul class="mb-0"><?php foreach($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul></div>
        <?php endif; ?>

        <form method="post">
            <?= csrf_field() ?>
            <div class="mb-3">
                <label class="form-label">Old password</label>
                <input type="password" name="old_password" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">New password</label>
                <input type="password" name="new_password1" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">New password confirmation</label>
                <input type="password" name="new_password2" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary">Change my password</button>
        </form>
    </div>
</div>
<?php admin_footer(); ?>
