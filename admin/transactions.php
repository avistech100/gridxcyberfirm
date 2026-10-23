<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/admin_layout.php';
admin_required();
set_active_nav('transactions');

$db     = get_db();
$action = $_GET['action'] ?? 'list';
$id     = (int)($_GET['id'] ?? 0);
$errors = [];

// Approve / Disapprove actions (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tx_action'], $_POST['tx_id'])) {
    verify_csrf();
    $tx_id = (int)$_POST['tx_id'];
    $tx_action = $_POST['tx_action'];
    
    $st = $db->prepare('SELECT * FROM user_trasaction WHERE id=?');
    $st->execute([$tx_id]);
    $tx = $st->fetch();
    
    if ($tx) {
        if ($tx_action === 'approve') {
            if ($tx['is_approved'] !== 'success') {
                $db->prepare('UPDATE user_gridxuser SET balance = balance + ? WHERE id = ?')
                   ->execute([$tx['amount'], $tx['user_id']]);
            }
            $db->prepare("UPDATE user_trasaction SET is_approved='success', admin_seen=1 WHERE id=?")
               ->execute([$tx_id]);
            set_flash('success', 'Transaction approved successfully.');
        } elseif ($tx_action === 'disapprove') {
            $db->prepare("UPDATE user_trasaction SET is_approved='failed', admin_seen=1 WHERE id=?")
               ->execute([$tx_id]);
            set_flash('success', 'Transaction disapproved.');
        }
    }
    redirect('/admin/transactions.php');
}

// Bulk delete actions (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_action']) && $_POST['bulk_action'] === 'delete') {
    verify_csrf();
    $ids = $_POST['ids'] ?? [];
    if ($ids) {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $db->prepare("DELETE FROM user_trasaction WHERE id IN ($placeholders)")->execute($ids);
        set_flash('success', 'Selected transactions deleted successfully.');
    }
    redirect('/admin/transactions.php');
}

$GIFT_CARDS = ['apple','itunes','google_play','amazon','steam','playstation','xbox','nintendo',
               'roblox','riot_games','blizzard','epic_games','ea_play','netflix','spotify',
               'youtube','hulu','disney_plus','uber','uber_eats','doordash','grubhub',
               'airbnb','booking','expedia','visa','mastercard','american_express','vanilla',
               'one4all','walmart','target','best_buy','ebay','costco','ikea','nike','adidas',
               'zara','hm','macys','starbucks','mcdonalds','kfc','subway','chipotle',
               'sephora','ulta','playstore','google','cashapp','paypal','razergold','paysafecard'];

// DELETE
if ($action === 'delete' && $id) {
    verify_csrf();
    $db->prepare('DELETE FROM user_trasaction WHERE id=?')->execute([$id]);
    set_flash('success', 'Transaction deleted successfully.');
    redirect('/admin/transactions.php');
}

