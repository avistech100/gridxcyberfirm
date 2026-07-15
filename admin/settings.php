<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/admin_layout.php';
admin_required();
set_active_nav('settings');

$db = get_db();
$errors = [];
$success = false;

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

<?php admin_footer(); ?>
