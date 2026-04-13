<?php
// reports/sale_detail.php — View individual sale receipt
$adminOnly = true;
require_once '../includes/auth_guard.php';
require_once '../config/db.php';

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) { header("Location: index.php"); exit; }

$stmt = $conn->prepare("SELECT s.*, u.username as cashier FROM sales s JOIN users u ON s.cashier_id = u.id WHERE s.id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$sale = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$sale) { header("Location: index.php"); exit; }

$stmt = $conn->prepare("SELECT si.*, p.name FROM sale_items si JOIN products p ON si.product_id = p.id WHERE si.sale_id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$pageTitle = "Sale #$id";
require_once '../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold"><i class="fas fa-receipt me-2"></i>Sale #<?= $id ?></h6>
                <button onclick="window.print()" class="btn btn-sm btn-outline-secondary">
                    <i class="fas fa-print me-1"></i>Print
                </button>
            </div>
            <div class="card-body">
                <div class="text-center mb-3">
                    <h5 class="fw-bold">QuickPOS</h5>
                    <small class="text-muted">
                        <?= date('D, d M Y — h:i A', strtotime($sale['date'])) ?><br>
                        Cashier: <?= htmlspecialchars($sale['cashier']) ?>
                    </small>
                </div>
                <table class="table table-sm">
                    <thead><tr><th>Item</th><th class="text-center">Qty</th><th class="text-end">Price</th><th class="text-end">Subtotal</th></tr></thead>
                    <tbody>
                    <?php foreach ($items as $item): ?>
                    <tr>
                        <td><?= htmlspecialchars($item['name']) ?></td>
                        <td class="text-center"><?= $item['quantity'] ?></td>
                        <td class="text-end">GH₵ <?= number_format($item['price'], 2) ?></td>
                        <td class="text-end">GH₵ <?= number_format($item['price'] * $item['quantity'], 2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="fw-bold fs-5">
                            <td colspan="3" class="text-end">TOTAL:</td>
                            <td class="text-end text-success">GH₵ <?= number_format($sale['total_amount'], 2) ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <div class="card-footer text-center">
                <a href="index.php" class="btn btn-secondary btn-sm">
                    <i class="fas fa-arrow-left me-1"></i>Back to Reports
                </a>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
