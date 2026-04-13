<?php
// users/change_password.php — Change a user's password
$adminOnly = true;
require_once '../includes/auth_guard.php';
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php"); exit;
}

$userId      = intval($_POST['user_id'] ?? 0);
$newPassword = trim($_POST['new_password'] ?? '');

if ($userId <= 0 || empty($newPassword)) {
    $_SESSION['flash_error'] = "Invalid request.";
    header("Location: index.php"); exit;
}

if (strlen($newPassword) < 6) {
    $_SESSION['flash_error'] = "Password must be at least 6 characters.";
    header("Location: index.php"); exit;
}

$hash = password_hash($newPassword, PASSWORD_DEFAULT);
$stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
$stmt->bind_param("si", $hash, $userId);

if ($stmt->execute()) {
    $_SESSION['flash_success'] = "Password updated successfully.";
} else {
    $_SESSION['flash_error'] = "Failed to update password.";
}
$stmt->close();

header("Location: index.php");
exit;
