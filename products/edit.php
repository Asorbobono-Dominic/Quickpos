<?php
// products/edit.php — Edit existing product
$adminOnly = true;
require_once '../includes/auth_guard.php';
require_once '../config/db.php';

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) { header("Location: index.php"); exit; }

$stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$product) { header("Location: index.php"); exit; }

$pageTitle = 'Edit Product';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name      = trim($_POST['name'] ?? '');
    $price     = trim($_POST['price'] ?? '');
    $cost_price= trim($_POST['cost_price'] ?? '0');
    $quantity  = trim($_POST['quantity'] ?? '');
    $barcode   = trim($_POST['barcode'] ?? '');

    if (empty($name))                                $errors[] = 'Product name is required.';
    if (!is_numeric($price) || $price < 0)           $errors[] = 'Enter a valid price.';
    if (!is_numeric($cost_price) || $cost_price < 0) $errors[] = 'Enter a valid cost price.';
    if (!is_numeric($quantity) || $quantity < 0)     $errors[] = 'Enter a valid quantity.';

    if (!empty($barcode)) {
        $bc = $conn->prepare("SELECT id FROM products WHERE barcode = ? AND id != ?");
        $bc->bind_param("si", $barcode, $id);
        $bc->execute();
        if ($bc->get_result()->num_rows > 0) $errors[] = 'Barcode already exists for another product.';
        $bc->close();
    }

    if (empty($errors)) {
        $barcode = !empty($barcode) ? $barcode : null;
        $stmt = $conn->prepare("UPDATE products SET name=?, price=?, cost_price=?, quantity=?, barcode=? WHERE id=?");
        $stmt->bind_param("sddisi", $name, $price, $cost_price, $quantity, $barcode, $id);
        if ($stmt->execute()) {
            $_SESSION['flash_success'] = "Product updated successfully.";
            header("Location: index.php");
            exit;
        }
        $stmt->close();
    } else {
        $product['name']     = $name;
        $product['price']    = $price;
        $product['quantity'] = $quantity;
        $product['barcode']  = $barcode;
    }
}

require_once '../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card shadow-sm">
            <div class="card-header bg-warning text-dark">
                <h5 class="mb-0"><i class="fas fa-edit me-2"></i>Edit Product</h5>
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
                               value="<?= htmlspecialchars($product['name']) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Cost Price (GH₵) *</label>
                        <div class="input-group">
                            <span class="input-group-text">GH₵</span>
                            <input type="number" name="cost_price" class="form-control"
                                   value="<?= htmlspecialchars($product['cost_price'] ?? '0') ?>"
                                   step="0.01" min="0" required>
                        </div>
                        <div class="form-text">Purchase/wholesale price for profit tracking.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Price (GH₵) *</label>
                        <div class="input-group">
                            <span class="input-group-text">GH₵</span>
                            <input type="number" name="price" class="form-control"
                                   value="<?= htmlspecialchars($product['price']) ?>"
                                   step="0.01" min="0" required>
                        </div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-semibold">Stock Quantity *</label>
                        <input type="number" name="quantity" class="form-control"
                               value="<?= htmlspecialchars($product['quantity']) ?>"
                               min="0" required>
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-semibold">
                            Barcode / Product Code
                            <span class="text-muted fw-normal small">(optional)</span>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-barcode"></i></span>
                            <input type="text" name="barcode" class="form-control"
                                   value="<?= htmlspecialchars($product['barcode'] ?? '') ?>"
                                   placeholder="Scan or type barcode...">
                        </div>
                        <div class="form-text">Leave empty if no barcode. Must be unique per product.</div>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-warning text-dark fw-semibold">
                            <i class="fas fa-save me-1"></i>Update Product
                        </button>
                        <a href="index.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
