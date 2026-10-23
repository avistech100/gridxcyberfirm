<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/admin_layout.php';
admin_required();
set_active_nav('refunds');

$db     = get_db();
$action = $_GET['action'] ?? 'list';
$id     = (int)($_GET['id'] ?? 0);
$errors = [];

// Bulk delete actions (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_action']) && $_POST['bulk_action'] === 'delete') {
    verify_csrf();
    $ids = $_POST['ids'] ?? [];
    if ($ids) {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $db->prepare("DELETE FROM refund_refund WHERE id IN ($placeholders)")->execute($ids);
        set_flash('success', 'Selected refunds deleted successfully.');
    }
    redirect('/admin/refunds.php');
}

// DELETE
if ($action === 'delete' && $id) {
    verify_csrf();
    $db->prepare('DELETE FROM refund_refund WHERE id=?')->execute([$id]);
    set_flash('success','Refund deleted successfully.');
    redirect('/admin/refunds.php');
}

// SAVE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($action,['add','edit'])) {
    verify_csrf();
    $user_id        = (int)($_POST['user_id'] ?? 0);
    $transactions_id= (int)($_POST['transactions_id'] ?? 0);
    $amount         = $_POST['amount'] ?? '';
    $reason         = trim($_POST['reason'] ?? '');

    if (!$user_id)         $errors[] = 'User is required.';
    if (!$transactions_id) $errors[] = 'Transaction is required.';
    if (!$amount)          $errors[] = 'Amount is required.';
    if (!$reason)          $errors[] = 'Reason is required.';

    if (!$errors) {
        if ($action === 'add') {
            $db->prepare('INSERT INTO refund_refund (user_id,transactions_id,amount,reason) VALUES (?,?,?,?)')
               ->execute([$user_id,$transactions_id,$amount,$reason]);
            set_flash('success','Refund added successfully.');
        } else {
            $db->prepare('UPDATE refund_refund SET user_id=?,transactions_id=?,amount=?,reason=? WHERE id=?')
               ->execute([$user_id,$transactions_id,$amount,$reason,$id]);
            set_flash('success','Refund updated successfully.');
        }
        redirect('/admin/refunds.php');
    }
}

// Fetch row
$row = [];
if (in_array($action,['edit','delete']) && $id) {
    $st = $db->prepare('SELECT * FROM refund_refund WHERE id=?');
    $st->execute([$id]);
    $row = $st->fetch();
    if (!$row) { set_flash('error','Refund not found.'); redirect('/admin/refunds.php'); }
    // Mark as seen when admin opens it
    $db->prepare('UPDATE refund_refund SET admin_seen=1 WHERE id=?')->execute([$id]);
}

