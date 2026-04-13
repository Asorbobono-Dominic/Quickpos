<?php
// users/delete_user.php — Delete a user
$adminOnly = true;
require_once '../includes/auth_guard.php';
require_once '../config/db.php';

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) { header("Location: index.php"); exit; }

// Cannot delete yourself
if ($id === $_SESSION['user_id']) {
    $_SESSION['flash_error'] = "You cannot delete your own account.";
    header("Location: index.php"); exit;
}

// Cannot delete admin accounts
$check = $conn->prepare("SELECT role FROM users WHERE id = ?");
$check->bind_param("i", $id);
$check->execute();
$user = $check->get_result()->fetch_assoc();
$check->close();

if (!$user) {
    $_SESSION['flash_error'] = "User not found.";
    header("Location: index.php"); exit;
}

if ($user['role'] === 'admin') {
    $_SESSION['flash_error'] = "Admin accounts cannot be deleted.";
    header("Location: index.php"); exit;
}

$stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
$stmt->bind_param("i", $id);
if ($stmt->execute()) {
    $_SESSION['flash_success'] = "User deleted successfully.";
} else {
    $_SESSION['flash_error'] = "Failed to delete user.";
}
$stmt->close();

header("Location: index.php");
exit;
