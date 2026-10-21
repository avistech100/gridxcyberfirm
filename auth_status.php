<?php
// Lightweight endpoint: tells the static pages whether the visitor is logged in.
require_once __DIR__ . '/config.php';
header('Content-Type: application/json');
header('Cache-Control: no-store');
echo json_encode(['logged_in' => is_authenticated()]);
