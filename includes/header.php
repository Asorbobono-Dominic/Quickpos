<?php
// includes/header.php — Shared navigation header
if (session_status() === PHP_SESSION_NONE) session_start();

define('SHOP_NAME', 'BigVybes Supermarket');
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="light" id="htmlRoot">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) . ' — ' : '' ?><?= SHOP_NAME ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/pos-system/assets/css/style.css">
    <script>
        // Apply saved theme BEFORE page renders to prevent flash
        (function() {
            const theme = localStorage.getItem('posTheme') || 'light';
            document.documentElement.setAttribute('data-bs-theme', theme);
        })();
    </script>
</head>
<body>
<nav class="navbar navbar-expand-lg px-3 main-navbar">
    <a class="navbar-brand fw-bold d-flex align-items-center gap-2" href="/pos-system/">
        <i class="fas fa-cash-register"></i>
        <div>
            <div class="shop-name"><?= SHOP_NAME ?></div>
            <div class="shop-sub">Point of Sale</div>
        </div>
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
        <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navMenu">
        <ul class="navbar-nav me-auto">
            <?php if (isset($_SESSION['role'])): ?>
                <li class="nav-item">
                    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'pos.php' ? 'active' : '' ?>" href="/pos-system/pos/pos.php">
                        <i class="fas fa-shopping-cart me-1"></i>POS
                    </a>
                </li>
                <?php if ($_SESSION['role'] === 'admin'): ?>
                <li class="nav-item">
                    <a class="nav-link <?= strpos($_SERVER['PHP_SELF'], 'products') !== false ? 'active' : '' ?>" href="/pos-system/products/index.php">
                        <i class="fas fa-box me-1"></i>Products
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= strpos($_SERVER['PHP_SELF'], 'reports') !== false ? 'active' : '' ?>" href="/pos-system/reports/index.php">
                        <i class="fas fa-chart-bar me-1"></i>Reports
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= strpos($_SERVER['PHP_SELF'], 'users') !== false ? 'active' : '' ?>" href="/pos-system/users/index.php">
                        <i class="fas fa-users me-1"></i>Users
                    </a>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle <?= strpos($_SERVER['PHP_SELF'], 'business') !== false ? 'active' : '' ?>"
                       href="#" data-bs-toggle="dropdown">
                        <i class="fas fa-store me-1"></i>Business
                    </a>
                    <ul class="dropdown-menu">
                        <li>
                            <a class="dropdown-item" href="/pos-system/business/daily_balance.php">
                                <i class="fas fa-cash-register me-2"></i>Daily Balance
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="/pos-system/business/profit.php">
                                <i class="fas fa-chart-line me-2"></i>Profit Tracking
                            </a>
                        </li>
                    </ul>
                </li>
                <?php endif; ?>
            <?php endif; ?>
        </ul>
        <?php if (isset($_SESSION['username'])): ?>
        <div class="d-flex align-items-center gap-3">
            <!-- Dark Mode Toggle -->
            <div class="dark-mode-toggle" onclick="toggleDarkMode()" title="Toggle Dark Mode" id="darkModeBtn">
                <i class="fas fa-moon" id="darkModeIcon"></i>
            </div>
            <div class="dropdown">
                <a class="navbar-text dropdown-toggle text-decoration-none" href="#" data-bs-toggle="dropdown">
                    <i class="fas fa-user-circle me-1"></i><?= htmlspecialchars($_SESSION['username']) ?>
                    <span class="badge <?= $_SESSION['role'] === 'admin' ? 'bg-warning text-dark' : 'bg-info text-dark' ?> ms-1"><?= $_SESSION['role'] ?></span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li>
                        <a class="dropdown-item" href="/pos-system/profile/index.php">
                            <i class="fas fa-user-cog me-2"></i>My Profile
                        </a>
                    </li>
                    <?php if ($_SESSION['role'] === 'admin'): ?>
                    <li>
                        <a class="dropdown-item" href="/pos-system/security/login_log.php">
                            <i class="fas fa-shield-alt me-2"></i>Login Activity
                        </a>
                    </li>
                    <?php endif; ?>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <a class="dropdown-item text-danger" href="/pos-system/auth/logout.php">
                            <i class="fas fa-sign-out-alt me-2"></i>Logout
                        </a>
                    </li>
                </ul>
            </div>
        </div>
        <?php endif; ?>
    </div>
</nav>
<div class="container-fluid py-4">

<script>
function toggleDarkMode() {
    const html = document.getElementById('htmlRoot');
    const icon = document.getElementById('darkModeIcon');
    const current = html.getAttribute('data-bs-theme');
    const next = current === 'light' ? 'dark' : 'light';
    html.setAttribute('data-bs-theme', next);
    localStorage.setItem('posTheme', next);
    icon.className = next === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
}

// Set correct icon on load
document.addEventListener('DOMContentLoaded', function() {
    const theme = localStorage.getItem('posTheme') || 'light';
    const icon = document.getElementById('darkModeIcon');
    if (icon) icon.className = theme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
});
</script>
