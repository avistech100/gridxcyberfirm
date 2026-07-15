<?php
// includes/admin_layout.php
// Replicates the exact Jazzmin AdminLTE layout

function admin_head(string $page_title = 'Dashboard'): void {
    $full_title = e($page_title) . ' | ' . SITE_TITLE;
    echo <<<HTML
<!doctype html>
<html lang="en-us" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <meta name="robots" content="none, noarchive">
    <script>
        (function() {
            var mode = localStorage.getItem('jazzmin-theme-mode') || 'light';
            if (mode === 'auto') {
                mode = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
            }
            document.documentElement.setAttribute('data-bs-theme', mode);
        })();
    </script>
    <title>{$full_title}</title>
    <link rel="stylesheet" href="/static/vendor/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="/static/vendor/adminlte/css/adminlte.min.css" id="adminlte-css">
    <link rel="stylesheet" id="jazzmin-theme" data-theme-base="/static/vendor/bootswatch">
    <link rel="stylesheet" href="/static/jazzmin/css/main.css">
    <link rel="shortcut icon" href="/static/vendor/adminlte/img/AdminLTELogo.png" type="image/png">
    <link rel="icon" href="/static/vendor/adminlte/img/AdminLTELogo.png" sizes="32x32" type="image/png">
    <link href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700" rel="stylesheet">
</head>
HTML;
}

function admin_body_open(string $current_url = ''): void {
    global $_SESSION;
    $username = e($_SESSION['username'] ?? 'Admin');
    $user_id  = (int)($_SESSION['user_id'] ?? 1);
    $nav = $_ENV['ACTIVE_NAV'] ?? [];
    $nav_dashboard    = $nav['dashboard']    ?? '';
    $nav_groups       = $nav['groups']       ?? '';
    $nav_refunds      = $nav['refunds']      ?? '';
    $nav_transactions = $nav['transactions'] ?? '';
    $nav_users        = $nav['users']        ?? '';
    $nav_settings     = $nav['settings']     ?? '';

    echo <<<HTML
<body class="layout-fixed sidebar-expand-lg bg-body-tertiary dashboard accent-primary theme-default app-loaded sidebar-collapse" data-admin-utc-offset="0">
<div class="skip-links">
    <a href="#main" class="skip-link">Skip to main content</a>
    <a href="#navigation" class="skip-link">Skip to navigation</a>
</div>
<div class="app-wrapper">

    <!-- TOP NAVBAR -->
    <nav class="app-header navbar navbar-expand bg-body navbar-white navbar-light" id="jazzy-navbar" tabindex="-1">
        <div class="container-fluid">
            <ul class="navbar-nav" role="navigation" aria-label="Navigation 1">
                <li class="nav-item">
                    <a class="nav-link" data-lte-toggle="sidebar" href="#" role="button">
                        <i class="fas fa-bars"></i>
                    </a>
                </li>
                <li class="nav-item d-none d-sm-inline-block">
                    <a href="/" class="nav-link">View Site</a>
                </li>
            </ul>

            <ul class="navbar-nav ms-auto" role="navigation" aria-label="Navigation 2">
                <li class="nav-item dropdown">
                    <a class="nav-link btn" data-bs-toggle="dropdown" href="#" title="{$username}">
                        <i class="far fa-user" aria-hidden="true"></i>
                    </a>
                    <div class="dropdown-menu dropdown-menu-lg dropdown-menu-end" id="jazzy-usermenu">
                        <span class="dropdown-header">Account</span>
                        <div class="dropdown-divider"></div>
                        <a href="/admin/change_password.php" class="dropdown-item">
                            <i class="fas fa-key me-2"></i> Change password
                        </a>
                        <div class="dropdown-divider"></div>
                        <form id="logout-form" method="post" action="/admin/logout.php">
HTML;
    echo csrf_field();
    echo <<<HTML
                            <button type="submit" class="dropdown-item">
                                <i class="fas fa-users me-2"></i> Log out
                            </button>
                        </form>
                        <div class="dropdown-divider"></div>
                        <a href="/admin/users.php?action=edit&id={$user_id}" class="dropdown-item dropdown-footer">See Profile</a>
                    </div>
                </li>
            </ul>
        </div>
    </nav>

    <!-- SIDEBAR -->
    <aside class="app-sidebar bg-body-secondary shadow sidebar-dark-primary" id="jazzy-sidebar" data-bs-theme="dark">
        <div class="sidebar-brand">
            <a href="/admin/" class="brand-link active" id="jazzy-logo">
                <img src="/static/vendor/adminlte/img/AdminLTELogo.png" alt="GridX Logo"
                     class="img-circle brand-image opacity-75 shadow">
                <span class="brand-text fw-light">GridX Cyber Solutions</span>
            </a>
        </div>

        <div class="sidebar-wrapper">
            <div class="user-panel d-flex">
                <div class="image">
                    <i class="fas fa-inverse user-profile fa-user-circle"></i>
                </div>
                <div class="info">
                    <a href="/admin/users.php?action=edit&id={$user_id}" class="d-block">{$username}</a>
                </div>
            </div>

            <nav class="mt-2">
                <ul class="nav sidebar-menu flex-column" data-lte-toggle="treeview"
                    role="navigation" aria-label="Main navigation"
                    data-accordion="false" id="jazzy-navigation">

                    <li class="nav-item">
                        <a href="/admin/" class="nav-link {$nav_dashboard}">
                            <i class="nav-icon fas fa-th-large"></i>
                            <p>Dashboard</p>
                        </a>
                    </li>

                    <li class="nav-header">Authentication and Authorization</li>
                    <li class="nav-item">
                        <a href="/admin/groups.php" class="nav-link {$nav_groups}">
                            <i class="nav-icon fas fa-circle"></i> <p>Groups</p>
                        </a>
                    </li>

                    <li class="nav-header">Refund</li>
                    <li class="nav-item">
                        <a href="/admin/refunds.php" class="nav-link {$nav_refunds}">
                            <i class="nav-icon fas fa-circle"></i> <p>Refunds</p>
                        </a>
                    </li>

                    <li class="nav-header">User</li>
                    <li class="nav-item">
                        <a href="/admin/transactions.php" class="nav-link {$nav_transactions}">
                            <i class="nav-icon fas fa-circle"></i> <p>Trasactions</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="/admin/users.php" class="nav-link {$nav_users}">
                            <i class="nav-icon fas fa-circle"></i> <p>Users</p>
                        </a>
                    </li>

                    <li class="nav-header">Settings</li>
                    <li class="nav-item">
                        <a href="/admin/settings.php" class="nav-link {$nav_settings}">
                            <i class="nav-icon fas fa-cogs"></i> <p>Payment Settings</p>
                        </a>
                    </li>

                </ul>
            </nav>
        </div>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="app-main" id="main" tabindex="-1">
HTML;
}

