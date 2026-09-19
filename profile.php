<?php
require_once __DIR__ . '/config.php';
login_required();

$db      = get_db();
$user    = current_user();
$user_id = (int)$_SESSION['user_id'];
$errors  = [];
$success = '';

// Ensure profile_picture column exists
try { $db->exec("ALTER TABLE user_gridxuser ADD COLUMN profile_picture VARCHAR(255) DEFAULT NULL"); } catch(Exception $e){}

// ── Handle profile picture upload ─────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_photo') {
    verify_csrf();
    if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
        $file    = $_FILES['profile_pic'];
        $allowed = ['image/jpeg','image/png','image/webp','image/gif'];
        $ftype   = mime_content_type($file['tmp_name']);
        $maxsize = 2 * 1024 * 1024; // 2MB

        if (!in_array($ftype, $allowed)) {
            $errors[] = 'Only JPG, PNG, WEBP or GIF images are allowed.';
        } elseif ($file['size'] > $maxsize) {
            $errors[] = 'Image must be under 2MB.';
        } else {
            $upload_dir = __DIR__ . '/static/images/profiles';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

            // Delete old picture
            if (!empty($user['profile_picture'])) {
                $old = __DIR__ . $user['profile_picture'];
                if (file_exists($old)) @unlink($old);
            }

            $ext      = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp','image/gif'=>'gif'][$ftype];
            $filename = 'avatar_' . $user_id . '_' . time() . '.' . $ext;
            $target   = $upload_dir . '/' . $filename;

            if (move_uploaded_file($file['tmp_name'], $target)) {
                $path = '/static/images/profiles/' . $filename;
                $db->prepare('UPDATE user_gridxuser SET profile_picture=? WHERE id=?')->execute([$path, $user_id]);
                $user['profile_picture'] = $path;
                $success = 'Profile picture updated successfully.';
            } else {
                $errors[] = 'Upload failed. Please try again.';
            }
        }
    } else {
        $errors[] = 'Please select an image file.';
    }
}

// ── Handle account info update ─────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_info') {
    verify_csrf();
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name  = trim($_POST['last_name']  ?? '');

    $db->prepare('UPDATE user_gridxuser SET first_name=?, last_name=? WHERE id=?')
       ->execute([$first_name, $last_name, $user_id]);
    $user = current_user(); // refresh
    $success = 'Account information updated.';
}

// ── Handle remove photo ────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'remove_photo') {
    verify_csrf();
    if (!empty($user['profile_picture'])) {
        $old = __DIR__ . $user['profile_picture'];
        if (file_exists($old)) @unlink($old);
    }
    $db->prepare('UPDATE user_gridxuser SET profile_picture=NULL WHERE id=?')->execute([$user_id]);
    $user = current_user(); // refresh
    $success = 'Profile picture removed successfully.';
    $avatar = '';
}

// ── Handle password change ─────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_password') {
    verify_csrf();
    $current  = $_POST['current_password']  ?? '';
    $new_pass = $_POST['new_password']      ?? '';
    $confirm  = $_POST['confirm_password']  ?? '';

    if (empty($current))  $errors[] = 'Current password is required.';
    if (strlen($new_pass) < 8) $errors[] = 'New password must be at least 8 characters.';
    if ($new_pass !== $confirm) $errors[] = 'New passwords do not match.';

    if (!$errors) {
        $valid = false;
        if (function_exists('django_check_pbkdf2')) {
            $valid = django_check_pbkdf2($current, $user['password']);
        }
        if (!$valid) {
            $valid = password_verify($current, $user['password']);
        }
        if (!$valid) {
            $errors[] = 'Current password is incorrect.';
        } else {
            $hash = password_hash($new_pass, PASSWORD_BCRYPT);
            $db->prepare('UPDATE user_gridxuser SET password=? WHERE id=?')->execute([$hash, $user_id]);
            $success = 'Password changed successfully.';
        }
    }
}

