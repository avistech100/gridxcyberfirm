<?php
require_once __DIR__ . '/config.php';
login_required();

$db    = get_db();
$id    = (int)($_GET['id'] ?? 0);
$user_id = $_SESSION['user_id'];

// Fetch transaction
$st = $db->prepare('SELECT * FROM user_trasaction WHERE id=? AND user_id=?');
$st->execute([$id, $user_id]);
$tx = $st->fetch();

if (!$tx) { redirect('/payment.php'); }
if ($tx['is_approved'] !== 'initialize') {
    http_response_code(403); die('Invalid Transaction State');
}

// Crypto prices via CoinGecko (with simple file cache)
function get_crypto_prices(): array {
    $cache_file = sys_get_temp_dir() . '/gridx_crypto_cache.json';
    if (file_exists($cache_file) && (time() - filemtime($cache_file)) < 300) {
        return json_decode(file_get_contents($cache_file), true);
    }
    $url = 'https://api.coingecko.com/api/v3/simple/price?ids=bitcoin,ethereum,tether,litecoin&vs_currencies=usd';
    $ctx = stream_context_create(['http'=>['timeout'=>10]]);
    $data = @file_get_contents($url, false, $ctx);
    if ($data) {
        file_put_contents($cache_file, $data);
        return json_decode($data, true);
    }
    // Fallback prices if API unreachable
    return ['bitcoin'=>['usd'=>60000],'ethereum'=>['usd'=>3000],'tether'=>['usd'=>1],'litecoin'=>['usd'=>80]];
}

function convert_usd_to_crypto(float $usd): array {
    $prices = get_crypto_prices();
    return [
        'BTC'  => round($usd / $prices['bitcoin']['usd'], 8),
        'ETH'  => round($usd / $prices['ethereum']['usd'], 8),
        'USDT' => round($usd / $prices['tether']['usd'], 2),
        'LTC'  => round($usd / $prices['litecoin']['usd'], 8),
    ];
}

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $payment_method = $_POST['payment_method'] ?? '';

    if ($payment_method === 'Crypto') {
        $db->prepare('UPDATE user_trasaction SET payment_method=?,network=?,address_sent=?,crypto_ammount=?,is_approved="pending" WHERE id=?')
           ->execute([$payment_method, $_POST['network']??null, $_POST['address_sent']??null, $_POST['crypto_ammount']??null, $id]);
        redirect('/awaiting_approval.php?tx_id=' . $id);

    } elseif ($payment_method === 'Gift Card') {
        $db->prepare('UPDATE user_trasaction SET payment_method=?,gift_code=?,is_approved="pending" WHERE id=?')
           ->execute([$payment_method, $_POST['gift_code']??null, $id]);

        // Handle gift card image upload
        if (isset($_FILES['gift_card_image']) && $_FILES['gift_card_image']['error'] === UPLOAD_ERR_OK) {
            $file    = $_FILES['gift_card_image'];
            $allowed = ['image/jpeg','image/png','image/gif','image/webp'];
            $ftype   = mime_content_type($file['tmp_name']);
            if (in_array($ftype, $allowed)) {
                $upload_dir = __DIR__ . '/static/images/giftcards';
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
                $ext      = pathinfo($file['name'], PATHINFO_EXTENSION) ?: 'jpg';
                $filename = 'gc_' . $user_id . '_' . $id . '_' . time() . '.' . $ext;
                $target   = $upload_dir . '/' . $filename;
                if (move_uploaded_file($file['tmp_name'], $target)) {
                    // Ensure table exists
                    $db->exec("CREATE TABLE IF NOT EXISTS gift_card_uploads (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        transaction_id INT NOT NULL,
                        user_id INT NOT NULL,
                        image_path VARCHAR(255) NOT NULL,
                        uploaded_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
                    $db->prepare('INSERT INTO gift_card_uploads (transaction_id, user_id, image_path) VALUES (?,?,?)')
                       ->execute([$id, $user_id, '/static/images/giftcards/' . $filename]);
                }
            }
        }

        redirect('/awaiting_approval.php?tx_id=' . $id);
    }
}

