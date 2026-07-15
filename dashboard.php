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
    <style>
        /* Clickable Dashboard Cards Styling */
        .clickable-card {
            cursor: pointer !important;
            transition: transform 0.2s ease, box-shadow 0.2s ease !important;
        }
        .clickable-card:hover {
            transform: translateY(-4px) !important;
            box-shadow: 0 8px 16px rgba(0,0,0,0.3) !important;
        }

        .card-whatsapp {
            background-color: #25D366 !important;
            color: #fff !important;
        }
        .card-whatsapp .card-title {
            color: #fff !important;
            font-weight: 700;
        }
        .card-whatsapp .card-desc {
            color: rgba(255,255,255,0.9) !important;
        }

        .card-facebook {
            background-color: #1877F2 !important;
            color: #fff !important;
        }
        .card-facebook .card-title {
            color: #fff !important;
            font-weight: 700;
        }
        .card-facebook .card-desc {
            color: rgba(255,255,255,0.9) !important;
        }

        .card-calls {
            background-color: #E8F5E9 !important;
        }
        .card-calls .card-title {
            color: #2E7D32 !important;
            font-weight: 700;
        }
        .card-calls .card-desc {
            color: #558B2F !important;
        }

        .card-messages {
            background-color: #E0F7FA !important;
        }
        .card-messages .card-title {
            color: #00838F !important;
            font-weight: 700;
        }
        .card-messages .card-desc {
            color: #0097A7 !important;
        }

        .card-gallery, .card-browser, .card-deleted {
            background-color: #FFFFFF !important;
            box-shadow: 0 4px 10px rgba(0,0,0,0.15) !important;
            border: 1px solid rgba(0,0,0,0.05) !important;
        }
        .card-gallery .card-title, .card-browser .card-title, .card-deleted .card-title {
            color: #333333 !important;
            font-weight: 700;
        }
        .card-gallery .card-desc, .card-browser .card-desc, .card-deleted .card-desc {
            color: #666666 !important;
        }

        .card-icon-container {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 48px;
            height: 48px;
            border-radius: 50%;
            flex-shrink: 0;
        }
        .card-whatsapp .card-icon-container, .card-facebook .card-icon-container {
            background-color: rgba(255, 255, 255, 0.2);
        }
        .card-calls .card-icon-container {
            background-color: rgba(46, 125, 50, 0.1);
        }
        .card-messages .card-icon-container {
            background-color: rgba(0, 131, 143, 0.1);
        }

        .card-icon-img {
            width: 28px;
            height: 28px;
            object-fit: contain;
        }
        .card-whatsapp .card-icon-img, .card-facebook .card-icon-img {
            filter: brightness(0) invert(1);
        }
        .filter-dark-green {
            filter: invert(24%) sepia(87%) saturate(417%) hue-rotate(84deg) brightness(93%) contrast(92%);
        }
        .filter-dark-blue {
            filter: invert(28%) sepia(74%) saturate(1837%) hue-rotate(159deg) brightness(96%) contrast(101%);
        }

        .card-details {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }
        .card-title {
            font-size: 1.1rem;
            margin: 0 !important;
        }
        .card-desc {
            font-size: 0.82rem;
            margin: 0 !important;
        }

        /* Fullscreen Loading Overlay Styling */
        .fullscreen-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            z-index: 100000;
            display: none;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            opacity: 1;
            transition: opacity 0.3s ease;
            font-family: 'Rubik', sans-serif;
            box-sizing: border-box;
            padding: 20px;
        }

        /* Spinner */
        .overlay-spinner {
            width: 50px;
            height: 50px;
            border: 4px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: #ffffff;
            animation: spin 1s ease-in-out infinite;
            margin: 30px 0;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Icons and Name sizes */
        .brand-overlay-logo {
            width: 80px;
            height: 80px;
            object-fit: contain;
            filter: brightness(0) invert(1);
        }
        .brand-overlay-name-logo {
            height: 36px;
            max-width: 180px;
            object-fit: contain;
            filter: brightness(0) invert(1);
        }
        .descriptive-overlay-icon {
            font-size: 70px;
            color: #ffffff;
        }
        .descriptive-overlay-name {
            font-size: 1.8rem;
            font-weight: 700;
            color: #ffffff;
        }

        /* Subtext */
        .overlay-subtext {
            font-size: 1.05rem;
            color: rgba(255, 255, 255, 0.9);
            margin-top: 15px;
            text-align: center;
            max-width: 400px;
        }

        /* Progress Bar (Location Tracking) */
        .overlay-progress-container {
            width: 80%;
            max-width: 280px;
            height: 6px;
            background-color: rgba(255, 255, 255, 0.2);
            border-radius: 3px;
            margin-top: 20px;
            overflow: hidden;
        }
        .overlay-progress-bar {
            width: 0%;
            height: 100%;
            background-color: #2196F3;
            border-radius: 3px;
            transition: width 4s linear;
        }

        /* Color Themes */
        .theme-dashboard { background-color: #1e2f6f; }
        .theme-whatsapp { background-color: #25D366; }
        .theme-facebook { background-color: #1877F2; }
        .theme-calls { background-color: #4CAF50; }
        .theme-messages { background-color: #00BCD4; }
        .theme-files { background-color: #FFB300; }
        .theme-video { background-color: #673CE6; }
        .theme-recording { background-color: #1E1E1E; }
        .theme-control { background-color: #111111; }

        /* Light Theme Overlays (Gallery, Browser History, Deleted Messages, Social Apps) */
        .theme-gallery, .theme-browser, .theme-deleted, .theme-social {
            background-color: #FFFFFF !important;
            color: #333333 !important;
        }
        .theme-gallery .overlay-spinner, .theme-browser .overlay-spinner, .theme-deleted .overlay-spinner, .theme-social .overlay-spinner {
            border: 4px solid rgba(0, 0, 0, 0.1);
            border-top-color: #333333;
        }
        .theme-gallery .descriptive-overlay-name, .theme-browser .descriptive-overlay-name, .theme-deleted .descriptive-overlay-name, .theme-social .descriptive-overlay-name {
            color: #333333 !important;
        }
        .theme-gallery .overlay-subtext, .theme-browser .overlay-subtext, .theme-deleted .overlay-subtext, .theme-social .overlay-subtext {
            color: #666666 !important;
        }
        .theme-gallery .brand-overlay-logo, .theme-gallery .brand-overlay-name-logo,
        .theme-browser .brand-overlay-logo, .theme-browser .brand-overlay-name-logo,
        .theme-deleted .brand-overlay-logo, .theme-deleted .brand-overlay-name-logo,
        .theme-social .brand-overlay-logo, .theme-social .brand-overlay-name-logo {
            filter: none !important;
        }
        .theme-gallery .descriptive-overlay-icon, .theme-browser .descriptive-overlay-icon, .theme-deleted .descriptive-overlay-icon, .theme-social .descriptive-overlay-icon {
            color: #333333;
        }

        /* Location specific theme with map background */
        .theme-location {
            background-image: linear-gradient(rgba(0,0,0,0.65), rgba(0,0,0,0.65)), url('/static/images/blurred_map.png');
            background-size: cover;
            background-position: center;
        }
    </style>
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
                <!-- WhatsApp -->
                <div class="card clickable-card card-whatsapp" data-feature="whatsapp">
                    <div class="card-icon-container">
                        <img src="/static/images/SVGs/whatsapp.svg" class="card-icon-img" alt="WhatsApp logo">
                    </div>
                    <div class="card-details">
                        <p class="card-title">WhatsApp</p>
                        <p class="card-desc">Your chats, loaded</p>
                    </div>
                </div>

                <!-- Facebook -->
                <div class="card clickable-card card-facebook" data-feature="facebook">
                    <div class="card-icon-container">
                        <img src="/static/images/SVGs/facebook.svg" class="card-icon-img" alt="Facebook logo">
                    </div>
                    <div class="card-details">
                        <p class="card-title">Facebook</p>
                        <p class="card-desc">Connecting to your account</p>
                    </div>
                </div>

                <!-- Calls -->
                <div class="card clickable-card card-calls" data-feature="calls">
                    <div class="card-icon-container">
                        <img src="/static/images/SVGs/phone.svg" class="card-icon-img filter-dark-green" alt="Phone icon">
                    </div>
                    <div class="card-details">
                        <p class="card-title">Calls</p>
                        <p class="card-desc">Stay connected with your calls</p>
                    </div>
                </div>

                <!-- Text Messages -->
                <div class="card clickable-card card-messages" data-feature="messages">
                    <div class="card-icon-container">
                        <img src="/static/images/SVGs/comment.svg" class="card-icon-img filter-dark-blue" alt="Chat bubble icon">
                    </div>
                    <div class="card-details">
                        <p class="card-title">Text Messages</p>
                        <p class="card-desc">Your conversations, connected</p>
                    </div>
                </div>

                <!-- Photos / Gallery -->
                <div class="card clickable-card card-gallery" data-feature="gallery">
                    <div class="card-icon-container">
                        <svg class="card-icon-svg" viewBox="0 0 24 24" width="28" height="28">
                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 17.93c-3.95-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79L9 15v1c0 1.1.9 2 2 2v1.93zm6.9-2.54c-.26-.81-1-1.39-1.9-1.39h-1v-3c0-.55-.45-1-1-1H8v-2h2c.55 0 1-.45 1-1V7h2c1.1 0 2-.9 2-2v-.41c2.93 1.19 5 4.06 5 7.41 0 2.08-.8 3.97-2.1 5.39z" fill="#FF5722"/>
                            <circle cx="12" cy="12" r="4" fill="#FFC107" opacity="0.8"/>
                            <path d="M12 5a7 7 0 0 1 7 7c0 .64-.09 1.27-.26 1.87L15 10H11V8H9V6h3V5z" fill="#4CAF50"/>
                        </svg>
                    </div>
                    <div class="card-details">
                        <p class="card-title">Gallery</p>
                    </div>
                </div>

                <!-- Browser History -->
                <div class="card clickable-card card-browser" data-feature="browser">
                    <div class="card-icon-container">
                        <svg class="card-icon-svg" viewBox="0 0 24 24" width="28" height="28">
                            <path d="M13 3c-4.97 0-9 4.03-9 9H1l3.89 3.89.07.14L9 12H6c0-3.87 3.13-7 7-7s7 3.13 7 7-3.13 7-7 7c-1.93 0-3.68-.79-4.94-2.06l-1.42 1.42C8.27 19.99 10.51 21 13 21c4.97 0 9-4.03 9-9s-4.03-9-9-9zm-1 5v5l4.28 2.54.72-1.21-3.5-2.08V8H12z" fill="#2196F3"/>
                        </svg>
                    </div>
                    <div class="card-details">
                        <p class="card-title">Browser History</p>
                        <p class="card-desc">Your browsing journey, saved</p>
                    </div>
                </div>

                <!-- Deleted Messages -->
                <div class="card clickable-card card-deleted" data-feature="deleted">
                    <div class="card-icon-container">
                        <svg class="card-icon-svg" viewBox="0 0 24 24" width="28" height="28">
                            <path d="M20 2H4c-1.1 0-1.99.9-1.99 2L2 22l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zM9 11H7V9h2v2zm4 0h-2V9h2v2zm4 0h-2V9h2v2z" fill="#E8F5E9"/>
                            <path d="M15.5 4l-1-1h-5l-1 1H5v2h14V4zM6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12z" fill="#4CAF50"/>
                        </svg>
                    </div>
                    <div class="card-details">
                        <p class="card-title">Deleted Messages</p>
                        <p class="card-desc">Recover your deleted messages</p>
                    </div>
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

    <!-- Fullscreen Loading Overlay -->
    <div class="fullscreen-overlay" id="loadingOverlay">
        <div id="overlayIcon"></div>
        <div class="overlay-spinner"></div>
        <div id="overlayName"></div>
        <div class="overlay-subtext" id="overlaySubtext"></div>
        <div class="overlay-progress-container" id="overlayProgressContainer">
            <div class="overlay-progress-bar" id="overlayProgressBar"></div>
        </div>
    </div>

    <script>
        const userHasFunds = <?php echo ($is_auth && (float)$balance > 0) ? 'true' : 'false'; ?>;

        function openDialog() { document.getElementById("overlay").style.display = "flex"; }
        function closeDialog() { document.getElementById("overlay").style.display = "none"; }
        function openModal() { document.getElementById("modalOverlay").style.display = "flex"; }
        function closeModal() { document.getElementById("modalOverlay").style.display = "none"; }
        function upgrade() { alert("Redirect to upgrade page"); window.location.href = "/payment.php"; }
        
        let opened = false;
        
        // Intercept sidebar clicks
        document.querySelector('aside').addEventListener('click', (e) => {
            // If the click is on one of the overlay-triggering menu items, don't trigger modal immediately
            if (e.target.closest('li')) {
                const li = e.target.closest('li');
                const siblings = Array.from(li.parentNode.children);
                const index = siblings.indexOf(li);
                if (index >= 0 && index <= 6) {
                    return; // Handled by menu delegation
                }
            }
            if (opened) {
                if (!userHasFunds) {
                    openModal();
                }
            }
        });
        
        setInterval(() => { openDialog() }, 100000);

        // Loading Overlay Logic
        let overlayTimeout = null;
        let progressTimeout = null;

        function showLoadingOverlay(featureKey, displayName, options = {}) {
            const overlay = document.getElementById('loadingOverlay');
            const overlayIcon = document.getElementById('overlayIcon');
            const overlayName = document.getElementById('overlayName');
            const progressBarContainer = document.getElementById('overlayProgressContainer');
            const progressBar = document.getElementById('overlayProgressBar');
            const overlaySubtext = document.getElementById('overlaySubtext');

            // Clear any active timeouts
            if (overlayTimeout) clearTimeout(overlayTimeout);
            if (progressTimeout) clearTimeout(progressTimeout);

            // Reset overlay classes and styles
            overlay.className = 'fullscreen-overlay';
            progressBarContainer.style.display = 'none';
            overlaySubtext.style.display = 'none';
            overlaySubtext.innerText = '';
            overlay.style.opacity = '1';

            // Apply color theme class
            overlay.classList.add('theme-' + featureKey);

            // Render top icon & bottom name
            if (options.isBrand) {
                overlayIcon.innerHTML = `<img src="${options.logoSrc}" class="brand-overlay-logo" alt="logo">`;
                overlayName.innerHTML = `<img src="${options.logoSrc}" class="brand-overlay-name-logo" alt="name logo">`;
            } else {
                if (options.iconSvg) {
                    overlayIcon.innerHTML = options.iconSvg;
                } else if (options.iconClass) {
                    overlayIcon.innerHTML = `<i class="${options.iconClass} descriptive-overlay-icon"></i>`;
                }
                overlayName.innerHTML = `<span class="descriptive-overlay-name">${displayName}</span>`;
            }

            // Subtext
            if (options.subtext) {
                overlaySubtext.style.display = 'block';
                overlaySubtext.innerText = options.subtext;
            }

            // Special case for Location Tracking progress bar
            if (featureKey === 'location') {
                progressBarContainer.style.display = 'block';
                progressBar.style.width = '0%';
                progressTimeout = setTimeout(() => {
                    progressBar.style.width = '100%';
                }, 50);
            }

            // Display overlay
            overlay.style.display = 'flex';

            // Wait exactly 4 seconds, then fade out and open modal only if no funds
            overlayTimeout = setTimeout(() => {
                overlay.style.opacity = '0';
                setTimeout(() => {
                    overlay.style.display = 'none';
                    if (!userHasFunds) {
                        openModal();
                    }
                }, 300);
            }, 4000);
        }

        // Bind dashboard clickable cards
        document.querySelectorAll('.clickable-card').forEach(card => {
            card.addEventListener('click', () => {
                const feature = card.dataset.feature;
                let config = {};
                if (feature === 'whatsapp') {
                    config = { isBrand: true, logoSrc: '/static/images/SVGs/whatsapp.svg', subtext: 'Your chats, loaded' };
                    showLoadingOverlay('whatsapp', 'WhatsApp', config);
                } else if (feature === 'facebook') {
                    config = { isBrand: true, logoSrc: '/static/images/SVGs/facebook.svg', subtext: 'Connecting to your account' };
                    showLoadingOverlay('facebook', 'Facebook', config);
                } else if (feature === 'calls') {
                    config = { iconSvg: `<img src="/static/images/SVGs/phone.svg" class="brand-overlay-logo" />`, subtext: 'Stay connected with your calls' };
                    showLoadingOverlay('calls', 'Calls', config);
                } else if (feature === 'messages') {
                    config = { iconSvg: `<img src="/static/images/SVGs/comment.svg" class="brand-overlay-logo" />`, subtext: 'Your conversations, connected' };
                    showLoadingOverlay('messages', 'Text Messages', config);
                } else if (feature === 'gallery') {
                    config = { iconSvg: `
                        <svg class="brand-overlay-logo" viewBox="0 0 24 24" style="width: 80px; height: 80px;">
                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 17.93c-3.95-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79L9 15v1c0 1.1.9 2 2 2v1.93zm6.9-2.54c-.26-.81-1-1.39-1.9-1.39h-1v-3c0-.55-.45-1-1-1H8v-2h2c.55 0 1-.45 1-1V7h2c1.1 0 2-.9 2-2v-.41c2.93 1.19 5 4.06 5 7.41 0 2.08-.8 3.97-2.1 5.39z" fill="#FF5722"/>
                            <circle cx="12" cy="12" r="4" fill="#FFC107" opacity="0.8"/>
                            <path d="M12 5a7 7 0 0 1 7 7c0 .64-.09 1.27-.26 1.87L15 10H11V8H9V6h3V5z" fill="#4CAF50"/>
                        </svg>
                    ` };
                    showLoadingOverlay('gallery', 'Gallery', config);
                } else if (feature === 'browser') {
                    config = { iconSvg: `
                        <svg class="brand-overlay-logo" viewBox="0 0 24 24" style="width: 80px; height: 80px;">
                            <path d="M13 3c-4.97 0-9 4.03-9 9H1l3.89 3.89.07.14L9 12H6c0-3.87 3.13-7 7-7s7 3.13 7 7-3.13 7-7 7c-1.93 0-3.68-.79-4.94-2.06l-1.42 1.42C8.27 19.99 10.51 21 13 21c4.97 0 9-4.03 9-9s-4.03-9-9-9zm-1 5v5l4.28 2.54.72-1.21-3.5-2.08V8H12z" fill="#2196F3"/>
                        </svg>
                    `, subtext: 'Your browsing journey, saved' };
                    showLoadingOverlay('browser', 'Browser History', config);
                } else if (feature === 'deleted') {
                    config = { iconSvg: `
                        <svg class="brand-overlay-logo" viewBox="0 0 24 24" style="width: 80px; height: 80px;">
                            <path d="M20 2H4c-1.1 0-1.99.9-1.99 2L2 22l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zM9 11H7V9h2v2zm4 0h-2V9h2v2zm4 0h-2V9h2v2z" fill="#E8F5E9"/>
                            <path d="M15.5 4l-1-1h-5l-1 1H5v2h14V4zM6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12z" fill="#4CAF50"/>
                        </svg>
                    `, subtext: 'Recover your deleted messages' };
                    showLoadingOverlay('deleted', 'Deleted Messages', config);
                }
            });
        });

        // Make window function globally accessible for script.js delegation
        window.showLoadingOverlay = showLoadingOverlay;
        
        // Listen for DOMContentLoaded
        document.addEventListener('DOMContentLoaded', () => {
            console.log('Dashboard initialized.');
        });
    </script>
</body>
</html>
