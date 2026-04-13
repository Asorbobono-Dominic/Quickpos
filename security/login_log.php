<?php
// security/login_log.php — Login activity log (Admin only)
$adminOnly = true;
require_once '../includes/auth_guard.php';
require_once '../config/db.php';

$pageTitle = 'Login Activity Log';

// Filters
$filterStatus = $_GET['status'] ?? '';
$filterUser   = trim($_GET['username'] ?? '');

$where = "WHERE 1=1";
$params = [];
$types = "";

if ($filterStatus && in_array($filterStatus, ['success', 'failed'])) {
    $where .= " AND status = ?";
    $params[] = $filterStatus;
    $types .= "s";
}
if ($filterUser) {
    $where .= " AND username LIKE ?";
    $params[] = "%$filterUser%";
    $types .= "s";
}

$query = "SELECT * FROM login_logs $where ORDER BY logged_at DESC LIMIT 100";
$stmt = $conn->prepare($query);
if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$logs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Stats
$totalLogins  = $conn->query("SELECT COUNT(*) as cnt FROM login_logs WHERE status='success'")->fetch_assoc()['cnt'];
$failedLogins = $conn->query("SELECT COUNT(*) as cnt FROM login_logs WHERE status='failed'")->fetch_assoc()['cnt'];
$todayLogins  = $conn->query("SELECT COUNT(*) as cnt FROM login_logs WHERE DATE(logged_at)=CURDATE()")->fetch_assoc()['cnt'];

require_once '../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold mb-0"><i class="fas fa-shield-alt me-2 text-primary"></i>Login Activity Log</h3>
</div>

<!-- Stats -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card shadow-sm border-0 bg-success text-white">
            <div class="card-body">
                <div class="small fw-semibold opacity-75 mb-1">Successful Logins</div>
                <div class="fs-4 fw-bold"><?= $totalLogins ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card shadow-sm border-0 bg-danger text-white">
            <div class="card-body">
                <div class="small fw-semibold opacity-75 mb-1">Failed Attempts</div>
                <div class="fs-4 fw-bold"><?= $failedLogins ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card shadow-sm border-0 bg-info text-white">
            <div class="card-body">
                <div class="small fw-semibold opacity-75 mb-1">Today's Activity</div>
                <div class="fs-4 fw-bold"><?= $todayLogins ?></div>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<form method="GET" class="card shadow-sm mb-4 p-3">
    <div class="row g-2 align-items-end">
        <div class="col-md-3">
            <label class="form-label small fw-semibold">Username</label>
            <input type="text" name="username" class="form-control form-control-sm"
                   placeholder="Filter by username..." value="<?= htmlspecialchars($filterUser) ?>">
        </div>
        <div class="col-md-3">
            <label class="form-label small fw-semibold">Status</label>
            <select name="status" class="form-select form-select-sm">
                <option value="">All</option>
                <option value="success" <?= $filterStatus === 'success' ? 'selected' : '' ?>>Success</option>
                <option value="failed" <?= $filterStatus === 'failed' ? 'selected' : '' ?>>Failed</option>
            </select>
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-primary btn-sm w-100">
                <i class="fas fa-filter me-1"></i>Filter
            </button>
        </div>
        <div class="col-md-2">
            <a href="login_log.php" class="btn btn-outline-secondary btn-sm w-100">Reset</a>
        </div>
    </div>
</form>

<!-- Log Table -->
<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-dark">
                <tr>
                    <th>#</th>
                    <th>Username</th>
                    <th>Status</th>
                    <th>IP Address</th>
                    <th>Browser/Device</th>
                    <th>Date & Time</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($logs)): ?>
                <tr>
                    <td colspan="6" class="text-center text-muted py-4">
                        <i class="fas fa-shield-alt fa-2x mb-2 d-block"></i>No login activity found.
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($logs as $i => $log): ?>
                <tr>
                    <td><?= $i + 1 ?></td>
                    <td><strong><?= htmlspecialchars($log['username']) ?></strong></td>
                    <td>
                        <?php if ($log['status'] === 'success'): ?>
                            <span class="badge bg-success"><i class="fas fa-check me-1"></i>Success</span>
                        <?php else: ?>
                            <span class="badge bg-danger"><i class="fas fa-times me-1"></i>Failed</span>
                        <?php endif; ?>
                    </td>
                    <td><code><?= htmlspecialchars($log['ip_address']) ?></code></td>
                    <td class="small text-muted" style="max-width:250px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
                        <?= htmlspecialchars(substr($log['user_agent'] ?? '', 0, 60)) ?>...
                    </td>
                    <td><?= date('d M Y, h:i A', strtotime($log['logged_at'])) ?></td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
