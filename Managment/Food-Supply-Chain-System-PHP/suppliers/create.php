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
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($name) || empty($email) || empty($password)) {
        $error = 'Name, email, and password are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        // Check if email exists
        $chk = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $chk->execute([$email]);
        if ($chk->fetch()) {
            $error = 'A user with this email already exists.';
        } else {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (name, email, password, role, phone, address) VALUES (?, ?, ?, 'supplier', ?, ?)");
            $stmt->execute([$name, $email, $hashedPassword, $phone, $address]);
            
            set_flash_message('success', 'Supplier partner "' . htmlspecialchars($name) . '" registered successfully.');
            header('Location: ' . BASE_URL . 'suppliers/index.php');
            exit();
        }
    }
}

$pageTitle = 'Register Supplier';
include __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-layout">
<?php include __DIR__ . '/../includes/sidebar.php'; ?>
<div class="dashboard-main">
<div class="dashboard-header">
    <div class="dashboard-title">
        <h2>🚜 Register Supplier Partner</h2>
        <p>Create a supplier credentials profile and dashboard login</p>
    </div>
    <a href="<?php echo BASE_URL; ?>suppliers/index.php" class="btn btn-secondary">← Back</a>
</div>

<div class="dashboard-content">
    <?php if ($error): ?><div class="alert alert-error">⚠️ <?php echo htmlspecialchars($error); ?></div><?php endif; ?>
    
    <div class="table-container" style="max-width:600px;padding:2rem;">
        <form method="POST">
            <div class="form-group">
                <label class="form-label" for="name">Supplier Name *</label>
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
                <label class="form-label" for="phone">Phone Number</label>
                <input class="form-control" type="text" name="phone" id="phone" value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
            </div>
            
            <div class="form-group">
                <label class="form-label" for="address">Address / Main Farm Location</label>
                <textarea class="form-control" name="address" id="address" rows="3"><?php echo htmlspecialchars($_POST['address'] ?? ''); ?></textarea>
            </div>
            
            <div style="margin-top:1.5rem; display:flex; gap:1rem;">
                <button type="submit" class="btn btn-primary">✅ Register Supplier</button>
                <a href="<?php echo BASE_URL; ?>suppliers/index.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
</div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