// SAVE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($action, ['add','edit'])) {
    verify_csrf();
    $user_id        = (int)($_POST['user_id'] ?? 0);
    $amount         = $_POST['amount'] ?? '';
    $crypto_ammount = $_POST['crypto_ammount'] ?: null;
    $network        = $_POST['network'] ?: null;
    $address_sent   = trim($_POST['address_sent'] ?? '') ?: null;
    $gift_type      = $_POST['gift_type'] ?? 'failed';
    $gift_code      = trim($_POST['gift_code'] ?? '') ?: null;
    $payment_method = $_POST['payment_method'] ?: null;
    $is_approved    = $_POST['is_approved'] ?? 'failed';

    if (!$user_id) $errors[] = 'User is required.';
    if (!$amount)  $errors[] = 'Amount is required.';

    if (!$errors) {
        if ($action === 'add') {
            $db->prepare('INSERT INTO user_trasaction
                (user_id,amount,crypto_ammount,network,address_sent,gift_type,gift_code,payment_method,is_approved)
                VALUES (?,?,?,?,?,?,?,?,?)')
               ->execute([$user_id,$amount,$crypto_ammount,$network,$address_sent,$gift_type,$gift_code,$payment_method,$is_approved]);
            set_flash('success', 'Transaction added successfully.');
        } else {
            $db->prepare('UPDATE user_trasaction SET user_id=?,amount=?,crypto_ammount=?,network=?,
                address_sent=?,gift_type=?,gift_code=?,payment_method=?,is_approved=? WHERE id=?')
               ->execute([$user_id,$amount,$crypto_ammount,$network,$address_sent,$gift_type,$gift_code,$payment_method,$is_approved,$id]);
            set_flash('success', 'Transaction updated successfully.');
        }
        redirect('/admin/transactions.php');
    }
}

// Fetch row
$row = [];
if (in_array($action,['edit','delete']) && $id) {
    $st = $db->prepare('SELECT * FROM user_trasaction WHERE id=?');
    $st->execute([$id]);
    $row = $st->fetch();
    if (!$row) { set_flash('error','Transaction not found.'); redirect('/admin/transactions.php'); }
    // Mark as seen when admin opens it
    $db->prepare('UPDATE user_trasaction SET admin_seen=1 WHERE id=?')->execute([$id]);
}

// List
$search  = trim($_GET['q'] ?? '');
$filter  = trim($_GET['filter'] ?? '');
$page    = max(1,(int)($_GET['page'] ?? 1));
$per_page= 10;
$where_parts = [];
$params  = [];
if ($search) { $where_parts[]="(u.username LIKE ? OR t.amount LIKE ?)"; $params[]="%$search%"; $params[]="%$search%"; }
if ($filter) { $where_parts[]="t.is_approved=?"; $params[]=$filter; }
$where = $where_parts ? 'WHERE '.implode(' AND ',$where_parts) : '';
$total_st = $db->prepare("SELECT COUNT(*) FROM user_trasaction t JOIN user_gridxuser u ON u.id=t.user_id $where");
$total_st->execute($params);
$total = (int)$total_st->fetchColumn();
$pg    = paginate($total, $per_page, $page);
$list_st = $db->prepare("SELECT t.*, u.username FROM user_trasaction t JOIN user_gridxuser u ON u.id=t.user_id $where ORDER BY t.id DESC LIMIT ? OFFSET ?");
$list_st->execute(array_merge($params,[$per_page,$pg['offset']]));
$transactions = $list_st->fetchAll();

$all_users = $db->query('SELECT id, username FROM user_gridxuser ORDER BY username')->fetchAll();

$page_title = ($action === 'add') ? 'Add transaction' : (($action === 'edit') ? 'Change transaction' : 'Select transaction to change');
admin_head($page_title);
admin_body_open();
admin_content_header($page_title, ['User' => '/admin/', 'Trasactions' => '/admin/transactions.php', $page_title => '']);
?>

<?= show_flash() ?>

<?php if ($action === 'list'): ?>
<div class="card">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
            <h3 class="card-title">Transactions</h3>
            <a href="/admin/transactions.php?action=add" class="btn btn-success btn-sm">
                <i class="fas fa-plus me-1"></i> Add Transaction
            </a>
        </div>
    </div>
    <div class="card-body">
        <form method="get" class="mb-3 d-flex gap-2 flex-wrap">
            <input type="text" name="q" class="form-control form-control-sm" style="width:200px"
                   placeholder="Search..." value="<?= e($search) ?>">
            <select name="filter" class="form-select form-select-sm" style="width:auto">
                <option value="">All statuses</option>
                <?php foreach (['failed','initialize','pending','success'] as $s): ?>
                <option value="<?= $s ?>" <?= $filter===$s?'selected':'' ?>><?= ucfirst($s) ?></option>
                <?php endforeach; ?>
            </select>
            <button class="btn btn-sm btn-outline-secondary">Filter</button>
            <?php if ($search||$filter): ?>
            <a href="/admin/transactions.php" class="btn btn-sm btn-outline-danger">Clear</a>
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
                    <th>ID</th>
                    <th>User</th>
                    <th>Amount</th>
                    <th>Method</th>
                    <th>Network</th>
                    <th>Gift Type</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($transactions as $t): ?>
                <tr>
                    <td><input type="checkbox" name="ids[]" value="<?= $t['id'] ?>"></td>
                    <td><a href="/admin/transactions.php?action=edit&id=<?= $t['id'] ?>"><?= $t['id'] ?></a></td>
                    <td>
                        <a href="#" onclick="openApprovalModal(<?= $t['id'] ?>, '<?= e($t['username']) ?>', '<?= e($t['amount']) ?>'); return false;">
                            <?= e($t['username']) ?>
                        </a>
                    </td>
                    <td>$<?= e($t['amount']) ?></td>
                    <td><?= e($t['payment_method'] ?? '-') ?></td>
                    <td><?= e($t['network'] ?? '-') ?></td>
                    <td><?= e($t['gift_type']) ?></td>
                    <td>
                        <?php $badge=['failed'=>'danger','initialize'=>'secondary','pending'=>'warning','success'=>'success'][$t['is_approved']]??'secondary'; ?>
                        <span class="badge bg-<?= $badge ?>"><?= e($t['is_approved']) ?></span>
                    </td>
                    <td>
                        <div class="dropdown">
                            <button class="btn btn-xs btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                Actions
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="/admin/transactions.php?action=edit&id=<?= $t['id'] ?>">Change</a></li>
                                <li><a class="dropdown-item text-danger" href="/admin/transactions.php?action=delete&id=<?= $t['id'] ?>" onclick="return confirm('Are you sure you want to delete this transaction?')">Delete</a></li>
                            </ul>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (!$transactions): ?>
                <tr><td colspan="9" class="text-center text-muted">No transactions found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
        </form>

        <?php if ($pg['total_pages'] > 1): ?>
        <nav><ul class="pagination pagination-sm">
            <?php for ($p=1; $p<=$pg['total_pages']; $p++): ?>
            <li class="page-item <?= $p===$page?'active':'' ?>">
                <a class="page-link" href="?page=<?= $p ?>&q=<?= urlencode($search) ?>&filter=<?= urlencode($filter) ?>"><?= $p ?></a>
            </li>
            <?php endfor; ?>
        </ul></nav>
        <?php endif; ?>
        <p class="text-muted small"><?= $total ?> transaction<?= $total!==1?'s':'' ?></p>
    </div>
</div>

<?php else: // FORM ?>
<div class="card card-primary card-outline">
    <div class="card-header"><h3 class="card-title"><?= e($page_title) ?></h3></div>
    <div class="card-body">
        <?php if ($errors): ?>
        <div class="alert alert-danger"><ul class="mb-0"><?php foreach($errors as $e_msg): ?><li><?= e($e_msg) ?></li><?php endforeach; ?></ul></div>
        <?php endif; ?>

        <form method="post">
            <?= csrf_field() ?>
            <div class="row">
                <div class="col-md-6">
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
                        <label class="form-label">Amount *</label>
                        <input type="number" name="amount" step="0.01" class="form-control"
                               value="<?= e($row['amount'] ?? '') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Crypto Amount</label>
                        <input type="number" name="crypto_ammount" step="0.000000001" class="form-control"
                               value="<?= e($row['crypto_ammount'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Network</label>
                        <select name="network" class="form-select">
                            <option value="">---------</option>
                            <?php foreach(['BTC'=>'Bitcoin','ETH'=>'Ethereum','USDT'=>'USDT','LTC'=>'Lite Coin'] as $v=>$l): ?>
                            <option value="<?= $v ?>" <?= ($row['network']??'')===$v?'selected':'' ?>><?= $l ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Address Sent</label>
                        <input type="text" name="address_sent" class="form-control"
                               value="<?= e($row['address_sent'] ?? '') ?>">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Gift Type</label>
                        <select name="gift_type" class="form-select">
                            <?php foreach($GIFT_CARDS as $gc): ?>
                            <option value="<?= $gc ?>" <?= ($row['gift_type']??'')===$gc?'selected':'' ?>><?= ucfirst(str_replace('_',' ',$gc)) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Gift Code</label>
                        <input type="text" name="gift_code" class="form-control"
                               value="<?= e($row['gift_code'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Payment Method</label>
                        <select name="payment_method" class="form-select">
                            <option value="">---------</option>
                            <?php foreach(['Gift Card','Crypto'] as $pm): ?>
                            <option value="<?= $pm ?>" <?= ($row['payment_method']??'')===$pm?'selected':'' ?>><?= $pm ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select name="is_approved" class="form-select">
                            <?php foreach(['failed','initialize','pending','success'] as $s): ?>
                            <option value="<?= $s ?>" <?= ($row['is_approved']??'failed')===$s?'selected':'' ?>><?= ucfirst($s) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">Save</button>
                <a href="/admin/transactions.php" class="btn btn-outline-secondary">Cancel</a>
                <?php if ($action==='edit'): ?>
                <a href="/admin/transactions.php?action=delete&id=<?= $id ?>"
                   onclick="return confirm('Delete this transaction?')"
                   class="btn btn-danger ms-auto">Delete</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- Approval Modal -->
<div class="modal fade" id="approvalModal" tabindex="-1" aria-labelledby="approvalModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="approvalModalLabel">Manage Transaction</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Manage transaction for user <strong id="modal-username"></strong> for the amount of <strong id="modal-amount"></strong>.</p>
            </div>
            <div class="modal-footer">
                <form method="post" action="" style="display:inline;">
                    <?= csrf_field() ?>
                    <input type="hidden" name="tx_action" value="approve">
                    <input type="hidden" name="tx_id" id="approve-tx-id">
                    <button type="submit" class="btn btn-success">Approve</button>
                </form>
                <form method="post" action="" style="display:inline;">
                    <?= csrf_field() ?>
                    <input type="hidden" name="tx_action" value="disapprove">
                    <input type="hidden" name="tx_id" id="disapprove-tx-id">
                    <button type="submit" class="btn btn-danger">Disapprove</button>
                </form>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('select-all')?.addEventListener('change', function() {
    document.querySelectorAll('input[name="ids[]"]').forEach(cb => cb.checked = this.checked);
});

function openApprovalModal(txId, username, amount) {
    document.getElementById('modal-username').textContent = username;
    document.getElementById('modal-amount').textContent = '$' + amount;
    document.getElementById('approve-tx-id').value = txId;
    document.getElementById('disapprove-tx-id').value = txId;
    var myModal = new bootstrap.Modal(document.getElementById('approvalModal'));
    myModal.show();
}

document.getElementById('changelist-form')?.addEventListener('submit', function(e) {
    const action = this.querySelector('select[name="bulk_action"]').value;
    if (action === 'delete') {
        const checked = this.querySelectorAll('input[name="ids[]"]:checked').length;
        if (checked === 0) {
            alert('No items selected.');
            e.preventDefault();
            return;
        }
        if (!confirm('Are you sure you want to delete the selected transactions?')) {
            e.preventDefault();
        }
    }
});
</script>
<?php admin_footer(); ?>
