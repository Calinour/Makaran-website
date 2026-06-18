<?php
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/auth_check.php';
check_role('admin');
$isAdminPage = true;

$suppliers = $conn->query("
    SELECT u.*, 
    (SELECT COUNT(*) FROM products WHERE supplier_id = u.id) as product_count,
    (SELECT COALESCE(AVG(rating), 0) FROM supplier_performance WHERE supplier_id = u.id) as avg_rating
    FROM users u 
    WHERE u.role = 'supplier' 
    ORDER BY u.name
")->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Suppliers Directory';
include __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-layout">
<?php include __DIR__ . '/../includes/sidebar.php'; ?>
<div class="dashboard-main">
<div class="dashboard-header">
    <div class="dashboard-title">
        <h2>🚜 Supplier Partners Directory</h2>
        <p>Manage supplier profiles, stock catalog connections, and performance records</p>
    </div>
    <a href="<?php echo BASE_URL; ?>suppliers/create.php" class="btn btn-primary">+ Register Supplier</a>
</div>

<div class="dashboard-content">
    <?php if ($msg = get_flash_message('success')): ?>
    <div class="alert alert-success">✅ <?php echo htmlspecialchars($msg); ?></div>
    <?php endif; ?>

    <div class="table-container">
        <div class="table-header-bar">
            <div class="table-title">Supplier Profiles (<?php echo count($suppliers); ?>)</div>
        </div>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Supplier Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Products Supplied</th>
                    <th>Average Rating</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($suppliers as $s): ?>
            <tr>
                <td style="font-weight:600;"><?php echo htmlspecialchars($s['name']); ?></td>
                <td><?php echo htmlspecialchars($s['email']); ?></td>
                <td><?php echo htmlspecialchars($s['phone'] ?? 'N/A'); ?></td>
                <td><span class="badge badge-info"><?php echo $s['product_count']; ?> products</span></td>
                <td>
                    <div style="color:var(--primary); font-weight:700;">
                        <?php 
                        $rating = (float)$s['avg_rating'];
                        if ($rating > 0) {
                            echo number_format($rating, 1) . ' ⭐';
                        } else {
                            echo '<span style="color:var(--text-muted); font-weight:normal;">Unrated</span>';
                        }
                        ?>
                    </div>
                </td>
                <td>
                    <div class="action-buttons">
                        <a href="<?php echo BASE_URL; ?>suppliers/edit.php?id=<?php echo $s['id']; ?>" class="btn-icon edit" title="Edit Profile">✏️</a>
                        <a href="<?php echo BASE_URL; ?>suppliers/performance.php?supplier_id=<?php echo $s['id']; ?>" class="btn-icon" title="View Performance Metrics">📈</a>
                        <a href="<?php echo BASE_URL; ?>suppliers/purchase_orders.php?supplier_id=<?php echo $s['id']; ?>" class="btn-icon" style="color:var(--warning);" title="Purchase Orders">📋</a>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($suppliers)): ?>
            <tr>
                <td colspan="6" style="text-align:center;color:var(--text-muted);padding:2rem;">No supplier profiles registered.</td>
            </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
