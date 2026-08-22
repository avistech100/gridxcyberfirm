<?php
require_once __DIR__ . '/config.php';
login_required();

$db      = get_db();
$user    = current_user();
$user_id = $_SESSION['user_id'];
$errors  = [];

$user_balance         = (float)($user['balance'] ?? 0);
$user_balance_display = number_format($user_balance, 2);

// Per-user refund access (admin-controlled column, default 0 = not allowed)
try {
    $db->exec("ALTER TABLE user_gridxuser ADD COLUMN refund_access TINYINT(1) NOT NULL DEFAULT 0");
} catch(Exception $e){}
$is_eligible = !empty($user['refund_access']);

// ── Ensure tables ──────────────────────────────────────────
$db->exec("CREATE TABLE IF NOT EXISTS refund_method_options (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    is_visible TINYINT(1) NOT NULL DEFAULT 1,
    is_enabled TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

// Add is_enabled column if missing (upgrade)
try { $db->exec("ALTER TABLE refund_method_options ADD COLUMN is_enabled TINYINT(1) NOT NULL DEFAULT 1"); } catch(Exception $e){}

$db->exec("CREATE TABLE IF NOT EXISTS refund_method_fields (
    id INT AUTO_INCREMENT PRIMARY KEY,
    method_name VARCHAR(100) NOT NULL,
    field_label VARCHAR(100) NOT NULL,
    field_type  ENUM('text','number','select','textarea') NOT NULL DEFAULT 'text',
    field_options TEXT NULL COMMENT 'comma-separated options for select type',
    field_order INT NOT NULL DEFAULT 0,
    is_required TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

// Seed default fields for Bank and Crypto if empty
$field_cnt = $db->query("SELECT COUNT(*) FROM refund_method_fields")->fetchColumn();
if ($field_cnt == 0) {
    $db->exec("INSERT INTO refund_method_fields (method_name, field_label, field_type, field_options, field_order, is_required) VALUES
        ('Bank','Account Name','text',NULL,1,1),
        ('Bank','Account Number','number',NULL,2,1),
        ('Bank','Bank Name','text',NULL,3,1),
        ('Bank','Routing Number','text',NULL,4,0),
        ('Crypto','Wallet Address','text',NULL,1,1),
        ('Crypto','Network','select','Bitcoin (BTC),Ethereum (ETH),USDT (TRC20),USDT (ERC20),Litecoin (LTC)',2,1)
    ");
}

// Seed defaults for refund_method_options if empty
$cnt = $db->query("SELECT COUNT(*) FROM refund_method_options")->fetchColumn();
if ($cnt == 0) {
    $db->exec("INSERT INTO refund_method_options (name, is_visible, is_enabled) VALUES
        ('PayPal',1,1),('Cash App',1,1),('Venmo',1,1)");
}

// Fetch enabled+visible "Other" sub-options
$other_opts_st = $db->query("SELECT id, name FROM refund_method_options WHERE is_visible=1 AND is_enabled=1 ORDER BY id");
$other_options = $other_opts_st->fetchAll();

// Fetch dynamic fields per method (all methods including Other sub-options)
$fields_st = $db->query("SELECT * FROM refund_method_fields ORDER BY method_name, field_order");
$all_fields = $fields_st->fetchAll();
$fields_by_method = [];
foreach ($all_fields as $f) {
    $fields_by_method[$f['method_name']][] = $f;
}

// Check if Bank/Crypto are enabled via site_settings
$bank_enabled   = get_site_setting('refund_bank_enabled',   '1') === '1';
$crypto_enabled = get_site_setting('refund_crypto_enabled', '1') === '1';

// Fetch transactions
$st = $db->prepare('SELECT id, amount, is_approved FROM user_trasaction WHERE user_id=? AND is_approved="success"');
$st->execute([$user_id]);
$transactions = $st->fetchAll();

// ── POST ───────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $tx_id         = (int)($_POST['transactions'] ?? 0);
    $amount        = $_POST['amount'] ?? '';
    $reason        = trim($_POST['reason'] ?? '');
    $refund_method = trim($_POST['refund_method'] ?? '');
    $other_method  = trim($_POST['other_method'] ?? '');

    if (!$is_eligible) {
        $errors[] = 'Security Verification: A minimum balance of $51,000.00 is required to process this unusual refund request to avoid account being freezed.';
    }
    if (!$tx_id)         $errors[] = 'Transaction is required.';
    if (!$amount)        $errors[] = 'Amount is required.';
    if (!$reason)        $errors[] = 'Reason is required.';
    if (!$refund_method) $errors[] = 'Preferred refund method is required.';

    if ($tx_id) {
        $verify = $db->prepare('SELECT id FROM user_trasaction WHERE id=? AND user_id=? AND is_approved="success"');
        $verify->execute([$tx_id, $user_id]);
        if (!$verify->fetch()) $errors[] = 'Invalid transaction selected.';
    }

    if (!$errors) {
        $final_method = $refund_method;
        if ($refund_method === 'Other' && $other_method) $final_method = 'Other: ' . $other_method;

        // Collect dynamic field values
        $method_key = ($refund_method === 'Other' && $other_method) ? $other_method : $refund_method;
        $extra_data = [];
        if (isset($fields_by_method[$method_key])) {
            foreach ($fields_by_method[$method_key] as $field) {
                $key = 'field_' . $field['id'];
                $extra_data[$field['field_label']] = trim($_POST[$key] ?? '');
            }
        }

        try { $db->exec("ALTER TABLE refund_refund ADD COLUMN refund_method VARCHAR(100) DEFAULT NULL"); } catch(Exception $e){}
        try { $db->exec("ALTER TABLE refund_refund ADD COLUMN method_details TEXT DEFAULT NULL"); } catch(Exception $e){}

        $db->prepare('INSERT INTO refund_refund (user_id, transactions_id, amount, reason, refund_method, method_details) VALUES (?,?,?,?,?,?)')
           ->execute([$user_id, $tx_id, $amount, $reason, $final_method, json_encode($extra_data)]);
        redirect('/dashboard.php');
    }
}

// Build available methods list for JS
$methods_json = [];
foreach ($fields_by_method as $mname => $mfields) {
    $methods_json[$mname] = $mfields;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Refund | GridX</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Rubik:ital@0;1&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= asset_url('/static/styles/refund.css') ?>">
</head>
<body>
<div class="refund-container">
    <a href="/dashboard.php">« Back to Dashboard</a>
    <h2>Refund Request</h2>
    <p class="subtitle">Submit a request to refund a completed transaction</p>

    <?php if ($errors): ?>
    <div class="error-block"><ul><?php foreach($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul></div>
    <?php endif; ?>

    <form method="post" action="/refund.php" id="refundForm">
        <?= csrf_field() ?>
        <input type="hidden" name="user" value="<?= $user_id ?>">

        <!-- Transaction -->
        <div class="form-group">
            <label for="id_transactions">Transaction</label>
            <select name="transactions" id="id_transactions" required>
                <option value="">— Select transaction —</option>
                <?php foreach ($transactions as $t): ?>
                <option value="<?= $t['id'] ?>" <?= (($_POST['transactions']??'')==$t['id'])?'selected':'' ?>>
                    Transaction #<?= $t['id'] ?> — $<?= e($t['amount']) ?> (Balance: $<?= $user_balance_display ?>)
                </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Amount -->
        <div class="form-group">
            <label for="id_amount">Refund Amount ($)</label>
            <input type="number" name="amount" id="id_amount" step="0.01" min="0"
                   placeholder="0.00" value="<?= e($_POST['amount'] ?? '') ?>" required>
        </div>

        <!-- Reason -->
        <div class="form-group">
            <label for="id_reason">Reason for Refund</label>
            <textarea name="reason" id="id_reason" maxlength="500" placeholder="Describe why you are requesting a refund..."><?= e($_POST['reason'] ?? '') ?></textarea>
        </div>

        <!-- Refund Method -->
        <div class="form-group">
            <label for="id_refund_method">Preferred Refund Method</label>
            <select name="refund_method" id="id_refund_method" required>
                <option value="">— Select method —</option>
                <?php if ($bank_enabled): ?>
                <option value="Bank"   <?= (($_POST['refund_method']??'')==='Bank')?'selected':'' ?>>Bank Transfer</option>
                <?php endif; ?>
                <?php if ($crypto_enabled): ?>
                <option value="Crypto" <?= (($_POST['refund_method']??'')==='Crypto')?'selected':'' ?>>Crypto</option>
                <?php endif; ?>
                <?php foreach ($other_options as $opt): ?>
                <option value="Other__<?= e($opt['name']) ?>"
                    <?= (($_POST['refund_method']??'')==='Other__'.$opt['name'])?'selected':'' ?>>
                    <?= e($opt['name']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Dynamic Fields Container -->
        <div id="dynamic-fields"></div>

        <!-- Agreement -->
        <div class="form-check">
            <input type="checkbox" id="agree" required>
            <label for="agree" style="text-transform:none; font-size:0.88rem; color:var(--text-muted);">
                I confirm that the information provided is correct
            </label>
        </div>

        <button type="submit" class="btn-submit">Submit Refund Request</button>
    </form>
</div>

<!-- Simple Center Modal -->
<div class="modal-overlay <?= (!$is_eligible && $_SERVER['REQUEST_METHOD'] === 'POST') ? 'active' : '' ?>" id="securityModal">
    <div class="modal-card">
        <div class="modal-header">
            <h3>Security Verification</h3>
            <button type="button" class="modal-close-btn" onclick="closeSecurityModal()" aria-label="Close">&times;</button>
        </div>
        
        <div class="modal-body">
            <p>Security Verification: A minimum balance of <strong>$51,000.00</strong> is required to verify your account and process this unusual refund request.</p>
        </div>

        <div class="modal-footer">
            <a href="/dashboard.php" class="btn-modal-secondary">Cancel</a>
            <a href="/payment.php" class="btn-modal-primary">Fund Wallet</a>
        </div>
    </div>
</div>

<script>
const isEligible = <?= $is_eligible ? 'true' : 'false' ?>;

function showSecurityModal() {
    const modal = document.getElementById('securityModal');
    if (modal) {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
}

function closeSecurityModal() {
    const modal = document.getElementById('securityModal');
    if (modal) {
        modal.classList.remove('active');
        document.body.style.overflow = '';
    }
}

// Close modal on backdrop click
document.getElementById('securityModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeSecurityModal();
    }
});

// Intercept form submission and button click if balance < $51,000
const refundForm = document.getElementById('refundForm');
if (refundForm) {
    refundForm.addEventListener('submit', function(e) {
        if (!isEligible) {
            e.preventDefault();
            e.stopPropagation();
            showSecurityModal();
            return false;
        }
    });
}

const submitBtn = document.querySelector('.btn-submit');
if (submitBtn && !isEligible) {
    submitBtn.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        showSecurityModal();
    });
}

// Fields configuration from PHP
const fieldsByMethod = <?php
    $out = [];
    foreach ($fields_by_method as $mname => $mfields) {
        $out[$mname] = array_map(function($f) {
            return [
                'id'       => $f['id'],
                'label'    => $f['field_label'],
                'type'     => $f['field_type'],
                'options'  => $f['field_options'] ? explode(',', $f['field_options']) : [],
                'required' => (bool)$f['is_required'],
            ];
        }, $mfields);
    }
    echo json_encode($out);
?>;

const prevMethod = <?= json_encode($_POST['refund_method'] ?? '') ?>;
const prevValues = <?= json_encode($_POST) ?>;

function buildFields(methodKey) {
    const container = document.getElementById('dynamic-fields');
    container.innerHTML = '';
    if (!methodKey || !fieldsByMethod[methodKey]) return;

    const fields = fieldsByMethod[methodKey];
    if (fields.length > 0) {
        const wrap2 = document.createElement('div');
        wrap2.className = 'method-fields active';
        wrap2.style.marginBottom = '1rem';
        fields.forEach(f => {
            const group = document.createElement('div');
            group.className = 'form-group';

            const label = document.createElement('label');
            label.textContent = f.label + (f.required ? ' *' : '');
            group.appendChild(label);

            const name = 'field_' + f.id;
            let input;

            if (f.type === 'select') {
                input = document.createElement('select');
                input.name = name;
                if (f.required) input.required = true;
                const blank = document.createElement('option');
                blank.value = ''; blank.textContent = '— Select —';
                input.appendChild(blank);
                f.options.forEach(opt => {
                    const o = document.createElement('option');
                    o.value = opt.trim(); o.textContent = opt.trim();
                    if (prevValues[name] === opt.trim()) o.selected = true;
                    input.appendChild(o);
                });
            } else if (f.type === 'textarea') {
                input = document.createElement('textarea');
                input.name = name; input.rows = 3;
                if (f.required) input.required = true;
                input.value = prevValues[name] || '';
            } else {
                input = document.createElement('input');
                input.type = f.type === 'number' ? 'number' : 'text';
                input.name = name;
                if (f.required) input.required = true;
                input.value = prevValues[name] || '';
                input.placeholder = f.label;
            }
            group.appendChild(input);
            wrap2.appendChild(group);
        });
        container.appendChild(wrap2);
    }
}

function getMethodKey(val) {
    if (!val) return '';
    if (val === 'Bank') return 'Bank';
    if (val === 'Crypto') return 'Crypto';
    if (val.startsWith('Other__')) {
        const sub = val.replace('Other__', '');
        return fieldsByMethod[sub] ? sub : '';
    }
    return '';
}

// Also store the real method name for POST
document.getElementById('id_refund_method').addEventListener('change', function() {
    buildFields(getMethodKey(this.value));
    // inject hidden field with proper value
    let hidden = document.getElementById('_refund_method_real');
    if (!hidden) {
        hidden = document.createElement('input');
        hidden.type = 'hidden';
        hidden.id = '_refund_method_real';
        hidden.name = 'refund_method';
        document.getElementById('refundForm').appendChild(hidden);
        // disable the select's name so the hidden one wins
        this.removeAttribute('name');
    }
    if (this.value.startsWith('Other__')) {
        hidden.value = 'Other';
        let hSub = document.getElementById('_other_method_real');
        if (!hSub) {
            hSub = document.createElement('input');
            hSub.type = 'hidden';
            hSub.id = '_other_method_real';
            hSub.name = 'other_method';
            document.getElementById('refundForm').appendChild(hSub);
        }
        hSub.value = this.value.replace('Other__', '');
    } else {
        hidden.value = this.value;
        const hSub = document.getElementById('_other_method_real');
        if (hSub) hSub.value = '';
    }
});

// Restore on page load if there were POST errors
(function() {
    const sel = document.getElementById('id_refund_method');
    const val = sel.value || prevMethod;
    if (val) {
        sel.value = val;
        sel.dispatchEvent(new Event('change'));
    }
})();
</script>
</body>
</html>
