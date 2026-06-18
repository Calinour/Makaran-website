<?php
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/auth_check.php';
check_role('admin');
$isAdminPage = true;

$error = '';
$success = '';

// Fetch unpaid orders
$unpaidOrders = $conn->query("
    SELECT o.id, o.total_amount, u.name as customer_name 
    FROM orders o 
    JOIN users u ON o.customer_id = u.id 
    WHERE o.payment_status = 'pending' AND o.status != 'cancelled'
    ORDER BY o.id DESC
")->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $orderId = (int)($_POST['order_id'] ?? 0);
    $method = $_POST['payment_method'] ?? 'cash';
    $transactionId = trim($_POST['transaction_id'] ?? '');
    
    // Fetch order details
    $oStmt = $conn->prepare("SELECT total_amount FROM orders WHERE id = ? AND payment_status = 'pending'");
    $oStmt->execute([$orderId]);
    $orderAmount = $oStmt->fetchColumn();
    
    if (!$orderAmount) {
        $error = 'Order not found, or it is already paid.';
    } elseif (!in_array($method, ['cash', 'card', 'mobile_money'])) {
        $error = 'Invalid payment method selected.';
    } else {
        $conn->beginTransaction();
        try {
            // Log Payment
            $pStmt = $conn->prepare("INSERT INTO payments (order_id, amount, payment_method, transaction_id, status) VALUES (?, ?, ?, ?, 'completed')");
            $pStmt->execute([$orderId, $orderAmount, $method, $transactionId]);
            
            // Update Order Payment Status
            $uStmt = $conn->prepare("UPDATE orders SET payment_status = 'paid', status = 'paid' WHERE id = ?");
            $uStmt->execute([$orderId]);
            
            $conn->commit();
            set_flash_message('success', 'Payment recorded successfully for Order #' . $orderId . '.');
            header('Location: ' . BASE_URL . 'payments/index.php');
            exit();
        } catch (Exception $e) {
            $conn->rollBack();
            $error = 'Failed to log payment: ' . $e->getMessage();
        }
    }
}

$pageTitle = 'Record Order Payment';
include __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-layout">
<?php include __DIR__ . '/../includes/sidebar.php'; ?>
<div class="dashboard-main">
<div class="dashboard-header">
    <div class="dashboard-title">
        <h2>💵 Record Order Payment</h2>
        <p>Log a payment arrival manually for pending orders</p>
    </div>
    <a href="<?php echo BASE_URL; ?>payments/index.php" class="btn btn-secondary">← Back</a>
</div>

<div class="dashboard-content">
    <?php if ($error): ?><div class="alert alert-error">⚠️ <?php echo htmlspecialchars($error); ?></div><?php endif; ?>
    
    <div class="table-container" style="max-width:550px;padding:2rem;">
        <form method="POST">
            <div class="form-group">
                <label class="form-label" for="order_id">Unpaid Order *</label>
                <select class="form-control" name="order_id" id="order_id" required>
                    <option value="">-- Select Unpaid Order --</option>
                    <?php foreach ($unpaidOrders as $o): ?>
                    <option value="<?php echo $o['id']; ?>" <?php echo (($_POST['order_id'] ?? '') == $o['id']) ? 'selected' : ''; ?>>
                        Order #<?php echo $o['id']; ?> - <?php echo htmlspecialchars($o['customer_name']); ?> ($<?php echo number_format($o['total_amount'], 2); ?>)
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="payment_method">Payment Method *</label>
                <select class="form-control" name="payment_method" id="payment_method" required>
                    <option value="cash">💵 Cash (On Delivery/Pickup)</option>
                    <option value="card">💳 Credit/Debit Card</option>
                    <option value="mobile_money">📱 Mobile Money (EVC Plus/Sahal/Zaad)</option>
                </select>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="transaction_id">Transaction / Reference ID (Optional)</label>
                <input class="form-control" type="text" name="transaction_id" id="transaction_id" placeholder="e.g. TXN98234120">
            </div>
            
            <div style="margin-top:1.5rem; display:flex; gap:1rem;">
                <button type="submit" class="btn btn-primary">💾 Log Payment Receipt</button>
                <a href="<?php echo BASE_URL; ?>payments/index.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
</div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
