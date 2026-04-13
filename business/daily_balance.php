<?php
// business/daily_balance.php — Daily opening/closing balance
$adminOnly = true;
require_once '../includes/auth_guard.php';
require_once '../config/db.php';

$pageTitle = 'Daily Balance';
$today = date('Y-m-d');

// Get today's balance record
$stmt = $conn->prepare("SELECT * FROM daily_balance WHERE date = ?");
$stmt->bind_param("s", $today);
$stmt->execute();
$todayBalance = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Get today's total sales
$stmt = $conn->prepare("SELECT SUM(total_amount) as total, COUNT(*) as txns FROM sales WHERE DATE(date) = ?");
$stmt->bind_param("s", $today);
$stmt->execute();
$todaySales = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'open') {
        $openingBalance = floatval($_POST['opening_balance'] ?? 0);
        $notes = trim($_POST['notes'] ?? '');
        $userId = $_SESSION['user_id'];

        if (!$todayBalance) {
            $stmt = $conn->prepare("INSERT INTO daily_balance (date, opening_balance, notes, created_by) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("sssi", $today, $openingBalance, $notes, $userId);
            $stmt->execute();
            $stmt->close();
        }
        header("Location: daily_balance.php");
        exit;
    }

    if ($action === 'close') {
        $closingBalance = floatval($_POST['closing_balance'] ?? 0);
        $stmt = $conn->prepare("UPDATE daily_balance SET closing_balance = ? WHERE date = ?");
        $stmt->bind_param("ds", $closingBalance, $today);
        $stmt->execute();
        $stmt->close();
        header("Location: daily_balance.php");
        exit;
    }
}

