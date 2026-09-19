<?php
require_once __DIR__ . '/config.php';
login_required();

$db      = get_db();
$user_id = $_SESSION['user_id'];
$id      = (int)($_GET['id'] ?? 0);

// Fetch subscription
$st = $db->prepare('SELECT * FROM antispyware_subscriptions WHERE id=? AND user_id=?');
$st->execute([$id, $user_id]);
$sub = $st->fetch();
if (!$sub) redirect('/antispyware.php');
if ($sub['is_approved'] !== 'initialize') redirect('/dashboard.php');

// Crypto prices
function as_get_crypto_prices(): array {
    $cache_file = sys_get_temp_dir() . '/gridx_crypto_cache.json';
    if (file_exists($cache_file) && (time() - filemtime($cache_file)) < 300)
        return json_decode(file_get_contents($cache_file), true);
    $url = 'https://api.coingecko.com/api/v3/simple/price?ids=bitcoin,ethereum,tether,litecoin&vs_currencies=usd';
    $ctx = stream_context_create(['http'=>['timeout'=>10]]);
    $data = @file_get_contents($url, false, $ctx);
    if ($data) { file_put_contents($cache_file, $data); return json_decode($data, true); }
    return ['bitcoin'=>['usd'=>60000],'ethereum'=>['usd'=>3000],'tether'=>['usd'=>1],'litecoin'=>['usd'=>80]];
}
function as_convert(float $usd): array {
    $p = as_get_crypto_prices();
    return [
        'BTC'  => round($usd / $p['bitcoin']['usd'],  8),
        'ETH'  => round($usd / $p['ethereum']['usd'], 8),
        'USDT' => round($usd / $p['tether']['usd'],   2),
        'LTC'  => round($usd / $p['litecoin']['usd'], 8),
    ];
}

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $method = $_POST['payment_method'] ?? '';

    if ($method === 'Crypto') {
        $db->prepare('UPDATE antispyware_subscriptions SET payment_method=?,network=?,address_sent=?,crypto_amount=?,is_approved="pending" WHERE id=?')
           ->execute([$method, $_POST['network']??null, $_POST['address_sent']??null, $_POST['crypto_ammount']??null, $id]);
        set_flash('success', 'Your payment has been submitted and is awaiting confirmation.');
        redirect('/antispyware_awaiting.php?id=' . $id);

    } elseif ($method === 'Gift Card') {
        $db->prepare('UPDATE antispyware_subscriptions SET payment_method=?,gift_code=?,is_approved="pending" WHERE id=?')
           ->execute([$method, $_POST['gift_code']??null, $id]);

        if (isset($_FILES['gift_card_image']) && $_FILES['gift_card_image']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['gift_card_image'];
            if (in_array(mime_content_type($file['tmp_name']), ['image/jpeg','image/png','image/webp','image/gif'])) {
                $dir = __DIR__ . '/static/images/giftcards';
                if (!is_dir($dir)) mkdir($dir, 0755, true);
                $ext = pathinfo($file['name'], PATHINFO_EXTENSION) ?: 'jpg';
                $fn  = 'asgc_' . $user_id . '_' . $id . '_' . time() . '.' . $ext;
                move_uploaded_file($file['tmp_name'], $dir . '/' . $fn);
            }
        }
        set_flash('success', 'Gift card received. Awaiting admin verification.');
        redirect('/antispyware_awaiting.php?id=' . $id);
    }
}

$amount = (float)$sub['amount'];
$crypto = as_convert($amount);
$btc_address  = get_payment_setting('btc_address',  'bc1qvatednkwp0m5rjzpftzrrgmjf8wsw5ze98f4xe');
$btc_qr       = get_payment_setting('btc_qr',       '/static/images/btc.jpeg');
$eth_address  = get_payment_setting('eth_address',  '0xF674e23db956A1830Cb6b42b712e84470D1A0971');
$eth_qr       = get_payment_setting('eth_qr',       '/static/images/eth.jpeg');
$usdt_address = get_payment_setting('usdt_address', '0xF674e23db956A1830Cb6b42b712e84470D1A0971');
$usdt_qr      = get_payment_setting('usdt_qr',      '/static/images/usdt.jpeg');
$ltc_address  = get_payment_setting('ltc_address',  'ltc1qnj03x0a6r77sz9a8d4aauwsrawe6u8tcv4d6u9');
$ltc_qr       = get_payment_setting('ltc_qr',       '/static/images/ltc.jpeg');

