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
    $email      = trim($_POST['email']      ?? '');

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'A valid email address is required.';
    }
    if (!$errors) {
        // Check email uniqueness
        $chk = $db->prepare('SELECT id FROM user_gridxuser WHERE email=? AND id!=?');
        $chk->execute([$email, $user_id]);
        if ($chk->fetch()) {
            $errors[] = 'That email address is already in use.';
        } else {
            $db->prepare('UPDATE user_gridxuser SET first_name=?, last_name=?, email=? WHERE id=?')
               ->execute([$first_name, $last_name, $email, $user_id]);
            $user = current_user(); // refresh
            $success = 'Account information updated.';
        }
    }
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
            <div class="crop-modal-inner">
                <h3><i class="fa-solid fa-crop-simple"></i> Crop Your Photo</h3>
                <p class="crop-note">Drag and zoom to position. The circle shows how it will appear.</p>
                <div class="crop-preview-wrap">
                    <div class="crop-frame" id="cropFrame">
                        <img id="cropImg" src="" alt="Crop preview">
                        <div class="crop-circle-mask"></div>
                    </div>
                </div>
                <div class="crop-controls">
                    <label>Zoom</label>
                    <input type="range" id="cropZoom" min="1" max="3" step="0.05" value="1">
                </div>
                <div class="crop-actions">
                    <button type="button" class="btn-outline" onclick="cancelCrop()">Cancel</button>
                    <button type="button" class="btn-primary-sm" onclick="applyCrop()">
                        <i class="fa-solid fa-check"></i> Use This Photo
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
                <h3 class="section-title"><i class="fa-solid fa-user-pen"></i> Personal Information</h3>
                <form method="post" action="/profile.php">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="update_info">
                    <div class="field-row">
                        <div class="form-group">
                            <label>First Name</label>
                            <input type="text" name="first_name" value="<?= e($first_name) ?>" placeholder="First name">
                        </div>
                        <div class="form-group">
                            <label>Last Name</label>
                            <input type="text" name="last_name" value="<?= e($last_name) ?>" placeholder="Last name">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Username</label>
                        <input type="text" value="<?= e($username) ?>" disabled class="input-disabled">
                        <small>Username cannot be changed.</small>
                    </div>
                    <div class="form-group">
                        <label>Email Address</label>
                        <input type="email" name="email" value="<?= e($email) ?>" required placeholder="you@example.com">
                    </div>
                    <button type="submit" class="btn-submit">Save Changes</button>
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
let cropScale = 1, cropStartX = 0, cropStartY = 0, cropX = 0, cropY = 0, isDragging = false;
let originalFile = null;

document.getElementById('profilePicInput').addEventListener('change', function() {
    const file = this.files[0];
    if (!file) return;
    if (file.size > 2 * 1024 * 1024) {
        alert('Image must be under 2MB.'); this.value = ''; return;
    }
    originalFile = file;
    const reader = new FileReader();
    reader.onload = e => {
        const img = document.getElementById('cropImg');
        img.src = e.target.result;
        cropX = 0; cropY = 0; cropScale = 1;
        document.getElementById('cropZoom').value = 1;
        updateCropTransform();
        document.getElementById('cropModal').style.display = 'flex';
    };
    reader.readAsDataURL(file);
});

function updateCropTransform() {
    const img = document.getElementById('cropImg');
    img.style.transform = `translate(${cropX}px, ${cropY}px) scale(${cropScale})`;
}

document.getElementById('cropZoom').addEventListener('input', function() {
    cropScale = parseFloat(this.value);
    updateCropTransform();
});

const cropImg = document.getElementById('cropImg');
cropImg.addEventListener('mousedown', startDrag);
cropImg.addEventListener('touchstart', e => { const t = e.touches[0]; startDrag({clientX:t.clientX,clientY:t.clientY}); }, {passive:true});
document.addEventListener('mousemove', doDrag);
document.addEventListener('touchmove', e => { if(!isDragging)return; const t=e.touches[0]; doDrag({clientX:t.clientX,clientY:t.clientY}); e.preventDefault(); }, {passive:false});
document.addEventListener('mouseup', ()=>{isDragging=false;});
document.addEventListener('touchend', ()=>{isDragging=false;});

function startDrag(e) { isDragging=true; cropStartX=e.clientX-cropX; cropStartY=e.clientY-cropY; }
function doDrag(e) { if(!isDragging)return; cropX=e.clientX-cropStartX; cropY=e.clientY-cropStartY; updateCropTransform(); }

function cancelCrop() {
    document.getElementById('cropModal').style.display='none';
    document.getElementById('profilePicInput').value='';
}

function applyCrop() {
    const frame = document.getElementById('cropFrame');
    const img   = document.getElementById('cropImg');
    const size  = frame.offsetWidth; // square crop frame

    const canvas = document.createElement('canvas');
    canvas.width  = 400;
    canvas.height = 400;
    const ctx = canvas.getContext('2d');

    ctx.beginPath();
    ctx.arc(200, 200, 200, 0, Math.PI*2);
    ctx.clip();

    const naturalW = img.naturalWidth;
    const naturalH = img.naturalHeight;
    const displayW = img.offsetWidth  * cropScale;
    const displayH = img.offsetHeight * cropScale;
    const scaleX   = naturalW / (img.offsetWidth);
    const scaleY   = naturalH / (img.offsetHeight);

    const sx = (-cropX / cropScale) * scaleX;
    const sy = (-cropY / cropScale) * scaleY;
    const sw = (size   / cropScale) * scaleX;
    const sh = (size   / cropScale) * scaleY;

    ctx.drawImage(img, sx, sy, sw, sh, 0, 0, 400, 400);

    canvas.toBlob(blob => {
        const croppedFile = new File([blob], 'profile.jpg', {type:'image/jpeg'});
        const dt = new DataTransfer();
        dt.items.add(croppedFile);
        const input = document.getElementById('profilePicInput');
        input.files = dt.files;

        // Preview
        const prev = document.getElementById('avatarPreview');
        if (prev.tagName === 'IMG') {
            prev.src = URL.createObjectURL(blob);
        } else {
            const newImg = document.createElement('img');
            newImg.src = URL.createObjectURL(blob);
            newImg.className = 'avatar-img';
            newImg.id = 'avatarPreview';
            prev.replaceWith(newImg);
        }

        document.getElementById('cropModal').style.display='none';
        document.getElementById('photoForm').submit();
    }, 'image/jpeg', 0.92);
}
</script>
</body>
</html>
