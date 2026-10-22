<?php
require_once __DIR__ . '/config.php';
header('Content-Type: application/json');
header('Cache-Control: no-store');
echo json_encode([
    'enabled' => get_site_setting('signup_fee_enabled', '0') === '1',
    'amount'  => (float)get_site_setting('signup_fee_amount', '50'),
]);
