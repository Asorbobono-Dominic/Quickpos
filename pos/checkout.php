<?php
// pos/checkout.php — Processes cart, stores sale, updates inventory
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

require_once '../config/db.php';

$input = json_decode(file_get_contents('php://input'), true);
$cartItems        = $input['cart'] ?? [];
$total            = floatval($input['total'] ?? 0);
$paymentMethod    = in_array($input['payment_method'] ?? '', ['cash','momo','card']) ? $input['payment_method'] : 'cash';
$paymentReference = trim($input['payment_reference'] ?? '') ?: null;

if (empty($cartItems) || $total <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid cart data']);
    exit;
}

// Begin transaction
$conn->begin_transaction();

try {
    // 1. Insert sale record
    $cashierId = $_SESSION['user_id'];
    $stmt = $conn->prepare("INSERT INTO sales (cashier_id, total_amount, payment_method, payment_reference) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("idss", $cashierId, $total, $paymentMethod, $paymentReference);
    $stmt->execute();
    $saleId = $conn->insert_id;
    $stmt->close();

    // 2. Insert sale items + reduce stock
    $itemStmt = $conn->prepare("INSERT INTO sale_items (sale_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
    $stockStmt = $conn->prepare("UPDATE products SET quantity = quantity - ? WHERE id = ? AND quantity >= ?");

    foreach ($cartItems as $item) {
        $productId = intval($item['id']);
        $qty       = intval($item['qty']);
        $price     = floatval($item['price']);

        // Check stock is still available
        $checkStmt = $conn->prepare("SELECT quantity FROM products WHERE id = ?");
        $checkStmt->bind_param("i", $productId);
        $checkStmt->execute();
        $stockRow = $checkStmt->get_result()->fetch_assoc();
        $checkStmt->close();

        if (!$stockRow || $stockRow['quantity'] < $qty) {
            throw new Exception("Insufficient stock for product ID $productId");
        }

        // Insert sale item
        $itemStmt->bind_param("iiid", $saleId, $productId, $qty, $price);
        $itemStmt->execute();

        // Reduce stock
        $stockStmt->bind_param("iii", $qty, $productId, $qty);
        $stockStmt->execute();
    }

    $itemStmt->close();
    $stockStmt->close();

    $conn->commit();

    echo json_encode(['success' => true, 'sale_id' => $saleId]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
