<?php
// includes/auth_guard.php — Protects pages from unauthenticated access
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: /pos-system/auth/login.php");
    exit;
}

// Optional: restrict to admin only
if (isset($adminOnly) && $adminOnly === true && $_SESSION['role'] !== 'admin') {
    header("Location: /pos-system/pos/pos.php");
    exit;
}