// Get balance history
$history = $conn->query("SELECT db.*, u.username as created_by_name
                          FROM daily_balance db
                          JOIN users u ON db.created_by = u.id
                          ORDER BY db.date DESC LIMIT 30")->fetch_all(MYSQLI_ASSOC);

require_once '../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold mb-0"><i class="fas fa-cash-register me-2 text-primary"></i>Daily Balance</h3>
    <span class="badge bg-secondary fs-6"><?= date('l, d M Y') ?></span>
</div>

<!-- Today's Status -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card shadow-sm border-0 <?= $todayBalance ? 'bg-success' : 'bg-warning' ?> text-white">
            <div class="card-body">
                <div class="small fw-semibold opacity-75 mb-1">Day Status</div>
                <div class="fs-5 fw-bold">
                    <?php if (!$todayBalance): ?>
                        <i class="fas fa-lock me-1"></i>Not Opened
                    <?php elseif ($todayBalance && is_null($todayBalance['closing_balance'])): ?>
                        <i class="fas fa-store-alt me-1"></i>Open
                    <?php else: ?>
                        <i class="fas fa-store-slash me-1"></i>Closed
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm border-0 bg-primary text-white">
            <div class="card-body">
                <div class="small fw-semibold opacity-75 mb-1">Opening Balance</div>
                <div class="fs-5 fw-bold">GH₵ <?= number_format($todayBalance['opening_balance'] ?? 0, 2) ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm border-0 bg-info text-white">
            <div class="card-body">
                <div class="small fw-semibold opacity-75 mb-1">Today's Sales</div>
                <div class="fs-5 fw-bold">GH₵ <?= number_format($todaySales['total'] ?? 0, 2) ?></div>
                <div class="small opacity-75"><?= $todaySales['txns'] ?> transactions</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm border-0 <?= $todayBalance && !is_null($todayBalance['closing_balance']) ? 'bg-dark' : 'bg-secondary' ?> text-white">
            <div class="card-body">
                <div class="small fw-semibold opacity-75 mb-1">Closing Balance</div>
                <div class="fs-5 fw-bold">
                    <?= $todayBalance && !is_null($todayBalance['closing_balance'])
                        ? 'GH₵ ' . number_format($todayBalance['closing_balance'], 2)
                        : '—' ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Action Cards -->
<div class="row g-3 mb-4">
    <?php if (!$todayBalance): ?>
    <!-- Open Day -->
    <div class="col-md-6">
        <div class="card shadow-sm border-success">
            <div class="card-header bg-success text-white fw-semibold">
                <i class="fas fa-store-alt me-2"></i>Open Today's Register
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="action" value="open">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Opening Cash Balance (GH₵)</label>
                        <div class="input-group">
                            <span class="input-group-text">GH₵</span>
                            <input type="number" name="opening_balance" class="form-control form-control-lg"
                                   step="0.01" min="0" placeholder="0.00" required>
                        </div>
                        <div class="form-text">Amount of cash in the register at start of day.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Notes (optional)</label>
                        <input type="text" name="notes" class="form-control" placeholder="Any notes...">
                    </div>
                    <button type="submit" class="btn btn-success fw-semibold">
                        <i class="fas fa-store-alt me-1"></i>Open Register
                    </button>
                </form>
            </div>
        </div>
    </div>

    <?php elseif (is_null($todayBalance['closing_balance'])): ?>
    <!-- Close Day -->
    <div class="col-md-6">
        <div class="card shadow-sm border-danger">
            <div class="card-header bg-danger text-white fw-semibold">
                <i class="fas fa-store-slash me-2"></i>Close Today's Register
            </div>
            <div class="card-body">
                <?php
                $expected = ($todayBalance['opening_balance'] ?? 0) + ($todaySales['total'] ?? 0);
                ?>
                <div class="alert alert-info small mb-3">
                    <strong>Expected closing balance:</strong>
                    GH₵ <?= number_format($expected, 2) ?>
                    (Opening GH₵ <?= number_format($todayBalance['opening_balance'], 2) ?>
                    + Sales GH₵ <?= number_format($todaySales['total'] ?? 0, 2) ?>)
                </div>
                <form method="POST">
                    <input type="hidden" name="action" value="close">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Actual Closing Cash Balance (GH₵)</label>
                        <div class="input-group">
                            <span class="input-group-text">GH₵</span>
                            <input type="number" name="closing_balance" class="form-control form-control-lg"
                                   step="0.01" min="0"
                                   placeholder="<?= number_format($expected, 2) ?>" required>
                        </div>
                        <div class="form-text">Count the actual cash in the register now.</div>
                    </div>
                    <button type="submit" class="btn btn-danger fw-semibold">
                        <i class="fas fa-store-slash me-1"></i>Close Register
                    </button>
                </form>
            </div>
        </div>
    </div>

    <?php else: ?>
    <!-- Day Already Closed -->
    <div class="col-md-6">
        <div class="card shadow-sm border-dark">
            <div class="card-body text-center py-4">
                <i class="fas fa-check-circle fa-3x text-success mb-3 d-block"></i>
                <h5 class="fw-bold">Register Closed for Today</h5>
                <?php
                $expected  = $todayBalance['opening_balance'] + ($todaySales['total'] ?? 0);
                $variance  = $todayBalance['closing_balance'] - $expected;
                ?>
                <div class="row g-2 mt-3 text-start">
                    <div class="col-6">
                        <div class="small text-muted">Opening Balance</div>
                        <div class="fw-semibold">GH₵ <?= number_format($todayBalance['opening_balance'], 2) ?></div>
                    </div>
                    <div class="col-6">
                        <div class="small text-muted">Total Sales</div>
                        <div class="fw-semibold">GH₵ <?= number_format($todaySales['total'] ?? 0, 2) ?></div>
                    </div>
                    <div class="col-6">
                        <div class="small text-muted">Expected</div>
                        <div class="fw-semibold">GH₵ <?= number_format($expected, 2) ?></div>
                    </div>
                    <div class="col-6">
                        <div class="small text-muted">Actual Closing</div>
                        <div class="fw-semibold">GH₵ <?= number_format($todayBalance['closing_balance'], 2) ?></div>
                    </div>
                    <div class="col-12 mt-2">
                        <div class="alert <?= $variance >= 0 ? 'alert-success' : 'alert-danger' ?> py-2 mb-0">
                            <strong>Variance: <?= $variance >= 0 ? '+' : '' ?>GH₵ <?= number_format($variance, 2) ?></strong>
                            <?= $variance == 0 ? ' ✅ Perfect balance!' : ($variance > 0 ? ' ⬆️ Overage' : ' ⬇️ Shortage') ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Balance History -->
<div class="card shadow-sm">
    <div class="card-header fw-semibold">
        <i class="fas fa-history me-2 text-primary"></i>Balance History (Last 30 Days)
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-dark">
                <tr>
                    <th>Date</th>
                    <th>Opening</th>
                    <th>Sales</th>
                    <th>Expected</th>
                    <th>Closing</th>
                    <th>Variance</th>
                    <th>By</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($history)): ?>
                <tr><td colspan="7" class="text-center text-muted py-3">No balance records yet.</td></tr>
            <?php else: ?>
                <?php foreach ($history as $h): ?>
                <?php
                    $hSales = $conn->prepare("SELECT SUM(total_amount) as total FROM sales WHERE DATE(date) = ?");
                    $hSales->bind_param("s", $h['date']);
                    $hSales->execute();
                    $hSalesTotal = $hSales->get_result()->fetch_assoc()['total'] ?? 0;
                    $hSales->close();
                    $hExpected  = $h['opening_balance'] + $hSalesTotal;
                    $hVariance  = !is_null($h['closing_balance']) ? $h['closing_balance'] - $hExpected : null;
                ?>
                <tr>
                    <td><?= date('D, d M Y', strtotime($h['date'])) ?></td>
                    <td>GH₵ <?= number_format($h['opening_balance'], 2) ?></td>
                    <td>GH₵ <?= number_format($hSalesTotal, 2) ?></td>
                    <td>GH₵ <?= number_format($hExpected, 2) ?></td>
                    <td><?= !is_null($h['closing_balance']) ? 'GH₵ ' . number_format($h['closing_balance'], 2) : '<span class="badge bg-warning text-dark">Open</span>' ?></td>
                    <td>
                        <?php if (!is_null($hVariance)): ?>
                            <span class="badge <?= $hVariance == 0 ? 'bg-success' : ($hVariance > 0 ? 'bg-info' : 'bg-danger') ?>">
                                <?= $hVariance >= 0 ? '+' : '' ?>GH₵ <?= number_format($hVariance, 2) ?>
                            </span>
                        <?php else: ?>
                            <span class="text-muted">—</span>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($h['created_by_name']) ?></td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
