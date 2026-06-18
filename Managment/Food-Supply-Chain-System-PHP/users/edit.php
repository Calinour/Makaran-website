<?php
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/auth_check.php';
check_role('admin');
$isAdminPage = true;

$userId = (int)($_GET['id'] ?? 0);
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$userProfile = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$userProfile) {
    set_flash_message('error', 'User profile not found.');
    header('Location: ' . BASE_URL . 'users/index.php');
    exit();
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role = $_POST['role'] ?? 'customer';
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($name) || empty($email) || empty($role)) {
        $error = 'Name, email, and role are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email address format.';
    } elseif (!array_key_exists($role, ROLES)) {
        $error = 'Invalid role selected.';
    } else {
        // Check email uniqueness
        $chk = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $chk->execute([$email, $userId]);
        if ($chk->fetch()) {
            $error = 'A user with this email address already exists.';
        } else {
            if (!empty($password)) {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $up = $conn->prepare("UPDATE users SET name = ?, email = ?, password = ?, role = ?, phone = ?, address = ? WHERE id = ?");
                $up->execute([$name, $email, $hashedPassword, $role, $phone, $address, $userId]);
            } else {
                $up = $conn->prepare("UPDATE users SET name = ?, email = ?, role = ?, phone = ?, address = ? WHERE id = ?");
                $up->execute([$name, $email, $role, $phone, $address, $userId]);
            }
            
            set_flash_message('success', 'User profile updated successfully.');
            header('Location: ' . BASE_URL . 'users/index.php');
            exit();
        }
    }
}

$pageTitle = 'Edit System User';
include __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-layout">
<?php include __DIR__ . '/../includes/sidebar.php'; ?>
<div class="dashboard-main">
<div class="dashboard-header">
    <div class="dashboard-title">
        <h2>✏️ Edit User Profile</h2>
        <p><?php echo htmlspecialchars($userProfile['name']); ?> &mdash; <?php echo ROLES[$userProfile['role']]; ?></p>
    </div>
    <a href="<?php echo BASE_URL; ?>users/index.php" class="btn btn-secondary">← Back</a>
</div>

<div class="dashboard-content">
    <?php if ($error): ?><div class="alert alert-error">⚠️ <?php echo htmlspecialchars($error); ?></div><?php endif; ?>
    
    <div class="table-container" style="max-width:600px;padding:2rem;">
        <form method="POST">
            <div class="form-group">
                <label class="form-label" for="name">Full Name *</label>
                <input class="form-control" type="text" name="name" id="name" value="<?php echo htmlspecialchars($_POST['name'] ?? $userProfile['name']); ?>" required>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="email">Email Address *</label>
                <input class="form-control" type="email" name="email" id="email" value="<?php echo htmlspecialchars($_POST['email'] ?? $userProfile['email']); ?>" required>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="password">Password (leave blank to keep current)</label>
                <input class="form-control" type="password" name="password" id="password">
            </div>
            
            <div class="form-group">
                <label class="form-label" for="role">System Access Role *</label>
                <select class="form-control" name="role" id="role" required <?php echo ($userProfile['id'] == $_SESSION['user']['id']) ? 'disabled' : ''; ?>>
                    <?php foreach (ROLES as $key => $val): ?>
                    <option value="<?php echo $key; ?>" <?php echo (($_POST['role'] ?? $userProfile['role']) === $key) ? 'selected' : ''; ?>><?php echo $val; ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if ($userProfile['id'] == $_SESSION['user']['id']): ?>
                <input type="hidden" name="role" value="<?php echo $userProfile['role']; ?>">
                <p style="font-size:0.8rem; color:var(--text-muted); margin-top:0.25rem;">You cannot change your own role to prevent system lockouts.</p>
                <?php endif; ?>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="phone">Phone Number</label>
                <input class="form-control" type="text" name="phone" id="phone" value="<?php echo htmlspecialchars($_POST['phone'] ?? $userProfile['phone']); ?>">
            </div>
            
            <div class="form-group">
                <label class="form-label" for="address">Mailing Address / Details</label>
                <textarea class="form-control" name="address" id="address" rows="3"><?php echo htmlspecialchars($_POST['address'] ?? $userProfile['address']); ?></textarea>
            </div>
            
            <div style="margin-top:1.5rem; display:flex; gap:1rem;">
                <button type="submit" class="btn btn-primary">💾 Save Changes</button>
                <a href="<?php echo BASE_URL; ?>users/index.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
</div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