$username   = $user['username']        ?? '';
$email      = $user['email']           ?? '';
$first_name = $user['first_name']      ?? '';
$last_name  = $user['last_name']       ?? '';
$avatar     = $user['profile_picture'] ?? '';
$balance    = $user['balance']         ?? '0.00';
$joined     = $user['date_joined']     ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile | GridX</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Rubik:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="<?= asset_url('/static/styles/profile.css') ?>">
</head>
<body>

<div class="profile-shell">

    <!-- Top nav bar -->
    <nav class="profile-nav">
        <a href="/dashboard.php" class="nav-back"><i class="fa-solid fa-arrow-left"></i> Dashboard</a>
        <span class="nav-title">My Profile</span>
        <form method="post" action="/logout.php" style="margin:0;">
            <?= csrf_field() ?>
            <button type="submit" class="nav-logout"><i class="fa-solid fa-right-from-bracket"></i> Logout</button>
        </form>
    </nav>

    <div class="profile-body">

        <!-- Flash -->
        <?php if ($success): ?>
        <div class="flash-success"><i class="fa-solid fa-circle-check"></i> <?= e($success) ?></div>
        <?php endif; ?>
        <?php if ($errors): ?>
        <div class="flash-error"><i class="fa-solid fa-triangle-exclamation"></i> <?= implode(' &bull; ', array_map('e', $errors)) ?></div>
        <?php endif; ?>

        <!-- ── PROFILE HERO ── -->
        <div class="profile-hero">
            <div class="avatar-wrap">
                <?php if ($avatar): ?>
                <img src="<?= e($avatar) ?>?v=<?= time() ?>" alt="Profile picture" class="avatar-img" id="avatarPreview">
                <form method="post" action="/profile.php" class="form-remove-photo" id="removePhotoForm" onsubmit="return confirm('Remove your profile picture?');">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="remove_photo">
                    <button type="submit" class="avatar-remove-btn" title="Remove photo"><i class="fa-solid fa-trash-can"></i></button>
                </form>
                <?php else: ?>
                <div class="avatar-placeholder" id="avatarPreview">
                    <i class="fa-solid fa-user"></i>
                </div>
                <?php endif; ?>
                <label class="avatar-edit-btn" for="profilePicInput" title="Change photo">
                    <i class="fa-solid fa-camera"></i>
                </label>
            </div>
            <div class="hero-info">
                <h2><?= e($username) ?></h2>
                <p class="hero-email"><?= e($email) ?></p>
                <div class="hero-badges">
                    <span class="badge badge-balance"><i class="fa-solid fa-wallet"></i> $<?= e($balance) ?></span>
                    <?php if (!empty($joined)): ?>
                    <span class="badge badge-joined"><i class="fa-solid fa-calendar"></i> Joined <?= e(substr($joined,0,10)) ?></span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Hidden file input — triggers from camera button -->
        <form method="post" action="/profile.php" enctype="multipart/form-data" id="photoForm">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="update_photo">
            <input type="file" id="profilePicInput" name="profile_pic" accept="image/*" style="display:none;">
        </form>

        <!-- Crop modal -->
        <div class="crop-modal" id="cropModal" style="display:none;">
            <div class="crop-modal-inner" role="dialog" aria-modal="true" aria-labelledby="cropTitle">
                <h3 id="cropTitle"><i class="fa-solid fa-crop-simple"></i> Crop Your Photo</h3>
                <p class="crop-note">Drag and zoom to position. The circle shows how it will appear.</p>
                <div class="crop-preview-wrap">
                    <div class="crop-stage" id="cropStage">
                        <canvas id="cropCanvas"></canvas>
                    </div>
                </div>
                <div class="crop-controls">
                    <label for="cropZoom">Zoom</label>
                    <input type="range" id="cropZoom" min="1" max="3" step="0.01" value="1">
                </div>
                <div class="crop-actions">
                    <button type="button" class="btn-outline" id="cropCancelBtn">Cancel</button>
                    <button type="button" class="btn-primary-sm" id="cropApplyBtn">
                        <i class="fa-solid fa-check"></i> <span>Use This Photo</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- ── TABS ── -->
        <div class="tabs">
            <button class="tab-btn active" data-tab="info"><i class="fa-solid fa-id-card"></i> Account Info</button>
            <button class="tab-btn" data-tab="security"><i class="fa-solid fa-lock"></i> Security</button>
        </div>

        <!-- ── TAB: ACCOUNT INFO ── -->
        <div class="tab-pane active" id="tab-info">
            <div class="card-section">
                <h3 class="section-title">
                    <span><i class="fa-solid fa-user-pen"></i> Personal Information</span>
                    <button type="button" class="btn-edit-pencil" id="editNameBtn" title="Edit name"><i class="fa-solid fa-pen"></i></button>
                </h3>
                <form method="post" action="/profile.php">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="update_info">
                    <div class="field-row">
                        <div class="form-group">
                            <label>First Name</label>
                            <input type="text" name="first_name" id="inpFirstName" value="<?= e($first_name) ?>" placeholder="First name" <?= $first_name ? 'readonly class="input-readonly"' : '' ?>>
                        </div>
                        <div class="form-group">
                            <label>Last Name</label>
                            <input type="text" name="last_name" id="inpLastName" value="<?= e($last_name) ?>" placeholder="Last name" <?= $last_name ? 'readonly class="input-readonly"' : '' ?>>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Username</label>
                        <input type="text" value="<?= e($username) ?>" disabled class="input-disabled">
                        <small>Username cannot be changed.</small>
                    </div>
                    <div class="form-group">
                        <label>Email Address</label>
                        <input type="email" value="<?= e($email) ?>" disabled class="input-disabled">
                    </div>
                    <div id="infoFormActions" style="display:<?= ($first_name && $last_name) ? 'none' : 'flex' ?>; gap:10px; margin-top:0.4rem;">
                        <button type="submit" class="btn-submit" style="margin-top:0;">Save Changes</button>
                        <button type="button" class="btn-outline" id="cancelEditBtn" style="margin-top:0; <?= ($first_name && $last_name) ? '' : 'display:none;' ?>">Cancel</button>
                    </div>
                </form>
            </div>

            <div class="card-section mt-section">
                <h3 class="section-title"><i class="fa-solid fa-circle-info"></i> Account Details</h3>
                <div class="info-grid">
                    <div class="info-item">
                        <span class="info-label">Account ID</span>
                        <span class="info-val">#<?= $user_id ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Username</span>
                        <span class="info-val"><?= e($username) ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Wallet Balance</span>
                        <span class="info-val">$<?= e($balance) ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Date Joined</span>
                        <span class="info-val"><?= e($joined ? substr($joined,0,10) : '—') ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── TAB: SECURITY ── -->
        <div class="tab-pane" id="tab-security">
            <div class="card-section">
                <h3 class="section-title"><i class="fa-solid fa-key"></i> Change Password</h3>
                <form method="post" action="/profile.php">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="change_password">
                    <div class="form-group">
                        <label>Current Password</label>
                        <div class="pw-wrap">
                            <input type="password" name="current_password" id="pwCurrent" required placeholder="Enter current password">
                            <button type="button" class="pw-eye" onclick="togglePw('pwCurrent',this)"><i class="fa-solid fa-eye"></i></button>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>New Password</label>
                        <div class="pw-wrap">
                            <input type="password" name="new_password" id="pwNew" required placeholder="At least 8 characters" minlength="8">
                            <button type="button" class="pw-eye" onclick="togglePw('pwNew',this)"><i class="fa-solid fa-eye"></i></button>
                        </div>
                        <div class="pw-strength" id="pwStrength"></div>
                    </div>
                    <div class="form-group">
                        <label>Confirm New Password</label>
                        <div class="pw-wrap">
                            <input type="password" name="confirm_password" id="pwConfirm" required placeholder="Repeat new password">
                            <button type="button" class="pw-eye" onclick="togglePw('pwConfirm',this)"><i class="fa-solid fa-eye"></i></button>
                        </div>
                        <small id="pwMatch"></small>
                    </div>
                    <button type="submit" class="btn-submit">Update Password</button>
                </form>
            </div>

            <div class="card-section mt-section">
                <h3 class="section-title"><i class="fa-solid fa-shield-halved"></i> Login & Session</h3>
                <div class="info-grid">
                    <div class="info-item">
                        <span class="info-label">Login Status</span>
                        <span class="info-val"><span class="status-dot"></span> Active</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Session</span>
                        <span class="info-val">Secured</span>
                    </div>
                </div>
                <form method="post" action="/logout.php" class="mt-2">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn-danger-outline"><i class="fa-solid fa-right-from-bracket"></i> Sign Out All Sessions</button>
                </form>
            </div>
        </div>

    </div><!-- /profile-body -->
