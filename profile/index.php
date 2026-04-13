<?php
// profile/index.php — Change own password
require_once '../includes/auth_guard.php';
require_once '../config/db.php';

$pageTitle = 'My Profile';
$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $currentPassword = trim($_POST['current_password'] ?? '');
    $newPassword     = trim($_POST['new_password'] ?? '');
    $confirmPassword = trim($_POST['confirm_password'] ?? '');

    if (empty($currentPassword))   $errors[] = 'Current password is required.';
    if (strlen($newPassword) < 6)  $errors[] = 'New password must be at least 6 characters.';
    if ($newPassword !== $confirmPassword) $errors[] = 'New passwords do not match.';

    if (empty($errors)) {
        // Verify current password
        $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!password_verify($currentPassword, $user['password'])) {
            $errors[] = 'Current password is incorrect.';
        } else {
            $hash = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->bind_param("si", $hash, $_SESSION['user_id']);
            if ($stmt->execute()) {
                $success = 'Password changed successfully!';
            } else {
                $errors[] = 'Failed to update password.';
            }
            $stmt->close();
        }
    }
}

// Get user info
$stmt = $conn->prepare("SELECT username, role, created_at FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Get user sales count
$stmt = $conn->prepare("SELECT COUNT(*) as cnt, SUM(total_amount) as total FROM sales WHERE cashier_id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();
$stmt->close();

require_once '../includes/header.php';
?>

<div class="row g-4">
    <!-- Profile Info Card -->
    <div class="col-md-4">
        <div class="card shadow-sm text-center p-4">
            <div class="profile-avatar mx-auto mb-3">
                <i class="fas fa-user-circle fa-5x text-primary"></i>
            </div>
            <h4 class="fw-bold"><?= htmlspecialchars($user['username']) ?></h4>
            <span class="badge <?= $user['role'] === 'admin' ? 'bg-warning text-dark' : 'bg-primary' ?> mb-3">
                <?= ucfirst($user['role']) ?>
            </span>
            <hr>
            <div class="row g-2 text-center">
                <div class="col-6">
                    <div class="fw-bold fs-4 text-primary"><?= $stats['cnt'] ?? 0 ?></div>
                    <div class="small text-muted">Total Sales</div>
                </div>
                <div class="col-6">
                    <div class="fw-bold fs-5 text-success">GH₵ <?= number_format($stats['total'] ?? 0, 2) ?></div>
                    <div class="small text-muted">Revenue</div>
                </div>
            </div>
            <hr>
            <div class="small text-muted">
                <i class="fas fa-calendar me-1"></i>
                Member since <?= date('d M Y', strtotime($user['created_at'])) ?>
            </div>
        </div>
    </div>

    <!-- Change Password Card -->
    <div class="col-md-8">
        <div class="card shadow-sm">
            <div class="card-header fw-semibold">
                <i class="fas fa-lock me-2 text-warning"></i>Change Password
            </div>
            <div class="card-body p-4">
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle me-1"></i><?= $success ?>
                    </div>
                <?php endif; ?>
                <?php if ($errors): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0 small">
                            <?php foreach ($errors as $e): ?>
                                <li><?= htmlspecialchars($e) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Current Password *</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            <input type="password" name="current_password" class="form-control"
                                   placeholder="Enter current password" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">New Password *</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-key"></i></span>
                            <input type="password" name="new_password" class="form-control"
                                   placeholder="Enter new password" required minlength="6">
                        </div>
                        <div class="form-text">Minimum 6 characters.</div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-semibold">Confirm New Password *</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-key"></i></span>
                            <input type="password" name="confirm_password" class="form-control"
                                   placeholder="Confirm new password" required minlength="6">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>Update Password
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
