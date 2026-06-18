<?php
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/auth_check.php';
check_role('admin');
$isAdminPage = true;

// Total deliveries count
$totalDeliveries = (int)$conn->query("SELECT COUNT(*) FROM orders WHERE driver_id IS NOT NULL")->fetchColumn();

// Status counts
$completed = (int)$conn->query("SELECT COUNT(*) FROM orders WHERE status='delivered'")->fetchColumn();
$outForDelivery = (int)$conn->query("SELECT COUNT(*) FROM orders WHERE status='out_for_delivery'")->fetchColumn();
$assigned = (int)$conn->query("SELECT COUNT(*) FROM orders WHERE status='assigned'")->fetchColumn();

// Deliveries by driver breakdown
$driverStats = $conn->query("
    SELECT d.name as driver_name, d.email as driver_email,
    COUNT(o.id) as total_runs,
    SUM(CASE WHEN o.status='delivered' THEN 1 ELSE 0 END) as completed_runs
    FROM users d
    LEFT JOIN orders o ON o.driver_id = d.id
    WHERE d.role = 'driver'
    GROUP BY d.id, d.name, d.email
    ORDER BY total_runs DESC
")->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Logistics Deliveries Report';
include __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-layout">
<?php include __DIR__ . '/../includes/sidebar.php'; ?>
<div class="dashboard-main">
<div class="dashboard-header">
    <div class="dashboard-title">
        <h2>🚚 Logistics & Deliveries Analytics</h2>
        <p>Monitor courier runs, active dispatch statuses, and driver work rates</p>
    </div>
</div>

<div class="dashboard-content">
    <!-- Stats widgets -->
    <div class="stats-grid" style="margin-bottom:1.5rem;">
        <div class="stat-card primary">
            <div>
                <div class="stat-label">Total Assigned Logistics Runs</div>
                <div class="stat-value"><?php echo $totalDeliveries; ?></div>
            </div>
            <div class="stat-icon">📦</div>
        </div>
        <div class="stat-card success">
            <div>
                <div class="stat-label">Successful Deliveries</div>
                <div class="stat-value"><?php echo $completed; ?></div>
            </div>
            <div class="stat-icon">✅</div>
        </div>
        <div class="stat-card warning">
            <div>
                <div class="stat-label">Out for Delivery (Transit)</div>
                <div class="stat-value"><?php echo $outForDelivery; ?></div>
            </div>
            <div class="stat-icon">🚚</div>
        </div>
        <div class="stat-card info">
            <div>
                <div class="stat-label">Assigned (Pending Transit)</div>
                <div class="stat-value"><?php echo $assigned; ?></div>
            </div>
            <div class="stat-icon">⏳</div>
        </div>
    </div>

    <!-- Drivers Table -->
    <div class="table-container">
        <div class="table-header-bar">
            <div class="table-title">🚚 Courier Run Stats & Completion Rates</div>
        </div>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Driver Name</th>
                    <th>Email</th>
                    <th>Total assigned Runs</th>
                    <th>Completed Runs</th>
                    <th>Delivery Success Rate</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($driverStats as $row): 
                    $rate = $row['total_runs'] > 0 ? ($row['completed_runs'] / $row['total_runs']) * 100 : 0;
                ?>
                <tr>
                    <td style="font-weight:600;"><?php echo htmlspecialchars($row['driver_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['driver_email']); ?></td>
                    <td style="font-weight:700;"><?php echo $row['total_runs']; ?> runs</td>
                    <td><?php echo $row['completed_runs']; ?> runs</td>
                    <td style="font-weight:700; color:var(--primary);">
                        <?php echo number_format($rate, 1); ?>%
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($driverStats)): ?>
                <tr><td colspan="5" style="text-align:center;color:var(--text-muted);">No logistics couriers registered.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
