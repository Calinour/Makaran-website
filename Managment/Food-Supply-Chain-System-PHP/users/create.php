<?php
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/auth_check.php';
check_role('admin');
$isAdminPage = true;

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'customer';
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    
    if (empty($name) || empty($email) || empty($password) || empty($role)) {
        $error = 'Name, email, password, and role are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email address format.';
    } elseif (!array_key_exists($role, ROLES)) {
        $error = 'Invalid role selected.';
    } else {
        $chk = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $chk->execute([$email]);
        if ($chk->fetch()) {
            $error = 'A user with this email already exists.';
        } else {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $conn->prepare("INSERT INTO users (name, email, password, role, phone, address) VALUES (?, ?, ?, ?, ?, ?)")
                 ->execute([$name, $email, $hashedPassword, $role, $phone, $address]);
            
            set_flash_message('success', 'User account created successfully.');
            header('Location: ' . BASE_URL . 'users/index.php');
            exit();
        }
    }
}

$pageTitle = 'Add System User';
include __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-layout">
<?php include __DIR__ . '/../includes/sidebar.php'; ?>
<div class="dashboard-main">
<div class="dashboard-header">
    <div class="dashboard-title">
        <h2>➕ Register New User Profile</h2>
        <p>Create credentials for any admin, supplier, driver, or customer</p>
    </div>
    <a href="<?php echo BASE_URL; ?>users/index.php" class="btn btn-secondary">← Back</a>
</div>

<div class="dashboard-content">
    <?php if ($error): ?><div class="alert alert-error">⚠️ <?php echo htmlspecialchars($error); ?></div><?php endif; ?>
    
    <div class="table-container" style="max-width:600px;padding:2rem;">
        <form method="POST">
            <div class="form-group">
                <label class="form-label" for="name">Full Name *</label>
                <input class="form-control" type="text" name="name" id="name" value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" required>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="email">Email Address *</label>
                <input class="form-control" type="email" name="email" id="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="password">Password *</label>
                <input class="form-control" type="password" name="password" id="password" required>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="role">System Access Role *</label>
                <select class="form-control" name="role" id="role" required>
                    <?php foreach (ROLES as $key => $val): ?>
                    <option value="<?php echo $key; ?>" <?php echo (($_POST['role'] ?? 'customer') === $key) ? 'selected' : ''; ?>><?php echo $val; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="phone">Phone Number</label>
                <input class="form-control" type="text" name="phone" id="phone" value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
            </div>
            
            <div class="form-group">
                <label class="form-label" for="address">Mailing Address / Details</label>
                <textarea class="form-control" name="address" id="address" rows="3"><?php echo htmlspecialchars($_POST['address'] ?? ''); ?></textarea>
            </div>
            
            <div style="margin-top:1.5rem; display:flex; gap:1rem;">
                <button type="submit" class="btn btn-primary">✅ Register User</button>
                <a href="<?php echo BASE_URL; ?>users/index.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
</div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
