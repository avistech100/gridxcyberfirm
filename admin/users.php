<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/admin_layout.php';
admin_required();
set_active_nav('users');

$db     = get_db();
$action = $_GET['action'] ?? 'list';
$id     = (int)($_GET['id'] ?? 0);
$errors = [];

// ── DELETE ─────────────────────────────────────────────────
if ($action === 'delete' && $id) {
    verify_csrf();
    $db->prepare('DELETE FROM user_gridxuser WHERE id=?')->execute([$id]);
    set_flash('success', 'User deleted successfully.');
    redirect('/admin/users.php');
}

// ── SAVE (add/edit) ────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($action, ['add','edit'])) {
    verify_csrf();
    $username   = trim($_POST['username'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name  = trim($_POST['last_name'] ?? '');
    $balance    = $_POST['balance'] ?? '0';
    $os         = $_POST['os'] ?? null;
    $is_staff   = isset($_POST['is_staff'])   ? 1 : 0;
    $is_superuser = isset($_POST['is_superuser']) ? 1 : 0;
    $is_active  = isset($_POST['is_active'])  ? 1 : 0;
    $password   = $_POST['password'] ?? '';
    $password2  = $_POST['password2'] ?? '';

    if (!$username) $errors[] = 'Username is required.';
    if (!$email)    $errors[] = 'Email is required.';

    if ($action === 'add') {
        if (!$password)                        $errors[] = 'Password is required.';
        if ($password !== $password2)          $errors[] = 'Passwords do not match.';
        // Check unique
        $chk = $db->prepare('SELECT id FROM user_gridxuser WHERE username=? OR email=?');
        $chk->execute([$username, $email]);
        if ($chk->fetch()) $errors[] = 'Username or email already exists.';
    }

    if (!$errors) {
        if ($action === 'add') {
            $hash = hash_password($password);
            $db->prepare('INSERT INTO user_gridxuser
                (username,email,first_name,last_name,password,balance,os,is_staff,is_superuser,is_active,date_joined)
                VALUES (?,?,?,?,?,?,?,?,?,?,NOW())')
               ->execute([$username,$email,$first_name,$last_name,$hash,$balance,$os ?: null,$is_staff,$is_superuser,$is_active]);
            set_flash('success', "User \"{$username}\" was added successfully.");
        } else {
            $fields = 'username=?,email=?,first_name=?,last_name=?,balance=?,os=?,is_staff=?,is_superuser=?,is_active=?';
            $params = [$username,$email,$first_name,$last_name,$balance,$os ?: null,$is_staff,$is_superuser,$is_active];
            if ($password) {
                if ($password !== $password2) { $errors[] = 'Passwords do not match.'; goto show_form; }
                $fields .= ',password=?';
                $params[] = hash_password($password);
            }
            $params[] = $id;
            $db->prepare("UPDATE user_gridxuser SET {$fields} WHERE id=?")->execute($params);
            set_flash('success', "User \"{$username}\" was changed successfully.");
        }
        redirect('/admin/users.php');
    }
}

show_form:
// ── FETCH ROW for edit ─────────────────────────────────────
$row = [];
if (in_array($action, ['edit','delete']) && $id) {
    $row = $db->prepare('SELECT * FROM user_gridxuser WHERE id=?');
    $row->execute([$id]);
    $row = $row->fetch();
    if (!$row) { set_flash('error','User not found.'); redirect('/admin/users.php'); }
}

// ── LIST ───────────────────────────────────────────────────
$search  = trim($_GET['q'] ?? '');
$page    = max(1,(int)($_GET['page'] ?? 1));
$per_page= 10;
$where   = $search ? "WHERE username LIKE ? OR email LIKE ?" : "";
$params  = $search ? ["%$search%","%$search%"] : [];
$total   = $db->prepare("SELECT COUNT(*) FROM user_gridxuser $where");
$total->execute($params);
$total   = (int)$total->fetchColumn();
$pg      = paginate($total, $per_page, $page);
$params_limit = array_merge($params, [$per_page, $pg['offset']]);
$users_st = $db->prepare("SELECT * FROM user_gridxuser $where ORDER BY id DESC LIMIT ? OFFSET ?");
$users_st->execute($params_limit);
$users = $users_st->fetchAll();

// ──────────────────────────────────────────────────────────
$page_title = ($action === 'add') ? 'Add user' : (($action === 'edit') ? 'Change user' : 'Select user to change');
admin_head($page_title);
admin_body_open('/admin/users.php');
admin_content_header($page_title, ['User' => '/admin/users.php', $page_title => '']);
?>

<?= show_flash() ?>

<?php if ($action === 'list'): ?>
<!-- LIST VIEW -->
<div class="card">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
            <h3 class="card-title">Users</h3>
            <a href="/admin/users.php?action=add" class="btn btn-success btn-sm">
                <i class="fas fa-plus me-1"></i> Add User
            </a>
        </div>
    </div>
    <div class="card-body">
        <!-- Search -->
        <form method="get" class="mb-3 d-flex gap-2">
            <input type="hidden" name="action" value="list">
            <input type="text" name="q" class="form-control form-control-sm w-25"
                   placeholder="Search users..." value="<?= e($search) ?>">
            <button class="btn btn-sm btn-outline-secondary" type="submit">Search</button>
            <?php if ($search): ?>
            <a href="/admin/users.php" class="btn btn-sm btn-outline-danger">Clear</a>
            <?php endif; ?>
        </form>

        <!-- Bulk action -->
        <form method="post" id="changelist-form">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="bulk_delete">
            <div class="mb-2 d-flex gap-2 align-items-center">
                <select name="bulk_action" class="form-select form-select-sm" style="width:auto">
                    <option value="">---------</option>
                    <option value="delete">Delete selected</option>
                </select>
                <button class="btn btn-sm btn-outline-secondary" type="submit">Run</button>
                <span class="text-muted small">0 of <?= $total ?> selected</span>
            </div>

        <table class="table table-sm table-hover table-bordered">
            <thead>
                <tr>
                    <th><input type="checkbox" id="select-all"></th>
                    <th><a href="?action=list&order=username">USER</a></th>
                    <th>Email</th>
                    <th>Balance</th>
                    <th>Staff</th>
                    <th>Active</th>
                    <th>Joined</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                <tr>
                    <td><input type="checkbox" name="ids[]" value="<?= $u['id'] ?>"></td>
                    <td>
                        <a href="/admin/users.php?action=edit&id=<?= $u['id'] ?>">
                            <?= e($u['username']) ?>
                        </a>
                    </td>
                    <td><?= e($u['email']) ?></td>
                    <td>$<?= e($u['balance']) ?></td>
                    <td><?= $u['is_staff'] ? '<i class="fas fa-check text-success"></i>' : '<i class="fas fa-times text-danger"></i>' ?></td>
                    <td><?= $u['is_active'] ? '<i class="fas fa-check text-success"></i>' : '<i class="fas fa-times text-danger"></i>' ?></td>
                    <td><?= e(substr($u['date_joined'],0,10)) ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (!$users): ?>
                <tr><td colspan="7" class="text-center text-muted">No users found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
        </form>

        <!-- Pagination -->
        <?php if ($pg['total_pages'] > 1): ?>
        <nav>
            <ul class="pagination pagination-sm">
                <?php for ($p=1; $p<=$pg['total_pages']; $p++): ?>
                <li class="page-item <?= $p===$page?'active':'' ?>">
                    <a class="page-link" href="?action=list&page=<?= $p ?>&q=<?= urlencode($search) ?>"><?= $p ?></a>
                </li>
                <?php endfor; ?>
            </ul>
        </nav>
        <?php endif; ?>
        <p class="text-muted small"><?= $total ?> user<?= $total!==1?'s':'' ?></p>
    </div>
</div>

<?php else: // ADD / EDIT FORM ?>
<div class="card card-primary card-outline">
    <div class="card-header">
        <h3 class="card-title"><?= e($page_title) ?></h3>
    </div>
    <div class="card-body">
        <?php if ($errors): ?>
        <div class="alert alert-danger">
            <ul class="mb-0"><?php foreach ($errors as $e_msg): ?><li><?= e($e_msg) ?></li><?php endforeach; ?></ul>
        </div>
        <?php endif; ?>

        <form method="post">
            <?= csrf_field() ?>
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Username *</label>
                        <input type="text" name="username" class="form-control"
                               value="<?= e($row['username'] ?? ($_POST['username'] ?? '')) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email *</label>
                        <input type="email" name="email" class="form-control"
                               value="<?= e($row['email'] ?? ($_POST['email'] ?? '')) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">First Name</label>
                        <input type="text" name="first_name" class="form-control"
                               value="<?= e($row['first_name'] ?? ($_POST['first_name'] ?? '')) ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Last Name</label>
                        <input type="text" name="last_name" class="form-control"
                               value="<?= e($row['last_name'] ?? ($_POST['last_name'] ?? '')) ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Balance</label>
                        <input type="number" name="balance" step="0.01" class="form-control"
                               value="<?= e($row['balance'] ?? ($_POST['balance'] ?? '0')) ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">OS</label>
                        <select name="os" class="form-select">
                            <option value="">---------</option>
                            <?php foreach (['And'=>'Android','IOS'=>'IOS','linux'=>'linux'] as $val=>$label): ?>
                            <option value="<?= $val ?>" <?= ($row['os']??'')===$val?'selected':'' ?>><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Password <?= $action==='edit'?'(leave blank to keep current)':' *' ?></label>
                        <input type="password" name="password" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password Confirmation</label>
                        <input type="password" name="password2" class="form-control">
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_staff" id="is_staff"
                                   <?= ($row['is_staff']??0)?'checked':'' ?>>
                            <label class="form-check-label" for="is_staff">Staff status</label>
                            <small class="form-text text-muted d-block">Designates whether the user can log into this admin site.</small>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_superuser" id="is_superuser"
                                   <?= ($row['is_superuser']??0)?'checked':'' ?>>
                            <label class="form-check-label" for="is_superuser">Superuser status</label>
                            <small class="form-text text-muted d-block">Designates that this user has all permissions.</small>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_active" id="is_active"
                                   <?= ($action==='add' || ($row['is_active']??0))?'checked':'' ?>>
                            <label class="form-check-label" for="is_active">Active</label>
                            <small class="form-text text-muted d-block">Unselect this instead of deleting accounts.</small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">Save</button>
                <button type="submit" name="save_add_another" class="btn btn-secondary">Save and add another</button>
                <a href="/admin/users.php" class="btn btn-outline-secondary">Cancel</a>
                <?php if ($action==='edit'): ?>
                <a href="/admin/users.php?action=delete&id=<?= $id ?>"
                   class="btn btn-danger ms-auto"
                   onclick="return confirm('Are you sure you want to delete this user?')">Delete</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<script>
document.getElementById('select-all')?.addEventListener('change', function() {
    document.querySelectorAll('input[name="ids[]"]').forEach(cb => cb.checked = this.checked);
});
</script>
<?php admin_footer(); ?>