</div><!-- /profile-shell -->

<script>
// ── Edit Pencil ───────────────────────────────────────────
const editNameBtn = document.getElementById('editNameBtn');
const cancelEditBtn = document.getElementById('cancelEditBtn');
const inpFirstName = document.getElementById('inpFirstName');
const inpLastName = document.getElementById('inpLastName');
const infoFormActions = document.getElementById('infoFormActions');

let origFirstName = inpFirstName ? inpFirstName.value : '';
let origLastName = inpLastName ? inpLastName.value : '';

if (editNameBtn) {
    if (!inpFirstName.hasAttribute('readonly')) {
        editNameBtn.style.display = 'none';
    }
    editNameBtn.addEventListener('click', () => {
        inpFirstName.removeAttribute('readonly');
        inpLastName.removeAttribute('readonly');
        inpFirstName.classList.remove('input-readonly');
        inpLastName.classList.remove('input-readonly');
        infoFormActions.style.display = 'flex';
        cancelEditBtn.style.display = 'inline-block';
        editNameBtn.style.display = 'none';
        inpFirstName.focus();
    });
}
if (cancelEditBtn) {
    cancelEditBtn.addEventListener('click', () => {
        inpFirstName.setAttribute('readonly', 'readonly');
        inpLastName.setAttribute('readonly', 'readonly');
        inpFirstName.classList.add('input-readonly');
        inpLastName.classList.add('input-readonly');
        inpFirstName.value = origFirstName;
        inpLastName.value = origLastName;
        infoFormActions.style.display = 'none';
        editNameBtn.style.display = 'inline-block';
    });
}