// List
$search  = trim($_GET['q'] ?? '');
$page    = max(1,(int)($_GET['page'] ?? 1));
$per_page= 10;
$where   = $search ? "WHERE u.username LIKE ? OR r.reason LIKE ?" : '';
$params  = $search ? ["%$search%","%$search%"] : [];
$total_st= $db->prepare("SELECT COUNT(*) FROM refund_refund r JOIN user_gridxuser u ON u.id=r.user_id $where");
$total_st->execute($params);
$total   = (int)$total_st->fetchColumn();
$pg      = paginate($total,$per_page,$page);
$list_st = $db->prepare("SELECT r.*, u.username, t.amount AS tx_amount, t.is_approved AS tx_status
    FROM refund_refund r
    JOIN user_gridxuser u ON u.id=r.user_id
    JOIN user_trasaction t ON t.id=r.transactions_id
    $where ORDER BY r.id DESC LIMIT ? OFFSET ?");
$list_st->execute(array_merge($params,[$per_page,$pg['offset']]));
$refunds = $list_st->fetchAll();

$all_users = $db->query('SELECT id, username FROM user_gridxuser ORDER BY username')->fetchAll();
$all_transactions = $db->query('SELECT t.id, u.username, t.amount, t.is_approved FROM user_trasaction t JOIN user_gridxuser u ON u.id=t.user_id ORDER BY t.id DESC')->fetchAll();

$page_title = ($action==='add') ? 'Add refund' : (($action==='edit') ? 'Change refund' : 'Select refund to change');
admin_head($page_title);
admin_body_open();
admin_content_header($page_title, ['Refund' => '/admin/', 'Refunds' => '/admin/refunds.php', $page_title => '']);
?>

<?= show_flash() ?>

<?php if ($action === 'list'): ?>
<div class="card">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
            <h3 class="card-title">Refunds</h3>
            <a href="/admin/refunds.php?action=add" class="btn btn-success btn-sm">
                <i class="fas fa-plus me-1"></i> Add Refund
            </a>
        </div>
    </div>
    <div class="card-body">
        <form method="get" class="mb-3 d-flex gap-2">
            <input type="text" name="q" class="form-control form-control-sm" style="width:200px"
                   placeholder="Search..." value="<?= e($search) ?>">
            <button class="btn btn-sm btn-outline-secondary">Search</button>
            <?php if ($search): ?><a href="/admin/refunds.php" class="btn btn-sm btn-outline-danger">Clear</a><?php endif; ?>
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
                    <th>ID</th>
                    <th>User</th>
                    <th>Transaction</th>
                    <th>Amount</th>
                    <th>Reason</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($refunds as $r): ?>
                <tr>
                    <td><input type="checkbox" name="ids[]" value="<?= $r['id'] ?>"></td>
                    <td><a href="/admin/refunds.php?action=edit&id=<?= $r['id'] ?>"><?= $r['id'] ?></a></td>
                    <td><?= e($r['username']) ?></td>
                    <td>
                        <a href="/admin/transactions.php?action=edit&id=<?= $r['transactions_id'] ?>">
                            #<?= $r['transactions_id'] ?> $<?= e($r['tx_amount']) ?> (<?= e($r['tx_status']) ?>)
                        </a>
                    </td>
                    <td>$<?= e($r['amount']) ?></td>
                    <td><?= e(substr($r['reason'],0,60)) ?><?= strlen($r['reason'])>60?'...':'' ?></td>
                    <td>
                        <div class="dropdown">
                            <button class="btn btn-xs btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                Actions
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="/admin/refunds.php?action=edit&id=<?= $r['id'] ?>">Change</a></li>
                                <li><a class="dropdown-item text-danger" href="/admin/refunds.php?action=delete&id=<?= $r['id'] ?>" onclick="return confirm('Are you sure you want to delete this refund?')">Delete</a></li>
                            </ul>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (!$refunds): ?>
                <tr><td colspan="7" class="text-center text-muted">No refunds found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
        </form>

        <?php if ($pg['total_pages']>1): ?>
        <nav><ul class="pagination pagination-sm">
            <?php for ($p=1;$p<=$pg['total_pages'];$p++): ?>
            <li class="page-item <?= $p===$page?'active':'' ?>">
                <a class="page-link" href="?page=<?= $p ?>&q=<?= urlencode($search) ?>"><?= $p ?></a>
            </li>
            <?php endfor; ?>
        </ul></nav>
        <?php endif; ?>
        <p class="text-muted small"><?= $total ?> refund<?= $total!==1?'s':'' ?></p>
    </div>
</div>

<?php else: ?>
<div class="card card-primary card-outline">
    <div class="card-header"><h3 class="card-title"><?= e($page_title) ?></h3></div>
    <div class="card-body">
        <?php if ($errors): ?>
        <div class="alert alert-danger"><ul class="mb-0"><?php foreach($errors as $e_msg): ?><li><?= e($e_msg) ?></li><?php endforeach; ?></ul></div>
        <?php endif; ?>

        <form method="post">
            <?= csrf_field() ?>
            <div class="mb-3">
                <label class="form-label">User *</label>
                <select name="user_id" class="form-select" required>
                    <option value="">---------</option>
                    <?php foreach($all_users as $u): ?>
                    <option value="<?= $u['id'] ?>" <?= ($row['user_id']??0)==$u['id']?'selected':'' ?>><?= e($u['username']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Transaction *</label>
                <select name="transactions_id" class="form-select" required>
                    <option value="">---------</option>
                    <?php foreach($all_transactions as $t): ?>
                    <option value="<?= $t['id'] ?>" <?= ($row['transactions_id']??0)==$t['id']?'selected':'' ?>>
                        <?= e($t['username']) ?> $<?= e($t['amount']) ?> (<?= e($t['is_approved']) ?>)
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Amount *</label>
                <input type="number" name="amount" step="0.01" class="form-control"
                       value="<?= e($row['amount'] ?? '') ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Reason *</label>
                <textarea name="reason" class="form-control" rows="4" maxlength="500" required><?= e($row['reason'] ?? '') ?></textarea>
            </div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">Save</button>
                <a href="/admin/refunds.php" class="btn btn-outline-secondary">Cancel</a>
                <?php if ($action==='edit'): ?>
                <a href="/admin/refunds.php?action=delete&id=<?= $id ?>"
                   onclick="return confirm('Delete this refund?')"
                   class="btn btn-danger ms-auto">Delete</a>
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

document.getElementById('changelist-form')?.addEventListener('submit', function(e) {
    const action = this.querySelector('select[name="bulk_action"]').value;
    if (action === 'delete') {
        const checked = this.querySelectorAll('input[name="ids[]"]:checked').length;
        if (checked === 0) {
            alert('No items selected.');
            e.preventDefault();
            return;
        }
        if (!confirm('Are you sure you want to delete the selected refunds?')) {
            e.preventDefault();
        }
    }
});
</script>
<?php admin_footer(); ?>
