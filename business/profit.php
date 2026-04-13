<?php
// business/profit.php — Profit tracking report
$adminOnly = true;
require_once '../includes/auth_guard.php';
require_once '../config/db.php';

$pageTitle = 'Profit Tracking';

$dateFrom = $_GET['date_from'] ?? date('Y-m-01');
$dateTo   = $_GET['date_to']   ?? date('Y-m-d');

// Profit per product
$stmt = $conn->prepare("
    SELECT p.name,
           SUM(si.quantity) as units_sold,
           AVG(p.cost_price) as cost_price,
           AVG(si.price) as sell_price,
           SUM(si.quantity * si.price) as revenue,
           SUM(si.quantity * p.cost_price) as total_cost,
           SUM(si.quantity * (si.price - p.cost_price)) as profit
    FROM sale_items si
    JOIN products p ON si.product_id = p.id
    JOIN sales s ON si.sale_id = s.id
    WHERE DATE(s.date) BETWEEN ? AND ?
    GROUP BY p.id
    ORDER BY profit DESC
");
$stmt->bind_param("ss", $dateFrom, $dateTo);
$stmt->execute();
$profitData = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Summary totals
$totalRevenue = array_sum(array_column($profitData, 'revenue'));
$totalCost    = array_sum(array_column($profitData, 'total_cost'));
$totalProfit  = array_sum(array_column($profitData, 'profit'));
$profitMargin = $totalRevenue > 0 ? ($totalProfit / $totalRevenue) * 100 : 0;

// Products with no cost price set
$noCostCount = $conn->query("SELECT COUNT(*) as cnt FROM products WHERE cost_price = 0")->fetch_assoc()['cnt'];

require_once '../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold mb-0"><i class="fas fa-chart-line me-2 text-success"></i>Profit Tracking</h3>
</div>

<?php if ($noCostCount > 0): ?>
<div class="alert alert-warning">
    <i class="fas fa-exclamation-triangle me-2"></i>
    <strong><?= $noCostCount ?> product(s)</strong> have no cost price set.
    <a href="/pos-system/products/index.php" class="alert-link ms-1">Set cost prices →</a>
</div>
<?php endif; ?>

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
    </div>
</form>

<!-- Summary Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card shadow-sm border-0 bg-primary text-white">
            <div class="card-body">
                <div class="small fw-semibold opacity-75 mb-1">Total Revenue</div>
                <div class="fs-4 fw-bold">GH₵ <?= number_format($totalRevenue, 2) ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm border-0 bg-danger text-white">
            <div class="card-body">
                <div class="small fw-semibold opacity-75 mb-1">Total Cost</div>
                <div class="fs-4 fw-bold">GH₵ <?= number_format($totalCost, 2) ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm border-0 bg-success text-white">
            <div class="card-body">
                <div class="small fw-semibold opacity-75 mb-1">Total Profit</div>
                <div class="fs-4 fw-bold">GH₵ <?= number_format($totalProfit, 2) ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm border-0 bg-info text-white">
            <div class="card-body">
                <div class="small fw-semibold opacity-75 mb-1">Profit Margin</div>
                <div class="fs-4 fw-bold"><?= number_format($profitMargin, 1) ?>%</div>
            </div>
        </div>
    </div>
</div>

<!-- Profit Table -->
<div class="card shadow-sm">
    <div class="card-header fw-semibold">
        <i class="fas fa-table me-2 text-success"></i>Profit Per Product
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-dark">
                <tr>
                    <th>#</th>
                    <th>Product</th>
                    <th class="text-center">Units Sold</th>
                    <th class="text-end">Cost Price</th>
                    <th class="text-end">Sell Price</th>
                    <th class="text-end">Revenue</th>
                    <th class="text-end">Total Cost</th>
                    <th class="text-end">Profit</th>
                    <th class="text-center">Margin</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($profitData)): ?>
                <tr><td colspan="9" class="text-center text-muted py-4">No sales data in this period.</td></tr>
            <?php else: ?>
                <?php foreach ($profitData as $i => $p): ?>
                <?php $margin = $p['revenue'] > 0 ? ($p['profit'] / $p['revenue']) * 100 : 0; ?>
                <tr>
                    <td><?= $i + 1 ?></td>
                    <td class="fw-semibold"><?= htmlspecialchars($p['name']) ?></td>
                    <td class="text-center"><?= $p['units_sold'] ?></td>
                    <td class="text-end">GH₵ <?= number_format($p['cost_price'], 2) ?></td>
                    <td class="text-end">GH₵ <?= number_format($p['sell_price'], 2) ?></td>
                    <td class="text-end">GH₵ <?= number_format($p['revenue'], 2) ?></td>
                    <td class="text-end">GH₵ <?= number_format($p['total_cost'], 2) ?></td>
                    <td class="text-end fw-bold <?= $p['profit'] >= 0 ? 'text-success' : 'text-danger' ?>">
                        GH₵ <?= number_format($p['profit'], 2) ?>
                    </td>
                    <td class="text-center">
                        <span class="badge <?= $margin >= 20 ? 'bg-success' : ($margin >= 10 ? 'bg-warning text-dark' : 'bg-danger') ?>">
                            <?= number_format($margin, 1) ?>%
                        </span>
                    </td>
                </tr>
                <?php endforeach; ?>
                <!-- Totals Row -->
                <tr class="table-dark fw-bold">
                    <td colspan="5" class="text-end">TOTALS:</td>
                    <td class="text-end">GH₵ <?= number_format($totalRevenue, 2) ?></td>
                    <td class="text-end">GH₵ <?= number_format($totalCost, 2) ?></td>
                    <td class="text-end text-success">GH₵ <?= number_format($totalProfit, 2) ?></td>
                    <td class="text-center"><?= number_format($profitMargin, 1) ?>%</td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
