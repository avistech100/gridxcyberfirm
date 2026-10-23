<?php
// locate/support_config.php — serves Telegram/support URL to locate pages (plain HTML can't use PHP)
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
echo json_encode([
    'telegram_url' => get_site_setting('customer_support_url', ''),
    'email'        => get_site_setting('smtp_from_email', 'app@gridxcyberfirm.com'),
]);