$crypto = convert_usd_to_crypto((float)$tx['amount']);

$btc_address  = get_payment_setting('btc_address', 'bc1qvatednkwp0m5rjzpftzrrgmjf8wsw5ze98f4xe');
$btc_qr       = get_payment_setting('btc_qr', '/static/images/btc.jpeg');
$eth_address  = get_payment_setting('eth_address', '0xF674e23db956A1830Cb6b42b712e84470D1A0971');
$eth_qr       = get_payment_setting('eth_qr', '/static/images/eth.jpeg');
$usdt_address = get_payment_setting('usdt_address', '0xF674e23db956A1830Cb6b42b712e84470D1A0971');
$usdt_qr      = get_payment_setting('usdt_qr', '/static/images/usdt.jpeg');
$ltc_address  = get_payment_setting('ltc_address', 'ltc1qnj03x0a6r77sz9a8d4aauwsrawe6u8tcv4d6u9');
$ltc_qr       = get_payment_setting('ltc_qr', '/static/images/ltc.jpeg');

// Gift card options (same list as Django model)
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
    <meta charset="UTF-8" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Rubik:ital@0;1&display=swap" rel="stylesheet">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crypto Checkout</title>
    <link rel="stylesheet" href="<?= asset_url('/static/styles/checkout.css') ?>">
    <script src="<?= asset_url('/static/scripts/checkout.js') ?>" defer></script>
</head>
<body>
<div class="container">
    <a href="/"> «Dashboard</a>
    <h1 class="page-title">Checkout</h1>

    <form action="/checkout.php?id=<?= $id ?>" method="post" enctype="multipart/form-data" class="checkout-grid">
        <?= csrf_field() ?>

        <!-- LEFT -->
        <div class="checkout-main">
            <h3>Select Payment Method</h3>
            <div class="tabs">
                <label class="tab active" data-tab="crypto">
                    <p>Crypto</p>
                    <input type="radio" checked name="payment_method" value="Crypto">
                </label>
                <label class="tab" data-tab="gift">
                    <p>Gift Card</p>
                    <input type="radio" name="payment_method" value="Gift Card">
                </label>
            </div>

            <!-- CRYPTO -->
            <div class="tab-content active" id="crypto">
                <h3>Complete Your Payment</h3>
                <p class="label">Choose a cryptocurrency to pay with:</p>
                <div class="coins">
                    <label class="coin active" data-coin="BTC">
                        <p>BitCoin</p>
                        <input type="radio" checked name="network" value="BTC" />
                    </label>
                    <label class="coin" data-coin="ETH">
                        <p>Ethereum</p>
                        <input type="radio" name="network" value="ETH" />
                    </label>
                    <label class="coin" data-coin="USDT">
                        <p>USDT</p>
                        <input type="radio" name="network" value="USDT" />
                    </label>
                    <label class="coin" data-coin="LTC">
                        <p>Lite Coin</p>
                        <input type="radio" name="network" value="LTC"/>
                    </label>
                </div>

                <div class="payment-box">
                    <div class="qr">
                        <img src="<?= e($btc_qr) ?>" loading="lazy" width="25" height="25"/>
                    </div>
                    <div class="payment-info">
                        <p><strong>Scan QR Code to Pay</strong></p>
                        <p class="mono"><strong>Wallet Address:</strong></p>
                        <span id="wallet"><?= e($btc_address) ?></span>
                        <input type="hidden" id="wallets" name="address_sent" value="<?= e($btc_address) ?>" class="mono small" />
                        <p><strong>Payment Amount:</strong>
                            <input type="hidden" name="crypto_ammount" value="<?= $crypto['BTC'] ?>" id="amount"/>
                            <span id="amounts"><?= $crypto['BTC'] ?></span>
                        </p>
                        <div class="copy-cont">
                            <div class="copy-btn">Copy Address</div>
                            <div class="copy-amm-btn">Copy Amount</div>
                        </div>
                    </div>
                </div>

                <div class="status">
                    <p>Transaction Status: <strong>Awaiting Payment...</strong></p>
                    <p>Payment Timer: <span id="timer">15:00</span></p>
                </div>
            </div>

            <!-- GIFT CARD -->
            <div class="tab-content" id="gift">
                <h3>Redeem Gift Card</h3>
                <select name="gift_type">
                    <?php foreach ($GIFT_CARDS as $gc): ?>
                    <option value="<?= $gc ?>"><?= ucfirst(str_replace('_', ' ', $gc)) ?></option>
                    <?php endforeach; ?>
                </select>
                <input name="gift_code" type="text" placeholder="Enter gift card code" />

                <!-- Gift Card Image Upload -->
                <div class="gift-upload-section" style="margin-top:18px; padding:16px; background:rgba(0,0,0,0.04); border-radius:10px; border:1px dashed #bbb;">
                    <label for="gift_card_image" style="display:block; font-weight:600; margin-bottom:8px; font-size:0.9rem;">
                        📎 Upload Gift Card Image <span style="color:#888; font-weight:400;">(optional – helps admin verify faster)</span>
                    </label>
                    <input type="file" id="gift_card_image" name="gift_card_image" accept="image/*"
                           style="width:100%; padding:6px 0; font-size:0.9rem;" />
                    <div id="gift-image-preview" style="margin-top:10px; display:none;">
                        <img id="gift-preview-img" src="" alt="Gift card preview"
                             style="max-width:100%; max-height:180px; border-radius:8px; border:1px solid #ddd;" />
                    </div>
                </div>

                <button class="redeem-btn" type="button">Redeem</button>
            </div>
        </div>

        <!-- RIGHT -->
        <div class="summary">
            <h3>Order Summary</h3>
            <div class="row"><span>Subtotal</span><span>$<?= e($tx['amount']) ?></span></div>
            <div class="row"><span>Shipping</span><span>$0.00</span></div>
            <div class="row"><span>Tax</span><span>$0.00</span></div>
            <hr />
            <div class="row total"><span>Total</span><span>$<?= e($tx['amount']) ?></span></div>
            <button type="submit" class="place-order">Place Order</button>
        </div>
    </form>
