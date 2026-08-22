<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/admin_layout.php';
admin_required();
set_active_nav('users');

$db     = get_db();
try {
    $db->exec("ALTER TABLE user_gridxuser ADD COLUMN dashboard_access TINYINT(1) NOT NULL DEFAULT 1");
} catch (Exception $e) {}
try {
    $db->exec("ALTER TABLE user_gridxuser ADD COLUMN refund_access TINYINT(1) NOT NULL DEFAULT 0");
} catch (Exception $e) {}

$action = $_GET['action'] ?? ($_POST['action'] ?? 'list');
$id     = (int)($_GET['id'] ?? ($_POST['id'] ?? 0));
$errors = [];

// ── TOGGLE DASHBOARD ACCESS (Single User) ───────────────────
if ($action === 'toggle_dashboard' && $id) {
    verify_csrf();
    $db->prepare('UPDATE user_gridxuser SET dashboard_access = NOT dashboard_access WHERE id=?')->execute([$id]);
    set_flash('success', 'Dashboard access status updated.');
    $page = (int)($_GET['page'] ?? 1);
    redirect('/admin/users.php' . ($page > 1 ? '?page=' . $page : ''));
}

// ── MASS TOGGLE DASHBOARD ACCESS (All Users) ─────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'mass_toggle_dashboard') {
    verify_csrf();
    $target_state = isset($_POST['target_state']) ? (int)$_POST['target_state'] : 0;
    $db->prepare('UPDATE user_gridxuser SET dashboard_access = ?')->execute([$target_state]);
    $msg = $target_state ? 'Dashboard access mass-enabled for all accounts.' : 'Dashboard access mass-disabled for all accounts.';
    set_flash('success', $msg);
    redirect('/admin/users.php');
}

// ── TOGGLE REFUND ACCESS (Single User) ──────────────────────
if ($action === 'toggle_refund' && $id) {
    verify_csrf();
    $db->prepare('UPDATE user_gridxuser SET refund_access = NOT refund_access WHERE id=?')->execute([$id]);
    set_flash('success', 'Refund access status updated.');
    $page = (int)($_GET['page'] ?? 1);
    redirect('/admin/users.php' . ($page > 1 ? '?page=' . $page : ''));
}

