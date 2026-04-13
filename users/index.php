<?php
// users/index.php — User Management (Admin only)
$adminOnly = true;
require_once '../includes/auth_guard.php';
require_once '../config/db.php';

$pageTitle = 'User Management';

$users = $conn->query("SELECT id, username, role, created_at FROM users ORDER BY role, username")->fetch_all(MYSQLI_ASSOC);

$success = $_SESSION['flash_success'] ?? '';
$flashError = $_SESSION['flash_error'] ?? '';
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

require_once '../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold mb-0"><i class="fas fa-users me-2 text-primary"></i>User Management</h3>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
        <i class="fas fa-user-plus me-1"></i>Add User
    </button>
</div>

<?php if ($success): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <i class="fas fa-check-circle me-1"></i><?= htmlspecialchars($success) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>
<?php if ($flashError): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="fas fa-exclamation-circle me-1"></i><?= htmlspecialchars($flashError) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-dark">
                <tr>
                    <th>#</th>
                    <th>Username</th>
                    <th>Role</th>
                    <th>Created</th>
                    <th>Total Sales</th>
                    <th class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($users as $i => $u): ?>
                <?php
                    // Get sales count per user
                    $sc = $conn->prepare("SELECT COUNT(*) as cnt FROM sales WHERE cashier_id = ?");
                    $sc->bind_param("i", $u['id']);
                    $sc->execute();
                    $salesCount = $sc->get_result()->fetch_assoc()['cnt'];
                    $sc->close();
                ?>
                <tr>
                    <td><?= $i + 1 ?></td>
                    <td>
                        <i class="fas fa-user-circle me-2 text-muted"></i>
                        <strong><?= htmlspecialchars($u['username']) ?></strong>
                        <?php if ($u['id'] == $_SESSION['user_id']): ?>
                            <span class="badge bg-info text-dark ms-1">You</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="badge <?= $u['role'] === 'admin' ? 'bg-warning text-dark' : 'bg-primary' ?> ">
                            <?= ucfirst($u['role']) ?>
                        </span>
                    </td>
                    <td><?= date('d M Y', strtotime($u['created_at'])) ?></td>
                    <td><span class="badge bg-secondary"><?= $salesCount ?> sales</span></td>
                    <td class="text-center">
                        <button class="btn btn-sm btn-outline-warning me-1"
                                onclick="openChangePassword(<?= $u['id'] ?>, '<?= htmlspecialchars($u['username'], ENT_QUOTES) ?>')">
                            <i class="fas fa-key"></i>
                        </button>
                        <?php if ($u['role'] !== 'admin'): ?>
                            <button class="btn btn-sm btn-outline-danger"
                                    onclick="confirmDelete(<?= $u['id'] ?>, '<?= htmlspecialchars($u['username'], ENT_QUOTES) ?>')">
                                <i class="fas fa-trash"></i>
                            </button>
                        <?php else: ?>
                            <button class="btn btn-sm btn-outline-secondary" disabled title="Cannot delete admin">
                                <i class="fas fa-lock"></i>
                            </button>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fas fa-user-plus me-2"></i>Add New User</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="add_user.php">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Username *</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-user"></i></span>
                            <input type="text" name="username" class="form-control"
                                   placeholder="Enter username" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Password *</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            <input type="password" name="password" class="form-control"
                                   placeholder="Enter password" required minlength="6">
                        </div>
                        <div class="form-text">Minimum 6 characters.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Role *</label>
                        <select name="role" class="form-select" required>
                            <option value="cashier">Cashier</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>Create User
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Change Password Modal -->
<div class="modal fade" id="changePasswordModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title"><i class="fas fa-key me-2"></i>Change Password</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="change_password.php">
                <input type="hidden" name="user_id" id="cpUserId">
                <div class="modal-body">
                    <p class="mb-3">Changing password for: <strong id="cpUsername"></strong></p>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">New Password *</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            <input type="password" name="new_password" class="form-control"
                                   placeholder="Enter new password" required minlength="6">
                        </div>
                        <div class="form-text">Minimum 6 characters.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning text-dark fw-semibold">
                        <i class="fas fa-save me-1"></i>Update Password
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirm Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header text-danger">
                <h5 class="modal-title"><i class="fas fa-trash me-2"></i>Delete User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to delete user <strong id="deleteUsername"></strong>?
                This cannot be undone.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <a id="deleteConfirmBtn" href="#" class="btn btn-danger">
                    <i class="fas fa-trash me-1"></i>Delete
                </a>
            </div>
        </div>
    </div>
</div>

<script>
function confirmDelete(id, username) {
    document.getElementById('deleteUsername').textContent = username;
    document.getElementById('deleteConfirmBtn').href = 'delete_user.php?id=' + id;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}

function openChangePassword(id, username) {
    document.getElementById('cpUserId').value = id;
    document.getElementById('cpUsername').textContent = username;
    new bootstrap.Modal(document.getElementById('changePasswordModal')).show();
}
</script>

<?php require_once '../includes/footer.php'; ?>
