<?php
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/auth_check.php';
check_role('admin');
$isAdminPage = true;

$search = trim($_GET['search'] ?? '');
$roleFilter = $_GET['role'] ?? '';

$sql = "SELECT id, name, email, role, phone, created_at FROM users WHERE 1";
$params = [];

if ($search) {
    $sql .= " AND (name LIKE ? OR email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($roleFilter) {
    $sql .= " AND role = ?";
    $params[] = $roleFilter;
}

$sql .= " ORDER BY name ASC";
$stmt = $conn->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Users Management';
include __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-layout">
<?php include __DIR__ . '/../includes/sidebar.php'; ?>
<div class="dashboard-main">
<div class="dashboard-header">
    <div class="dashboard-title">
        <h2>👥 System Users Management</h2>
        <p>Manage access credentials, role capabilities, and contact profiles</p>
    </div>
    <a href="<?php echo BASE_URL; ?>users/create.php" class="btn btn-primary">+ Register User</a>
</div>

<div class="dashboard-content">
    <?php if ($msg = get_flash_message('success')): ?>
    <div class="alert alert-success">✅ <?php echo htmlspecialchars($msg); ?></div>
    <?php endif; ?>
    <?php if ($msg = get_flash_message('error')): ?>
    <div class="alert alert-error">⚠️ <?php echo htmlspecialchars($msg); ?></div>
    <?php endif; ?>

    <!-- Filter Bar -->
    <form method="GET" style="display:flex;gap:1rem;flex-wrap:wrap;margin-bottom:1.5rem;">
        <input class="form-control" style="max-width:250px;" type="text" name="search" placeholder="🔍 Search name or email..." value="<?php echo htmlspecialchars($search); ?>">
        <select class="form-control" style="max-width:180px;" name="role">
            <option value="">All Roles</option>
            <?php foreach (ROLES as $key => $val): ?>
            <option value="<?php echo $key; ?>" <?php echo $roleFilter === $key ? 'selected' : ''; ?>><?php echo $val; ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-primary" style="padding:.6rem 1.2rem;">Filter</button>
        <a href="<?php echo BASE_URL; ?>users/index.php" class="btn btn-secondary" style="padding:.6rem 1.2rem;">Clear</a>
    </form>

    <div class="table-container">
        <div class="table-header-bar">
            <div class="table-title">User Accounts (<?php echo count($users); ?> profiles)</div>
        </div>
        <table class="admin-table" id="users-table">
            <thead>
                <tr>
                    <th>Full Name</th>
                    <th>Email Address</th>
                    <th>System Role</th>
                    <th>Phone</th>
                    <th>Registered Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($users as $u): ?>
            <tr>
                <td style="font-weight:600;"><?php echo htmlspecialchars($u['name']); ?></td>
                <td><?php echo htmlspecialchars($u['email']); ?></td>
                <td>
                    <?php 
                    $roleBadges = ['admin' => 'success', 'supplier' => 'info', 'driver' => 'warning', 'customer' => 'neutral'];
                    $badgeCls = $roleBadges[$u['role']] ?? 'neutral';
                    ?>
                    <span class="badge badge-<?php echo $badgeCls; ?>"><?php echo ROLES[$u['role']] ?? $u['role']; ?></span>
                </td>
                <td><?php echo htmlspecialchars($u['phone'] ?? '—'); ?></td>
                <td><?php echo date('M j, Y', strtotime($u['created_at'])); ?></td>
                <td>
                    <div class="action-buttons">
                        <a href="<?php echo BASE_URL; ?>users/edit.php?id=<?php echo $u['id']; ?>" class="btn-icon edit" title="Edit">✏️</a>
                        <?php if ($u['id'] != $_SESSION['user']['id']): ?>
                        <a href="<?php echo BASE_URL; ?>users/delete.php?id=<?php echo $u['id']; ?>" class="btn-icon delete" title="Delete" data-confirm="Are you sure you want to delete this user profile permanently?">🗑️</a>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
</div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