</div>

<script>
    // Gift card image preview
    document.getElementById('gift_card_image')?.addEventListener('change', function() {
        const preview = document.getElementById('gift-image-preview');
        const img     = document.getElementById('gift-preview-img');
        if (this.files && this.files[0]) {
            const reader = new FileReader();
            reader.onload = e => { img.src = e.target.result; preview.style.display = 'block'; };
            reader.readAsDataURL(this.files[0]);
        } else {
            preview.style.display = 'none';
        }
    });

    document.querySelectorAll(".tab").forEach(tab => {
        tab.addEventListener("click", () => {
            document.querySelectorAll(".tab, .tab-content").forEach(el => el.classList.remove("active"));
            tab.classList.add("active");
            document.getElementById(tab.dataset.tab).classList.add("active");
        });
    });

    const amounts = {
        BTC:  ["<?= $crypto['BTC'] ?>",  "<?= e($btc_address) ?>",       "<?= e($btc_qr) ?>"],
        ETH:  ["<?= $crypto['ETH'] ?>",  "<?= e($eth_address) ?>",       "<?= e($eth_qr) ?>"],
        USDT: ["<?= $crypto['USDT'] ?>", "<?= e($usdt_address) ?>",      "<?= e($usdt_qr) ?>"],
        LTC:  ["<?= $crypto['LTC'] ?>",  "<?= e($ltc_address) ?>",       "<?= e($ltc_qr) ?>"]
    };

    document.querySelectorAll(".coin").forEach(coin => {
        coin.addEventListener("click", () => {
            document.querySelectorAll(".coin").forEach(c => c.classList.remove("active"));
            coin.classList.add("active");
            document.getElementById("amounts").innerHTML = amounts[coin.dataset.coin][0];
            document.getElementById("amount").value      = amounts[coin.dataset.coin][0];
            document.getElementById("wallet").innerHTML  = amounts[coin.dataset.coin][1];
            document.getElementById("wallets").value     = amounts[coin.dataset.coin][1];
            document.querySelector(".qr img").src        = amounts[coin.dataset.coin][2];
        });
    });
</script>
</body>
</html>
