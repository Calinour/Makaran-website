<?php
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';

if (is_logged_in()) {
    header('Location: ' . BASE_URL . 'dashboard/index.php');
    exit();
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Please enter your email and password.';
    } else {
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            login_user($user);
            set_flash_message('success', 'Welcome back, ' . $user['name'] . '!');
            header('Location: ' . BASE_URL . 'dashboard/index.php');
            exit();
        } else {
            $error = 'Invalid email or password. Please try again.';
        }
    }
}

$pageTitle = 'Login';
include __DIR__ . '/../includes/header.php';
?>
<style>
body { display:flex; flex-direction:column; justify-content:center; align-items:center; min-height:100vh; padding:2rem; }
</style>
<div class="auth-container">
    <div class="auth-header">
        <div style="font-size:2.5rem;margin-bottom:0.5rem;">🌿</div>
        <h2>Welcome Back</h2>
        <p>Sign in to your SahanFresh account</p>
    </div>

    <?php if ($error): ?>
    <div class="alert alert-error">⚠️ <?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <?php if ($msg = get_flash_message('success')): ?>
    <div class="alert alert-success">✅ <?php echo htmlspecialchars($msg); ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="form-group">
            <label class="form-label" for="email">Email Address</label>
            <input class="form-control" type="email" id="email" name="email"
                   value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                   placeholder="you@example.com" required>
        </div>
        <div class="form-group">
            <label class="form-label" for="password">Password</label>
            <input class="form-control" type="password" id="password" name="password"
                   placeholder="••••••••" required>
        </div>
        <button type="submit" class="btn btn-primary" style="width:100%;margin-top:0.5rem;">Sign In</button>
    </form>

    <div class="auth-footer">
        <a href="<?php echo BASE_URL; ?>auth/forgot_password.php">Forgot password?</a>
        &nbsp;·&nbsp;
        Don't have an account? <a href="<?php echo BASE_URL; ?>auth/register.php">Register</a>
    </div>

    <div style="margin-top:1.5rem;padding:1rem;background:rgba(255,255,255,0.03);border:1px solid var(--border-color);border-radius:8px;font-size:0.8rem;color:var(--text-muted);">
        <strong style="color:var(--primary);">Demo Accounts:</strong><br>
        Admin: admin@sahanfresh.com / admin123<br>
        Supplier: supplier1@sahanfresh.com / supplier123<br>
        Customer: customer@sahanfresh.com / customer123<br>
        Driver: driver1@sahanfresh.com / driver123
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