$GIFT_CARDS = ['apple','itunes','google_play','amazon','steam','playstation','xbox','nintendo',
               'roblox','riot_games','blizzard','epic_games','ea_play','netflix','spotify',
               'youtube','hulu','disney_plus','uber','uber_eats','doordash','grubhub',
               'airbnb','booking','expedia','visa','mastercard','american_express','vanilla',
               'one4all','walmart','target','best_buy','ebay','costco','ikea','nike','adidas',
               'zara','hm','macys','starbucks','mcdonalds','kfc','subway','chipotle',
               'sephora','ulta','playstore','google','cashapp','paypal','razergold','paysafecard'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout | Antispyware Protection | GridX</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Rubik:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root {
            --primary: #2563eb;
            --primary-hover: #1d4ed8;
            --primary-light: #eff6ff;
            --primary-border: #bfdbfe;
            --dark: #0f172a;
            --navy: #1e293b;
            --text: #1e293b;
            --text-secondary: #475569;
            --text-muted: #64748b;
            --border: #e2e8f0;
            --border-hover: #cbd5e1;
            --bg: #f8fafc;
            --card-bg: #ffffff;
            --success-bg: #f0fdf4;
            --success-border: #bbf7d0;
            --success-text: #166534;
            --radius-sm: 8px;
            --radius-md: 12px;
            --shadow-sm: 0 1px 2px rgba(0, 0, 0, 0.04);
            --shadow-md: 0 4px 12px rgba(15, 23, 42, 0.06);
        }

        *, *::before, *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Rubik', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            line-height: 1.5;
            -webkit-font-smoothing: antialiased;
        }

        /* Nav */
        .co-nav {
            background: var(--navy);
            height: 56px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 1.5rem;
            position: sticky;
            top: 0;
            z-index: 50;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        }

        .co-nav-back {
            color: #cbd5e1;
            text-decoration: none;
            font-size: 0.88rem;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 10px;
            border-radius: var(--radius-sm);
            transition: all 0.15s ease;
        }

        .co-nav-back:hover {
            background: rgba(255, 255, 255, 0.1);
            color: #ffffff;
        }

        .co-nav-title {
            color: #ffffff;
            font-size: 0.95rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .co-nav-title i {
            color: #60a5fa;
        }

        .co-badge-ssl {
            color: #94a3b8;
            font-size: 0.78rem;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .co-badge-ssl i {
            color: #10b981;
        }

        /* Container */
        .co-container {
            max-width: 960px;
            margin: 0 auto;
            padding: 1.5rem 1rem 3rem;
        }

        .co-header {
            margin-bottom: 1.25rem;
        }

        .co-header h1 {
            font-size: 1.35rem;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 0.25rem;
        }

        .co-header p {
            font-size: 0.88rem;
            color: var(--text-secondary);
        }

        /* Two-Column Grid */
        .co-grid {
            display: grid;
            grid-template-columns: 1.35fr 1fr;
            gap: 1.25rem;
            align-items: start;
        }

        /* Cards */
        .co-card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-sm);
            padding: 1.25rem;
        }

        /* Tabs */
        .co-tabs {
            display: grid;
            grid-template-columns: 1fr 1fr;
            background: #f1f5f9;
            padding: 4px;
            border-radius: var(--radius-sm);
            gap: 4px;
            margin-bottom: 1.25rem;
        }

        .co-tab {
            background: transparent;
            border: none;
            padding: 0.65rem 0.85rem;
            font-family: inherit;
            font-size: 0.88rem;
            font-weight: 600;
            color: var(--text-secondary);
            border-radius: 6px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.15s ease;
        }

        .co-tab.active {
            background: var(--card-bg);
            color: var(--primary);
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
        }

        .co-tab-content {
            display: none;
        }

        .co-tab-content.active {
            display: block;
        }

        /* Form elements */
        .co-field {
            margin-bottom: 1rem;
        }

        .co-label {
            display: block;
            font-size: 0.82rem;
            font-weight: 600;
            color: var(--text-secondary);
            margin-bottom: 0.4rem;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }

        .co-select, .co-input {
            width: 100%;
            padding: 0.7rem 0.85rem;
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            font-family: inherit;
            font-size: 0.92rem;
            color: var(--text);
            background: #ffffff;
            transition: border-color 0.15s ease, box-shadow 0.15s ease;
        }

        .co-select:focus, .co-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
        }

        /* Crypto Payment Details */
        .co-amount-box {
            background: var(--primary-light);
            border: 1px solid var(--primary-border);
            border-radius: var(--radius-sm);
            padding: 0.85rem 1rem;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .co-amount-box span {
            font-size: 0.82rem;
            font-weight: 500;
            color: var(--text-secondary);
        }

        .co-amount-box strong {
            font-size: 1.15rem;
            font-weight: 700;
            color: var(--primary);
        }

        .co-address-wrap {
            margin-bottom: 1rem;
        }

        .co-address-box {
            display: flex;
            align-items: center;
            background: #f8fafc;
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            padding: 0.35rem 0.4rem 0.35rem 0.75rem;
            gap: 8px;
        }

        .co-address-text {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: 0.8rem;
            color: var(--dark);
            word-break: break-all;
            flex: 1;
        }

        .co-copy-btn {
            background: var(--card-bg);
            border: 1px solid var(--border);
            color: var(--text-secondary);
            padding: 0.5rem 0.75rem;
            border-radius: 6px;
            font-family: inherit;
            font-size: 0.78rem;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            white-space: nowrap;
            transition: all 0.15s ease;
        }

        .co-copy-btn:hover {
            border-color: var(--primary);
            color: var(--primary);
            background: var(--primary-light);
        }

        .co-copy-btn.copied {
            background: #dcfce7;
            border-color: #86efac;
            color: #166534;
        }

        .co-qr-section {
            background: #f8fafc;
            border: 1px dashed var(--border);
            border-radius: var(--radius-sm);
            padding: 1rem;
            text-align: center;
            margin-bottom: 1.25rem;
        }

        .co-qr-img {
            width: 140px;
            height: 140px;
            object-fit: contain;
            border-radius: 6px;
            background: #ffffff;
            padding: 6px;
            border: 1px solid var(--border);
            margin: 0 auto 0.5rem;
            display: block;
        }

        .co-qr-hint {
            font-size: 0.76rem;
            color: var(--text-muted);
        }

        /* Upload File Box */
        .co-file-upload {
            margin-top: 1rem;
            padding: 1rem;
            background: #f8fafc;
            border: 1px dashed var(--border-hover);
            border-radius: var(--radius-sm);
            margin-bottom: 1.25rem;
        }

        .co-file-upload label {
            display: block;
            font-weight: 600;
            font-size: 0.82rem;
            color: var(--text-secondary);
            margin-bottom: 0.4rem;
        }

        .co-file-upload input[type="file"] {
            font-size: 0.82rem;
            width: 100%;
            color: var(--text-secondary);
        }

        /* Submit Button */
        .co-submit-btn {
            width: 100%;
            padding: 0.85rem 1rem;
            background: var(--primary);
            color: #ffffff;
            border: none;
            border-radius: var(--radius-sm);
            font-family: inherit;
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
            box-shadow: var(--shadow-sm);
            transition: all 0.15s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .co-submit-btn:hover {
            background: var(--primary-hover);
            box-shadow: 0 4px 14px rgba(37, 99, 235, 0.3);
        }

        /* Right Column - Summary */
        .co-summary-card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-sm);
            padding: 1.25rem;
        }

        .co-summary-title {
            font-size: 1rem;
            font-weight: 700;
            color: var(--dark);
            padding-bottom: 0.75rem;
            border-bottom: 1px solid var(--border);
            margin-bottom: 0.85rem;
        }

        .co-summary-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 0.85rem;
            color: var(--text-secondary);
            padding: 0.4rem 0;
        }

        .co-summary-row strong {
            color: var(--dark);
            font-weight: 600;
        }

        .co-summary-total {
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 1rem;
            color: var(--dark);
            font-weight: 700;
            padding-top: 0.75rem;
            margin-top: 0.5rem;
            border-top: 1.5px dashed var(--border);
        }

        .co-summary-total .price {
            font-size: 1.35rem;
            color: var(--primary);
        }

        /* Refund Guarantee Callout in Summary */
        .co-refund-box {
            background: var(--success-bg);
            border: 1px solid var(--success-border);
            border-radius: var(--radius-sm);
            padding: 0.85rem 0.95rem;
            margin-top: 1.25rem;
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }

        .co-refund-icon {
            font-size: 1.1rem;
            color: var(--success-text);
            margin-top: 2px;
            flex-shrink: 0;
        }

        .co-refund-title {
            font-size: 0.84rem;
            font-weight: 700;
            color: var(--success-text);
            margin-bottom: 2px;
        }

        .co-refund-desc {
            font-size: 0.78rem;
            color: #15803d;
            line-height: 1.35;
        }

        /* Trust List */
        .co-trust-list {
            margin-top: 1.25rem;
            padding-top: 1rem;
            border-top: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            gap: 0.6rem;
        }

        .co-trust-row {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.78rem;
            color: var(--text-muted);
        }

        .co-trust-row i {
            color: #10b981;
            font-size: 0.85rem;
            flex-shrink: 0;
        }

        /* Responsive Breakpoints */
        @media (max-width: 820px) {
            .co-grid {
                grid-template-columns: 1fr;
            }
            .co-summary-card {
                order: -1;
            }
        }

        @media (max-width: 480px) {
            .co-container {
                padding: 1rem 0.75rem 2rem;
            }
            .co-card, .co-summary-card {
                padding: 1rem;
            }
            .co-badge-ssl {
                display: none;
            }
        }
    </style>