function admin_content_header(string $title, array $breadcrumbs = []): void {
    echo '<div class="app-content-header"><div class="container-fluid"><div class="row">';
    echo '<div class="col-sm-6"><h3 class="mb-0">' . e($title) . '</h3></div>';
    echo '<div class="col-sm-6"><div class="float-sm-end"><ol class="breadcrumb">';
    echo '<li class="breadcrumb-item"><a href="/admin/" class="active">Home</a></li>';
    foreach ($breadcrumbs as $label => $url) {
        if ($url) {
            echo '<li class="breadcrumb-item"><a href="' . e($url) . '">' . e($label) . '</a></li>';
        } else {
            echo '<li class="breadcrumb-item">' . e($label) . '</li>';
        }
    }
    echo '</ol></div></div></div></div></div>';
    echo '<div class="app-content"><div class="container-fluid">';
}

function admin_footer(): void {
    echo <<<HTML
        </div></div>
    </main>
</div>

<script src="/static/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="/static/vendor/adminlte/js/adminlte.min.js"></script>
<script src="/static/jazzmin/js/main.js"></script>
<script src="/static/jazzmin/js/ui-builder.js"></script>
<script>
// Theme mode toggle
document.addEventListener('DOMContentLoaded', function() {
    var themeToggle = document.getElementById('jazzmin-theme-toggle');
    if (themeToggle) {
        themeToggle.addEventListener('click', function() {
            var current = document.documentElement.getAttribute('data-bs-theme');
            var next = current === 'dark' ? 'light' : 'dark';
            document.documentElement.setAttribute('data-bs-theme', next);
            localStorage.setItem('jazzmin-theme-mode', next);
        });
    }
});
</script>
</body>
</html>
HTML;
}

// Set which nav item is active
function set_active_nav(string $section): void {
    $_ENV['ACTIVE_NAV'][$section] = 'active';
}
