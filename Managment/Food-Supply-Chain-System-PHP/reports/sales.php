<?php
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/auth_check.php';
check_role('admin');
$isAdminPage = true;

// Total completed payments amount
$totalSales = (float)$conn->query("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE status = 'completed'")->fetchColumn();

// Total count of paid orders
$totalPaidOrders = (int)$conn->query("SELECT COUNT(*) FROM orders WHERE payment_status = 'paid'")->fetchColumn();

// Sales by payment method
$methodBreakdown = $conn->query("
    SELECT payment_method, COUNT(*) as count, SUM(amount) as revenue 
    FROM payments 
    WHERE status = 'completed' 
    GROUP BY payment_method
")->fetchAll(PDO::FETCH_ASSOC);

// Daily revenue of past 30 days
$dailySales = $conn->query("
    SELECT DATE(created_at) as date, SUM(amount) as revenue 
    FROM payments 
    WHERE status = 'completed' AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY DATE(created_at)
    ORDER BY DATE(created_at) ASC
")->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Sales Reports';
include __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-layout">
<?php include __DIR__ . '/../includes/sidebar.php'; ?>
<div class="dashboard-main">
<div class="dashboard-header">
    <div class="dashboard-title">
        <h2>📈 Revenue & Sales Reporting</h2>
        <p>Analyze revenue growth, transactions history, and payment breakdowns</p>
    </div>
</div>

<div class="dashboard-content">
    <!-- Stats widgets -->
    <div class="stats-grid" style="margin-bottom:1.5rem;">
        <div class="stat-card primary">
            <div>
                <div class="stat-label">Gross Sales Revenue</div>
                <div class="stat-value">$<?php echo number_format($totalSales, 2); ?></div>
            </div>
            <div class="stat-icon">💰</div>
        </div>
        <div class="stat-card accent">
            <div>
                <div class="stat-label">Total Completed Orders</div>
                <div class="stat-value"><?php echo $totalPaidOrders; ?></div>
            </div>
            <div class="stat-icon">🛒</div>
        </div>
        <div class="stat-card info">
            <div>
                <div class="stat-label">Average Order Value</div>
                <div class="stat-value">$<?php echo $totalPaidOrders > 0 ? number_format($totalSales / $totalPaidOrders, 2) : '0.00'; ?></div>
            </div>
            <div class="stat-icon">📈</div>
        </div>
    </div>

    <!-- Daily Revenue Graph -->
    <?php if (!empty($dailySales)): 
        $chartData = json_encode(array_map(fn($r) => ['label' => date('M j', strtotime($r['date'])), 'value' => (float)$r['revenue']], $dailySales));
    ?>
    <div class="charts-grid" style="grid-template-columns: 1fr; margin-bottom: 2rem;">
        <div class="chart-card">
            <div class="table-header-bar" style="padding:0 0 1rem 0; border:none;">
                <div class="table-title">📊 Daily Sales Growth (Past 30 Days)</div>
            </div>
            <div class="chart-body" id="sales-growth-chart"></div>
        </div>
    </div>
    <script>window.salesGrowthData = <?php echo $chartData; ?>;</script>
    <?php endif; ?>

    <!-- Payment Methods Breakdown -->
    <div class="table-container">
        <div class="table-header-bar">
            <div class="table-title">💳 Sales Breakdown by Payment Method</div>
        </div>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Payment Method</th>
                    <th>Transactions Count</th>
                    <th>Total Revenue</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($methodBreakdown as $row): ?>
                <tr>
                    <td style="font-weight:600; text-transform:uppercase;"><?php echo str_replace('_', ' ', $row['payment_method']); ?></td>
                    <td><?php echo $row['count']; ?> checkouts</td>
                    <td style="font-weight:700; color:var(--primary);">$<?php echo number_format($row['revenue'], 2); ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if(empty($methodBreakdown)): ?>
                <tr><td colspan="3" style="text-align:center;color:var(--text-muted);">No sales data available.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
