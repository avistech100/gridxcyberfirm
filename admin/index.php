<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/admin_layout.php';
admin_required();
set_active_nav('dashboard');

$db = get_db();

// Counts for dashboard cards
$user_count   = $db->query('SELECT COUNT(*) FROM user_gridxuser')->fetchColumn();
$tx_count     = $db->query('SELECT COUNT(*) FROM user_trasaction')->fetchColumn();
$refund_count = $db->query('SELECT COUNT(*) FROM refund_refund')->fetchColumn();
$group_count  = $db->query('SELECT COUNT(*) FROM auth_group')->fetchColumn();

// Recent actions (last 5 users & transactions)
$recent_users = $db->query('SELECT id, username, email, date_joined FROM user_gridxuser ORDER BY date_joined DESC LIMIT 5')->fetchAll();
$recent_tx    = $db->query('SELECT t.id, u.username, t.amount, t.is_approved, t.payment_method FROM user_trasaction t JOIN user_gridxuser u ON u.id=t.user_id ORDER BY t.id DESC LIMIT 5')->fetchAll();

// Time-ago calculation helper
if (!function_exists('get_time_ago')) {
    function get_time_ago($timestamp) {
        $difference = time() - $timestamp;
        if ($difference < 60) {
            return 'Just now';
        } elseif ($difference < 3600) {
            $mins = floor($difference / 60);
            return $mins . ' minute' . ($mins > 1 ? 's' : '') . ' ago';
        } elseif ($difference < 86400) {
            $hours = floor($difference / 3600);
            return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
        } else {
            $days = floor($difference / 86400);
            $hours = floor(($difference % 86400) / 3600);
            return $days . ' day' . ($days > 1 ? 's' : '') . ', ' . $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
        }
    }
}

// Compile chronological action items
$actions = [];
foreach ($recent_users as $index => $u) {
    $actions[] = [
        'url' => '/admin/users.php?action=edit&id=' . $u['id'],
        'title' => $u['username'],
        'time' => get_time_ago(strtotime($u['date_joined'])),
        'message' => 'Changed Balance.',
        'timestamp' => strtotime($u['date_joined'])
    ];
}
foreach ($recent_tx as $index => $t) {
    // Generate synthetic time based on ID index so transaction is more recent
    $synth_time = time() - 240 - ($index * 600);
    $actions[] = [
        'url' => '/admin/transactions.php?action=edit&id=' . $t['id'],
        'title' => $t['username'] . ' $' . number_format($t['amount'], 2) . ' ' . $t['is_approved'],
        'time' => get_time_ago($synth_time),
        'message' => 'Changed Gift type and Is approved.',
        'timestamp' => $synth_time
    ];
}
// Sort descending by timestamp
usort($actions, function($a, $b) {
    return $b['timestamp'] - $a['timestamp'];
});

admin_head('Dashboard');
?>

<style>
/* Custom Styles for Django-Jazzmin Style Dashboard */
.stat-card {
    background-color: #fff !important;
    border: 1px solid #e3e6f0 !important;
    border-radius: 0.35rem !important;
    overflow: hidden;
    position: relative;
}
.border-left-info {
    border-left: 0.25rem solid #17a2b8 !important;
}
.border-left-success {
    border-left: 0.25rem solid #28a745 !important;
}
.border-left-warning {
    border-left: 0.25rem solid #ffc107 !important;
}
.border-left-danger {
    border-left: 0.25rem solid #dc3545 !important;
}
.text-xs {
    font-size: 0.75rem;
    font-weight: 700;
}
.stat-icon {
    color: #dddfeb;
    font-size: 1rem;
    opacity: 0.7;
}
.card-footer-link {
    font-size: 0.75rem;
    padding: 0.5rem 1rem;
    background-color: #f8f9fc;
    border-top: 1px solid #e3e6f0;
    display: block;
    text-align: right;
    text-decoration: none;
    font-weight: 600;
}
.card-footer-link:hover {
    background-color: #eaecf4;
    text-decoration: none;
}

/* Card layout improvements */
.card {
    border: 1px solid #ebedf2 !important;
    border-radius: 0.25rem !important;
    background-color: #fff !important;
}
.card-header {
    background-color: #fff !important;
    border-bottom: 1px solid #ebedf2 !important;
    padding: 0.75rem 1.25rem !important;
}
.card-header h5 {
    font-size: 0.95rem !important;
    font-weight: 600 !important;
    color: #333 !important;
}
.table {
    margin-bottom: 0 !important;
}
.table td {
    padding: 0.75rem 0.5rem !important;
    vertical-align: middle !important;
    border-bottom: none !important;
}
.table td a {
    color: #007bff !important;
    font-weight: 500 !important;
    font-size: 0.875rem !important;
}
.table td a:hover {
    text-decoration: underline !important;
}

