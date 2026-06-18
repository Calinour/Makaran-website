<?php
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/session.php';
$pageTitle = 'Forgot Password';
include __DIR__ . '/../includes/header.php';
$submitted = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') { $submitted = true; }
?>
<style>body{display:flex;flex-direction:column;justify-content:center;align-items:center;min-height:100vh;padding:2rem;}</style>
<div class="auth-container">
    <div class="auth-header">
        <div style="font-size:2.5rem;margin-bottom:0.5rem;">🔑</div>
        <h2>Forgot Password</h2>
        <p>Enter your email to receive reset instructions</p>
    </div>
    <?php if ($submitted): ?>
    <div class="alert alert-success">✅ If an account with that email exists, a reset link has been sent.</div>
    <?php else: ?>
    <form method="POST">
        <div class="form-group">
            <label class="form-label" for="email">Email Address</label>
            <input class="form-control" type="email" id="email" name="email" placeholder="you@example.com" required>
        </div>
        <button type="submit" class="btn btn-primary" style="width:100%;">Send Reset Link</button>
    </form>
    <?php endif; ?>
    <div class="auth-footer"><a href="<?php echo BASE_URL; ?>auth/login.php">← Back to Login</a></div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