// ── MASS TOGGLE REFUND ACCESS (All Users) ────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'mass_toggle_refund') {
    verify_csrf();
    $target_state = isset($_POST['target_state']) ? (int)$_POST['target_state'] : 0;
    $db->prepare('UPDATE user_gridxuser SET refund_access = ?')->execute([$target_state]);
    $msg = $target_state ? 'Refund access mass-enabled for all accounts.' : 'Refund access mass-disabled for all accounts.';
    set_flash('success', $msg);
    redirect('/admin/users.php');
}

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
    $dashboard_access = isset($_POST['dashboard_access']) ? 1 : 0;
    $refund_access    = isset($_POST['refund_access'])    ? 1 : 0;
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
                (username,email,first_name,last_name,password,balance,os,is_staff,is_superuser,is_active,dashboard_access,refund_access,date_joined)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?,NOW())')
               ->execute([$username,$email,$first_name,$last_name,$hash,$balance,$os ?: null,$is_staff,$is_superuser,$is_active,$dashboard_access,$refund_access]);
            set_flash('success', "User \"{$username}\" was added successfully.");
        } else {
            $fields = 'username=?,email=?,first_name=?,last_name=?,balance=?,os=?,is_staff=?,is_superuser=?,is_active=?,dashboard_access=?,refund_access=?';
            $params = [$username,$email,$first_name,$last_name,$balance,$os ?: null,$is_staff,$is_superuser,$is_active,$dashboard_access,$refund_access];
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

// ── Handle popup settings save ─────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_popup_settings']) && $id) {
    verify_csrf();
    $db->exec("CREATE TABLE IF NOT EXISTS user_popup_settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL UNIQUE,
        antispyware ENUM('on','off','default') NOT NULL DEFAULT 'default',
        storage     ENUM('on','off','default') NOT NULL DEFAULT 'default',
        FOREIGN KEY (user_id) REFERENCES user_gridxuser(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    $as_val = in_array($_POST['popup_antispyware']??'', ['on','off','default']) ? $_POST['popup_antispyware'] : 'default';
    $st_val = in_array($_POST['popup_storage']??'', ['on','off','default']) ? $_POST['popup_storage'] : 'default';
    $db->prepare('INSERT INTO user_popup_settings (user_id, antispyware, storage) VALUES (?,?,?)
                  ON DUPLICATE KEY UPDATE antispyware=?, storage=?')
       ->execute([$id, $as_val, $st_val, $as_val, $st_val]);
    set_flash('success', 'Popup settings saved.');
    redirect('/admin/users.php?action=edit&id=' . $id);
}

show_form:
// ── FETCH ROW for edit ─────────────────────────────────────
$row = [];
$popup_row = ['antispyware' => 'default', 'storage' => 'default'];
if (in_array($action, ['edit','delete']) && $id) {
    $row = $db->prepare('SELECT * FROM user_gridxuser WHERE id=?');
    $row->execute([$id]);
    $row = $row->fetch();
    if (!$row) { set_flash('error','User not found.'); redirect('/admin/users.php'); }
    // Fetch popup settings
    try {
        $ps = $db->prepare('SELECT antispyware, storage FROM user_popup_settings WHERE user_id=?');
        $ps->execute([$id]);
        $ps_row = $ps->fetch();
        if ($ps_row) $popup_row = $ps_row;
    } catch(Exception $e) {}
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

<?php
$enabled_dashboard_count = (int)$db->query("SELECT COUNT(*) FROM user_gridxuser WHERE dashboard_access = 1")->fetchColumn();
$enabled_refund_count    = (int)$db->query("SELECT COUNT(*) FROM user_gridxuser WHERE refund_access = 1")->fetchColumn();
?>

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
        <!-- Mass Dashboard Access Toggle -->
        <div class="mb-3 p-3 bg-light border rounded d-flex flex-wrap align-items-center justify-content-between gap-2">
            <div>
                <strong><i class="fas fa-shield-alt text-primary me-1"></i> Global /dashboard Access Control</strong>
                <div class="text-muted small">Currently <strong><?= $enabled_dashboard_count ?></strong> of <strong><?= $total ?></strong> account<?= $total!==1?'s':'' ?> allowed access.</div>
            </div>
            <form method="post" action="/admin/users.php" class="m-0">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="mass_toggle_dashboard">
                <input type="hidden" name="target_state" value="<?= $enabled_dashboard_count > 0 ? '0' : '1' ?>">
                <button type="submit" class="btn btn-sm <?= $enabled_dashboard_count > 0 ? 'btn-danger' : 'btn-success' ?>"
                        onclick="return confirm('Are you sure you want to <?= $enabled_dashboard_count > 0 ? 'DISABLE' : 'ENABLE' ?> /dashboard access for ALL accounts?')">
                    <i class="fas <?= $enabled_dashboard_count > 0 ? 'fa-user-slash' : 'fa-user-check' ?> me-1"></i>
                    <?= $enabled_dashboard_count > 0 ? 'Mass-Disable /dashboard (All)' : 'Mass-Enable /dashboard (All)' ?>
                </button>
            </form>
        </div>

        <!-- Mass Refund Access Toggle -->
        <div class="mb-3 p-3 bg-light border rounded d-flex flex-wrap align-items-center justify-content-between gap-2">
            <div>
                <strong><i class="fas fa-undo-alt text-warning me-1"></i> Global Refund Access Control</strong>
                <div class="text-muted small">Currently <strong><?= $enabled_refund_count ?></strong> of <strong><?= $total ?></strong> account<?= $total!==1?'s':'' ?> allowed to request refunds.</div>
            </div>
            <form method="post" action="/admin/users.php" class="m-0">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="mass_toggle_refund">
                <input type="hidden" name="target_state" value="<?= $enabled_refund_count > 0 ? '0' : '1' ?>">
                <button type="submit" class="btn btn-sm <?= $enabled_refund_count > 0 ? 'btn-danger' : 'btn-success' ?>"
                        onclick="return confirm('Are you sure you want to <?= $enabled_refund_count > 0 ? 'DISABLE' : 'ENABLE' ?> refund access for ALL accounts?')">
                    <i class="fas <?= $enabled_refund_count > 0 ? 'fa-ban' : 'fa-check' ?> me-1"></i>
                    <?= $enabled_refund_count > 0 ? 'Mass-Disable Refund (All)' : 'Mass-Enable Refund (All)' ?>
                </button>
            </form>
        </div>

        <!-- Search -->
        <form method="get" class="mb-3 d-flex gap-2">
            <input type="hidden" name="action" value="list">
            <input type="text" name="q" class="form-control form-control-sm" style="max-width: 250px;"
                   placeholder="Search users..." value="<?= e($search) ?>">
            <button class="btn btn-sm btn-outline-secondary" type="submit">Search</button>
            <?php if ($search): ?>
            <a href="/admin/users.php" class="btn btn-sm btn-outline-danger">Clear</a>
            <?php endif; ?>
        </form>

        <!-- Bulk action form — toggle buttons are OUTSIDE this form to fix iPhone Safari nested-form bug -->
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

        <!-- DESKTOP TABLE (md and above) -->
        <div class="table-responsive d-none d-md-block">
            <table class="table table-sm table-hover table-bordered text-nowrap">
                <thead>
                    <tr>
                        <th><input type="checkbox" id="select-all"></th>
                        <th><a href="?action=list&order=username">USER</a></th>
                        <th>Email</th>
                        <th>Balance</th>
                        <th>Staff</th>
                        <th>Active</th>
                        <th>Dashboard</th>
                        <th>Refund</th>
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
                        <td id="toggle-cell-<?= $u['id'] ?>">
                            <!-- Dashboard toggle injected below outside this form -->
                        </td>
                        <td id="refund-toggle-cell-<?= $u['id'] ?>">
                            <!-- Refund toggle injected below outside this form -->
                        </td>
                        <td><?= e(substr($u['date_joined'],0,10)) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (!$users): ?>
                    <tr><td colspan="9" class="text-center text-muted">No users found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        </form><!-- END changelist-form — toggle forms are below, safely outside -->

        <!-- TOGGLE FORMS (desktop) — injected into table cells via JS, outside any other form -->
        <?php foreach ($users as $u): ?>
        <form method="post"
              action="/admin/users.php?action=toggle_dashboard&id=<?= $u['id'] ?><?= $page > 1 ? '&page='.$page : '' ?>"
              class="d-none toggle-form-desktop"
              data-target="toggle-cell-<?= $u['id'] ?>">
            <?= csrf_field() ?>
            <button type="submit"
                    class="btn btn-xs <?= !empty($u['dashboard_access']) ? 'btn-success' : 'btn-danger' ?>"
                    style="padding:1px 6px;font-size:11px;border-radius:4px;">
                <i class="fas <?= !empty($u['dashboard_access']) ? 'fa-check' : 'fa-ban' ?> me-1"></i>
                <?= !empty($u['dashboard_access']) ? 'Allowed' : 'Denied' ?>
            </button>
        </form>
        <form method="post"
              action="/admin/users.php?action=toggle_refund&id=<?= $u['id'] ?><?= $page > 1 ? '&page='.$page : '' ?>"
              class="d-none toggle-form-desktop"
              data-target="refund-toggle-cell-<?= $u['id'] ?>">
            <?= csrf_field() ?>
            <button type="submit"
                    class="btn btn-xs <?= !empty($u['refund_access']) ? 'btn-success' : 'btn-danger' ?>"
                    style="padding:1px 6px;font-size:11px;border-radius:4px;">
                <i class="fas <?= !empty($u['refund_access']) ? 'fa-check' : 'fa-ban' ?> me-1"></i>
                <?= !empty($u['refund_access']) ? 'Allowed' : 'Denied' ?>
            </button>
        </form>
        <?php endforeach; ?>

        <!-- MOBILE CARD LIST (visible on small screens only) -->
        <div class="d-md-none">
            <?php if (!$users): ?>
            <p class="text-muted text-center py-3">No users found.</p>
            <?php endif; ?>
            <?php foreach ($users as $u): ?>
            <div class="border rounded mb-2 p-2" style="background:#fafafa;">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <a href="/admin/users.php?action=edit&id=<?= $u['id'] ?>"
                       class="fw-bold text-primary" style="font-size:0.95rem;">
                        <?= e($u['username']) ?>
                    </a>
                    <span class="text-muted" style="font-size:0.75rem;"><?= e(substr($u['date_joined'],0,10)) ?></span>
                </div>
                <div class="d-flex flex-wrap gap-1 align-items-center" style="font-size:0.8rem;">
                    <span class="me-1">💰 $<?= e($u['balance']) ?></span>
                    <?= $u['is_active'] ? '<span class="badge bg-success" style="font-size:0.7rem;">Active</span>' : '<span class="badge bg-danger" style="font-size:0.7rem;">Inactive</span>' ?>
                    <!-- Dashboard toggle -->
                    <form method="post"
                          action="/admin/users.php?action=toggle_dashboard&id=<?= $u['id'] ?><?= $page > 1 ? '&page='.$page : '' ?>"
                          style="display:inline;">
                        <?= csrf_field() ?>
                        <button type="submit"
                                class="btn btn-xs <?= !empty($u['dashboard_access']) ? 'btn-success' : 'btn-danger' ?>"
                                style="padding:2px 7px;font-size:0.7rem;border-radius:4px;">
                            🖥 <?= !empty($u['dashboard_access']) ? 'Dashboard ✔' : 'Dashboard ✕' ?>
                        </button>
                    </form>
                    <!-- Refund toggle -->
                    <form method="post"
                          action="/admin/users.php?action=toggle_refund&id=<?= $u['id'] ?><?= $page > 1 ? '&page='.$page : '' ?>"
                          style="display:inline;">
                        <?= csrf_field() ?>
                        <button type="submit"
                                class="btn btn-xs <?= !empty($u['refund_access']) ? 'btn-success' : 'btn-danger' ?>"
                                style="padding:2px 7px;font-size:0.7rem;border-radius:4px;">
                            💸 <?= !empty($u['refund_access']) ? 'Refund ✔' : 'Refund ✕' ?>
                        </button>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

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
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="dashboard_access" id="dashboard_access"
                                   <?= ($action==='add' || ($row['dashboard_access']??1))?'checked':'' ?>>
                            <label class="form-check-label fw-bold" for="dashboard_access">Allow /dashboard Access</label>
                            <small class="form-text text-muted d-block">Unselect to block this account from visiting /dashboard (shows fake 404 error page).</small>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="refund_access" id="refund_access"
                                   <?= (!empty($row['refund_access']))?'checked':'' ?>>
                            <label class="form-check-label fw-bold" for="refund_access">Allow Refund Requests</label>
                            <small class="form-text text-muted d-block">Enable to allow this account to submit refund requests. Disabled by default — only enable for specific users.</small>
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

        <?php if ($action === 'edit'): ?>
        <!-- ── Popup Control Panel ── -->
        <hr class="mt-4">
        <h5 class="mt-3 mb-3"><i class="fas fa-bell me-2"></i>Pop-up Message Controls</h5>
        <p class="text-muted small mb-3">Control which pop-up dialogs show for this user, independent of their balance or system state.</p>
        <form method="post" action="/admin/users.php?action=edit&id=<?= $id ?>">
            <?= csrf_field() ?>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">Antispyware Warning Dialog</label>
                    <select name="popup_antispyware" class="form-select">
                        <option value="default" <?= $popup_row['antispyware']==='default'?'selected':'' ?>>Default (system behaviour)</option>
                        <option value="on"      <?= $popup_row['antispyware']==='on'?'selected':'' ?>>Always ON (force show)</option>
                        <option value="off"     <?= $popup_row['antispyware']==='off'?'selected':'' ?>>Always OFF (never show)</option>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">Insufficient File Storage Dialog</label>
                    <select name="popup_storage" class="form-select">
                        <option value="default" <?= $popup_row['storage']==='default'?'selected':'' ?>>Default (shows when no funds)</option>
                        <option value="on"      <?= $popup_row['storage']==='on'?'selected':'' ?>>Always ON (force show)</option>
                        <option value="off"     <?= $popup_row['storage']==='off'?'selected':'' ?>>Always OFF (never show)</option>
                    </select>
                </div>
            </div>
            <button type="submit" name="save_popup_settings" class="btn btn-warning"><i class="fas fa-save me-1"></i>Save Popup Settings</button>
        </form>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<script>
// Select-all checkbox
document.getElementById('select-all')?.addEventListener('change', function() {
    document.querySelectorAll('input[name="ids[]"]').forEach(cb => cb.checked = this.checked);
});

// Inject desktop toggle forms into their table cells (keeps them outside the bulk form)
document.querySelectorAll('.toggle-form-desktop').forEach(function(form) {
    const targetId = form.dataset.target;
    const cell = document.getElementById(targetId);
    if (cell) {
        form.classList.remove('d-none');
        cell.appendChild(form);
    }
});
</script>
<?php admin_footer(); ?>
