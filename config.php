<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
// ============================================================
// GridX Cyber Solutions - Configuration
// ============================================================

// Database settings - update these for your Hostinger account
define('DB_HOST', 'localhost');
define('DB_NAME', 'u797191620_GridXSolutions');
define('DB_USER', 'u797191620_GridXcyber');         // Change to your Hostinger DB user
define('DB_PASS', 'GridXCyberSolutions2026');             // Change to your Hostinger DB password
define('DB_CHARSET', 'utf8mb4');

// App settings
define('APP_NAME',  'GridX Cyber Solutions');
define('APP_URL',   'https://gridxcyberfirm.com');   // Change to your domain
define('SITE_TITLE','GridX Admin');

// Static files base path (relative to site root)
define('STATIC_URL', '/static');

// Session settings
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
session_start();

// Database connection (PDO)
function get_db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    }
    return $pdo;
}

// CSRF token helpers
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . csrf_token() . '">';
}

function verify_csrf(): void {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = $_POST['csrf_token'] ?? '';
        if (!hash_equals(csrf_token(), $token)) {
            http_response_code(403);
            die('CSRF token mismatch.');
        }
    }
}

// Auth helpers
function current_user(): ?array {
    if (!empty($_SESSION['user_id'])) {
        $db = get_db();
        $st = $db->prepare('SELECT * FROM user_gridxuser WHERE id = ?');
        $st->execute([$_SESSION['user_id']]);
        $user = $st->fetch();
        if ($user) {
            return $user;
        } else {
            unset($_SESSION['user_id']);
            unset($_SESSION['username']);
            unset($_SESSION['is_staff']);
            unset($_SESSION['is_superuser']);
        }
    }
    return null;
}

function login_required(): void {
    if (!is_authenticated()) {
        header('Location: /login.php?next=' . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }
}

function admin_required(): void {
    $user = current_user();
    if ($user === null || empty($user['is_staff'])) {
        header('Location: /admin/login.php');
        exit;
    }
}

function is_authenticated(): bool {
    return current_user() !== null;
}

// Password helpers (uses Django-compatible bcrypt via PHP password_hash)
function hash_password(string $raw): string {
    return password_hash($raw, PASSWORD_BCRYPT, ['cost' => 12]);
}

function check_password(string $raw, string $hashed): bool {
    // Handle Django's PBKDF2 hashes - falls back to bcrypt for PHP-created accounts
    if (str_starts_with($hashed, 'pbkdf2_sha256$')) {
        return django_check_pbkdf2($raw, $hashed);
    }
    return password_verify($raw, $hashed);
}

function django_check_pbkdf2(string $password, string $encoded): bool {
    // Django PBKDF2 format: pbkdf2_sha256$<iterations>$<salt>$<hash>
    $parts = explode('$', $encoded, 4);
    if (count($parts) !== 4) return false;
    [, $iterations, $salt, $hash] = $parts;
    $dk = hash_pbkdf2('sha256', $password, $salt, (int)$iterations, 32, true);
    return hash_equals($hash, base64_encode($dk));
}

// Redirect helper
function redirect(string $url): void {
    header('Location: ' . $url);
    exit;
}

// Flash messages
function set_flash(string $type, string $message): void {
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function get_flash(): array {
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $messages;
}

function show_flash(): string {
    $html = '';
    foreach (get_flash() as $f) {
        $cls = $f['type'] === 'error' ? 'danger' : $f['type'];
        $html .= '<div class="alert alert-' . $cls . ' alert-dismissible fade show" role="alert">'
               . htmlspecialchars($f['message'])
               . '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
    }
    return $html;
}

// Escape output
function e(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

// Pagination helper
function paginate(int $total, int $per_page, int $current_page): array {
    $total_pages = (int)ceil($total / $per_page);
    $offset = ($current_page - 1) * $per_page;
    return ['total' => $total, 'per_page' => $per_page, 'current' => $current_page,
            'total_pages' => $total_pages, 'offset' => $offset];
}
