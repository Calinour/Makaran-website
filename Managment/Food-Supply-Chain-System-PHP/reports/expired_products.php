<?php
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/auth_check.php';
check_role('admin');
$isAdminPage = true;

// Fetch active but expired batches
$expired = $conn->query("
    SELECT ib.*, p.name as product_name, p.sku, u.name as supplier_name
    FROM inventory_batches ib
    JOIN products p ON ib.product_id = p.id
    LEFT JOIN users u ON p.supplier_id = u.id
    WHERE ib.status = 'active' AND ib.expiry_date < NOW()
    ORDER BY ib.expiry_date ASC
")->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Expired Batches Report';
include __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-layout">
<?php include __DIR__ . '/../includes/sidebar.php'; ?>
<div class="dashboard-main">
<div class="dashboard-header">
    <div class="dashboard-title">
        <h2>🗑️ Expired Stock Batches Auditor</h2>
        <p>Audit and clear expired batches from active inventory systems</p>
    </div>
</div>

<div class="dashboard-content">
    <?php if ($msg = get_flash_message('success')): ?>
    <div class="alert alert-success">✅ <?php echo htmlspecialchars($msg); ?></div>
    <?php endif; ?>

    <div class="table-container">
        <div class="table-header-bar">
            <div class="table-title">Expired Inventory Items (<?php echo count($expired); ?> batches)</div>
        </div>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Batch ID</th>
                    <th>Product</th>
                    <th>SKU</th>
                    <th>Supplier</th>
                    <th>Quantity Affected</th>
                    <th>Expired Date</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($expired as $row): ?>
                <tr style="background: rgba(239, 68, 68, 0.04);">
                    <td style="font-family:monospace; font-weight:600;"><?php echo htmlspecialchars($row['batch_number']); ?></td>
                    <td style="font-weight:600;"><?php echo htmlspecialchars($row['product_name']); ?></td>
                    <td style="font-family:monospace;"><?php echo htmlspecialchars($row['sku']); ?></td>
                    <td><?php echo htmlspecialchars($row['supplier_name'] ?? '—'); ?></td>
                    <td style="font-weight:700; color:var(--danger);"><?php echo $row['quantity']; ?> units</td>
                    <td style="color:var(--danger); font-weight:600;"><?php echo date('M j, Y', strtotime($row['expiry_date'])); ?></td>
                    <td>
                        <a href="<?php echo BASE_URL; ?>inventory/damaged.php?flag=<?php echo $row['id']; ?>" class="btn btn-danger" style="font-size:0.8rem; padding:0.3rem 0.8rem;" data-confirm="Remove this expired batch from inventory?">🗑️ Remove Stock</a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($expired)): ?>
                <tr>
                    <td colspan="7" style="text-align:center;color:var(--primary);padding:2rem;">🎉 No expired active batches found in inventory. Keep it fresh!</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
