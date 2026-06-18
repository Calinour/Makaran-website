<?php
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/auth_check.php';
check_role('admin');
$isAdminPage = true;

$orderId = (int)($_GET['id'] ?? 0);
$stmt = $conn->prepare("SELECT o.*, u.name as customer_name FROM orders o JOIN users u ON o.customer_id = u.id WHERE o.id = ?");
$stmt->execute([$orderId]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    set_flash_message('error', 'Order not found.');
    header('Location: ' . BASE_URL . 'orders/index.php');
    exit();
}

$drivers = $conn->query("SELECT id, name, email FROM users WHERE role = 'driver' ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $driverId = (int)($_POST['driver_id'] ?? 0) ?: null;
    
    $up = $conn->prepare("UPDATE orders SET driver_id = ?, status = ? WHERE id = ?");
    $newStatus = $driverId ? 'assigned' : 'paid';
    $up->execute([$driverId, $newStatus, $orderId]);
    
    set_flash_message('success', 'Driver assigned successfully. Order status updated to ' . $newStatus . '.');
    header('Location: ' . BASE_URL . 'orders/details.php?id=' . $orderId);
    exit();
}

$pageTitle = 'Assign Driver to Order #' . $orderId;
include __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-layout">
<?php include __DIR__ . '/../includes/sidebar.php'; ?>
<div class="dashboard-main">
<div class="dashboard-header">
    <div class="dashboard-title">
        <h2>🚚 Assign Driver</h2>
        <p>Assign logistics delivery driver for Order #<?php echo $orderId; ?></p>
    </div>
    <a href="<?php echo BASE_URL; ?>orders/details.php?id=<?php echo $orderId; ?>" class="btn btn-secondary">← Back to Details</a>
</div>

<div class="dashboard-content">
    <div class="table-container" style="max-width:500px;padding:2rem;">
        <h3 style="font-size:1.15rem;font-weight:700;margin-bottom:1.5rem;">Order Summary</h3>
        <div style="margin-bottom:1rem;color:var(--text-muted);">
            <div><strong>Customer:</strong> <?php echo htmlspecialchars($order['customer_name']); ?></div>
            <div><strong>Amount:</strong> $<?php echo number_format($order['total_amount'], 2); ?></div>
            <div><strong>Shipping Address:</strong> <?php echo htmlspecialchars($order['shipping_address']); ?></div>
        </div>
        
        <form method="POST">
            <div class="form-group">
                <label class="form-label" for="driver_id">Select Logistics Driver *</label>
                <select class="form-control" name="driver_id" id="driver_id" required>
                    <option value="">-- Select Driver --</option>
                    <?php foreach ($drivers as $d): ?>
                    <option value="<?php echo $d['id']; ?>" <?php echo $order['driver_id'] == $d['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($d['name']); ?> (<?php echo htmlspecialchars($d['email']); ?>)
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div style="margin-top:1.5rem; display:flex; gap:1rem;">
                <button type="submit" class="btn btn-primary">🚚 Assign Driver</button>
                <a href="<?php echo BASE_URL; ?>orders/details.php?id=<?php echo $orderId; ?>" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
</div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
