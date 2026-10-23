<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/admin_layout.php';
admin_required();
set_active_nav('email');

$db = get_db();

// ── Ensure tables ────────────────────────────────────────────────────────────
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

// Count new leads BEFORE marking seen (used for badge on tab)
$new_leads_count = (int)$db->query(
    "SELECT COUNT(*) FROM locate_email_leads WHERE admin_seen=0"
)->fetchColumn();

// Mark leads as seen when admin visits email page
$db->exec("UPDATE locate_email_leads SET admin_seen=1 WHERE admin_seen=0");

// ── Handle Delete Actions ───────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_lead_id'])) {
        verify_csrf();
        $del_id = (int)$_POST['delete_lead_id'];
        if ($del_id > 0) {
            $stmt = $db->prepare("DELETE FROM locate_email_leads WHERE id = ?");
            $stmt->execute([$del_id]);
            set_flash('success', 'Lead deleted.');
        }
        redirect('/admin/email.php');
    }

    if (isset($_POST['delete_thread_email'])) {
        verify_csrf();
        $thread_email = trim($_POST['delete_thread_email']);
        if ($thread_email !== '') {
            $stmt = $db->prepare("DELETE FROM email_messages WHERE recipient_email = ?");
            $stmt->execute([$thread_email]);
            set_flash('success', 'Conversation thread deleted.');
        }
        redirect('/admin/email.php#chat');
    }
}

// ── Data ─────────────────────────────────────────────────────────────────────
$leads = $db->query(
    "SELECT id, email, created_at FROM locate_email_leads ORDER BY created_at DESC"
)->fetchAll();

// Chat threads: one per unique recipient_email
$threads_raw = $db->query(
    "SELECT recipient_email,
            MAX(sent_at) AS last_message,
            SUM(CASE WHEN direction='incoming' AND admin_seen=0 THEN 1 ELSE 0 END) AS unread,
            COUNT(*) AS total
     FROM email_messages
     GROUP BY recipient_email
     ORDER BY last_message DESC"
)->fetchAll();

// For chat view: load messages for a selected thread
$open_thread = trim($_GET['thread'] ?? '');
$thread_messages = [];
if ($open_thread && filter_var($open_thread, FILTER_VALIDATE_EMAIL)) {
    $st = $db->prepare(
        "SELECT id, subject, body, direction, admin_seen, sent_at
         FROM email_messages WHERE recipient_email=? ORDER BY sent_at ASC"
    );
    $st->execute([$open_thread]);
    $thread_messages = $st->fetchAll();
    // Mark incoming messages in this thread as seen
    $db2 = $db->prepare("UPDATE email_messages SET admin_seen=1 WHERE recipient_email=? AND direction='incoming' AND admin_seen=0");
    $db2->execute([$open_thread]);
}

$from_addr = get_site_setting('smtp_from_email', 'app@gridxcyberfirm.com');
$from_name = get_site_setting('smtp_from_name', 'GridX Cyber Solutions');

admin_head('Email');
?>
<style>
/* ── Email page layout ────────────────────────────────────────────────── */
/* AdminLTE already provides app-content > container-fluid — we just need
   the wrapper to fill the remaining vertical space without double-nesting */
.gx-email-wrapper {
  display: flex;
  gap: 0;
  height: calc(100vh - 200px);
  min-height: 480px;
  border: 1px solid #dee2e6;
  border-radius: 6px;
  overflow: hidden;
  background: #fff;
  margin-bottom: 20px;
}

/* Left panel */
.gx-email-left { width:280px; min-width:220px; background:#fff; border-right:1px solid #dee2e6;
                 display:flex; flex-direction:column; flex-shrink:0; }
