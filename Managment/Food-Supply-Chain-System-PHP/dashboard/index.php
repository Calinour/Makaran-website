<?php
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/auth_check.php';

$user = get_logged_in_user();
$isAdminPage = true;

// Load role-specific dashboard data
$stats = [];
if ($user['role'] === 'admin') {
    $stats['total_orders']   = $conn->query("SELECT COUNT(*) FROM orders")->fetchColumn();
    $stats['total_revenue']  = $conn->query("SELECT COALESCE(SUM(amount),0) FROM payments WHERE status='completed'")->fetchColumn();
    $stats['total_products'] = $conn->query("SELECT COUNT(*) FROM products")->fetchColumn();
    $stats['low_stock']      = $conn->query("SELECT COUNT(DISTINCT product_id) FROM inventory_batches WHERE quantity < 10 AND status='active'")->fetchColumn();
    $stats['total_users']    = $conn->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $stats['pending_orders'] = $conn->query("SELECT COUNT(*) FROM orders WHERE status='pending'")->fetchColumn();

    // Recent orders
    $recentOrders = $conn->query("SELECT o.*, u.name as customer_name FROM orders o JOIN users u ON o.customer_id=u.id ORDER BY o.created_at DESC LIMIT 8")->fetchAll(PDO::FETCH_ASSOC);

    // Monthly sales chart data (last 6 months)
    $chartRows = $conn->query("
        SELECT DATE_FORMAT(created_at,'%b') as month, COALESCE(SUM(amount),0) as total
        FROM payments WHERE status='completed' AND created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
        GROUP BY MONTH(created_at), DATE_FORMAT(created_at,'%b')
        ORDER BY MONTH(created_at)
    ")->fetchAll(PDO::FETCH_ASSOC);

} elseif ($user['role'] === 'supplier') {
    $supplierId = $user['id'];
    $stats['active_batches']  = $conn->prepare("SELECT COUNT(*) FROM inventory_batches ib JOIN products p ON ib.product_id=p.id WHERE p.supplier_id=? AND ib.status='active'");
    $stats['active_batches']->execute([$supplierId]); $stats['active_batches'] = $stats['active_batches']->fetchColumn();
    $stats['expiring_soon']   = $conn->prepare("SELECT COUNT(*) FROM inventory_batches ib JOIN products p ON ib.product_id=p.id WHERE p.supplier_id=? AND ib.status='active' AND ib.expiry_date <= DATE_ADD(NOW(), INTERVAL 7 DAY)");
    $stats['expiring_soon']->execute([$supplierId]); $stats['expiring_soon'] = $stats['expiring_soon']->fetchColumn();
    $stats['pending_po']      = $conn->prepare("SELECT COUNT(*) FROM purchase_orders WHERE supplier_id=? AND status='pending'");
    $stats['pending_po']->execute([$supplierId]); $stats['pending_po'] = $stats['pending_po']->fetchColumn();

    $myProducts = $conn->prepare("SELECT p.*, (SELECT SUM(ib.quantity) FROM inventory_batches ib WHERE ib.product_id=p.id AND ib.status='active') as stock FROM products p WHERE p.supplier_id=? ORDER BY p.name LIMIT 10");
    $myProducts->execute([$supplierId]); $myProducts = $myProducts->fetchAll(PDO::FETCH_ASSOC);

} elseif ($user['role'] === 'driver') {
    $driverId = $user['id'];
    $stats['assigned']   = $conn->prepare("SELECT COUNT(*) FROM orders WHERE driver_id=? AND status IN ('assigned','out_for_delivery')");
    $stats['assigned']->execute([$driverId]); $stats['assigned'] = $stats['assigned']->fetchColumn();
    $stats['delivered']  = $conn->prepare("SELECT COUNT(*) FROM orders WHERE driver_id=? AND status='delivered'");
    $stats['delivered']->execute([$driverId]); $stats['delivered'] = $stats['delivered']->fetchColumn();

    $myDeliveries = $conn->prepare("SELECT o.*, u.name as customer_name, u.phone as customer_phone FROM orders o JOIN users u ON o.customer_id=u.id WHERE o.driver_id=? AND o.status IN ('assigned','out_for_delivery') ORDER BY o.updated_at DESC");
    $myDeliveries->execute([$driverId]); $myDeliveries = $myDeliveries->fetchAll(PDO::FETCH_ASSOC);

} elseif ($user['role'] === 'customer') {
    $custId = $user['id'];
    $stats['my_orders']   = $conn->prepare("SELECT COUNT(*) FROM orders WHERE customer_id=?");
    $stats['my_orders']->execute([$custId]); $stats['my_orders'] = $stats['my_orders']->fetchColumn();
    $stats['pending']     = $conn->prepare("SELECT COUNT(*) FROM orders WHERE customer_id=? AND status NOT IN ('delivered','cancelled')");
    $stats['pending']->execute([$custId]); $stats['pending'] = $stats['pending']->fetchColumn();
    $stats['spent']       = $conn->prepare("SELECT COALESCE(SUM(amount),0) FROM payments p JOIN orders o ON p.order_id=o.id WHERE o.customer_id=? AND p.status='completed'");
    $stats['spent']->execute([$custId]); $stats['spent'] = $stats['spent']->fetchColumn();

    $myOrders = $conn->prepare("SELECT * FROM orders WHERE customer_id=? ORDER BY created_at DESC LIMIT 5");
    $myOrders->execute([$custId]); $myOrders = $myOrders->fetchAll(PDO::FETCH_ASSOC);
}

$pageTitle = 'Dashboard';
include __DIR__ . '/../includes/header.php';

function statusBadge($status) {
    $map = [
        'pending'=>'neutral','paid'=>'info','approved'=>'info',
        'assigned'=>'warning','out_for_delivery'=>'warning',
        'delivered'=>'success','cancelled'=>'danger',
        'completed'=>'success','refunded'=>'warning',
        'active'=>'success','expired'=>'danger','damaged'=>'danger',
    ];
    $cls = $map[$status] ?? 'neutral';
    return '<span class="badge badge-'.$cls.'">'.str_replace('_',' ',ucfirst($status)).'</span>';
}
?>
<div class="dashboard-layout">
<?php include __DIR__ . '/../includes/sidebar.php'; ?>
<div class="dashboard-main">

<div class="dashboard-header">
    <div class="dashboard-title">
        <h2>👋 Welcome, <?php echo htmlspecialchars(explode(' ',$user['name'])[0]); ?>!</h2>
        <p><?php echo date('l, F j, Y'); ?> &mdash; <?php echo ROLES[$user['role']]; ?> Dashboard</p>
    </div>
    <div style="display:flex;gap:0.75rem;align-items:center;">
        <?php if ($user['role']==='customer'): ?>
        <a href="<?php echo BASE_URL; ?>customer/products.php" class="btn btn-primary">🛍️ Shop Now</a>
        <?php elseif ($user['role']==='admin'): ?>
        <a href="<?php echo BASE_URL; ?>reports/sales.php" class="btn btn-secondary">📈 View Reports</a>
        <?php endif; ?>
    </div>
</div>

<div class="dashboard-content">

<?php if ($msg = get_flash_message('success')): ?>
<div class="alert alert-success">✅ <?php echo htmlspecialchars($msg); ?></div>
<?php endif; ?>

<!-- ========== ADMIN DASHBOARD ========== -->
<?php if ($user['role'] === 'admin'): ?>
<div class="stats-grid">
    <div class="stat-card primary">
        <div><div class="stat-label">Total Revenue</div><div class="stat-value">$<?php echo number_format($stats['total_revenue'],2); ?></div></div>
        <div class="stat-icon">💰</div>
    </div>
    <div class="stat-card accent">
        <div><div class="stat-label">Total Orders</div><div class="stat-value"><?php echo $stats['total_orders']; ?></div></div>
        <div class="stat-icon">🛒</div>
    </div>
    <div class="stat-card warning">
        <div><div class="stat-label">Pending Orders</div><div class="stat-value"><?php echo $stats['pending_orders']; ?></div></div>
        <div class="stat-icon">⏳</div>
    </div>
    <div class="stat-card danger">
        <div><div class="stat-label">Low Stock Items</div><div class="stat-value"><?php echo $stats['low_stock']; ?></div></div>
        <div class="stat-icon">⚠️</div>
    </div>
    <div class="stat-card info">
        <div><div class="stat-label">Total Products</div><div class="stat-value"><?php echo $stats['total_products']; ?></div></div>
        <div class="stat-icon">📦</div>
    </div>
    <div class="stat-card primary">
        <div><div class="stat-label">Registered Users</div><div class="stat-value"><?php echo $stats['total_users']; ?></div></div>
        <div class="stat-icon">👥</div>
    </div>
</div>

<!-- Sales Chart -->
<?php if (!empty($chartRows)):
    $chartJson = json_encode(array_map(fn($r)=>['label'=>$r['month'],'value'=>(float)$r['total']], $chartRows));
?>
<div class="charts-grid" style="grid-template-columns:1fr;">
<div class="chart-card">
    <div class="table-header-bar" style="padding:0 0 1rem 0;border:none;">
        <div class="table-title">📊 Monthly Revenue (Last 6 Months)</div>
    </div>
    <div class="chart-body" id="sales-chart"></div>
</div>
</div>
<script>window.salesChartData = <?php echo $chartJson; ?>;</script>
<?php endif; ?>

<!-- Recent Orders -->
<div class="table-container">
    <div class="table-header-bar">
        <div class="table-title">🛒 Recent Orders</div>
        <a href="<?php echo BASE_URL; ?>orders/index.php" class="btn btn-secondary" style="font-size:.85rem;padding:.4rem .9rem;">View All</a>
    </div>
    <table class="admin-table" id="recent-orders-table">
        <thead><tr><th>#</th><th>Customer</th><th>Amount</th><th>Status</th><th>Payment</th><th>Date</th><th>Actions</th></tr></thead>
        <tbody>
        <?php foreach ($recentOrders as $ord): ?>
        <tr>
            <td>#<?php echo $ord['id']; ?></td>
            <td><?php echo htmlspecialchars($ord['customer_name']); ?></td>
            <td>$<?php echo number_format($ord['total_amount'],2); ?></td>
            <td><?php echo statusBadge($ord['status']); ?></td>
            <td><?php echo statusBadge($ord['payment_status']); ?></td>
            <td><?php echo date('M j, Y', strtotime($ord['created_at'])); ?></td>
            <td>
                <div class="action-buttons">
                    <a href="<?php echo BASE_URL; ?>orders/details.php?id=<?php echo $ord['id']; ?>" class="btn-icon" title="View">👁️</a>
                    <?php if ($ord['status']==='paid' && !$ord['driver_id']): ?>
                    <a href="<?php echo BASE_URL; ?>orders/assign_driver.php?id=<?php echo $ord['id']; ?>" class="btn-icon edit" title="Assign Driver">🚚</a>
                    <?php endif; ?>
                </div>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Admin Quick Links -->
<div class="stats-grid" style="grid-template-columns:repeat(auto-fit,minmax(160px,1fr));margin-top:0;">
    <?php $links=[['href'=>'products/create.php','icon'=>'➕','label'=>'Add Product'],['href'=>'users/create.php','icon'=>'👤','label'=>'Add User'],['href'=>'inventory/add_stock.php','icon'=>'📥','label'=>'Add Stock'],['href'=>'reports/low_stock.php','icon'=>'⚠️','label'=>'Low Stock'],['href'=>'reports/expired_products.php','icon'=>'🗑️','label'=>'Expired'],['href'=>'reports/sales.php','icon'=>'📈','label'=>'Sales Report']];
    foreach ($links as $l): ?>
    <a href="<?php echo BASE_URL.$l['href']; ?>" class="stat-card" style="text-decoration:none;cursor:pointer;flex-direction:column;gap:.5rem;justify-content:center;text-align:center;">
        <div style="font-size:1.8rem;"><?php echo $l['icon']; ?></div>
        <div style="font-weight:600;font-size:.95rem;"><?php echo $l['label']; ?></div>
    </a>
    <?php endforeach; ?>
</div>

<!-- ========== SUPPLIER DASHBOARD ========== -->
<?php elseif ($user['role'] === 'supplier'): ?>
<div class="stats-grid">
    <div class="stat-card primary"><div><div class="stat-label">Active Batches</div><div class="stat-value"><?php echo $stats['active_batches']; ?></div></div><div class="stat-icon">📦</div></div>
    <div class="stat-card warning"><div><div class="stat-label">Expiring in 7 Days</div><div class="stat-value"><?php echo $stats['expiring_soon']; ?></div></div><div class="stat-icon">⚠️</div></div>
    <div class="stat-card info"><div><div class="stat-label">Open Purchase Orders</div><div class="stat-value"><?php echo $stats['pending_po']; ?></div></div><div class="stat-icon">📋</div></div>
</div>
<div class="table-container">
    <div class="table-header-bar">
        <div class="table-title">📦 My Products & Stock</div>
        <a href="<?php echo BASE_URL; ?>inventory/add_stock.php" class="btn btn-primary" style="font-size:.85rem;padding:.4rem .9rem;">+ Add Stock</a>
    </div>
    <table class="admin-table">
        <thead><tr><th>Product</th><th>Price</th><th>Current Stock</th><th>Actions</th></tr></thead>
        <tbody>
        <?php foreach ($myProducts as $p): ?>
        <tr>
            <td><?php echo htmlspecialchars($p['name']); ?></td>
            <td>$<?php echo number_format($p['price'],2); ?></td>
            <td><?php $st=$p['stock']??0; echo '<span class="badge badge-'.($st<10?'danger':($st<30?'warning':'success')).'">'.$st.' units</span>'; ?></td>
            <td><a href="<?php echo BASE_URL; ?>inventory/batches.php?product_id=<?php echo $p['id']; ?>" class="btn-icon">📋</a></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- ========== DRIVER DASHBOARD ========== -->
<?php elseif ($user['role'] === 'driver'): ?>
<div class="stats-grid">
    <div class="stat-card warning"><div><div class="stat-label">Active Deliveries</div><div class="stat-value"><?php echo $stats['assigned']; ?></div></div><div class="stat-icon">🚚</div></div>
    <div class="stat-card primary"><div><div class="stat-label">Completed Deliveries</div><div class="stat-value"><?php echo $stats['delivered']; ?></div></div><div class="stat-icon">✅</div></div>
</div>
<div class="table-container">
    <div class="table-header-bar"><div class="table-title">🚚 My Active Deliveries</div></div>
    <table class="admin-table">
        <thead><tr><th>Order #</th><th>Customer</th><th>Phone</th><th>Address</th><th>Status</th><th>Action</th></tr></thead>
        <tbody>
        <?php foreach ($myDeliveries as $d): ?>
        <tr>
            <td>#<?php echo $d['id']; ?></td>
            <td><?php echo htmlspecialchars($d['customer_name']); ?></td>
            <td><?php echo htmlspecialchars($d['customer_phone']); ?></td>
            <td><?php echo htmlspecialchars(substr($d['shipping_address'],0,40)); ?>...</td>
            <td><?php echo statusBadge($d['status']); ?></td>
            <td>
                <a href="<?php echo BASE_URL; ?>deliveries/update_status.php?id=<?php echo $d['id']; ?>" class="btn btn-primary" style="font-size:.8rem;padding:.3rem .7rem;">Update Status</a>
            </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($myDeliveries)): ?><tr><td colspan="6" style="text-align:center;color:var(--text-muted);padding:2rem;">No active deliveries assigned.</td></tr><?php endif; ?>
        </tbody>
    </table>
