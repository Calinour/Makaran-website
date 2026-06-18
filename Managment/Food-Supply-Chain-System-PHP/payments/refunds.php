<?php
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/auth_check.php';
check_role('admin');

$paymentId = (int)($_GET['id'] ?? 0);

$stmt = $conn->prepare("SELECT p.*, o.id as order_id, o.status as order_status 
                        FROM payments p 
                        JOIN orders o ON p.order_id = o.id 
                        WHERE p.id = ? AND p.status = 'completed'");
$stmt->execute([$paymentId]);
$payment = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$payment) {
    set_flash_message('error', 'Completed payment record not found.');
    header('Location: ' . BASE_URL . 'payments/index.php');
    exit();
}

$conn->beginTransaction();
try {
    // 1. Update Payment Status to Refunded
    $upPay = $conn->prepare("UPDATE payments SET status = 'refunded' WHERE id = ?");
    $upPay->execute([$paymentId]);
    
    // 2. Update Order Status to Cancelled & Payment Status to Refunded
    $upOrd = $conn->prepare("UPDATE orders SET status = 'cancelled', payment_status = 'refunded' WHERE id = ?");
    $upOrd->execute([$payment['order_id']]);
    
    // 3. Restore stock (create a new refund-restored batch for each product item)
    $itemsStmt = $conn->prepare("SELECT * FROM order_items WHERE order_id = ?");
    $itemsStmt->execute([$payment['order_id']]);
    $items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($items as $item) {
        $batchNumber = 'REF-B-' . str_pad($payment['order_id'], 5, '0', STR_PAD_LEFT);
        $expiryDate = date('Y-m-d', strtotime('+14 days')); // Default 14 days for returned items
        $notes = 'Restored stock from Refunded Order #' . $payment['order_id'];
        
        $insBatch = $conn->prepare("INSERT INTO inventory_batches (product_id, batch_number, quantity, expiry_date, status, notes) VALUES (?, ?, ?, ?, 'active', ?)");
        $insBatch->execute([$item['product_id'], $batchNumber, $item['quantity'], $expiryDate, $notes]);
    }
    
    $conn->commit();
    set_flash_message('success', 'Payment refunded successfully. Order #' . $payment['order_id'] . ' has been cancelled and stock has been restored.');
} catch (Exception $e) {
    $conn->rollBack();
    set_flash_message('error', 'Failed to process refund: ' . $e->getMessage());
}

header('Location: ' . BASE_URL . 'payments/index.php');
exit();
?>
