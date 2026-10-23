<?php
// admin/fetch_mail.php — Poll IMAP inbox, store real incoming emails as 'incoming' in email_messages
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/admin_layout.php';
admin_required();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' && ($_GET['ping'] ?? '') !== '1') {
    // Allow GET with ?ping=1 from JS auto-fetch
}

$imap_host = get_site_setting('imap_host', 'imap.hostinger.com');
$imap_port = (int) get_site_setting('imap_port', '993');
$user      = get_site_setting('smtp_user', '');
$pass      = get_site_setting('smtp_pass', '');
$own_email = strtolower(trim(get_site_setting('smtp_from_email', $user)));

if (!$user || !$pass) {
    echo json_encode(['ok' => false, 'error' => 'SMTP credentials not configured', 'fetched' => 0]);
    exit;
}

if (!function_exists('imap_open')) {
    echo json_encode(['ok' => false, 'error' => 'PHP IMAP extension not available on this server', 'fetched' => 0]);
    exit;
}

// Connect to IMAP
$mailbox_str = "{{$imap_host}:{$imap_port}/imap/ssl/novalidate-cert}INBOX";
$mbox = @imap_open($mailbox_str, $user, $pass, 0, 1, ['DISABLE_AUTHENTICATOR' => 'GSSAPI']);

if (!$mbox) {
    $err = imap_last_error();
    echo json_encode(['ok' => false, 'error' => 'IMAP connect failed: ' . htmlspecialchars($err), 'fetched' => 0]);
    exit;
}

$db = get_db();