</div>

<!-- ========== CUSTOMER DASHBOARD ========== -->
<?php elseif ($user['role'] === 'customer'): ?>
<div class="stats-grid">
    <div class="stat-card primary"><div><div class="stat-label">Total Orders</div><div class="stat-value"><?php echo $stats['my_orders']; ?></div></div><div class="stat-icon">🛒</div></div>
    <div class="stat-card warning"><div><div class="stat-label">Active Orders</div><div class="stat-value"><?php echo $stats['pending']; ?></div></div><div class="stat-icon">⏳</div></div>
    <div class="stat-card info"><div><div class="stat-label">Total Spent</div><div class="stat-value">$<?php echo number_format($stats['spent'],2); ?></div></div><div class="stat-icon">💳</div></div>
</div>
<div style="display:flex;gap:1rem;margin-bottom:1.5rem;flex-wrap:wrap;">
    <a href="<?php echo BASE_URL; ?>customer/products.php" class="btn btn-primary">🛍️ Browse Products</a>
    <a href="<?php echo BASE_URL; ?>customer/cart.php" class="btn btn-secondary">🛒 View Cart</a>
</div>
<div class="table-container">
    <div class="table-header-bar">
        <div class="table-title">📋 Recent Orders</div>
        <a href="<?php echo BASE_URL; ?>customer/orders.php" class="btn btn-secondary" style="font-size:.85rem;padding:.4rem .9rem;">All Orders</a>
    </div>
    <table class="admin-table">
        <thead><tr><th>#</th><th>Amount</th><th>Status</th><th>Date</th><th>Actions</th></tr></thead>
        <tbody>
        <?php foreach ($myOrders as $ord): ?>
        <tr>
            <td>#<?php echo $ord['id']; ?></td>
            <td>$<?php echo number_format($ord['total_amount'],2); ?></td>
            <td><?php echo statusBadge($ord['status']); ?></td>
            <td><?php echo date('M j, Y', strtotime($ord['created_at'])); ?></td>
            <td><a href="<?php echo BASE_URL; ?>customer/track_order.php?id=<?php echo $ord['id']; ?>" class="btn btn-secondary" style="font-size:.8rem;padding:.3rem .7rem;">Track</a></td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($myOrders)): ?><tr><td colspan="5" style="text-align:center;color:var(--text-muted);padding:2rem;">No orders yet. <a href="<?php echo BASE_URL; ?>customer/products.php" style="color:var(--primary);">Start shopping!</a></td></tr><?php endif; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

</div><!-- dashboard-content -->
</div><!-- dashboard-main -->
</div><!-- dashboard-layout -->
<?php include __DIR__ . '/../includes/footer.php'; ?>
