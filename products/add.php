<?php
// products/add.php — Add new product
$adminOnly = true;
require_once '../includes/auth_guard.php';
require_once '../config/db.php';

$pageTitle = 'Add Product';
$errors = [];
$data = ['name' => '', 'price' => '', 'quantity' => '', 'barcode' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data['name']      = trim($_POST['name'] ?? '');
    $data['price']     = trim($_POST['price'] ?? '');
    $data['cost_price']= trim($_POST['cost_price'] ?? '0');
    $data['quantity']  = trim($_POST['quantity'] ?? '');
    $data['barcode']   = trim($_POST['barcode'] ?? '');

    if (empty($data['name']))                                     $errors[] = 'Product name is required.';
    if (!is_numeric($data['price']) || $data['price'] < 0)        $errors[] = 'Enter a valid price.';
    if (!is_numeric($data['cost_price']) || $data['cost_price'] < 0) $errors[] = 'Enter a valid cost price.';
    if (!is_numeric($data['quantity']) || $data['quantity'] < 0)  $errors[] = 'Enter a valid quantity.';

    if (!empty($data['barcode'])) {
        $bc = $conn->prepare("SELECT id FROM products WHERE barcode = ?");
        $bc->bind_param("s", $data['barcode']);
        $bc->execute();
        if ($bc->get_result()->num_rows > 0) $errors[] = 'Barcode already exists for another product.';
        $bc->close();
    }

    if (empty($errors)) {
        $barcode = !empty($data['barcode']) ? $data['barcode'] : null;
        $stmt = $conn->prepare("INSERT INTO products (name, price, cost_price, quantity, barcode) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sddis", $data['name'], $data['price'], $data['cost_price'], $data['quantity'], $barcode);
        if ($stmt->execute()) {
            $_SESSION['flash_success'] = "Product '{$data['name']}' added successfully.";
            header("Location: index.php");
            exit;
        } else {
            $errors[] = "Database error: " . $conn->error;
        }
        $stmt->close();
    }
}

require_once '../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-plus me-2"></i>Add New Product</h5>
            </div>
            <div class="card-body p-4">
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
                        <label class="form-label fw-semibold">Product Name *</label>
                        <input type="text" name="name" class="form-control"
                               value="<?= htmlspecialchars($data['name']) ?>"
                               placeholder="e.g. Coca-Cola 500ml" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Cost Price (GH₵) *</label>
                        <div class="input-group">
                            <span class="input-group-text">GH₵</span>
                            <input type="number" name="cost_price" class="form-control"
                                   value="<?= htmlspecialchars($data['cost_price'] ?? '0') ?>"
                                   step="0.01" min="0" placeholder="0.00" required>
                        </div>
                        <div class="form-text">Purchase/wholesale price for profit tracking.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Price (GH₵) *</label>
                        <div class="input-group">
                            <span class="input-group-text">GH₵</span>
                            <input type="number" name="price" class="form-control"
                                   value="<?= htmlspecialchars($data['price']) ?>"
                                   step="0.01" min="0" placeholder="0.00" required>
                        </div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-semibold">Initial Stock Quantity *</label>
                        <input type="number" name="quantity" class="form-control"
                               value="<?= htmlspecialchars($data['quantity']) ?>"
                               min="0" placeholder="0" required>
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-semibold">
                            Barcode / Product Code
                            <span class="text-muted fw-normal small">(optional)</span>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-barcode"></i></span>
                            <input type="text" name="barcode" class="form-control"
                                   value="<?= htmlspecialchars($data['barcode']) ?>"
                                   placeholder="Scan or type barcode...">
                        </div>
                        <div class="form-text">Leave empty if no barcode. Must be unique per product.</div>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>Save Product
                        </button>
                        <a href="index.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-1"></i>Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
