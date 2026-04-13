<?php
// reports/export_pdf.php — Export sales report as printable PDF
$adminOnly = true;
require_once '../includes/auth_guard.php';
require_once '../config/db.php';

$dateFrom = $_GET['date_from'] ?? date('Y-m-01');
$dateTo   = $_GET['date_to']   ?? date('Y-m-d');

// Summary
$stmt = $conn->prepare("SELECT COUNT(*) as total_txns, SUM(total_amount) as total_revenue FROM sales WHERE DATE(date) BETWEEN ? AND ?");
$stmt->bind_param("ss", $dateFrom, $dateTo);
$stmt->execute();
$summary = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Transactions
$stmt = $conn->prepare("SELECT s.id, s.total_amount, s.date, s.payment_method, u.username as cashier
                         FROM sales s JOIN users u ON s.cashier_id = u.id
                         WHERE DATE(s.date) BETWEEN ? AND ?
                         ORDER BY s.date DESC");
$stmt->bind_param("ss", $dateFrom, $dateTo);
$stmt->execute();
$transactions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Top products
$stmt = $conn->prepare("SELECT p.name, SUM(si.quantity) as total_sold, SUM(si.quantity * si.price) as revenue
                         FROM sale_items si JOIN products p ON si.product_id = p.id
                         JOIN sales s ON si.sale_id = s.id
                         WHERE DATE(s.date) BETWEEN ? AND ?
                         GROUP BY p.id ORDER BY total_sold DESC LIMIT 10");
$stmt->bind_param("ss", $dateFrom, $dateTo);
$stmt->execute();
$topProducts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Payment breakdown
$stmt = $conn->prepare("SELECT payment_method, COUNT(*) as count, SUM(total_amount) as total
                         FROM sales WHERE DATE(date) BETWEEN ? AND ?
                         GROUP BY payment_method");
$stmt->bind_param("ss", $dateFrom, $dateTo);
$stmt->execute();
$paymentBreakdown = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Sales Report — BigVybes Supermarket</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 12px; color: #333; padding: 20px; }
        .header { text-align: center; border-bottom: 3px solid #0d6efd; padding-bottom: 15px; margin-bottom: 20px; }
        .header h1 { font-size: 22px; color: #0d6efd; }
        .header p { color: #666; font-size: 11px; }
        .section { margin-bottom: 20px; }
        .section h2 { font-size: 14px; background: #0d6efd; color: white; padding: 6px 10px; margin-bottom: 8px; border-radius: 4px; }
        .summary-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin-bottom: 20px; }
        .summary-card { border: 1px solid #ddd; border-radius: 6px; padding: 12px; text-align: center; }
        .summary-card .value { font-size: 18px; font-weight: bold; color: #0d6efd; }
        .summary-card .label { font-size: 10px; color: #666; margin-top: 4px; }
        table { width: 100%; border-collapse: collapse; font-size: 11px; }
        th { background: #f0f4ff; padding: 7px; text-align: left; border: 1px solid #ddd; font-size: 11px; }
        td { padding: 6px 7px; border: 1px solid #ddd; }
        tr:nth-child(even) { background: #f9f9f9; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .badge { padding: 2px 6px; border-radius: 3px; font-size: 10px; font-weight: bold; }
        .badge-cash { background: #d4edda; color: #155724; }
        .badge-momo { background: #fff3cd; color: #856404; }
        .badge-card { background: #cce5ff; color: #004085; }
        .footer { text-align: center; margin-top: 20px; padding-top: 10px; border-top: 1px solid #ddd; color: #999; font-size: 10px; }
        @media print {
            .no-print { display: none; }
            body { padding: 10px; }
        }
    </style>
</head>
<body>

<!-- Print Button -->
<div class="no-print" style="text-align:right;margin-bottom:15px">
    <button onclick="window.print()" style="background:#0d6efd;color:white;border:none;padding:8px 20px;border-radius:6px;cursor:pointer;font-size:13px">
        🖨️ Print / Save as PDF
    </button>
    <a href="index.php?date_from=<?= $dateFrom ?>&date_to=<?= $dateTo ?>"
       style="background:#6c757d;color:white;border:none;padding:8px 20px;border-radius:6px;cursor:pointer;font-size:13px;text-decoration:none;margin-left:8px">
        ← Back to Reports
    </a>
</div>

<!-- Header -->
<div class="header">
    <h1>BigVybes Supermarket</h1>
    <p>Navrongo, Upper East Region, Ghana</p>
    <p>+233 XX XXX XXXX &bull; bigvybes@email.com &bull; Quality Products, Great Prices!</p>
    <p style="margin-top:6px">Sales Report &bull; <?= date('d M Y', strtotime($dateFrom)) ?> to <?= date('d M Y', strtotime($dateTo)) ?></p>
    <p>Generated: <?= date('d M Y, h:i A') ?> &bull; By: <?= htmlspecialchars($_SESSION['username']) ?></p>
</div>

<!-- Summary Cards -->
<div class="summary-grid">
    <div class="summary-card">
        <div class="value">GH₵ <?= number_format($summary['total_revenue'] ?? 0, 2) ?></div>
        <div class="label">Total Revenue</div>
    </div>
    <div class="summary-card">
        <div class="value"><?= $summary['total_txns'] ?? 0 ?></div>
        <div class="label">Total Transactions</div>
    </div>
    <div class="summary-card">
        <div class="value">GH₵ <?= $summary['total_txns'] > 0 ? number_format($summary['total_revenue'] / $summary['total_txns'], 2) : '0.00' ?></div>
        <div class="label">Average Sale</div>
    </div>
</div>

<!-- Payment Breakdown -->
<?php if ($paymentBreakdown): ?>
<div class="section">
    <h2>Payment Method Breakdown</h2>
    <table>
        <thead><tr><th>Method</th><th class="text-center">Transactions</th><th class="text-right">Total Revenue</th></tr></thead>
        <tbody>
        <?php foreach ($paymentBreakdown as $p): ?>
        <tr>
            <td><span class="badge badge-<?= $p['payment_method'] ?>"><?= strtoupper($p['payment_method']) ?></span></td>
            <td class="text-center"><?= $p['count'] ?></td>
            <td class="text-right">GH₵ <?= number_format($p['total'], 2) ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<!-- Top Products -->
<?php if ($topProducts): ?>
<div class="section">
    <h2>Top Selling Products</h2>
    <table>
        <thead><tr><th>#</th><th>Product</th><th class="text-center">Units Sold</th><th class="text-right">Revenue</th></tr></thead>
        <tbody>
        <?php foreach ($topProducts as $i => $p): ?>
        <tr>
            <td><?= $i + 1 ?></td>
            <td><?= htmlspecialchars($p['name']) ?></td>
            <td class="text-center"><?= $p['total_sold'] ?></td>
            <td class="text-right">GH₵ <?= number_format($p['revenue'], 2) ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<!-- Transaction History -->
<div class="section">
    <h2>Transaction History</h2>
    <table>
        <thead>
            <tr>
                <th>Sale #</th>
                <th>Date & Time</th>
                <th>Cashier</th>
                <th>Payment</th>
                <th class="text-right">Amount</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($transactions)): ?>
            <tr><td colspan="5" class="text-center">No transactions in this period.</td></tr>
        <?php else: ?>
            <?php foreach ($transactions as $t): ?>
            <tr>
                <td>#<?= $t['id'] ?></td>
                <td><?= date('d M Y, h:i A', strtotime($t['date'])) ?></td>
                <td><?= htmlspecialchars($t['cashier']) ?></td>
                <td><span class="badge badge-<?= $t['payment_method'] ?>"><?= strtoupper($t['payment_method']) ?></span></td>
                <td class="text-right">GH₵ <?= number_format($t['total_amount'], 2) ?></td>
            </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<div class="footer">
    BigVybes Supermarket &bull; POS System &bull; Report generated on <?= date('d M Y \a\t h:i A') ?>
</div>

</body>
</html>
