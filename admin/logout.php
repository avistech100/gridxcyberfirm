<?php
require_once __DIR__ . '/../config.php';
verify_csrf();
session_destroy();
redirect('/admin/login.php');
