<?php
// products/delete.php — Delete a product
$adminOnly = true;
require_once '../includes/auth_guard.php';
require_once '../config/db.php';

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) { header("Location: index.php"); exit; }

// Check if product is used in any sales
$check = $conn->prepare("SELECT COUNT(*) as cnt FROM sale_items WHERE product_id = ?");
$check->bind_param("i", $id);
$check->execute();
$row = $check->get_result()->fetch_assoc();
$check->close();

if ($row['cnt'] > 0) {
    $_SESSION['flash_error'] = "Cannot delete: this product has sales history. Reduce stock to 0 instead.";
    header("Location: index.php");
    exit;
}

$stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
$stmt->bind_param("i", $id);
if ($stmt->execute()) {
    $_SESSION['flash_success'] = "Product deleted successfully.";
} else {
    $_SESSION['flash_error'] = "Could not delete product.";
}
$stmt->close();

header("Location: index.php");
exit;
