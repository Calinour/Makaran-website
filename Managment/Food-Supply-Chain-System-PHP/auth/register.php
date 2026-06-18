<?php
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';

if (is_logged_in()) {
    header('Location: ' . BASE_URL . 'dashboard/index.php');
    exit();
}

$error = '';
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $role = $_POST['role'] ?? 'customer';
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (empty($name) || empty($email) || empty($password)) {
        $error = 'Name, email, and password are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif (!in_array($role, ['customer', 'supplier'])) {
        $error = 'Invalid role selected.';
    } else {
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = 'An account with this email already exists.';
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (name,email,password,role,phone,address) VALUES (?,?,?,?,?,?)");
            $stmt->execute([$name, $email, $hashed, $role, $phone, $address]);
            set_flash_message('success', 'Account created successfully! Please log in.');
            header('Location: ' . BASE_URL . 'auth/login.php');
            exit();
        }
    }
}

$pageTitle = 'Create Account';
include __DIR__ . '/../includes/header.php';
?>
<style>
body { display:flex;flex-direction:column;justify-content:center;align-items:center;min-height:100vh;padding:2rem; }
.auth-container { max-width:520px; }
</style>
<div class="auth-container">
    <div class="auth-header">
        <div style="font-size:2.5rem;margin-bottom:0.5rem;">🌿</div>
        <h2>Create Account</h2>
        <p>Join the SahanFresh supply chain network</p>
    </div>

    <?php if ($error): ?>
    <div class="alert alert-error">⚠️ <?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="POST" class="register-form">
        <div class="form-group">
            <label class="form-label" for="name">Full Name</label>
            <input class="form-control" type="text" id="name" name="name"
                   value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>"
                   placeholder="e.g. Farah Ahmed" required>
        </div>
        <div class="form-group">
            <label class="form-label" for="email">Email Address</label>
            <input class="form-control" type="email" id="email" name="email"
                   value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                   placeholder="you@example.com" required>
        </div>
        <div class="form-group">
            <label class="form-label" for="phone">Phone Number</label>
            <input class="form-control" type="tel" id="phone" name="phone"
                   value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>"
                   placeholder="+252615000000">
        </div>
        <div class="form-group">
            <label class="form-label" for="address">Delivery Address</label>
            <input class="form-control" type="text" id="address" name="address"
                   value="<?php echo htmlspecialchars($_POST['address'] ?? ''); ?>"
                   placeholder="District, City">
        </div>
        <div class="form-group">
            <label class="form-label" for="role">Account Type</label>
            <select class="form-control" id="role" name="role">
                <option value="customer" <?php echo (($_POST['role'] ?? '') === 'customer') ? 'selected' : ''; ?>>🛒 Customer</option>
                <option value="supplier" <?php echo (($_POST['role'] ?? '') === 'supplier') ? 'selected' : ''; ?>>🏪 Supplier Partner</option>
            </select>
        </div>
        <div class="form-group">
            <label class="form-label" for="password">Password</label>
            <input class="form-control" type="password" id="password" name="password" placeholder="Min. 6 characters" required>
        </div>
        <div class="form-group">
            <label class="form-label" for="confirm_password">Confirm Password</label>
            <input class="form-control" type="password" id="confirm_password" name="confirm_password" placeholder="Repeat password" required>
        </div>
        <button type="submit" class="btn btn-primary" style="width:100%;margin-top:0.5rem;">Create Account</button>
    </form>
    <div class="auth-footer">Already have an account? <a href="<?php echo BASE_URL; ?>auth/login.php">Sign In</a></div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
