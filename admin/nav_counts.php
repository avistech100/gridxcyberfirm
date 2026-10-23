<?php
// Returns sidebar badge counts for the admin nav.
// Called once per admin page load inside admin_body_open().
// Safe to call even if columns don't exist yet (try/catch on ALTER).
require_once __DIR__ . '/../config.php';

function _col_exists(PDO $db, string $table, string $col): bool {
    $st = $db->prepare(
        "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?"
    );
    $st->execute([$table, $col]);
    return (int)$st->fetchColumn() > 0;
}

function _table_exists(PDO $db, string $table): bool {
    $st = $db->prepare(
        "SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?"
    );
    $st->execute([$table]);
    return (int)$st->fetchColumn() > 0;
}

function _ensure_col(PDO $db, string $table, string $col, string $def): void {
    if (_table_exists($db, $table) && !_col_exists($db, $table, $col)) {
        try { $db->exec("ALTER TABLE `$table` ADD COLUMN `$col` $def"); } catch (Exception $e) {}
    }
}

function mark_nav_seen(string $section): void {
    $db = get_db();
    try {
        if ($section === 'transactions') {
            _ensure_col($db, 'user_trasaction', 'admin_seen', 'TINYINT(1) NOT NULL DEFAULT 0');
            $db->exec("UPDATE user_trasaction SET admin_seen=1 WHERE admin_seen=0");
        } elseif ($section === 'refunds') {
            _ensure_col($db, 'refund_refund', 'admin_seen', 'TINYINT(1) NOT NULL DEFAULT 0');
            $db->exec("UPDATE refund_refund SET admin_seen=1 WHERE admin_seen=0");
        } elseif ($section === 'users') {
            _ensure_col($db, 'user_gridxuser', 'admin_seen', 'TINYINT(1) NOT NULL DEFAULT 0');
            $db->exec("UPDATE user_gridxuser SET admin_seen=1 WHERE admin_seen=0");
        } elseif ($section === 'antispyware') {
            if (_table_exists($db, 'antispyware_subscriptions')) {
                _ensure_col($db, 'antispyware_subscriptions', 'admin_seen', 'TINYINT(1) NOT NULL DEFAULT 0');
                $db->exec("UPDATE antispyware_subscriptions SET admin_seen=1 WHERE admin_seen=0");
            }
        }
    } catch (Exception $e) {}
}

function get_nav_counts(): array {
    $db = get_db();

    // Ensure columns exist (checks INFORMATION_SCHEMA first, so ALTER only runs once)
    _ensure_col($db, 'user_trasaction', 'admin_seen',      'TINYINT(1) NOT NULL DEFAULT 0');
    _ensure_col($db, 'refund_refund',   'admin_seen',      'TINYINT(1) NOT NULL DEFAULT 0');
    _ensure_col($db, 'user_gridxuser',  'admin_seen',      'TINYINT(1) NOT NULL DEFAULT 0');
    _ensure_col($db, 'user_gridxuser',  'signup_paid',     'TINYINT(1) NOT NULL DEFAULT 0');
    _ensure_col($db, 'user_gridxuser',  'dashboard_access','TINYINT(1) NOT NULL DEFAULT 1');

    // Now safe to query — columns are guaranteed to exist
    $tx_count = (int)$db->query(
        "SELECT COUNT(*) FROM user_trasaction WHERE is_approved='pending' AND admin_seen=0"
    )->fetchColumn();

    $refund_count = (int)$db->query(
        "SELECT COUNT(*) FROM refund_refund WHERE admin_seen=0"
    )->fetchColumn();

    $user_count = (int)$db->query(
        "SELECT COUNT(*) FROM user_gridxuser WHERE signup_paid=0 AND dashboard_access=0 AND admin_seen=0"
    )->fetchColumn();

    $antispyware_count = 0;
    if (_table_exists($db, 'antispyware_subscriptions')) {
        _ensure_col($db, 'antispyware_subscriptions', 'admin_seen', 'TINYINT(1) NOT NULL DEFAULT 0');
        $antispyware_count = (int)$db->query(
            "SELECT COUNT(*) FROM antispyware_subscriptions WHERE is_approved='pending' AND admin_seen=0"
        )->fetchColumn();
    }

    return [
        'transactions' => $tx_count,
        'refunds'      => $refund_count,
        'users'        => $user_count,
        'antispyware'  => $antispyware_count,
        'total'        => $tx_count + $refund_count + $user_count + $antispyware_count,
    ];
}

function nav_badge(int $count): string {
    if ($count <= 0) return '';
    $label = $count > 9 ? '9+' : (string)$count;
    return '<span class="gx-nav-badge">' . $label . '</span>';
}