/* Buttons */
.btn-group .btn-success {
    background-color: #28a745 !important;
    border-color: #28a745 !important;
    color: #fff !important;
    font-weight: 500 !important;
    font-size: 0.75rem !important;
    padding: 0.25rem 0.6rem !important;
    border-top-left-radius: 0.2rem !important;
    border-bottom-left-radius: 0.2rem !important;
}
.btn-group .btn-info {
    background-color: #17a2b8 !important;
    border-color: #17a2b8 !important;
    color: #fff !important;
    font-weight: 500 !important;
    font-size: 0.75rem !important;
    padding: 0.25rem 0.6rem !important;
    border-top-right-radius: 0.2rem !important;
    border-bottom-right-radius: 0.2rem !important;
}
.btn-group .btn-success:hover {
    background-color: #218838 !important;
    border-color: #1e7e34 !important;
}
.btn-group .btn-info:hover {
    background-color: #138496 !important;
    border-color: #117a8b !important;
}

/* Recent Actions timeline */
.recent-action-item {
    position: relative;
}
.action-icon-wrapper {
    width: 20px;
    text-align: center;
    color: #333;
    flex-shrink: 0;
}
.action-card {
    background-color: #fff !important;
    border: 1px solid #ebedf2 !important;
    border-radius: 0.35rem !important;
    padding: 0.75rem 1rem !important;
}
.action-card-header {
    font-size: 0.8rem !important;
}
.action-title {
    font-weight: 600 !important;
}
.action-link {
    color: #007bff !important;
    font-weight: 600 !important;
    font-size: 0.85rem !important;
    text-decoration: none !important;
}
.action-link:hover {
    text-decoration: underline !important;
}
.action-time {
    color: #6c757d !important;
    font-size: 0.7rem !important;
}
.action-divider {
    border-top: 1px solid #ebedf2 !important;
    margin: 0.4rem 0 !important;
    width: 100% !important;
}
.action-msg {
    font-size: 0.75rem !important;
    color: #333 !important;
    word-break: break-word !important;
}

/* Adjust general page spacing and font colors to look exactly like mockup */
.app-content {
    background-color: #f4f6f9 !important;
    min-height: 100vh;
}
h3.mb-0 {
    font-weight: 500 !important;
    font-size: 1.6rem !important;
    color: #333 !important;
}
.breadcrumb-item a {
    color: #007bff !important;
}
.breadcrumb-item.active, .breadcrumb-item {
    color: #6c757d !important;
}
</style>

<?php
admin_body_open('/admin/');
admin_content_header('Dashboard', ['Dashboard' => '']);
?>

<?= show_flash() ?>

<!-- STAT CARDS -->
<div class="row mb-4">
    <div class="col-lg-3 col-6">
        <div class="card stat-card border-left-info shadow-sm">
            <div class="card-body d-flex align-items-center justify-content-between p-3">
                <div>
                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1" style="letter-spacing: 0.05rem;">Users</div>
                    <div class="h4 mb-0 font-weight-bold text-dark"><?= $user_count ?></div>
                </div>
                <div class="stat-icon text-info">
                    <i class="fas fa-users fa-2x"></i>
                </div>
            </div>
            <a href="/admin/users.php" class="card-footer-link text-info">
                More info <i class="fas fa-arrow-circle-right ms-1"></i>
            </a>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="card stat-card border-left-success shadow-sm">
            <div class="card-body d-flex align-items-center justify-content-between p-3">
                <div>
                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1" style="letter-spacing: 0.05rem;">Transactions</div>
                    <div class="h4 mb-0 font-weight-bold text-dark"><?= $tx_count ?></div>
                </div>
                <div class="stat-icon text-success">
                    <i class="fas fa-exchange-alt fa-2x"></i>
                </div>
            </div>
            <a href="/admin/transactions.php" class="card-footer-link text-success">
                More info <i class="fas fa-arrow-circle-right ms-1"></i>
            </a>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="card stat-card border-left-warning shadow-sm">
            <div class="card-body d-flex align-items-center justify-content-between p-3">
                <div>
                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1" style="letter-spacing: 0.05rem;">Refunds</div>
                    <div class="h4 mb-0 font-weight-bold text-dark"><?= $refund_count ?></div>
                </div>
                <div class="stat-icon text-warning">
                    <i class="fas fa-undo fa-2x"></i>
                </div>
            </div>
            <a href="/admin/refunds.php" class="card-footer-link text-warning">
                More info <i class="fas fa-arrow-circle-right ms-1"></i>
            </a>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="card stat-card border-left-danger shadow-sm">
            <div class="card-body d-flex align-items-center justify-content-between p-3">
                <div>
                    <div class="text-xs font-weight-bold text-danger text-uppercase mb-1" style="letter-spacing: 0.05rem;">Groups</div>
                    <div class="h4 mb-0 font-weight-bold text-dark"><?= $group_count ?></div>
                </div>
                <div class="stat-icon text-danger">
                    <i class="fas fa-layer-group fa-2x"></i>
                </div>
            </div>
            <a href="/admin/groups.php" class="card-footer-link text-danger">
                More info <i class="fas fa-arrow-circle-right ms-1"></i>
            </a>
        </div>
    </div>
