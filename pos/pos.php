<?php
// pos/pos.php — Main POS interface (Cashier + Admin)
require_once '../includes/auth_guard.php';
require_once '../config/db.php';

$pageTitle = 'POS';

// Fetch all in-stock products
$products = $conn->query("SELECT * FROM products WHERE quantity > 0 ORDER BY name")->fetch_all(MYSQLI_ASSOC);

require_once '../includes/header.php';
?>

<div class="row g-3" id="posApp">
    <!-- LEFT: Product Grid -->
    <div class="col-lg-7">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><i class="fas fa-th me-2"></i>Products</h6>
                <input type="text" id="productSearch" class="form-control form-control-sm w-auto"
                       placeholder="Search..." style="max-width:200px"
                       oninput="filterProducts(this.value)">
            </div>
            <!-- Barcode Scanner Bar -->
            <div class="px-3 pt-3 pb-1">
                <div class="input-group">
                    <span class="input-group-text bg-warning text-dark"><i class="fas fa-barcode"></i></span>
                    <input type="text" id="barcodeInput" class="form-control fw-semibold"
                           placeholder="Scan barcode or type product code and press Enter..."
                           onkeydown="if(event.key==='Enter') scanBarcode(this.value)">
                    <button class="btn btn-warning text-dark" onclick="scanBarcode(document.getElementById('barcodeInput').value)">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </div>
            <div class="card-body p-3 overflow-auto" style="max-height:72vh">
                <div class="row g-2" id="productGrid">
                    <?php foreach ($products as $p): ?>
                    <div class="col-6 col-md-4 col-xl-3 product-card-wrap"
                         data-name="<?= strtolower(htmlspecialchars($p['name'])) ?>">
                        <div class="product-tile <?= $p['quantity'] == 0 ? 'disabled' : '' ?>"
                             onclick="addToCart(<?= $p['id'] ?>, '<?= htmlspecialchars($p['name'], ENT_QUOTES) ?>', <?= $p['price'] ?>, <?= $p['quantity'] ?>)">
                            <div class="product-icon"><i class="fas fa-cube"></i></div>
                            <div class="product-name"><?= htmlspecialchars($p['name']) ?></div>
                            <div class="product-price">GH₵ <?= number_format($p['price'], 2) ?></div>
                            <div class="product-stock small text-muted">Stock: <?= $p['quantity'] ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php if (empty($products)): ?>
                    <div class="col-12 text-center text-muted py-5">
                        <i class="fas fa-box-open fa-3x mb-2 d-block"></i>No products available.
                        <?php if ($_SESSION['role'] === 'admin'): ?>
                            <a href="/pos-system/products/add.php" class="btn btn-sm btn-primary mt-2">Add Products</a>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- RIGHT: Cart -->
    <div class="col-lg-5">
        <div class="card shadow-sm h-100 d-flex flex-column">
            <div class="card-header bg-primary text-white">
                <h6 class="mb-0"><i class="fas fa-shopping-cart me-2"></i>Cart
                    <span id="cartCount" class="badge bg-warning text-dark ms-2">0</span>
                </h6>
            </div>
            <div class="card-body p-0 overflow-auto flex-grow-1" style="max-height:50vh">
                <table class="table table-sm align-middle mb-0" id="cartTable">
                    <thead class="table-light sticky-top">
                        <tr>
                            <th>Item</th>
                            <th class="text-center">Qty</th>
                            <th class="text-end">Subtotal</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody id="cartBody">
                        <tr id="emptyCartRow">
                            <td colspan="4" class="text-center text-muted py-4">
                                <i class="fas fa-cart-arrow-down fa-2x mb-2 d-block"></i>
                                Cart is empty
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <!-- Totals -->
            <div class="card-footer bg-light">
                <div class="d-flex justify-content-between mb-1">
                    <span class="text-muted">Items:</span>
                    <span id="totalItems">0</span>
                </div>
                <div class="d-flex justify-content-between fw-bold fs-5 mb-3">
                    <span>Total:</span>
                    <span class="text-primary" id="cartTotal">GH₵ 0.00</span>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-outline-danger flex-fill" onclick="clearCart()">
                        <i class="fas fa-trash me-1"></i>Clear
                    </button>
                    <button class="btn btn-success flex-fill fw-bold" onclick="openCheckout()" id="checkoutBtn" disabled>
                        <i class="fas fa-check-circle me-1"></i>Checkout
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Checkout Modal -->
<div class="modal fade" id="checkoutModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="fas fa-receipt me-2"></i>Checkout</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="checkoutSummary"></div>
                <hr>

                <!-- Payment Method -->
                <div class="mb-3">
                    <label class="fw-semibold mb-2 d-block">Payment Method</label>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-outline-success flex-fill payment-btn active" 
                                data-method="cash" onclick="selectPayment('cash')">
                            <i class="fas fa-money-bill-wave d-block fs-4 mb-1"></i>Cash
                        </button>
                        <button type="button" class="btn btn-outline-warning flex-fill payment-btn" 
                                data-method="momo" onclick="selectPayment('momo')">
                            <i class="fas fa-mobile-alt d-block fs-4 mb-1"></i>MoMo
                        </button>
                        <button type="button" class="btn btn-outline-primary flex-fill payment-btn" 
                                data-method="card" onclick="selectPayment('card')">
                            <i class="fas fa-credit-card d-block fs-4 mb-1"></i>Card
                        </button>
                    </div>
                </div>

                <!-- MoMo Reference -->
                <div id="momoFields" class="mb-3 d-none">
                    <label class="fw-semibold">MoMo Phone Number</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-phone"></i></span>
                        <input type="text" id="momoPhone" class="form-control"
                               placeholder="e.g. 024XXXXXXX">
                    </div>
                </div>

                <!-- Card Reference -->
                <div id="cardFields" class="mb-3 d-none">
                    <label class="fw-semibold">Card Reference / Transaction ID</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-hashtag"></i></span>
                        <input type="text" id="cardRef" class="form-control"
                               placeholder="e.g. TXN123456">
                    </div>
                </div>

                <!-- Cash Fields -->
                <div id="cashFields" class="row g-3 align-items-center">
                    <div class="col-md-6">
                        <label class="fw-semibold">Amount Tendered (GH₵)</label>
                        <input type="number" id="amountTendered" class="form-control form-control-lg"
                               min="0" step="0.01" placeholder="0.00"
                               oninput="calcChange()">
                    </div>
                    <div class="col-md-6">
                        <label class="fw-semibold">Change</label>
                        <div id="changeDisplay" class="fs-3 fw-bold text-success">GH₵ 0.00</div>
                    </div>
                </div>

            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-success btn-lg fw-bold px-4" onclick="completeSale()">
                    <i class="fas fa-check me-2"></i>Complete Sale
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Receipt Modal -->
<div class="modal fade" id="receiptModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-file-invoice me-2"></i>Receipt</h5>
            </div>
            <div class="modal-body" id="receiptContent"></div>
            <div class="modal-footer">
                <button class="btn btn-outline-secondary" onclick="window.print()">
                    <i class="fas fa-print me-1"></i>Print
                </button>
                <button class="btn btn-primary" onclick="newSale()">
                    <i class="fas fa-plus me-1"></i>New Sale
                </button>
            </div>
        </div>
    </div>
</div>

<script src="/pos-system/assets/js/pos.js"></script>

<?php require_once '../includes/footer.php'; ?>
