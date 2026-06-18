<?php
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/auth_check.php';
check_role('admin');
$isAdminPage = true;

$supplierId = (int)($_GET['supplier_id'] ?? 0);
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ? AND role = 'supplier'");
$stmt->execute([$supplierId]);
$supplier = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$supplier) {
    set_flash_message('error', 'Supplier not found.');
    header('Location: ' . BASE_URL . 'suppliers/index.php');
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rating = (int)($_POST['rating'] ?? 5);
    $review = trim($_POST['review'] ?? '');
    $ontimeRate = (float)($_POST['ontime_delivery_rate'] ?? 100.00);
    
    if ($rating < 1 || $rating > 5) {
        $error = 'Rating must be between 1 and 5 stars.';
    } else {
        $conn->prepare("INSERT INTO supplier_performance (supplier_id, rating, review, ontime_delivery_rate) VALUES (?, ?, ?, ?)")
             ->execute([$supplierId, $rating, $review, $ontimeRate]);
        
        set_flash_message('success', 'Performance rating logged successfully.');
        header('Location: ' . BASE_URL . 'suppliers/performance.php?supplier_id=' . $supplierId);
        exit();
    }
}

// Fetch performance history
$perfStmt = $conn->prepare("SELECT * FROM supplier_performance WHERE supplier_id = ? ORDER BY created_at DESC");
$perfStmt->execute([$supplierId]);
$performanceLogs = $perfStmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate Stats
$avgRating = 0;
$avgOntime = 0;
$reviewCount = count($performanceLogs);

if ($reviewCount > 0) {
    $avgRating = array_sum(array_column($performanceLogs, 'rating')) / $reviewCount;
    $avgOntime = array_sum(array_column($performanceLogs, 'ontime_delivery_rate')) / $reviewCount;
}

$pageTitle = htmlspecialchars($supplier['name']) . ' - Performance Metrics';
include __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-layout">
<?php include __DIR__ . '/../includes/sidebar.php'; ?>
<div class="dashboard-main">
<div class="dashboard-header">
    <div class="dashboard-title">
        <h2>📈 Supplier Performance Audit</h2>
        <p><?php echo htmlspecialchars($supplier['name']); ?></p>
    </div>
    <a href="<?php echo BASE_URL; ?>suppliers/index.php" class="btn btn-secondary">← Back to Directory</a>
</div>

<div class="dashboard-content">
    <?php if ($msg = get_flash_message('success')): ?>
    <div class="alert alert-success">✅ <?php echo htmlspecialchars($msg); ?></div>
    <?php endif; ?>
    <?php if ($error): ?><div class="alert alert-error">⚠️ <?php echo htmlspecialchars($error); ?></div><?php endif; ?>

    <!-- Summary Metrics -->
    <div class="stats-grid" style="margin-bottom:1.5rem;">
        <div class="stat-card primary">
            <div>
                <div class="stat-label">Average Rating</div>
                <div class="stat-value"><?php echo $reviewCount > 0 ? number_format($avgRating, 1) . ' ⭐' : 'Unrated'; ?></div>
            </div>
            <div class="stat-icon">⭐</div>
        </div>
        <div class="stat-card accent">
            <div>
                <div class="stat-label">On-Time delivery Rate</div>
                <div class="stat-value"><?php echo $reviewCount > 0 ? number_format($avgOntime, 1) . '%' : '100%'; ?></div>
            </div>
            <div class="stat-icon">⏰</div>
        </div>
        <div class="stat-card info">
            <div>
                <div class="stat-label">Performance Reviews</div>
                <div class="stat-value"><?php echo $reviewCount; ?></div>
            </div>
            <div class="stat-icon">📝</div>
        </div>
    </div>

    <div style="display:grid; grid-template-columns: 2fr 1fr; gap:1.5rem; align-items:start;">
        <!-- Left: Performance Logs -->
        <div class="table-container">
            <div class="table-header-bar"><div class="table-title">Performance Log History</div></div>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Rating</th>
                        <th>On-Time Rate</th>
                        <th>Review Comments</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($performanceLogs as $log): ?>
                    <tr>
                        <td><?php echo date('M j, Y', strtotime($log['created_at'])); ?></td>
                        <td style="font-weight:700; color:var(--primary);"><?php echo $log['rating']; ?> / 5 ⭐</td>
                        <td style="font-weight:600;"><?php echo number_format($log['ontime_delivery_rate'], 1); ?>%</td>
                        <td style="color:var(--text-muted); font-size:0.9rem;"><?php echo htmlspecialchars($log['review'] ?? '—'); ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($performanceLogs)): ?>
                    <tr>
                        <td colspan="4" style="text-align:center;color:var(--text-muted);padding:2rem;">No performance reviews logged yet.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Right: Logging Form -->
        <div class="table-container" style="padding:1.5rem;">
            <h3 style="font-size:1.1rem; font-weight:700; margin-bottom:1.25rem;">📝 Audit Supplier</h3>
            <form method="POST">
                <div class="form-group">
                    <label class="form-label" for="rating">Supplier Rating (1-5) *</label>
                    <select class="form-control" name="rating" id="rating" required>
                        <option value="5">⭐⭐⭐⭐⭐ (5 - Excellent)</option>
                        <option value="4">⭐⭐⭐⭐ (4 - Good)</option>
                        <option value="3">⭐⭐⭐ (3 - Satisfactory)</option>
                        <option value="2">⭐⭐ (2 - Poor)</option>
                        <option value="1">⭐ (1 - Unacceptable)</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="ontime_delivery_rate">On-Time Delivery Rate (%) *</label>
                    <input class="form-control" type="number" step="0.1" name="ontime_delivery_rate" id="ontime_delivery_rate" min="0" max="100" value="100.0" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="review">Audit Comments & Review</label>
                    <textarea class="form-control" name="review" id="review" rows="4" placeholder="Mention product quality, packaging integrity, response speeds..."></textarea>
                </div>
                
                <button type="submit" class="btn btn-primary" style="width:100%; margin-top:0.5rem;">💾 Log Audit Record</button>
            </form>
        </div>
    </div>
</div>
</div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
