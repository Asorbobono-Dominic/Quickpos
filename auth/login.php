<?php
// auth/login.php — Login page
session_start();

// Already logged in? Redirect
if (isset($_SESSION['user_id'])) {
    header("Location: /pos-system/pos/pos.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once '../config/db.php';

    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
    } else {
        // Prepared statement — prevents SQL injection
        $stmt = $conn->prepare("SELECT id, username, password, role FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        // Get IP and user agent for logging
        $ip        = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id']  = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role']     = $user['role'];

            // Log successful login
            $log = $conn->prepare("INSERT INTO login_logs (username, status, ip_address, user_agent) VALUES (?, 'success', ?, ?)");
            $log->bind_param("sss", $username, $ip, $userAgent);
            $log->execute();
            $log->close();

            header("Location: /pos-system/pos/pos.php");
            exit;
        } else {
            $error = 'Invalid username or password.';

            // Log failed attempt
            $log = $conn->prepare("INSERT INTO login_logs (username, status, ip_address, user_agent) VALUES (?, 'failed', ?, ?)");
            $log->bind_param("sss", $username, $ip, $userAgent);
            $log->execute();
            $log->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — BigVybes Supermarket</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/pos-system/assets/css/style.css">
</head>
<body class="login-bg d-flex align-items-center justify-content-center min-vh-100">
<div class="card shadow-lg login-card p-4">
    <div class="text-center mb-4">
        <div class="login-icon mb-3">
            <i class="fas fa-cash-register fa-3x text-primary"></i>
        </div>
        <h2 class="fw-bold">BigVybes Supermarket</h2>
        <p class="text-muted small">Point of Sale System</p>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger py-2 small">
            <i class="fas fa-exclamation-circle me-1"></i><?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <form method="POST">
        <div class="mb-3">
            <label class="form-label fw-semibold">Username</label>
            <div class="input-group">
                <span class="input-group-text"><i class="fas fa-user"></i></span>
                <input type="text" name="username" class="form-control"
                       placeholder="Enter username"
                       value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                       required autofocus>
            </div>
        </div>
        <div class="mb-4">
            <label class="form-label fw-semibold">Password</label>
            <div class="input-group">
                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                <input type="password" name="password" class="form-control"
                       placeholder="Enter password" required>
            </div>
        </div>
        <button type="submit" class="btn btn-primary w-100 fw-semibold py-2">
            <i class="fas fa-sign-in-alt me-2"></i>Sign In
        </button>
    </form>

    <div class="mt-4 p-3 bg-light rounded small text-muted">
        <strong>Demo Accounts:</strong><br>
        Admin: <code>admin</code> / <code>admin123</code><br>
        Cashier: <code>cashier</code> / <code>cashier123</code>
    </div>
</div>
</body>
</html>
