<?php
// users/add_user.php — Create new user
$adminOnly = true;
require_once '../includes/auth_guard.php';
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php"); exit;
}

$username = trim($_POST['username'] ?? '');
$password = trim($_POST['password'] ?? '');
$role     = in_array($_POST['role'] ?? '', ['admin', 'cashier']) ? $_POST['role'] : 'cashier';

if (empty($username) || empty($password)) {
    $_SESSION['flash_error'] = "Username and password are required.";
    header("Location: index.php"); exit;
}

if (strlen($password) < 6) {
    $_SESSION['flash_error'] = "Password must be at least 6 characters.";
    header("Location: index.php"); exit;
}

// Check username uniqueness
$check = $conn->prepare("SELECT id FROM users WHERE username = ?");
$check->bind_param("s", $username);
$check->execute();
if ($check->get_result()->num_rows > 0) {
    $_SESSION['flash_error'] = "Username '$username' already exists.";
    header("Location: index.php"); exit;
}
$check->close();

$hash = password_hash($password, PASSWORD_DEFAULT);
$stmt = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
$stmt->bind_param("sss", $username, $hash, $role);

if ($stmt->execute()) {
    $_SESSION['flash_success'] = "User '$username' created successfully as $role.";
} else {
    $_SESSION['flash_error'] = "Failed to create user.";
}
$stmt->close();

header("Location: index.php");
exit;