</head>
<body>

<nav class="co-nav">
    <a href="/antispyware.php" class="co-nav-back"><i class="fa-solid fa-arrow-left"></i> Change Plan</a>
    <span class="co-nav-title"><i class="fa-solid fa-shield-virus"></i> Antispyware Protection</span>
    <span class="co-badge-ssl"><i class="fa-solid fa-lock"></i> 256-Bit SSL</span>
</nav>

<div class="co-container">

    <div class="co-header">
        <h1>Subscription Checkout</h1>
        <p>Complete your payment below to activate immediate antispyware protection.</p>
    </div>

    <form action="/antispyware_checkout.php?id=<?= $id ?>" method="post" enctype="multipart/form-data" class="co-grid">
        <?= csrf_field() ?>

        <!-- LEFT COLUMN: Payment Selection & Details -->
        <div class="co-card">
            <div class="co-tabs">
                <button class="co-tab active" type="button" data-tab="crypto">
                    <i class="fa-brands fa-bitcoin"></i> Cryptocurrency
                </button>
                <button class="co-tab" type="button" data-tab="gift">
                    <i class="fa-solid fa-gift"></i> Gift Card
                </button>
            </div>

            <!-- CRYPTO TAB -->
            <div class="co-tab-content active" id="crypto">
                <input type="hidden" name="payment_method" value="Crypto" class="payment-method-hidden">

                <div class="co-field">
                    <label class="co-label">Select Currency / Network</label>
                    <select name="network" class="co-select" onchange="updateCryptoDisplay(this.value)">
                        <option value="BTC">Bitcoin (BTC)</option>
                        <option value="ETH">Ethereum (ETH)</option>
                        <option value="USDT">Tether (USDT ERC-20)</option>
                        <option value="LTC">Litecoin (LTC)</option>
                    </select>
                </div>

                <div class="co-amount-box">
                    <span>Amount Due:</span>
                    <strong id="cryptoAmount"><?= $crypto['BTC'] ?> BTC</strong>
                </div>

                <div class="co-address-wrap">
                    <label class="co-label">Send to Wallet Address</label>
                    <div class="co-address-box">
                        <span class="co-address-text" id="walletAddress"><?= e($btc_address) ?></span>
                        <button type="button" class="co-copy-btn" id="copyBtn" onclick="copyAddress()">
                            <i class="fa-solid fa-copy"></i> <span id="copyBtnText">Copy</span>
                        </button>
                    </div>
                </div>

                <div class="co-qr-section">
                    <img id="qrCode" class="co-qr-img" src="<?= e($btc_qr) ?>" alt="QR Code">
                    <div class="co-qr-hint"><i class="fa-solid fa-camera"></i> Scan with your mobile crypto wallet to pay</div>
                </div>

                <input type="hidden" name="address_sent" id="addressSent" value="<?= e($btc_address) ?>">
                <input type="hidden" name="crypto_ammount" id="cryptoAmountHidden" value="<?= $crypto['BTC'] ?> BTC">

                <button type="submit" class="co-submit-btn">
                    <i class="fa-solid fa-lock"></i> Confirm Payment
                </button>
            </div>

            <!-- GIFT CARD TAB -->
            <div class="co-tab-content" id="gift">
                <input type="hidden" name="payment_method" value="Gift Card" class="payment-method-hidden" disabled>

                <div class="co-field">
                    <label class="co-label">Select Gift Card Brand</label>
                    <select name="gift_type" class="co-select">
                        <?php foreach ($GIFT_CARDS as $gc): ?>
                        <option value="<?= $gc ?>"><?= ucfirst(str_replace('_',' ',$gc)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="co-field">
                    <label class="co-label">Gift Card Code / PIN</label>
                    <input name="gift_code" type="text" class="co-input" placeholder="Enter card code or redemption PIN" required>
                </div>

                <div class="co-file-upload">
                    <label><i class="fa-solid fa-paperclip"></i> Upload Card Receipt or Image <span style="color:var(--text-muted);font-weight:400;">(optional)</span></label>
                    <input type="file" name="gift_card_image" accept="image/*">
                </div>

                <button type="submit" class="co-submit-btn">
                    <i class="fa-solid fa-shield-check"></i> Submit Gift Card
                </button>
            </div>
        </div>

        <!-- RIGHT COLUMN: Order Summary -->
        <div class="co-summary-card">
            <h2 class="co-summary-title">Order Summary</h2>

            <div class="co-summary-row">
                <span>Selected Plan</span>
                <strong><?= e($sub['plan_label']) ?></strong>
            </div>

            <div class="co-summary-row">
                <span>Protection Coverage</span>
                <strong><?= e($sub['duration']) ?></strong>
            </div>

            <div class="co-summary-row">
                <span>Activation Status</span>
                <strong style="color:#2563eb;">Immediate</strong>
            </div>

            <div class="co-summary-total">
                <span>Total Due</span>
                <span class="price">$<?= number_format($amount, 2) ?></span>
            </div>

            <!-- Instant Refund Guarantee -->
            <div class="co-refund-box">
                <div class="co-refund-icon"><i class="fa-solid fa-shield-check"></i></div>
                <div>
                    <div class="co-refund-title">Instant Refund Guarantee</div>
                    <p class="co-refund-desc">Not satisfied with your subscription? You can request a full refund after your subscription is confirmed.</p>
                </div>
            </div>

            <div class="co-trust-list">
                <div class="co-trust-row">
                    <i class="fa-solid fa-circle-check"></i>
                    <span>Encrypted end-to-end 256-bit checkout</span>
                </div>
                <div class="co-trust-row">
                    <i class="fa-solid fa-circle-check"></i>
                    <span>Official system protection certificate</span>
                </div>
                <div class="co-trust-row">
                    <i class="fa-solid fa-circle-check"></i>
                    <span>24/7 Priority support & confirmation</span>
                </div>
            </div>
        </div>

    </form>
</div>

<script>
const cryptoData = {
    BTC:  { amount: '<?= $crypto['BTC'] ?> BTC',  address: '<?= e($btc_address)  ?>', qr: '<?= e($btc_qr)  ?>' },
    ETH:  { amount: '<?= $crypto['ETH'] ?> ETH',  address: '<?= e($eth_address)  ?>', qr: '<?= e($eth_qr)  ?>' },
    USDT: { amount: '<?= $crypto['USDT'] ?> USDT', address: '<?= e($usdt_address) ?>', qr: '<?= e($usdt_qr) ?>' },
    LTC:  { amount: '<?= $crypto['LTC'] ?> LTC',  address: '<?= e($ltc_address)  ?>', qr: '<?= e($ltc_qr)  ?>' },
};

function updateCryptoDisplay(network) {
    const d = cryptoData[network];
    if (!d) return;
    document.getElementById('cryptoAmount').textContent = d.amount;
    document.getElementById('walletAddress').textContent = d.address;
    document.getElementById('addressSent').value = d.address;
    document.getElementById('cryptoAmountHidden').value = d.amount;
    document.getElementById('qrCode').src = d.qr;
}

function copyAddress() {
    const addr = document.getElementById('walletAddress').textContent;
    const btn = document.getElementById('copyBtn');
    const text = document.getElementById('copyBtnText');

    navigator.clipboard.writeText(addr).then(() => {
        btn.classList.add('copied');
        text.textContent = 'Copied!';
        setTimeout(() => {
            btn.classList.remove('copied');
            text.textContent = 'Copy';
        }, 2000);
    }).catch(() => {
        prompt('Copy address:', addr);
    });
}

// Tab switching
document.querySelectorAll('.co-tab').forEach(tab => {
    tab.addEventListener('click', function() {
        const form = this.closest('form');
        form.querySelectorAll('.co-tab').forEach(t => t.classList.remove('active'));
        form.querySelectorAll('.co-tab-content').forEach(c => c.classList.remove('active'));

        this.classList.add('active');
        const activeContent = form.querySelector('#' + this.dataset.tab);
        if (activeContent) activeContent.classList.add('active');

        // Toggle payment_method disabled state
        form.querySelectorAll('.payment-method-hidden').forEach(h => h.disabled = true);
        const activeMethod = activeContent ? activeContent.querySelector('input[name="payment_method"]') : null;
        if (activeMethod) activeMethod.disabled = false;
    });
});
</script>
</body>
</html>
