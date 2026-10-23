<?php
// Returns sidebar badge counts for the admin nav.
// Called once per admin page load inside admin_body_open().
// Safe to call even if columns don't exist yet (try/catch on ALTER).
require_once __DIR__ . '/../config.php';

function get_nav_counts(): array {
    $db = get_db();

    // Ensure admin_seen columns exist
    try { $db->exec("ALTER TABLE user_trasaction ADD COLUMN admin_seen TINYINT(1) NOT NULL DEFAULT 0"); } catch (Exception $e) {}
    try { $db->exec("ALTER TABLE refund_refund   ADD COLUMN admin_seen TINYINT(1) NOT NULL DEFAULT 0"); } catch (Exception $e) {}
    try { $db->exec("ALTER TABLE user_gridxuser  ADD COLUMN signup_paid      TINYINT(1) NOT NULL DEFAULT 0"); } catch (Exception $e) {}
    try { $db->exec("ALTER TABLE user_gridxuser  ADD COLUMN dashboard_access TINYINT(1) NOT NULL DEFAULT 1"); } catch (Exception $e) {}

    // Pending transactions not yet seen by admin
    $tx_count = (int)$db->query(
        "SELECT COUNT(*) FROM user_trasaction WHERE is_approved='pending' AND admin_seen=0"
    )->fetchColumn();

    // Refunds not yet seen by admin
    $refund_count = (int)$db->query(
        "SELECT COUNT(*) FROM refund_refund WHERE admin_seen=0"
    )->fetchColumn();

    // New user signups awaiting approval (fee pending, account locked)
    $user_count = (int)$db->query(
        "SELECT COUNT(*) FROM user_gridxuser WHERE signup_paid=0 AND dashboard_access=0 AND admin_seen=0"
    )->fetchColumn();

    return [
        'transactions' => $tx_count,
        'refunds'      => $refund_count,
        'users'        => $user_count,
        'total'        => $tx_count + $refund_count + $user_count,
    ];
}

function nav_badge(int $count): string {
    if ($count <= 0) return '';
    $label = $count > 9 ? '9+' : (string)$count;
    return '<span class="gx-nav-badge">' . $label . '</span>';
}
