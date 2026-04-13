<?php
// reports/index.php — Sales reports and analytics (Admin only)
$adminOnly = true;
require_once '../includes/auth_guard.php';
require_once '../config/db.php';

$pageTitle = 'Reports';

// Date filter
$dateFrom = $_GET['date_from'] ?? date('Y-m-01');
$dateTo   = $_GET['date_to']   ?? date('Y-m-d');

// Total sales in range
$stmt = $conn->prepare("SELECT COUNT(*) as total_txns, SUM(total_amount) as total_revenue
                         FROM sales WHERE DATE(date) BETWEEN ? AND ?");
$stmt->bind_param("ss", $dateFrom, $dateTo);
$stmt->execute();
$summary = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Daily totals
$stmt = $conn->prepare("SELECT DATE(date) as day, COUNT(*) as txns, SUM(total_amount) as revenue
                         FROM sales WHERE DATE(date) BETWEEN ? AND ?
                         GROUP BY DATE(date) ORDER BY day DESC");
$stmt->bind_param("ss", $dateFrom, $dateTo);
$stmt->execute();
$dailyTotals = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Recent transactions
$stmt = $conn->prepare("SELECT s.id, s.total_amount, s.date, u.username as cashier
                         FROM sales s JOIN users u ON s.cashier_id = u.id
                         WHERE DATE(s.date) BETWEEN ? AND ?
                         ORDER BY s.date DESC LIMIT 50");
$stmt->bind_param("ss", $dateFrom, $dateTo);
$stmt->execute();
$transactions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Low stock products
$lowStock = $conn->query("SELECT * FROM products WHERE quantity <= 10 ORDER BY quantity ASC")->fetch_all(MYSQLI_ASSOC);

// Top selling products
$stmt = $conn->prepare("SELECT p.name, SUM(si.quantity) as total_sold, SUM(si.quantity * si.price) as revenue
                         FROM sale_items si JOIN products p ON si.product_id = p.id
                         JOIN sales s ON si.sale_id = s.id
                         WHERE DATE(s.date) BETWEEN ? AND ?
                         GROUP BY p.id ORDER BY total_sold DESC LIMIT 5");
$stmt->bind_param("ss", $dateFrom, $dateTo);
$stmt->execute();
$topProducts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

require_once '../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold mb-0"><i class="fas fa-chart-bar me-2 text-primary"></i>Sales Reports</h3>
    <div class="d-flex gap-2">
        <a href="export_pdf.php?date_from=<?= $dateFrom ?>&date_to=<?= $dateTo ?>"
           target="_blank" class="btn btn-danger">
            <i class="fas fa-file-pdf me-1"></i>Export PDF
        </a>
        <a href="export_excel.php?date_from=<?= $dateFrom ?>&date_to=<?= $dateTo ?>"
           class="btn btn-success">
            <i class="fas fa-file-excel me-1"></i>Export Excel
        </a>
    </div>
</div>

<!-- Date Filter -->
<form method="GET" class="card shadow-sm mb-4 p-3">
    <div class="row g-2 align-items-end">
        <div class="col-md-3">
            <label class="form-label small fw-semibold">From</label>
            <input type="date" name="date_from" class="form-control" value="<?= $dateFrom ?>">
        </div>
        <div class="col-md-3">
            <label class="form-label small fw-semibold">To</label>
            <input type="date" name="date_to" class="form-control" value="<?= $dateTo ?>">
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-primary w-100">
                <i class="fas fa-filter me-1"></i>Filter
            </button>
        </div>
        <div class="col-md-2">
            <a href="index.php" class="btn btn-outline-secondary w-100">Reset</a>
        </div>
    </div>
</form>

<!-- Summary Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card shadow-sm border-0 bg-primary text-white h-100">
            <div class="card-body">
                <div class="small fw-semibold opacity-75 mb-1">Total Revenue</div>
                <div class="fs-4 fw-bold">GH₵ <?= number_format($summary['total_revenue'] ?? 0, 2) ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm border-0 bg-success text-white h-100">
            <div class="card-body">
                <div class="small fw-semibold opacity-75 mb-1">Transactions</div>
                <div class="fs-4 fw-bold"><?= $summary['total_txns'] ?? 0 ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm border-0 bg-info text-white h-100">
            <div class="card-body">
                <div class="small fw-semibold opacity-75 mb-1">Avg. Sale Value</div>
                <div class="fs-4 fw-bold">
                    GH₵ <?= $summary['total_txns'] > 0 ? number_format($summary['total_revenue'] / $summary['total_txns'], 2) : '0.00' ?>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm border-0 <?= count($lowStock) > 0 ? 'bg-danger' : 'bg-secondary' ?> text-white h-100">
            <div class="card-body">
                <div class="small fw-semibold opacity-75 mb-1">Low Stock Items</div>
                <div class="fs-4 fw-bold"><?= count($lowStock) ?></div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <!-- Daily Totals -->
    <div class="col-md-6">
        <div class="card shadow-sm h-100">
            <div class="card-header fw-semibold">
                <i class="fas fa-calendar-day me-2 text-primary"></i>Daily Revenue
            </div>
            <div class="card-body p-0" style="max-height:300px;overflow-y:auto">
                <table class="table table-sm align-middle mb-0">
                    <thead class="table-light sticky-top">
                        <tr><th>Date</th><th class="text-center">Transactions</th><th class="text-end">Revenue</th></tr>
                    </thead>
                    <tbody>
                    <?php if (empty($dailyTotals)): ?>
                        <tr><td colspan="3" class="text-center text-muted py-3">No data in range.</td></tr>
                    <?php else: ?>
                        <?php foreach ($dailyTotals as $d): ?>
                        <tr>
                            <td><?= date('D, d M Y', strtotime($d['day'])) ?></td>
                            <td class="text-center"><?= $d['txns'] ?></td>
                            <td class="text-end fw-semibold">GH₵ <?= number_format($d['revenue'], 2) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Top Products -->
    <div class="col-md-6">
        <div class="card shadow-sm h-100">
            <div class="card-header fw-semibold">
                <i class="fas fa-trophy me-2 text-warning"></i>Top Selling Products
            </div>
            <div class="card-body p-0">
                <table class="table table-sm align-middle mb-0">
                    <thead class="table-light">
                        <tr><th>#</th><th>Product</th><th class="text-center">Units Sold</th><th class="text-end">Revenue</th></tr>
                    </thead>
                    <tbody>
                    <?php if (empty($topProducts)): ?>
                        <tr><td colspan="4" class="text-center text-muted py-3">No data.</td></tr>
                    <?php else: ?>
                        <?php foreach ($topProducts as $i => $p): ?>
                        <tr>
                            <td><span class="badge <?= $i === 0 ? 'bg-warning text-dark' : 'bg-secondary' ?>"><?= $i+1 ?></span></td>
                            <td><?= htmlspecialchars($p['name']) ?></td>
                            <td class="text-center"><?= $p['total_sold'] ?></td>
                            <td class="text-end">GH₵ <?= number_format($p['revenue'], 2) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Low Stock Alert -->
    <?php if ($lowStock): ?>
    <div class="col-12">
        <div class="card shadow-sm border-warning">
            <div class="card-header bg-warning text-dark fw-semibold">
                <i class="fas fa-exclamation-triangle me-2"></i>Low Stock Alert (≤ 10 units)
            </div>
            <div class="card-body p-0">
                <table class="table table-sm align-middle mb-0">
                    <thead class="table-light">
                        <tr><th>Product</th><th>Price</th><th>Remaining Stock</th><th>Action</th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($lowStock as $p): ?>
                    <tr class="<?= $p['quantity'] == 0 ? 'table-danger' : 'table-warning' ?>">
                        <td class="fw-semibold"><?= htmlspecialchars($p['name']) ?></td>
                        <td>GH₵ <?= number_format($p['price'], 2) ?></td>
                        <td>
                            <?php if ($p['quantity'] == 0): ?>
                                <span class="badge bg-danger">Out of Stock</span>
                            <?php else: ?>
                                <span class="badge bg-warning text-dark"><?= $p['quantity'] ?> left</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="/pos-system/products/edit.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-edit me-1"></i>Restock
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Transaction History -->
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-header fw-semibold">
                <i class="fas fa-history me-2 text-primary"></i>Transaction History
                <small class="text-muted ms-2">(Last 50 in range)</small>
            </div>
            <div class="card-body p-0" style="max-height:350px;overflow-y:auto">
                <table class="table table-sm align-middle mb-0">
                    <thead class="table-dark sticky-top">
                        <tr><th>Sale #</th><th>Date & Time</th><th>Cashier</th><th class="text-end">Amount</th><th></th></tr>
                    </thead>
                    <tbody>
                    <?php if (empty($transactions)): ?>
                        <tr><td colspan="5" class="text-center text-muted py-3">No transactions in range.</td></tr>
                    <?php else: ?>
                        <?php foreach ($transactions as $t): ?>
                        <tr>
                            <td><span class="badge bg-secondary">#<?= $t['id'] ?></span></td>
                            <td><?= date('d M Y, h:i A', strtotime($t['date'])) ?></td>
                            <td><?= htmlspecialchars($t['cashier']) ?></td>
                            <td class="text-end fw-semibold">GH₵ <?= number_format($t['total_amount'], 2) ?></td>
                            <td>
                                <a href="sale_detail.php?id=<?= $t['id'] ?>" class="btn btn-sm btn-outline-secondary">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