// ── Tabs ──────────────────────────────────────────────────
document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        document.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('active'));
        this.classList.add('active');
        document.getElementById('tab-' + this.dataset.tab).classList.add('active');
    });
});

// ── Password eye toggle ───────────────────────────────────
function togglePw(id, btn) {
    const inp = document.getElementById(id);
    const icon = btn.querySelector('i');
    if (inp.type === 'password') {
        inp.type = 'text';
        icon.className = 'fa-solid fa-eye-slash';
    } else {
        inp.type = 'password';
        icon.className = 'fa-solid fa-eye';
    }
}

// ── Password strength ────────────────────────────────────
const pwNew = document.getElementById('pwNew');
const pwConfirm = document.getElementById('pwConfirm');
if (pwNew) {
    pwNew.addEventListener('input', function() {
        const v = this.value;
        const bar = document.getElementById('pwStrength');
        let score = 0;
        if (v.length >= 8)  score++;
        if (/[A-Z]/.test(v)) score++;
        if (/[0-9]/.test(v)) score++;
        if (/[^A-Za-z0-9]/.test(v)) score++;
        const labels = ['', 'Weak', 'Fair', 'Good', 'Strong'];
        const colors = ['', '#e53935', '#fb8c00', '#f9a825', '#2e7d32'];
        bar.innerHTML = v.length ? `<span style="color:${colors[score]}">● ${labels[score]}</span>` : '';
    });
}
if (pwConfirm) {
    pwConfirm.addEventListener('input', function() {
        const match = document.getElementById('pwMatch');
        if (!match) return;
        if (this.value === pwNew.value) {
            match.innerHTML = '<span style="color:#2e7d32">✔ Passwords match</span>';
        } else {
            match.innerHTML = '<span style="color:#e53935">✘ Passwords do not match</span>';
        }
    });
}