.gx-email-tabs { display:flex; border-bottom:1px solid #dee2e6; }
.gx-email-tab  { flex:1; padding:12px 8px; text-align:center; cursor:pointer;
                 font-size:.82rem; font-weight:600; color:#6c757d; border:none;
                 background:none; border-bottom:3px solid transparent; transition:.2s; }
.gx-email-tab.active { color:#2c3e50; border-bottom-color:#2c3e50; }
.gx-email-tab:hover  { color:#2c3e50; background:#f8f9fa; }

/* Lead list */
.gx-lead-list { flex:1; overflow-y:auto; }
.gx-lead-item { padding:10px 14px; border-bottom:1px solid #f0f0f0; cursor:pointer;
                transition:background .15s; display:flex; align-items:center; gap:8px; }
.gx-lead-item:hover { background:#f8f9fa; }
.gx-lead-item .gx-lead-email { font-size:.82rem; color:#2c3e50; font-weight:600;
                                white-space:nowrap; overflow:hidden; text-overflow:ellipsis; flex:1; }
.gx-lead-item .gx-lead-date  { font-size:.72rem; color:#aaa; white-space:nowrap; }
.gx-lead-btn  { background:#2c3e50; color:#fff; border:none; border-radius:4px;
                padding:3px 8px; font-size:.72rem; cursor:pointer; white-space:nowrap; }
.gx-lead-btn:hover { background:#3d5166; }
.gx-lead-del-btn { background:transparent; color:#dc3545; border:none; padding:2px 5px; font-size:.78rem; cursor:pointer; }
.gx-lead-del-btn:hover { color:#bd2130; }

/* Thread list */
.gx-thread-list { flex:1; overflow-y:auto; }
.gx-thread-item { padding:10px 14px; border-bottom:1px solid #f0f0f0; cursor:pointer;
                  transition:background .15s; display:flex; align-items:center; gap:8px; }
.gx-thread-item:hover, .gx-thread-item.active { background:#eef2f7; }
.gx-thread-avatar { width:36px; height:36px; border-radius:50%; background:#2c3e50;
                    color:#fff; display:flex; align-items:center; justify-content:center;
                    font-size:.85rem; font-weight:700; flex-shrink:0; }
.gx-thread-info   { flex:1; overflow:hidden; }
.gx-thread-name   { font-size:.83rem; font-weight:600; color:#2c3e50;
                    white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.gx-thread-preview { font-size:.75rem; color:#999; white-space:nowrap;
                     overflow:hidden; text-overflow:ellipsis; }
.gx-thread-meta   { display:flex; flex-direction:column; align-items:flex-end; gap:3px; }
.gx-thread-time   { font-size:.7rem; color:#aaa; }

/* Main panel */
.gx-email-main { flex:1; display:flex; flex-direction:column; background:#f4f6f8;
                 overflow:hidden; min-width:0; }
.gx-email-main-empty { display:flex; align-items:center; justify-content:center;
                        flex:1; color:#bbb; flex-direction:column; gap:12px; font-size:.95rem; }

/* Chat pane */
.gx-chat-header { background:#fff; border-bottom:1px solid #dee2e6;
                  padding:12px 18px; display:flex; align-items:center; gap:10px; }
.gx-chat-header .gx-thread-avatar { width:38px; height:38px; }
.gx-chat-email-label { font-size:.9rem; font-weight:600; color:#2c3e50; }

.gx-chat-body { flex:1; overflow-y:auto; padding:16px 18px;
                display:flex; flex-direction:column; gap:10px; }
.gx-msg { max-width:72%; display:flex; flex-direction:column; }
.gx-msg.outgoing { align-self:flex-end; align-items:flex-end; }
.gx-msg.incoming { align-self:flex-start; align-items:flex-start; }
.gx-msg-bubble { padding:10px 14px; border-radius:16px; font-size:.84rem; line-height:1.5;
                 word-break:break-word; white-space:pre-wrap; }
.gx-msg.outgoing .gx-msg-bubble { background:#2c3e50; color:#fff;
                                   border-bottom-right-radius:4px; }
.gx-msg.incoming .gx-msg-bubble { background:#fff; color:#222; border:1px solid #dee2e6;
                                   border-bottom-left-radius:4px; }
.gx-msg-subject { font-size:.72rem; opacity:.7; margin-bottom:2px; }
.gx-msg-time { font-size:.68rem; color:#aaa; margin-top:3px; }

/* Mini compose in chat */
.gx-chat-compose { background:#fff; border-top:1px solid #dee2e6; padding:12px 16px; }
.gx-chat-compose input[type=text] { border:1px solid #dee2e6; border-radius:6px;
    padding:6px 10px; font-size:.82rem; width:100%; margin-bottom:6px; outline:none; }
.gx-chat-compose input[type=text]:focus { border-color:#2c3e50; }
.gx-chat-compose textarea { border:1px solid #dee2e6; border-radius:6px; padding:8px 10px;
    font-size:.82rem; width:100%; resize:vertical; min-height:60px; outline:none;
    font-family:inherit; }
.gx-chat-compose textarea:focus { border-color:#2c3e50; }
.gx-chat-send-row { display:flex; justify-content:flex-end; margin-top:6px; gap:8px; }
.gx-btn-send { background:#2c3e50; color:#fff; border:none; border-radius:6px;
               padding:7px 18px; font-size:.82rem; cursor:pointer; font-weight:600; }
.gx-btn-send:hover { background:#3d5166; }
.gx-btn-send:disabled { opacity:.55; cursor:not-allowed; }

/* Floating compose */
#gx-fab-compose { position:fixed; bottom:28px; right:28px; width:52px; height:52px;
    border-radius:50%; background:#2c3e50; color:#fff; border:none; font-size:1.4rem;
    cursor:pointer; box-shadow:0 4px 16px rgba(0,0,0,.25); z-index:999;
    display:flex; align-items:center; justify-content:center; transition:.2s; }
#gx-fab-compose:hover { background:#3d5166; transform:scale(1.07); }

/* Compose modal */
.gx-compose-modal-backdrop { position:fixed; inset:0; background:rgba(0,0,0,.45);
    z-index:1050; display:none; align-items:center; justify-content:center; }
.gx-compose-modal-backdrop.open { display:flex; }
.gx-compose-modal { background:#fff; border-radius:10px; width:100%; max-width:540px;
    box-shadow:0 8px 32px rgba(0,0,0,.18); display:flex; flex-direction:column;
    max-height:90vh; }
.gx-compose-header { background:#2c3e50; color:#fff; padding:14px 18px;
    border-radius:10px 10px 0 0; display:flex; align-items:center; justify-content:space-between; }
.gx-compose-header h6 { margin:0; font-size:.95rem; font-weight:600; }
.gx-compose-close { background:none; border:none; color:#fff; font-size:1.2rem;
    cursor:pointer; line-height:1; padding:0; }
.gx-compose-body { padding:18px; display:flex; flex-direction:column; gap:10px; overflow-y:auto; }
.gx-compose-body label { font-size:.8rem; font-weight:600; color:#555; margin-bottom:2px; }
.gx-compose-body input, .gx-compose-body textarea {
    border:1px solid #dee2e6; border-radius:6px; padding:8px 12px;
    font-size:.85rem; width:100%; outline:none; font-family:inherit; }
.gx-compose-body input:focus, .gx-compose-body textarea:focus { border-color:#2c3e50; }
.gx-compose-body textarea { min-height:130px; resize:vertical; }
.gx-compose-footer { padding:12px 18px; border-top:1px solid #eee;
    display:flex; justify-content:flex-end; gap:8px; }
.gx-btn-cancel { background:#f1f3f5; color:#555; border:none; border-radius:6px;
                 padding:8px 18px; font-size:.84rem; cursor:pointer; }
.gx-btn-cancel:hover { background:#e2e6ea; }
#gx-compose-status { font-size:.8rem; padding:4px 0; min-height:20px; }

/* SMTP setup badge */
.smtp-status-badge { font-size:.75rem; padding:3px 8px; border-radius:12px; font-weight:600; }
.smtp-status-badge.configured { background:#d4edda; color:#155724; }
.smtp-status-badge.not-configured { background:#f8d7da; color:#721c24; }

/* Empty state */
.gx-empty-state { padding:40px 20px; text-align:center; color:#aaa; }
.gx-empty-state i { font-size:2rem; margin-bottom:10px; }

/* Panel hide/show via tab */
[data-panel] { display:none !important; }
[data-panel].active { display:flex !important; flex-direction:column; flex:1; overflow:hidden; }

/* Scrollbar thin */
.gx-lead-list::-webkit-scrollbar, .gx-thread-list::-webkit-scrollbar,
.gx-chat-body::-webkit-scrollbar { width:4px; }
.gx-lead-list::-webkit-scrollbar-thumb, .gx-thread-list::-webkit-scrollbar-thumb,
.gx-chat-body::-webkit-scrollbar-thumb { background:#ccc; border-radius:2px; }

/* ── Mobile responsive ─────────────────────────────────────────────────── */
@media (max-width: 767px) {
  .gx-email-wrapper {
    flex-direction: column;
    height: auto;
    min-height: 0;
    border-radius: 0;
    border-left: none;
    border-right: none;
  }

  /* On mobile, left panel is full-width */
  .gx-email-left {
    width: 100%;
    min-width: 0;
    border-right: none;
    border-bottom: 1px solid #dee2e6;
    /* When a thread is open, hide the left panel (show main instead) */
  }

  /* When a thread is open on mobile, hide left and show main full-width */
  .gx-email-wrapper.thread-open .gx-email-left {
    display: none !important;
  }
  .gx-email-wrapper.thread-open .gx-email-main {
    min-height: calc(100vh - 160px);
  }

  /* Without a thread open, hide main panel */
  .gx-email-wrapper:not(.thread-open) .gx-email-main {
    display: none !important;
  }

  .gx-email-main {
    width: 100%;
    min-height: calc(100vh - 160px);
  }

  /* Back button shown only on mobile */
  .gx-mobile-back {
    display: flex !important;
    align-items: center;
    gap: 8px;
    padding: 10px 14px;
    background: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
    cursor: pointer;
    font-size: .84rem;
    font-weight: 600;
    color: #2c3e50;
    border: none;
    width: 100%;
    text-align: left;
  }
  .gx-mobile-back:hover { background: #eef2f7; }

  /* Thread list items: bigger tap targets */
  .gx-thread-item, .gx-lead-item { padding: 13px 14px; }

  /* Chat compose: full width inputs */
  .gx-chat-compose { padding: 10px 12px; }
  .gx-chat-compose input[type=text],
  .gx-chat-compose textarea { font-size: .9rem; }

  /* Chat header */
  .gx-chat-header { padding: 10px 14px; }

  /* FAB: smaller margin on mobile */
  #gx-fab-compose { bottom: 16px; right: 16px; width: 46px; height: 46px; font-size: 1.2rem; }

  /* Compose modal: full-screen on mobile */
  .gx-compose-modal-backdrop { align-items: flex-end; }
  .gx-compose-modal {
    max-width: 100%;
    border-radius: 14px 14px 0 0;
    max-height: 95vh;
  }

  /* Messages: wider bubbles on mobile */
  .gx-msg { max-width: 90%; }

  /* Chat body padding */
  .gx-chat-body { padding: 12px; }
}

/* Hide back button on desktop */
.gx-mobile-back { display: none; }
</style>

<?php admin_body_open('/admin/email.php'); ?>
<?php admin_content_header('Email', ['Dashboard' => '/admin/', 'Email' => '']); ?>

    <!-- SMTP not configured warning -->
    <?php
    $smtp_configured = (bool) get_site_setting('smtp_pass', '');
    if (!$smtp_configured): ?>
    <div class="alert alert-warning py-2 mb-2" style="font-size:.84rem;">
      <i class="fas fa-exclamation-triangle me-1"></i>
      SMTP not configured — emails won't send.
      <a href="/admin/settings.php#smtp-settings" class="alert-link ms-1">Configure now →</a>
    </div>
    <?php endif; ?>

    <div class="gx-email-wrapper<?= $open_thread ? ' thread-open' : '' ?>">

      <!-- ── LEFT PANEL ───────────────────────────────────────────────── -->
      <div class="gx-email-left">
        <div class="gx-email-tabs">
          <button class="gx-email-tab active" data-tab="leads" onclick="switchTab('leads',this)">
            <i class="fas fa-envelope-open-text me-1"></i> Leads
            <?php if ($new_leads_count > 0): ?>
            <span class="gx-nav-badge ms-1"><?= $new_leads_count > 9 ? '9+' : $new_leads_count ?></span>
            <?php endif; ?>
          </button>
          <button class="gx-email-tab" data-tab="chat" onclick="switchTab('chat',this)">
            <i class="fas fa-comments me-1"></i> Chat
            <?php
            $unread_msgs = array_sum(array_column($threads_raw, 'unread'));
            if ($unread_msgs > 0): ?>
            <span class="gx-nav-badge ms-1"><?= $unread_msgs > 9 ? '9+' : $unread_msgs ?></span>
            <?php endif; ?>
          </button>
        </div>

        <!-- Leads panel -->
        <div data-panel="leads" class="active gx-lead-list">
          <?php if (empty($leads)): ?>
          <div class="gx-empty-state">
            <i class="fas fa-inbox d-block"></i>
            No leads yet.<br><small>Emails collected from the Locate page will appear here.</small>
          </div>
          <?php else: foreach ($leads as $lead): ?>
          <div class="gx-lead-item">
            <i class="fas fa-user-circle text-muted" style="font-size:1.1rem;flex-shrink:0;"></i>
            <span class="gx-lead-email" title="<?= e($lead['email']) ?>"><?= e($lead['email']) ?></span>
            <button class="gx-lead-btn"
                    onclick="openComposeFor('<?= e(addslashes($lead['email'])) ?>')">
              Mail
            </button>
            <form method="post" class="d-inline" onsubmit="return confirm('Delete this email lead?');">
              <?= csrf_field() ?>
              <input type="hidden" name="delete_lead_id" value="<?= (int)$lead['id'] ?>">
              <button type="submit" class="gx-lead-del-btn" title="Delete lead">
                <i class="fas fa-trash-alt"></i>
              </button>
            </form>
          </div>
          <?php endforeach; endif; ?>
        </div>

        <!-- Chat thread list panel -->
        <div data-panel="chat" class="gx-thread-list">
          <?php if (empty($threads_raw)): ?>
          <div class="gx-empty-state">
            <i class="fas fa-comment-slash d-block"></i>
            No conversations yet.
          </div>
          <?php else: foreach ($threads_raw as $t):
            $initial = strtoupper(substr($t['recipient_email'], 0, 1));
            $is_active = ($open_thread === $t['recipient_email']) ? 'active' : '';
            $time_str = date('d M', strtotime($t['last_message']));
          ?>
          <div class="gx-thread-item <?= $is_active ?>"
               onclick="openThread('<?= e(addslashes($t['recipient_email'])) ?>')">
            <div class="gx-thread-avatar"><?= e($initial) ?></div>
            <div class="gx-thread-info">
              <div class="gx-thread-name"><?= e($t['recipient_email']) ?></div>
              <div class="gx-thread-preview"><?= (int)$t['total'] ?> message<?= $t['total']!=1?'s':'' ?></div>
            </div>
            <div class="gx-thread-meta">
              <span class="gx-thread-time"><?= $time_str ?></span>
              <?php if ($t['unread'] > 0): ?>
              <span class="gx-nav-badge"><?= $t['unread'] > 9 ? '9+' : (int)$t['unread'] ?></span>
              <?php endif; ?>
            </div>
          </div>
          <?php endforeach; endif; ?>
        </div>
      </div>

      <!-- ── MAIN PANEL ───────────────────────────────────────────────── -->
      <div class="gx-email-main" id="gx-main-pane">
        <?php if ($open_thread): ?>
        <button class="gx-mobile-back" onclick="window.location.href='/admin/email.php#chat'">
          <i class="fas fa-arrow-left"></i> Back to threads
        </button>
        <?php endif; ?>

        <?php if ($open_thread && !empty($thread_messages)): ?>
        <!-- Chat view for open thread -->
        <div class="gx-chat-header justify-content-between">
          <div class="d-flex align-items-center gap-2">
            <div class="gx-thread-avatar"><?= strtoupper(substr($open_thread, 0, 1)) ?></div>
            <div>
              <div class="gx-chat-email-label"><?= e($open_thread) ?></div>
              <div style="font-size:.72rem;color:#aaa;"><?= count($thread_messages) ?> messages</div>
            </div>
          </div>
          <form method="post" onsubmit="return confirm('Delete this entire email conversation?');">
            <?= csrf_field() ?>
            <input type="hidden" name="delete_thread_email" value="<?= e($open_thread) ?>">
            <button type="submit" class="btn btn-outline-danger btn-sm" title="Delete conversation">
              <i class="fas fa-trash-alt me-1"></i>Delete Conversation
            </button>
          </form>
        </div>
        <div class="gx-chat-body" id="gx-chat-body">
          <?php foreach ($thread_messages as $msg): ?>
          <div class="gx-msg <?= e($msg['direction']) ?>">
            <?php if ($msg['subject']): ?>
            <div class="gx-msg-subject">📧 <?= e($msg['subject']) ?></div>
            <?php endif; ?>
            <div class="gx-msg-bubble"><?= e($msg['body']) ?></div>
            <div class="gx-msg-time"><?= date('d M Y, g:ia', strtotime($msg['sent_at'])) ?></div>
          </div>
          <?php endforeach; ?>
        </div>
        <!-- Mini compose -->
        <div class="gx-chat-compose">
          <input type="text" id="mini-subject" placeholder="Subject" />
          <textarea id="mini-body" placeholder="Write your message…"></textarea>
          <div class="gx-chat-send-row">
            <span id="mini-status" style="font-size:.78rem;color:#888;align-self:center;"></span>
            <button class="gx-btn-send" id="mini-send-btn"
                    onclick="sendMiniCompose('<?= e(addslashes($open_thread)) ?>')">
              <i class="fas fa-paper-plane me-1"></i> Send
            </button>
          </div>
        </div>

        <?php elseif ($open_thread): ?>
        <div class="gx-email-main-empty">
          <i class="fas fa-envelope-open fa-2x" style="color:#ddd;"></i>
          <span>No messages yet with <strong><?= e($open_thread) ?></strong>.<br>Use the compose button below to start.</span>
        </div>
        <div class="gx-chat-compose">
          <input type="text" id="mini-subject" placeholder="Subject" />
          <textarea id="mini-body" placeholder="Write your first message…"></textarea>
          <div class="gx-chat-send-row">
            <span id="mini-status" style="font-size:.78rem;color:#888;align-self:center;"></span>
            <button class="gx-btn-send" onclick="sendMiniCompose('<?= e(addslashes($open_thread)) ?>')">
              <i class="fas fa-paper-plane me-1"></i> Send
            </button>
          </div>
        </div>

        <?php else: ?>
        <div class="gx-email-main-empty">
          <i class="fas fa-envelope fa-3x" style="color:#ddd;"></i>
          <span>Select a lead to compose, or<br>open a chat thread to reply.</span>
        </div>
        <?php endif; ?>

      </div>
    </div><!-- .gx-email-wrapper -->

<!-- ── FLOATING COMPOSE BUTTON ──────────────────────────────────────── -->
<button id="gx-fab-compose" title="Compose new email" onclick="openComposeFor('')">
  <i class="fas fa-pen"></i>
</button>

<!-- ── COMPOSE MODAL ─────────────────────────────────────────────────── -->
<div class="gx-compose-modal-backdrop" id="compose-backdrop">
  <div class="gx-compose-modal">
    <div class="gx-compose-header">
      <h6><i class="fas fa-pen me-2"></i>New Message</h6>
      <button class="gx-compose-close" onclick="closeCompose()">×</button>
    </div>
    <div class="gx-compose-body">
      <div>
        <label>From</label>
        <input type="text" value="<?= e($from_name) ?> &lt;<?= e($from_addr) ?>&gt;" readonly
               style="background:#f8f9fa;color:#888;">
      </div>
      <div>
        <label>To <span style="color:red">*</span></label>
        <input type="email" id="compose-to" placeholder="recipient@example.com" />
      </div>
      <div>
        <label>Subject <span style="color:red">*</span></label>
        <input type="text" id="compose-subject" placeholder="Email subject" />
      </div>
      <div>
        <label>Message <span style="color:red">*</span></label>
        <textarea id="compose-body" placeholder="Write your message here…"></textarea>
      </div>
      <div id="gx-compose-status"></div>
    </div>
    <div class="gx-compose-footer">
      <button class="gx-btn-cancel" onclick="closeCompose()">Cancel</button>
      <button class="gx-btn-send" id="compose-send-btn" onclick="submitCompose()">
        <i class="fas fa-paper-plane me-1"></i> Send
      </button>
    </div>
  </div>
</div>

<script>
// ── Tab switching ───────────────────────────────────────────────────────────
function switchTab(tab, btn) {
  document.querySelectorAll('.gx-email-tab').forEach(t => t.classList.remove('active'));
  btn.classList.add('active');
  document.querySelectorAll('[data-panel]').forEach(p => p.classList.remove('active'));
  const panel = document.querySelector('[data-panel="' + tab + '"]');
  if (panel) panel.classList.add('active');
}

// ── Open a thread ───────────────────────────────────────────────────────────
function openThread(email) {
  window.location.href = '/admin/email.php?thread=' + encodeURIComponent(email) + '#chat';
}
// ── Mobile back: go back to thread list ─────────────────────────────────────
function mobileBack() {
  window.location.href = '/admin/email.php#chat';
}

// ── Compose modal ───────────────────────────────────────────────────────────
function openComposeFor(email) {
  document.getElementById('compose-to').value = email;
  document.getElementById('compose-subject').value = '';
  document.getElementById('compose-body').value = '';
  document.getElementById('gx-compose-status').textContent = '';
  document.getElementById('compose-backdrop').classList.add('open');
  (email ? document.getElementById('compose-subject') : document.getElementById('compose-to')).focus();
}
function closeCompose() {
  document.getElementById('compose-backdrop').classList.remove('open');
}
document.getElementById('compose-backdrop').addEventListener('click', function(e){
  if (e.target === this) closeCompose();
});

async function submitCompose() {
  const to      = document.getElementById('compose-to').value.trim();
  const subject = document.getElementById('compose-subject').value.trim();
  const body    = document.getElementById('compose-body').value.trim();
  const status  = document.getElementById('gx-compose-status');
  const btn     = document.getElementById('compose-send-btn');

  if (!to || !subject || !body) {
    status.innerHTML = '<span style="color:#c0392b;">⚠ Please fill in all fields.</span>'; return;
  }
  btn.disabled = true;
  status.innerHTML = '<span style="color:#888;">Sending…</span>';

  const fd = new FormData();
  fd.append('to', to); fd.append('subject', subject); fd.append('body', body);
  const r = await fetch('/admin/email_send.php', { method:'POST', body:fd });
  const d = await r.json();
  btn.disabled = false;

  if (d.ok) {
    status.innerHTML = '<span style="color:#27ae60;">✓ Sent successfully.</span>';
    setTimeout(() => { closeCompose(); window.location.href='/admin/email.php?thread='+encodeURIComponent(to); }, 900);
  } else {
    status.innerHTML = '<span style="color:#c0392b;">✗ ' + (d.error||'Unknown error') + '</span>';
  }
}

// ── Mini compose (chat pane) ────────────────────────────────────────────────
async function sendMiniCompose(to) {
  const subject = document.getElementById('mini-subject').value.trim();
  const body    = document.getElementById('mini-body').value.trim();
  const status  = document.getElementById('mini-status');
  const btn     = document.getElementById('mini-send-btn');

  if (!subject || !body) {
    status.textContent = '⚠ Subject and message required.'; return;
  }
  if (btn) btn.disabled = true;
  status.textContent = 'Sending…';

  const fd = new FormData();
  fd.append('to', to); fd.append('subject', subject); fd.append('body', body);
  const r = await fetch('/admin/email_send.php', { method:'POST', body:fd });
  const d = await r.json();
  if (btn) btn.disabled = false;

  if (d.ok) {
    status.textContent = '✓ Sent.';
    setTimeout(() => window.location.reload(), 700);
  } else {
    status.textContent = '✗ ' + (d.error||'Failed');
  }
}

// ── On open: scroll chat to bottom ─────────────────────────────────────────
window.addEventListener('DOMContentLoaded', () => {
  const cb = document.getElementById('gx-chat-body');
  if (cb) cb.scrollTop = cb.scrollHeight;

  // If URL has #chat, switch to chat tab
  if (window.location.hash === '#chat') {
    const btn = document.querySelector('.gx-email-tab[data-tab="chat"]');
    if (btn) switchTab('chat', btn);
  }

  // Auto-fetch real incoming emails from IMAP inbox silently on load
  fetchIncomingMail();
});

// ── Fetch incoming emails from IMAP ─────────────────────────────────────────
async function fetchIncomingMail() {
  try {
    const r = await fetch('/admin/fetch_mail.php?ping=1', { method: 'GET' });
    const d = await r.json();
    if (d.ok && d.fetched > 0) {
      // New emails arrived — reload page so chat list/badges update
      window.location.reload();
    }
  } catch(e) {
    // Silent fail — IMAP may not be configured yet
  }
}
</script>

<?php admin_footer(); ?>
