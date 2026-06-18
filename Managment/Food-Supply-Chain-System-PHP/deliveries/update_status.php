<?php
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/auth_check.php';
check_role('driver');
$isAdminPage = true;

$user = get_logged_in_user();
$orderId = (int)($_GET['id'] ?? 0);

$stmt = $conn->prepare("SELECT o.*, u.name as customer_name FROM orders o JOIN users u ON o.customer_id = u.id WHERE o.id = ? AND o.driver_id = ?");
$stmt->execute([$orderId, $user['id']]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    set_flash_message('error', 'Order not found or unauthorized.');
    header('Location: ' . BASE_URL . 'deliveries/assigned.php');
    exit();
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $status = $_POST['status'] ?? $order['status'];
    $notes = trim($_POST['delivery_notes'] ?? '');
    
    if (!in_array($status, ['assigned', 'out_for_delivery', 'delivered', 'cancelled'])) {
        $error = 'Invalid status selected.';
    } else {
        $up = $conn->prepare("UPDATE orders SET status = ?, delivery_notes = ? WHERE id = ?");
        $up->execute([$status, $notes, $orderId]);
        
        set_flash_message('success', 'Delivery status updated to ' . ucfirst(str_replace('_', ' ', $status)) . '.');
        header('Location: ' . BASE_URL . 'deliveries/assigned.php');
        exit();
    }
}

$pageTitle = 'Update Delivery Status';
include __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-layout">
<?php include __DIR__ . '/../includes/sidebar.php'; ?>
<div class="dashboard-main">
<div class="dashboard-header">
    <div class="dashboard-title">
        <h2>✏️ Update Delivery Status</h2>
        <p>Order #<?php echo $orderId; ?> &mdash; <?php echo htmlspecialchars($order['customer_name']); ?></p>
    </div>
    <a href="<?php echo BASE_URL; ?>deliveries/assigned.php" class="btn btn-secondary">← Back</a>
</div>

<div class="dashboard-content">
    <?php if ($error): ?><div class="alert alert-error">⚠️ <?php echo htmlspecialchars($error); ?></div><?php endif; ?>

    <div class="table-container" style="max-width:550px;padding:2rem;">
        <form method="POST">
            <div class="form-group">
                <label class="form-label" for="status">Logistics Delivery Status *</label>
                <select class="form-control" name="status" id="status" required>
                    <option value="assigned" <?php echo $order['status'] === 'assigned' ? 'selected' : ''; ?>>Assigned (Pending Route)</option>
                    <option value="out_for_delivery" <?php echo $order['status'] === 'out_for_delivery' ? 'selected' : ''; ?>>Out for Delivery (On the road)</option>
                    <option value="delivered" <?php echo $order['status'] === 'delivered' ? 'selected' : ''; ?>>✓ Delivered (Order completed)</option>
                    <option value="cancelled" <?php echo $order['status'] === 'cancelled' ? 'selected' : ''; ?>>❌ Cancelled (Undeliverable)</option>
                </select>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="delivery_notes">Delivery Notes / Route Logs</label>
                <textarea class="form-control" name="delivery_notes" id="delivery_notes" rows="4" placeholder="Add details like Gate Codes, customer notes, route traffic, delays..."><?php echo htmlspecialchars($order['delivery_notes'] ?? ''); ?></textarea>
            </div>
            
            <div style="margin-top:1.5rem; display:flex; gap:1rem;">
                <button type="submit" class="btn btn-primary">💾 Save Logistics Status</button>
                <a href="<?php echo BASE_URL; ?>deliveries/assigned.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
</div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