// ── Profile picture upload & crop ────────────────────────
// Canvas-based cropper. The crop frame is a square; the circle is that square's
// inscribed circle, so what you see is exactly what gets saved.
(function () {
    const MAX_SOURCE_PX = 1600;  // working size of the photo (keeps memory low on phones)
    const OUTPUT_PX     = 400;   // saved avatar size
    const MAX_ZOOM      = 3;

    const input     = document.getElementById('profilePicInput');
    const form      = document.getElementById('photoForm');
    const modal     = document.getElementById('cropModal');
    const stage     = document.getElementById('cropStage');
    const canvas    = document.getElementById('cropCanvas');
    const zoomEl    = document.getElementById('cropZoom');
    const applyBtn  = document.getElementById('cropApplyBtn');
    const cancelBtn = document.getElementById('cropCancelBtn');
    const ctx       = canvas.getContext('2d');

    // cx/cy = the point of the photo that sits at the centre of the crop frame.
    // Everything else (position, limits, export) is derived from it.
    const s = { src: null, iw: 0, ih: 0, size: 0, dpr: 1, minScale: 1, zoom: 1,
                cx: 0, cy: 0, dragging: false, lx: 0, ly: 0, raf: 0 };

    const scale = () => s.minScale * s.zoom;

    // Keep the photo covering the whole frame. If the photo has no spare room
    // in a direction, that direction simply can't move: no special cases needed.
    function clampCenter() {
        const half = s.size / (2 * scale());
        s.cx = Math.min(s.iw - half, Math.max(half, s.cx));
        s.cy = Math.min(s.ih - half, Math.max(half, s.cy));
    }

    // Read the real frame size from the DOM (never hard-code it)
    function measure() {
        s.size = stage.clientWidth;
        s.dpr  = Math.min(window.devicePixelRatio || 1, 2);
        canvas.width = canvas.height = Math.round(s.size * s.dpr);
        s.minScale = s.size / Math.min(s.iw, s.ih);   // shortest side fills the frame exactly
    }

    function draw() {
        s.raf = 0;
        if (!s.src) return;
        const sc = scale(), half = s.size / 2;
        ctx.setTransform(s.dpr, 0, 0, s.dpr, 0, 0);
        ctx.clearRect(0, 0, s.size, s.size);
        ctx.imageSmoothingQuality = 'high';
        ctx.drawImage(s.src, half - s.cx * sc, half - s.cy * sc, s.iw * sc, s.ih * sc);
    }
    const requestDraw = () => { if (!s.raf) s.raf = requestAnimationFrame(draw); };

    function loadImage(file) {
        return new Promise((resolve, reject) => {
            const url = URL.createObjectURL(file);          // no base64 string in memory
            const img = new Image();
            img.onload  = () => { URL.revokeObjectURL(url); resolve(img); };
            img.onerror = () => { URL.revokeObjectURL(url); reject(new Error('decode')); };
            img.src = url;
        });
    }

    // Big phone photos (12MP+) are shrunk once so dragging/zooming stays smooth
    function toWorkingSource(img) {
        const w = img.naturalWidth, h = img.naturalHeight;
        const k = Math.min(1, MAX_SOURCE_PX / Math.max(w, h));
        if (k === 1) return { src: img, w: w, h: h };
        const c = document.createElement('canvas');
        c.width  = Math.round(w * k);
        c.height = Math.round(h * k);
        const cctx = c.getContext('2d');
        cctx.imageSmoothingQuality = 'high';
        cctx.drawImage(img, 0, 0, c.width, c.height);
        return { src: c, w: c.width, h: c.height };
    }

    function setBusy(busy) {
        applyBtn.disabled = cancelBtn.disabled = busy;
        applyBtn.querySelector('span').textContent = busy ? 'Saving…' : 'Use This Photo';
    }

    function openModal() {
        setBusy(false);
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
        measure();          // must run after the modal is visible
        clampCenter();
        draw();
    }

    function closeModal() {
        modal.style.display = 'none';
        document.body.style.overflow = '';
        s.src = null;       // release the image memory
    }

    function cancelCrop() {
        closeModal();
        input.value = '';
    }

    input.addEventListener('change', async function () {
        const file = input.files && input.files[0];
        if (!file) return;
        try {
            const img = await loadImage(file);
            if (!img.naturalWidth || !img.naturalHeight) throw new Error('empty');
            const work = toWorkingSource(img);
            s.src = work.src; s.iw = work.w; s.ih = work.h;
        } catch (err) {
            alert('That image could not be opened. Please choose a JPG, PNG or WEBP photo.');
            input.value = '';
            return;
        }
        s.zoom = 1; zoomEl.value = 1;
        s.cx = s.iw / 2; s.cy = s.ih / 2;   // start centred
        openModal();
    });

    zoomEl.addEventListener('input', function () {
        s.zoom = parseFloat(zoomEl.value);
        clampCenter();
        requestDraw();
    });

    // Mouse wheel zoom (desktop)
    stage.addEventListener('wheel', function (e) {
        e.preventDefault();
        s.zoom = Math.min(MAX_ZOOM, Math.max(1, s.zoom - e.deltaY * 0.002));
        zoomEl.value = s.zoom;
        clampCenter();
        requestDraw();
    }, { passive: false });

    // Drag (mouse + touch)
    stage.addEventListener('pointerdown', function (e) {
        s.dragging = true; s.lx = e.clientX; s.ly = e.clientY;
        stage.setPointerCapture(e.pointerId);
        stage.classList.add('is-dragging');
        e.preventDefault();
    });
    stage.addEventListener('pointermove', function (e) {
        if (!s.dragging) return;
        const sc = scale();
        s.cx -= (e.clientX - s.lx) / sc;
        s.cy -= (e.clientY - s.ly) / sc;
        s.lx = e.clientX; s.ly = e.clientY;
        clampCenter();
        requestDraw();
        e.preventDefault();
    });
    const endDrag = () => { s.dragging = false; stage.classList.remove('is-dragging'); };
    stage.addEventListener('pointerup', endDrag);
    stage.addEventListener('pointercancel', endDrag);
    stage.addEventListener('lostpointercapture', endDrag);

    // Rotation / resize while the modal is open
    window.addEventListener('resize', function () {
        if (!s.src || modal.style.display === 'none' || stage.clientWidth === s.size) return;
        measure(); clampCenter(); requestDraw();
    });

    cancelBtn.addEventListener('click', cancelCrop);
    modal.addEventListener('click', function (e) { if (e.target === modal && !applyBtn.disabled) cancelCrop(); });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && modal.style.display !== 'none' && !applyBtn.disabled) cancelCrop();
    });
    window.addEventListener('pageshow', () => setBusy(false));   // back-button safety

    applyBtn.addEventListener('click', function () {
        if (!s.src || applyBtn.disabled) return;
        setBusy(true);

        // The square of the photo currently inside the crop frame → saved avatar.
        // Saved as a full square (no baked-in circle/black corners); CSS rounds it.
        const side = s.size / scale();
        const out  = document.createElement('canvas');
        out.width = out.height = OUTPUT_PX;
        const octx = out.getContext('2d');
        octx.imageSmoothingQuality = 'high';
        octx.drawImage(s.src, s.cx - side / 2, s.cy - side / 2, side, side, 0, 0, OUTPUT_PX, OUTPUT_PX);

        out.toBlob(function (blob) {
            if (!blob) { setBusy(false); alert('Could not process the image. Please try again.'); return; }
            const file = new File([blob], 'profile.jpg', { type: 'image/jpeg' });
            try {
                const dt = new DataTransfer();
                dt.items.add(file);
                input.files = dt.files;
                form.submit();
            } catch (err) {   // very old browsers without DataTransfer
                const fd = new FormData(form);
                fd.set('profile_pic', file);
                fetch(form.action, { method: 'POST', body: fd, credentials: 'same-origin' })
                    .then(() => location.reload())
                    .catch(() => { setBusy(false); alert('Upload failed. Please try again.'); });
            }
        }, 'image/jpeg', 0.9);
    });
})();
</script>
</body>
</html>
