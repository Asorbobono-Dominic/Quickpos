<?php
// pos/barcode_lookup.php — Looks up product by barcode
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

require_once '../config/db.php';

$barcode = trim($_GET['barcode'] ?? '');

if (empty($barcode)) {
    echo json_encode(['success' => false, 'message' => 'No barcode provided']);
    exit;
}

$stmt = $conn->prepare("SELECT id, name, price, quantity FROM products WHERE barcode = ?");
$stmt->bind_param("s", $barcode);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$product) {
    echo json_encode(['success' => false, 'message' => "No product found for barcode: $barcode"]);
    exit;
}

if ($product['quantity'] <= 0) {
    echo json_encode(['success' => false, 'message' => "'{$product['name']}' is out of stock"]);
    exit;
}

echo json_encode(['success' => true, 'product' => $product]);
