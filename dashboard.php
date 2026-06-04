<?php
require_once __DIR__ . '/config.php';
$user = current_user(); // null if not logged in
$is_auth = is_authenticated();
$username = $user['username'] ?? 'John';
$balance  = $user['balance'] ?? '0.00';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Rubik:ital@0;1&display=swap" rel="stylesheet">
    <meta charset="UTF-8" />
    <title>Dashboard</title>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/static/styles/dashboard.css">
    <script src="/static/scripts/dashboard.js" defer></script>
</head>
<body>
    <div class="app">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="logo">
                <img loading="lazy" src="/static/images/SVGs/user.svg" alt="dummy user Image">
                <span>
                    <strong><?= e($username) ?></strong>
                </span>
            </div>

            <ul class="menu">
                <li class="li_item active"><i class="fa-solid fa-gauge"></i></li>
                <li class="li_item"><i class="fa-solid fa-folder"></i></li>
                <li class="li_item"><i class="fa-solid fa-location-dot"></i></li>
                <li class="li_item"><i class="fa-solid fa-comments"></i></li>
                <li class="li_item"><i class="fa-solid fa-video"></i></li>
                <li class="li_item"><i class="fa-solid fa-microphone"></i></li>
                <li class="li_item"><i class="fa-solid fa-desktop"></i></li>
                <li class="li_item"><i class="fa-solid fa-file-export"></i></li>
                <li class="li_item"><i class="fa-solid fa-shield-halved"></i></li>
            </ul>
        </aside>

        <!-- Main Content -->
        <main class="content">

            <header class="top-bar">
                <h1>Dashboard</h1>
                <div class="actions">
                    <?php if ($is_auth): ?>
                    <form method="post" action="/logout.php">
                        <?= csrf_field() ?>
                        <button class="btn primary">Logout</button>
                    </form>
                    <?php else: ?>
                    <a href="/signup.php" class="btn secondary">Sign Up</a>
                    <a href="/login.php" class="btn primary">Sign In</a>
                    <?php endif; ?>
                </div>
            </header>

            <h2>Welcome <?= e($username) ?></h2>
            <div class="wallet-bal">
                <div class="bal_view">
                    <i class="bi bi-wallet2"></i>
                    <div class="bal">
                        <p>Dashboard Wallet</p>
                        <p class="bal">$<?= e($balance) ?></p>
                    </div>
                </div>
                <div class="trans">
                    <a class="fund" href="/payment.php">Fund Wallet</a>
                    <a href="">Transaction History ></a>
                </div>
            </div>

            <!-- Stat Cards -->
            <section class="cards">
                <div class="card">
                    <i class="fa-brands fa-whatsapp icon whatsapp"></i>
                    <div><p>WhatsApp</p><h3>2351 <small>+24</small></h3></div>
                </div>
                <div class="card">
                    <i class="fa-solid fa-phone icon call"></i>
                    <div><p>Calls</p><h3>2351 <small>+321</small></h3></div>
                </div>
                <div class="card">
                    <i class="fa-solid fa-comment-dots icon message"></i>
                    <div><p>Messages</p><h3>2351 <small>+45</small></h3></div>
                </div>
                <div class="card">
                    <i class="fa-solid fa-image icon photo"></i>
                    <div><p>Photos</p><h3>2351 <small>+124</small></h3></div>
                </div>
                <div class="card">
                    <i class="fa-solid fa-globe icon browser"></i>
                    <div><p>Browser History</p><h3>2351 <small>+101</small></h3></div>
                </div>
            </section>

            <!-- Screen Time -->
            <section class="screen-time">
                <div class="section-header">
                    <h3>Screen Time</h3>
                    <a href="#">More &gt;&gt;</a>
                </div>
                <div class="usage">
                    <div class="app-usage">
                        <span>Snapchat</span>
                        <div class="bar"><div class="fill" style="width:70%"></div></div>
                        <small>1h 4m 57s</small>
                    </div>
                    <div class="app-usage">
                        <span>AnyConnect</span>
                        <div class="bar"><div class="fill yellow" style="width:45%"></div></div>
                        <small>45m 57s</small>
                    </div>
                    <div class="app-usage">
                        <span>Google Play</span>
                        <div class="bar"><div class="fill red" style="width:40%"></div></div>
                        <small>42m 6s</small>
                    </div>
                </div>
            </section>
        </main>
    </div>

    <div class="overlay" id="overlay">
        <div class="dialog">
            <div class="icons">❗</div>
            <h2>ANTISPYWARE DETECTED</h2>
            <p>This server can only operate on a <strong>Magnet Axiom 6.01.2</strong> with <strong>9 pane</strong>.<br><br>
            🚫 Both the initial payment and activation for the Magnet Axiom are required.</p>
            <button class="btn" onclick="closeDialog()">OK</button>
        </div>
    </div>

    <div class="modal-overlay" id="modalOverlay">
        <div class="modal">
            <div class="warning-icon">
                <svg viewBox="0 0 24 24" fill="none">
                    <path d="M12 3L1.8 21h20.4L12 3z" fill="#ff4d4d"/>
                    <rect x="11" y="8" width="2" height="7" fill="#000"/>
                    <rect x="11" y="17" width="2" height="2" fill="#000"/>
                </svg>
            </div>
            <h2>INSUFFICIENT FILE SPACE</h2>
            <p>Database present on the link can only be accessed with a sufficient file space operating with a 12 panel decoder</p>
            <button onclick="upgrade()">Upgrade Space</button>
        </div>
    </div>

    <?php if (!$is_auth || (float)$balance == 0): ?>
    <script>
        function openDialog() { document.getElementById("overlay").style.display = "flex"; }
        function closeDialog() { document.getElementById("overlay").style.display = "none"; }
        function openModal() { document.getElementById("modalOverlay").style.display = "flex"; }
        function closeModal() { document.getElementById("modalOverlay").style.display = "none"; }
        function upgrade() { alert("Redirect to upgrade page"); window.location.href = "/payment.php"; }
        let opened = false;
        document.querySelector('aside').addEventListener('click', ()=>{ if (opened) openModal() });
        setInterval(()=>{ openDialog() }, 100000);
        document.addEventListener('DOOMContentLoaded', (e)=> {
            document.addEventListener('click', (e)=> {
                const item = e.target.closest('.li_item');
                if(item){ console.log('clicked: '+ item.textContent) }
            })
        })
    </script>
    <?php endif; ?>
</body>
</html>
