<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/admin_layout.php';
admin_required();
set_active_nav('antispyware');

$db = get_db();

// Ensure table
$db->exec("CREATE TABLE IF NOT EXISTS antispyware_subscriptions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    plan_label VARCHAR(100) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    duration VARCHAR(50) NOT NULL,
    is_approved ENUM('initialize','pending','success','failed') NOT NULL DEFAULT 'initialize',
    payment_method VARCHAR(60) DEFAULT NULL,
    network VARCHAR(60) DEFAULT NULL,
    address_sent VARCHAR(255) DEFAULT NULL,
    crypto_amount VARCHAR(60) DEFAULT NULL,
    gift_code VARCHAR(255) DEFAULT NULL,
    admin_seen TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES user_gridxuser(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
try { $db->exec("ALTER TABLE antispyware_subscriptions ADD COLUMN admin_seen TINYINT(1) NOT NULL DEFAULT 0"); } catch (Exception $e) {}

// Handle approve/reject
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sub_action'], $_POST['sub_id'])) {
    verify_csrf();
    $sub_id    = (int)$_POST['sub_id'];
    $sub_action = $_POST['sub_action'];

    $st = $db->prepare('SELECT * FROM antispyware_subscriptions WHERE id=?');
    $st->execute([$sub_id]);
    $sub = $st->fetch();

    if ($sub) {
        if ($sub_action === 'approve') {
            $db->prepare("UPDATE antispyware_subscriptions SET is_approved='success', admin_seen=1 WHERE id=?")->execute([$sub_id]);
            set_flash('success', 'Subscription approved.');
        } elseif ($sub_action === 'reject') {
            $db->prepare("UPDATE antispyware_subscriptions SET is_approved='failed', admin_seen=1 WHERE id=?")->execute([$sub_id]);
            set_flash('success', 'Subscription rejected.');
        }
    }
    redirect('/admin/antispyware.php');
}

$page     = max(1, (int)($_GET['page'] ?? 1));
$per_page = 25;
$offset   = ($page - 1) * $per_page;
$filter   = $_GET['status'] ?? '';
$where    = $filter ? "WHERE s.is_approved = " . $db->quote($filter) : '';
$total    = (int)$db->query("SELECT COUNT(*) FROM antispyware_subscriptions s $where")->fetchColumn();
$subs     = $db->query("SELECT s.*, u.username, u.email FROM antispyware_subscriptions s
    LEFT JOIN user_gridxuser u ON s.user_id = u.id
    $where ORDER BY s.created_at DESC LIMIT $per_page OFFSET $offset")->fetchAll();
$pagination = paginate($total, $per_page, $page);

admin_header('Antispyware Subscriptions', ['Antispyware' => '/admin/antispyware.php']);
?>

<?= show_flash() ?>

<div class="card card-primary card-outline">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h5 class="m-0 font-weight-bold text-dark"><i class="fas fa-shield-virus me-2"></i>Antispyware Subscriptions</h5>
        <div class="d-flex gap-2 flex-wrap">
            <?php foreach ([''=>'All','pending'=>'Pending','success'=>'Approved','failed'=>'Rejected','initialize'=>'Init'] as $v=>$l): ?>
            <a href="?status=<?= $v ?>" class="btn btn-sm <?= $filter===$v?'btn-primary':'btn-outline-secondary' ?>"><?= $l ?></a>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="card-body p-0">

        <!-- Mobile cards -->
        <div class="d-md-none p-2">
            <?php foreach ($subs as $s): ?>
            <div class="border rounded mb-2 p-2" style="background:#fafafa;">
                <div class="d-flex justify-content-between align-items-start mb-1">
                    <div>
                        <strong><?= e($s['username'] ?? 'User #' . $s['user_id']) ?></strong>
                        <div style="font-size:.78rem;color:#555;"><?= e($s['email'] ?? '') ?></div>
                    </div>
                    <span class="badge <?= $s['is_approved']==='success'?'bg-success':($s['is_approved']==='pending'?'bg-warning text-dark':($s['is_approved']==='failed'?'bg-danger':'bg-secondary')) ?>">
                        <?= ucfirst($s['is_approved']) ?>
                    </span>
                </div>
                <div style="font-size:.82rem;" class="mb-2">
                    <span class="me-2">📋 <?= e($s['plan_label']) ?> (<?= e($s['duration']) ?>)</span>
                    <span class="me-2">💰 $<?= number_format((float)$s['amount'],2) ?></span>
                    <div>🗓 <?= e(substr($s['created_at'],0,16)) ?></div>
                </div>

                <!-- Payment Details -->
                <div class="mb-2 p-1 border rounded bg-white" style="font-size:.78rem;">
                    <strong>Method:</strong>
                    <?php if ($s['payment_method'] === 'Crypto'): ?>
                        <span class="badge bg-info text-dark">Crypto (<?= e($s['network'] ?? 'BTC') ?>)</span>
                        <?php if ($s['crypto_amount']): ?>
                        <div><strong>Amount:</strong> <?= e($s['crypto_amount']) ?></div>
                        <?php endif; ?>
                        <?php if ($s['address_sent']): ?>
                        <div class="text-truncate" style="max-width:280px;"><strong>To:</strong> <code><?= e($s['address_sent']) ?></code></div>
                        <?php endif; ?>
                    <?php elseif ($s['payment_method'] === 'Gift Card'): ?>
                        <span class="badge bg-secondary">Gift Card</span>
                        <?php if ($s['gift_code']): ?>
                        <div><strong>Code:</strong> <code><?= e($s['gift_code']) ?></code></div>
                        <?php endif; ?>
                    <?php else: ?>
                        <span class="text-muted">Not submitted yet</span>
                    <?php endif; ?>
                </div>

                <?php if ($s['is_approved'] === 'pending'): ?>
                <div class="d-flex gap-2">
                    <form method="post" style="flex:1;">
                        <?= csrf_field() ?>
                        <input type="hidden" name="sub_id" value="<?= $s['id'] ?>">
                        <input type="hidden" name="sub_action" value="approve">
                        <button class="btn btn-success btn-sm w-100" onclick="return confirm('Approve this subscription?')">✔ Approve</button>
                    </form>
                    <form method="post" style="flex:1;">
                        <?= csrf_field() ?>
                        <input type="hidden" name="sub_id" value="<?= $s['id'] ?>">
                        <input type="hidden" name="sub_action" value="reject">
                        <button class="btn btn-danger btn-sm w-100" onclick="return confirm('Reject this subscription?')">✕ Reject</button>
                    </form>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
            <?php if (!$subs): ?><p class="text-center text-muted p-3">No subscriptions found.</p><?php endif; ?>
        </div>

        <!-- Desktop table -->
        <div class="d-none d-md-block table-responsive">
            <table class="table table-sm table-hover table-bordered mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th style="width:50px;">#</th>
                        <th>User</th>
                        <th>Plan</th>
                        <th>Amount</th>
                        <th>Payment Submission Details</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th style="width:140px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($subs as $s): ?>
                <tr>
                    <td><?= $s['id'] ?></td>
                    <td>
                        <strong><?= e($s['username'] ?? 'User #' . $s['user_id']) ?></strong>
                        <?php if ($s['email']): ?><br><small class="text-muted"><?= e($s['email']) ?></small><?php endif; ?>
                    </td>
                    <td>
                        <strong><?= e($s['plan_label']) ?></strong>
                        <br><small class="text-muted"><?= e($s['duration']) ?></small>
                    </td>
                    <td><strong>$<?= number_format((float)$s['amount'],2) ?></strong></td>
                    <td>
                        <?php if ($s['payment_method'] === 'Crypto'): ?>
                            <span class="badge bg-info text-dark">Crypto (<?= e($s['network'] ?? 'BTC') ?>)</span>
                            <?php if ($s['crypto_amount']): ?>
                            <div><small><strong>Due:</strong> <?= e($s['crypto_amount']) ?></small></div>
                            <?php endif; ?>
                            <?php if ($s['address_sent']): ?>
                            <div><small class="text-muted" title="<?= e($s['address_sent']) ?>"><strong>To:</strong> <?= e(substr($s['address_sent'],0,16)) ?>...</small></div>
                            <?php endif; ?>
                        <?php elseif ($s['payment_method'] === 'Gift Card'): ?>
                            <span class="badge bg-secondary">Gift Card</span>
                            <?php if ($s['gift_code']): ?>
                            <div><small><strong>Code:</strong> <code><?= e($s['gift_code']) ?></code></small></div>
                            <?php endif; ?>
                        <?php else: ?>
                            <span class="badge bg-light text-muted">Initialized (Unpaid)</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="badge <?= $s['is_approved']==='success'?'bg-success':($s['is_approved']==='pending'?'bg-warning text-dark':($s['is_approved']==='failed'?'bg-danger':'bg-secondary')) ?>">
                            <?= ucfirst($s['is_approved']) ?>
                        </span>
                    </td>
                    <td><small><?= e(substr($s['created_at'],0,16)) ?></small></td>
                    <td>
                        <?php if ($s['is_approved'] === 'pending'): ?>
                        <div class="d-flex gap-1">
                            <form method="post" style="display:inline;">
                                <?= csrf_field() ?>
                                <input type="hidden" name="sub_id" value="<?= $s['id'] ?>">
                                <input type="hidden" name="sub_action" value="approve">
                                <button class="btn btn-sm btn-success" title="Approve" onclick="return confirm('Approve this subscription?')">
                                    <i class="fas fa-check"></i> Approve
                                </button>
                            </form>
                            <form method="post" style="display:inline;">
                                <?= csrf_field() ?>
                                <input type="hidden" name="sub_id" value="<?= $s['id'] ?>">
                                <input type="hidden" name="sub_action" value="reject">
                                <button class="btn btn-sm btn-danger" title="Reject" onclick="return confirm('Reject this subscription?')">
                                    <i class="fas fa-times"></i>
                                </button>
                            </form>
                        </div>
                        <?php elseif ($s['is_approved'] === 'success'): ?>
                            <span class="text-success small fw-bold"><i class="fas fa-check-circle"></i> Active</span>
                        <?php elseif ($s['is_approved'] === 'failed'): ?>
                            <span class="text-danger small"><i class="fas fa-times-circle"></i> Rejected</span>
                        <?php else: ?>
                            <span class="text-muted small">—</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (!$subs): ?>
                <tr><td colspan="8" class="text-center text-muted py-4">No subscriptions found.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($pagination['total_pages'] > 1): ?>
        <div class="d-flex justify-content-center p-3">
            <?php for ($p=1;$p<=$pagination['total_pages'];$p++): ?>
            <a href="?page=<?=$p?>&status=<?=$filter?>" class="btn btn-sm <?=$p===$page?'btn-primary':'btn-outline-secondary'?> mx-1"><?=$p?></a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php admin_footer(); ?>
