<?php
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/auth_check.php';
check_role('driver');
$isAdminPage = true;

$user = get_logged_in_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $orderId = (int)($_POST['order_id'] ?? 0);
    $notes = trim($_POST['delivery_notes'] ?? '');
    
    // Verify driver is assigned
    $stmt = $conn->prepare("UPDATE orders SET delivery_notes = ? WHERE id = ? AND driver_id = ?");
    $stmt->execute([$notes, $orderId, $user['id']]);
    
    set_flash_message('success', 'Route notes appended successfully.');
    header('Location: ' . BASE_URL . 'deliveries/notes.php');
    exit();
}

$stmt = $conn->prepare("
    SELECT o.id as order_id, o.delivery_notes, u.name as customer_name 
    FROM orders o 
    JOIN users u ON o.customer_id = u.id 
    WHERE o.driver_id = ? AND o.status IN ('assigned', 'out_for_delivery')
");
$stmt->execute([$user['id']]);
$deliveries = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Delivery Route Notes';
include __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-layout">
<?php include __DIR__ . '/../includes/sidebar.php'; ?>
<div class="dashboard-main">
<div class="dashboard-header">
    <div class="dashboard-title">
        <h2>📝 Route Logs & Notes</h2>
        <p>Quickly update delivery notes for your active routes</p>
    </div>
</div>

<div class="dashboard-content">
    <?php if ($msg = get_flash_message('success')): ?>
    <div class="alert alert-success">✅ <?php echo htmlspecialchars($msg); ?></div>
    <?php endif; ?>

    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap:1.5rem;">
        <?php foreach ($deliveries as $d): ?>
        <div class="table-container" style="padding:1.5rem; display:flex; flex-direction:column; justify-content:space-between;">
            <div>
                <h3 style="font-size:1.1rem; font-weight:700; margin-bottom:0.5rem; color:var(--primary);">Order #<?php echo $d['order_id']; ?></h3>
                <p style="font-size:0.9rem; color:var(--text-muted); margin-bottom:1rem;">Customer: <?php echo htmlspecialchars($d['customer_name']); ?></p>
            </div>
            
            <form method="POST">
                <input type="hidden" name="order_id" value="<?php echo $d['order_id']; ?>">
                <div class="form-group">
                    <label class="form-label" style="font-size:0.85rem;">Delivery Route Notes</label>
                    <textarea class="form-control" name="delivery_notes" rows="3" placeholder="Enter route details..."><?php echo htmlspecialchars($d['delivery_notes'] ?? ''); ?></textarea>
                </div>
                <button type="submit" class="btn btn-primary" style="width:100%; margin-top:0.5rem; font-size:0.9rem;">💾 Save Notes</button>
            </form>
        </div>
        <?php endforeach; ?>
        
        <?php if (empty($deliveries)): ?>
        <div class="table-container" style="padding:3rem; text-align:center; grid-column:1/-1; color:var(--text-muted);">
            <div style="font-size:3rem; margin-bottom:1rem;">📝</div>
            <p>No active deliveries to log route notes for.</p>
        </div>
        <?php endif; ?>
    </div>
</div>
</div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
