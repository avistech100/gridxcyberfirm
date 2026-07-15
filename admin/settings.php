<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/admin_layout.php';
admin_required();
set_active_nav('settings');

$db = get_db();
$errors = [];
$success = false;

// Ensure tables exist
$db->exec("CREATE TABLE IF NOT EXISTS site_settings (setting_key VARCHAR(100) PRIMARY KEY, setting_value TEXT NOT NULL DEFAULT '') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
$db->exec("CREATE TABLE IF NOT EXISTS refund_method_options (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(100) NOT NULL, is_visible TINYINT(1) NOT NULL DEFAULT 1) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
$cnt = $db->query("SELECT COUNT(*) FROM refund_method_options")->fetchColumn();
if ($cnt == 0) {
    $db->exec("INSERT INTO refund_method_options (name, is_visible) VALUES ('PayPal',1),('Cash App',1),('Venmo',1)");
}
$db->exec("CREATE TABLE IF NOT EXISTS gift_card_uploads (id INT AUTO_INCREMENT PRIMARY KEY, transaction_id INT NOT NULL, user_id INT NOT NULL, image_path VARCHAR(255) NOT NULL, uploaded_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

// ── Handle Self-Registration Toggle ────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_site_settings'])) {
    verify_csrf();
    $allow_reg = isset($_POST['allow_self_registration']) ? '1' : '0';
    $db->prepare('INSERT INTO site_settings (setting_key, setting_value) VALUES (?,?) ON DUPLICATE KEY UPDATE setting_value=?')
       ->execute(['allow_self_registration', $allow_reg, $allow_reg]);
    set_flash('success', 'Site settings saved.');
    redirect('/admin/settings.php');
}

// ── Handle Refund Options ───────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_refund_options'])) {
    verify_csrf();
    // Add new option
    $new_opt = trim($_POST['new_refund_option'] ?? '');
    if ($new_opt !== '') {
        $db->prepare('INSERT INTO refund_method_options (name, is_visible) VALUES (?,1)')->execute([$new_opt]);
    }
    // Update visibility
    $all_ids = $db->query('SELECT id FROM refund_method_options')->fetchAll(PDO::FETCH_COLUMN);
    foreach ($all_ids as $oid) {
        $visible = isset($_POST['visible_' . $oid]) ? 1 : 0;
        $db->prepare('UPDATE refund_method_options SET is_visible=? WHERE id=?')->execute([$visible, $oid]);
    }
    // Delete
    $del_id = (int)($_POST['delete_refund_option'] ?? 0);
    if ($del_id) $db->prepare('DELETE FROM refund_method_options WHERE id=?')->execute([$del_id]);
    set_flash('success', 'Refund options saved.');
    redirect('/admin/settings.php');
}

$allow_reg_val    = $db->query("SELECT setting_value FROM site_settings WHERE setting_key='allow_self_registration'")->fetchColumn();
$allow_reg_val    = ($allow_reg_val !== false) ? $allow_reg_val : '0';
$refund_opts      = $db->query('SELECT * FROM refund_method_options ORDER BY id')->fetchAll();
$gift_card_uploads= $db->query('SELECT g.*, u.username, t.amount FROM gift_card_uploads g
    JOIN user_gridxuser u ON g.user_id=u.id
    JOIN user_trasaction t ON g.transaction_id=t.id
    ORDER BY g.uploaded_at DESC LIMIT 50')->fetchAll();

// Define default values
$defaults = [
    'btc_address'  => 'bc1qvatednkwp0m5rjzpftzrrgmjf8wsw5ze98f4xe',
    'btc_qr'       => '/static/images/btc.jpeg',
    'eth_address'  => '0xF674e23db956A1830Cb6b42b712e84470D1A0971',
    'eth_qr'       => '/static/images/eth.jpeg',
    'usdt_address' => '0xF674e23db956A1830Cb6b42b712e84470D1A0971',
    'usdt_qr'      => '/static/images/usdt.jpeg',
    'ltc_address'  => 'ltc1qnj03x0a6r77sz9a8d4aauwsrawe6u8tcv4d6u9',
    'ltc_qr'       => '/static/images/ltc.jpeg',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $coins = ['btc', 'eth', 'usdt', 'ltc'];
    foreach ($coins as $coin) {
        // Handle address update
        if (isset($_POST[$coin . '_address'])) {
            $addr = trim($_POST[$coin . '_address']);
            if ($addr !== '') {
                set_payment_setting($coin . '_address', $addr);
            } else {
                $errors[] = strtoupper($coin) . ' address cannot be empty.';
            }
        }

        // Handle QR code upload
        if (isset($_FILES[$coin . '_qr']) && $_FILES[$coin . '_qr']['error'] !== UPLOAD_ERR_NO_FILE) {
            $file = $_FILES[$coin . '_qr'];
            if ($file['error'] !== UPLOAD_ERR_OK) {
                $errors[] = 'Error uploading ' . strtoupper($coin) . ' QR code image.';
            } else {
                // Validate file type
                $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/jpg'];
                $file_type = function_exists('mime_content_type') ? mime_content_type($file['tmp_name']) : $file['type'];
                if (!in_array($file_type, $allowed_types) && !in_array(strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                    $errors[] = 'Invalid file type for ' . strtoupper($coin) . ' QR code. Only JPG, JPEG, PNG, GIF, and WEBP are allowed.';
                } else {
                    // Create directory if not exists
                    $upload_dir = __DIR__ . '/../static/images/uploads';
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0755, true);
                    }

                    // Generate a unique filename to bypass caching issues
                    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                    if (!$ext) {
                        $ext = ($file_type === 'image/png') ? 'png' : (($file_type === 'image/webp') ? 'webp' : 'jpeg');
                    }
                    $filename = 'qr_' . $coin . '_' . time() . '.' . $ext;
                    $target_path = $upload_dir . '/' . $filename;

                    if (move_uploaded_file($file['tmp_name'], $target_path)) {
                        set_payment_setting($coin . '_qr', '/static/images/uploads/' . $filename);
                    } else {
                        $errors[] = 'Failed to save ' . strtoupper($coin) . ' QR code image file.';
                    }
                }
            }
        }
    }

    if (empty($errors)) {
        $success = true;
        set_flash('success', 'Payment Settings updated successfully.');
        redirect('/admin/settings.php');
    }
}

// Fetch values for editing
$btc_address  = get_payment_setting('btc_address', $defaults['btc_address']);
$btc_qr       = get_payment_setting('btc_qr', $defaults['btc_qr']);
$eth_address  = get_payment_setting('eth_address', $defaults['eth_address']);
$eth_qr       = get_payment_setting('eth_qr', $defaults['eth_qr']);
$usdt_address = get_payment_setting('usdt_address', $defaults['usdt_address']);
$usdt_qr      = get_payment_setting('usdt_qr', $defaults['usdt_qr']);
$ltc_address  = get_payment_setting('ltc_address', $defaults['ltc_address']);
$ltc_qr       = get_payment_setting('ltc_qr', $defaults['ltc_qr']);

admin_head('Payment Settings');
admin_body_open('/admin/settings.php');
admin_content_header('Payment Settings', ['Payment Settings' => '']);
?>

<style>
    .mono {
        font-family: SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
    }
</style>

<form method="post" enctype="multipart/form-data">
    <?= csrf_field() ?>
    
    <?php if ($success || !empty($_SESSION['flash'])): ?>
        <?= show_flash() ?>
    <?php endif; ?>
    
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                    <li><?= e($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="row">
        <!-- BITCOIN -->
        <div class="col-md-6 col-12 mb-4">
            <div class="card card-primary card-outline shadow-sm h-100">
                <div class="card-header d-flex align-items-center justify-content-between bg-white">
                    <h5 class="m-0 font-weight-bold text-dark"><i class="fab fa-btc me-2 text-warning"></i>Bitcoin (BTC)</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label font-weight-bold">Wallet Address</label>
                        <input type="text" name="btc_address" class="form-control mono" value="<?= e($btc_address) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label font-weight-bold">Upload QR Code Image</label>
                        <input type="file" name="btc_qr" class="form-control" accept="image/*">
                        <small class="text-muted">Leave empty to keep current QR code.</small>
                    </div>
                    <div class="mt-4 p-3 bg-light rounded border">
                        <div class="row align-items-center">
                            <div class="col-sm-3 text-center mb-3 mb-sm-0">
                                <img src="<?= e($btc_qr) ?>" alt="BTC QR" class="img-thumbnail" style="max-height: 100px; max-width: 100px;">
                            </div>
                            <div class="col-sm-9">
                                <span class="d-block text-xs text-uppercase text-secondary font-weight-bold">Current Address</span>
                                <span class="text-sm mono text-break font-weight-bold"><?= e($btc_address) ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ETHEREUM -->
        <div class="col-md-6 col-12 mb-4">
            <div class="card card-primary card-outline shadow-sm h-100">
                <div class="card-header d-flex align-items-center justify-content-between bg-white">
                    <h5 class="m-0 font-weight-bold text-dark"><i class="fab fa-ethereum me-2 text-primary"></i>Ethereum (ETH)</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label font-weight-bold">Wallet Address</label>
                        <input type="text" name="eth_address" class="form-control mono" value="<?= e($eth_address) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label font-weight-bold">Upload QR Code Image</label>
                        <input type="file" name="eth_qr" class="form-control" accept="image/*">
                        <small class="text-muted">Leave empty to keep current QR code.</small>
                    </div>
                    <div class="mt-4 p-3 bg-light rounded border">
                        <div class="row align-items-center">
                            <div class="col-sm-3 text-center mb-3 mb-sm-0">
                                <img src="<?= e($eth_qr) ?>" alt="ETH QR" class="img-thumbnail" style="max-height: 100px; max-width: 100px;">
                            </div>
                            <div class="col-sm-9">
                                <span class="d-block text-xs text-uppercase text-secondary font-weight-bold">Current Address</span>
                                <span class="text-sm mono text-break font-weight-bold"><?= e($eth_address) ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- TETHER (USDT) -->
        <div class="col-md-6 col-12 mb-4">
            <div class="card card-primary card-outline shadow-sm h-100">
                <div class="card-header d-flex align-items-center justify-content-between bg-white">
                    <h5 class="m-0 font-weight-bold text-dark"><i class="fas fa-dollar-sign me-2 text-success"></i>Tether (USDT)</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label font-weight-bold">Wallet Address</label>
                        <input type="text" name="usdt_address" class="form-control mono" value="<?= e($usdt_address) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label font-weight-bold">Upload QR Code Image</label>
                        <input type="file" name="usdt_qr" class="form-control" accept="image/*">
                        <small class="text-muted">Leave empty to keep current QR code.</small>
                    </div>
                    <div class="mt-4 p-3 bg-light rounded border">
                        <div class="row align-items-center">
                            <div class="col-sm-3 text-center mb-3 mb-sm-0">
                                <img src="<?= e($usdt_qr) ?>" alt="USDT QR" class="img-thumbnail" style="max-height: 100px; max-width: 100px;">
                            </div>
                            <div class="col-sm-9">
                                <span class="d-block text-xs text-uppercase text-secondary font-weight-bold">Current Address</span>
                                <span class="text-sm mono text-break font-weight-bold"><?= e($usdt_address) ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- LITECOIN (LTC) -->
        <div class="col-md-6 col-12 mb-4">
            <div class="card card-primary card-outline shadow-sm h-100">
                <div class="card-header d-flex align-items-center justify-content-between bg-white">
                    <h5 class="m-0 font-weight-bold text-dark"><i class="fas fa-coins me-2 text-info"></i>Litecoin (LTC)</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label font-weight-bold">Wallet Address</label>
                        <input type="text" name="ltc_address" class="form-control mono" value="<?= e($ltc_address) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label font-weight-bold">Upload QR Code Image</label>
                        <input type="file" name="ltc_qr" class="form-control" accept="image/*">
                        <small class="text-muted">Leave empty to keep current QR code.</small>
                    </div>
                    <div class="mt-4 p-3 bg-light rounded border">
                        <div class="row align-items-center">
                            <div class="col-sm-3 text-center mb-3 mb-sm-0">
                                <img src="<?= e($ltc_qr) ?>" alt="LTC QR" class="img-thumbnail" style="max-height: 100px; max-width: 100px;">
                            </div>
                            <div class="col-sm-9">
                                <span class="d-block text-xs text-uppercase text-secondary font-weight-bold">Current Address</span>
                                <span class="text-sm mono text-break font-weight-bold"><?= e($ltc_address) ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-3 mb-5">
        <div class="col-12">
            <button type="submit" class="btn btn-success btn-lg px-5 shadow-sm"><i class="fas fa-save me-2"></i>Save All Settings</button>
        </div>
    </div>
</form>

<!-- ── SITE SETTINGS: Self-Registration ── -->
<div class="card card-primary card-outline mt-4">
    <div class="card-header bg-white">
        <h5 class="m-0 font-weight-bold text-dark"><i class="fas fa-user-plus me-2"></i>User Registration Control</h5>
    </div>
    <div class="card-body">
        <p class="text-muted mb-3">By default, only admins can create user accounts. Enable the toggle below to allow visitors to self-register.</p>
        <form method="post">
            <?= csrf_field() ?>
            <div class="form-check form-switch mb-3">
                <input class="form-check-input" type="checkbox" name="allow_self_registration" id="allow_self_reg"
                       <?= $allow_reg_val === '1' ? 'checked' : '' ?> style="width:2.5rem; height:1.3rem;">
                <label class="form-check-label ms-2" for="allow_self_reg">
                    <strong>Allow Self-Registration</strong>
                    <small class="d-block text-muted">When ON, the sign-up page is accessible to visitors. When OFF (default), it is hidden.</small>
                </label>
            </div>
            <button type="submit" name="save_site_settings" class="btn btn-primary"><i class="fas fa-save me-1"></i>Save</button>
        </form>
    </div>
</div>

<!-- ── REFUND METHOD OPTIONS ── -->
<div class="card card-primary card-outline mt-4">
    <div class="card-header bg-white">
        <h5 class="m-0 font-weight-bold text-dark"><i class="fas fa-exchange-alt me-2"></i>Refund Method Options (under "Other")</h5>
    </div>
    <div class="card-body">
        <p class="text-muted mb-3">Bank and Crypto are always visible. Manage additional options that appear when a user selects "Other" on the refund page.</p>
        <form method="post">
            <?= csrf_field() ?>
            <table class="table table-sm table-bordered mb-3">
                <thead><tr><th>Option Name</th><th style="width:100px;">Visible</th><th style="width:80px;">Delete</th></tr></thead>
                <tbody>
                <?php foreach ($refund_opts as $opt): ?>
                <tr>
                    <td><?= e($opt['name']) ?></td>
                    <td class="text-center">
                        <input type="checkbox" name="visible_<?= $opt['id'] ?>" <?= $opt['is_visible'] ? 'checked' : '' ?>>
                    </td>
                    <td class="text-center">
                        <button type="submit" name="delete_refund_option" value="<?= $opt['id'] ?>"
                                class="btn btn-xs btn-danger btn-sm"
                                onclick="return confirm('Delete this option?')">✕</button>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (!$refund_opts): ?>
                <tr><td colspan="3" class="text-muted text-center">No options yet.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
            <div class="d-flex gap-2 align-items-center mb-3">
                <input type="text" name="new_refund_option" class="form-control" style="max-width:260px;" placeholder="Add new option (e.g. Zelle)">
                <button type="submit" name="save_refund_options" class="btn btn-success btn-sm"><i class="fas fa-plus me-1"></i>Add &amp; Save</button>
            </div>
            <button type="submit" name="save_refund_options" class="btn btn-primary btn-sm"><i class="fas fa-save me-1"></i>Save Visibility Changes</button>
        </form>
    </div>
</div>

<!-- ── GIFT CARD UPLOADS VIEWER ── -->
<div class="card card-primary card-outline mt-4 mb-5">
    <div class="card-header bg-white">
        <h5 class="m-0 font-weight-bold text-dark"><i class="fas fa-gift me-2"></i>Gift Card Image Uploads</h5>
    </div>
    <div class="card-body">
        <?php if ($gift_card_uploads): ?>
        <table class="table table-sm table-hover table-bordered">
            <thead><tr><th>#</th><th>User</th><th>Transaction</th><th>Amount</th><th>Uploaded</th><th>Image</th></tr></thead>
            <tbody>
            <?php foreach ($gift_card_uploads as $gcu): ?>
            <tr>
                <td><?= $gcu['id'] ?></td>
                <td><?= e($gcu['username']) ?></td>
                <td>#<?= $gcu['transaction_id'] ?></td>
                <td>$<?= e($gcu['amount']) ?></td>
                <td><?= e(substr($gcu['uploaded_at'],0,16)) ?></td>
                <td><a href="<?= e($gcu['image_path']) ?>" target="_blank">
                    <img src="<?= e($gcu['image_path']) ?>" alt="gift card" style="height:50px; border-radius:4px; border:1px solid #ddd;">
                </a></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <p class="text-muted">No gift card images uploaded yet.</p>
        <?php endif; ?>
    </div>
</div>

<?php admin_footer(); ?>