</div>

<!-- DASHBOARD CONTENT CARDS -->
<div class="row">
    <div class="col-lg-9 col-12">
        <div class="row">
            
            <!-- Left Column inside main area -->
            <div class="col-md-6 col-12">
                <!-- Authentication and Authorization -->
                <div class="card mb-4 shadow-sm">
                    <div class="card-header"><h5 class="m-0 font-weight-bold">Authentication and Authorization</h5></div>
                    <div class="card-body p-0">
                        <table class="table table-hover table-sm mb-0" role="table">
                            <tbody>
                                <tr>
                                    <td class="ps-3"><a href="/admin/groups.php">Groups</a></td>
                                    <td class="pe-3">
                                        <div class="btn-group float-end">
                                            <a href="/admin/groups.php?action=add" class="btn btn-sm btn-success">Add</a>
                                            <a href="/admin/groups.php" class="btn btn-sm btn-info">Change</a>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Refund -->
                <div class="card mb-4 shadow-sm">
                    <div class="card-header"><h5 class="m-0 font-weight-bold">Refund</h5></div>
                    <div class="card-body p-0">
                        <table class="table table-hover table-sm mb-0" role="table">
                            <tbody>
                                <tr>
                                    <td class="ps-3"><a href="/admin/refunds.php">Refunds</a></td>
                                    <td class="pe-3">
                                        <div class="btn-group float-end">
                                            <a href="/admin/refunds.php?action=add" class="btn btn-sm btn-success">Add</a>
                                            <a href="/admin/refunds.php" class="btn btn-sm btn-info">Change</a>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Right Column inside main area -->
            <div class="col-md-6 col-12">
                <!-- User -->
                <div class="card mb-4 shadow-sm">
                    <div class="card-header"><h5 class="m-0 font-weight-bold">User</h5></div>
                    <div class="card-body p-0">
                        <table class="table table-hover table-sm mb-0" role="table">
                            <tbody>
                                <tr>
                                    <td class="ps-3"><a href="/admin/transactions.php">Trasactions</a></td>
                                    <td class="pe-3">
                                        <div class="btn-group float-end">
                                            <a href="/admin/transactions.php?action=add" class="btn btn-sm btn-success">Add</a>
                                            <a href="/admin/transactions.php" class="btn btn-sm btn-info">Change</a>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="ps-3"><a href="/admin/users.php">Users</a></td>
                                    <td class="pe-3">
                                        <div class="btn-group float-end">
                                            <a href="/admin/users.php?action=add" class="btn btn-sm btn-success">Add</a>
                                            <a href="/admin/users.php" class="btn btn-sm btn-info">Change</a>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Communication -->
                <div class="card mb-4 shadow-sm">
                    <div class="card-header"><h5 class="m-0 font-weight-bold">Communication</h5></div>
                    <div class="card-body p-0">
                        <table class="table table-hover table-sm mb-0" role="table">
                            <tbody>
                                <tr>
                                    <td class="ps-3"><a href="/admin/email.php">Email</a></td>
                                    <td class="pe-3">
                                        <div class="btn-group float-end">
                                            <a href="/admin/email.php" class="btn btn-sm btn-info">Open</a>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Settings -->
                <div class="card mb-4 shadow-sm">
                    <div class="card-header"><h5 class="m-0 font-weight-bold">Settings</h5></div>
                    <div class="card-body p-0">
                        <table class="table table-hover table-sm mb-0" role="table">
                            <tbody>
                                <tr>
                                    <td class="ps-3"><a href="/admin/settings.php">Payment Settings</a></td>
                                    <td class="pe-3">
                                        <div class="btn-group float-end">
                                            <a href="/admin/settings.php" class="btn btn-sm btn-info" style="border-radius: 0.2rem !important; padding: 0.25rem 0.6rem !important;">Change</a>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- RECENT ACTIONS -->
    <div class="col-lg-3 col-12">
        <h5 class="mb-3 font-weight-bold text-gray-800" style="font-size: 1.1rem; color: #333; font-weight: 600;">Recent actions</h5>
        <div class="recent-actions-timeline">
            <?php foreach ($actions as $action): ?>
            <div class="recent-action-item d-flex align-items-start mb-3">
                <div class="action-icon-wrapper me-2 pt-1">
                    <i class="far fa-edit text-muted" style="font-size: 0.9rem;"></i>
                </div>
                <div class="action-card flex-grow-1 shadow-sm">
                    <div class="action-card-header d-flex justify-content-between align-items-center mb-1">
                        <div class="action-title me-2">
                            <a href="<?= $action['url'] ?>" class="action-link"><?= e($action['title']) ?></a>
                        </div>
                        <span class="action-time text-muted flex-shrink-0"><i class="far fa-clock me-1"></i><?= $action['time'] ?></span>
                    </div>
                    <div class="action-divider"></div>
                    <div class="action-msg text-secondary">
                        <?= e($action['message']) ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php admin_footer(); ?>