// Ensure tables
$db->exec("CREATE TABLE IF NOT EXISTS locate_email_leads (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    email      VARCHAR(255) NOT NULL,
    admin_seen TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
try { $db->exec("ALTER TABLE locate_email_leads ADD UNIQUE KEY uq_email (email)"); } catch(Exception $e){}

$db->exec("CREATE TABLE IF NOT EXISTS email_messages (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    recipient_email VARCHAR(255) NOT NULL,
    subject         VARCHAR(500) NOT NULL DEFAULT '',
    body            TEXT NOT NULL,
    direction       ENUM('outgoing','incoming') NOT NULL DEFAULT 'outgoing',
    admin_seen      TINYINT(1) NOT NULL DEFAULT 0,
    sent_at         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Add imap_msg_id column if not exists (dedup key)
try {
    $db->exec("ALTER TABLE email_messages ADD COLUMN imap_msg_id VARCHAR(500) DEFAULT NULL");
} catch(Exception $e) {}
try {
    $db->exec("ALTER TABLE email_messages ADD UNIQUE KEY uq_imap_msg_id (imap_msg_id(250))");
} catch(Exception $e) {}

// Search UNSEEN only
$uids = imap_search($mbox, 'UNSEEN');
$fetched = 0;

if ($uids) {
    foreach ($uids as $uid) {
        $header = @imap_headerinfo($mbox, $uid);
        if (!$header) continue;

        // FROM address
        $from_obj   = $header->from[0] ?? null;
        if (!$from_obj) continue;
        $from_email = strtolower(trim(($from_obj->mailbox ?? '') . '@' . ($from_obj->host ?? '')));
        if (!filter_var($from_email, FILTER_VALIDATE_EMAIL)) continue;

        // Skip own server bouncebacks / our own outgoing reflected
        if ($from_email === $own_email || $from_email === strtolower(trim($user))) {
            imap_setflag_full($mbox, (string)$uid, '\\Seen');
            continue;
        }

        // Decode subject
        $subject = '';
        if (!empty($header->subject)) {
            $mime_parts = imap_mime_header_decode($header->subject);
            foreach ($mime_parts as $p) {
                $charset = ($p->charset !== 'default') ? $p->charset : 'UTF-8';
                $text    = $p->text;
                if ($charset && strtoupper($charset) !== 'UTF-8') {
                    $text = @mb_convert_encoding($text, 'UTF-8', $charset) ?: $text;
                }
                $subject .= $text;
            }
        }
        $subject = trim($subject) ?: '(no subject)';

        // Message-ID for dedup
        $msg_id = trim($header->message_id ?? '');

        // Check by message-id first
        if ($msg_id) {
            $chk = $db->prepare("SELECT COUNT(*) FROM email_messages WHERE imap_msg_id=?");
            $chk->execute([$msg_id]);
            if ($chk->fetchColumn() > 0) {
                imap_setflag_full($mbox, (string)$uid, '\\Seen');
                continue;
            }
        }

        // Parse date
        $sent_at = date('Y-m-d H:i:s', $header->udate ?? time());

        // Get body text
        $body = _get_body($mbox, $uid);

        // Insert
        try {
            $st = $db->prepare(
                "INSERT INTO email_messages
                 (recipient_email, subject, body, direction, admin_seen, sent_at, imap_msg_id)
                 VALUES (?, ?, ?, 'incoming', 0, ?, ?)"
            );
            $st->execute([$from_email, $subject, $body, $sent_at, $msg_id ?: null]);
        } catch (Exception $e) {
            // Unique constraint hit — already have it
            imap_setflag_full($mbox, (string)$uid, '\\Seen');
            continue;
        }

        // Also upsert into leads (for Leads tab visibility)
        try {
            $db->prepare(
                "INSERT IGNORE INTO locate_email_leads (email, admin_seen) VALUES (?, 0)"
            )->execute([$from_email]);
        } catch (Exception $e) {}

        // Mark read in IMAP so we don't pull it again
        imap_setflag_full($mbox, (string)$uid, '\\Seen');
        $fetched++;
    }
}

imap_close($mbox);
echo json_encode(['ok' => true, 'fetched' => $fetched]);

// ── Helpers ──────────────────────────────────────────────────────────────────
function _get_body($mbox, int $uid): string {
    $structure = @imap_fetchstructure($mbox, $uid);
    if (!$structure) return '(could not read message)';

    // Single-part (non-multipart)
    if (empty($structure->parts)) {
        $raw = imap_fetchbody($mbox, $uid, '1');
        $txt = _decode_part($raw, $structure->encoding ?? 0, $structure->parameters ?? []);
        if (strtolower($structure->subtype ?? '') === 'html') {
            $txt = strip_tags($txt);
        }
        return _clean($txt);
    }

    // Multipart: walk parts looking for text/plain or text/html
    $plain = '';
    $html  = '';
    _walk_parts($mbox, $uid, $structure->parts, '1', $plain, $html);

    if ($plain) return _clean($plain);
    if ($html)  return _clean(strip_tags($html));
    return '(no readable body)';
}

function _walk_parts($mbox, int $uid, array $parts, string $prefix,
                     string &$plain, string &$html): void {
    foreach ($parts as $i => $part) {
        $part_num = $prefix . '.' . ($i + 1);
        // Remove leading "1." for top-level multipart
        $part_num = ltrim($part_num, '1.');
        if ($part_num === '') $part_num = (string)($i + 1);

        $type    = strtolower($part->type ?? '');     // 0=text
        $subtype = strtolower($part->subtype ?? '');

        if ($type == '0' || $type === 'text') {
            $raw     = imap_fetchbody($mbox, $uid, (string)($i + 1));
            $decoded = _decode_part($raw, $part->encoding ?? 0, $part->parameters ?? []);
            if ($subtype === 'plain' && !$plain) $plain = $decoded;
            if ($subtype === 'html'  && !$html)  $html  = $decoded;
        }

        // Recurse into nested multipart
        if (!empty($part->parts)) {
            _walk_parts($mbox, $uid, $part->parts, $part_num, $plain, $html);
        }
    }
}

function _decode_part(string $raw, int $encoding, array $params): string {
    $text = match($encoding) {
        3 => base64_decode($raw),
        4 => quoted_printable_decode($raw),
        default => $raw,
    };
    // Convert charset to UTF-8
    $charset = 'UTF-8';
    foreach ($params as $p) {
        if (strtolower($p->attribute ?? '') === 'charset') {
            $charset = $p->value;
            break;
        }
    }
    if ($charset && strtoupper($charset) !== 'UTF-8') {
        $text = @mb_convert_encoding($text, 'UTF-8', $charset) ?: $text;
    }
    return $text;
}

function _clean(string $txt): string {
    // Collapse whitespace, trim
    $txt = preg_replace('/\r\n|\r/', "\n", $txt);
    $txt = preg_replace('/\n{3,}/', "\n\n", $txt);
    return trim($txt) ?: '(empty message)';
}
