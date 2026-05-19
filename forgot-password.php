<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

session_start();
redirectIfLoggedIn();

$pageTitle = 'Forgot Password';
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (empty($email)) {
        $error = 'Please enter your email address.';
    } else {
        // Check if user exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            $token = bin2hex(random_bytes(32));
            $expires = date("Y-m-d H:i:s", strtotime('+1 hour'));

            // Delete any existing tokens for this email to avoid duplicate rows
            $pdo->prepare("DELETE FROM password_resets WHERE email = ?")->execute([$email]);

            // Store the token in the database table 'password_resets'
            $stmt = $pdo->prepare("INSERT INTO password_resets (email, token, expires_at, created_at) VALUES (?, ?, ?, NOW())");
            $stmt->execute([$email, $token, $expires]);

            $success = 'A password reset link has been sent to your email address.';
        } else {
            $error = 'No account found with that email address.';
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<style>
    .login-wrapper {
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 70vh;
        padding: 2rem 0;
    }
    .login-card {
        margin: 0 !important;
        width: 100%;
        max-width: 450px;
    }
</style>

<div class="login-wrapper">
    <div class="form-card login-card">
        <div class="text-center mb-4">
            <img src="<?= url('assets/img/AFAKLOGO.jpg') ?>" alt="AFAK Logo" style="width: 100px; height: 100px; border-radius: 50%; object-fit: cover; margin: 0 auto 1.5rem auto; display: block;">
            <h2 style="margin: 0; color: var(--afak-blue);">Reset Password</h2>
            <p class="text-muted">Enter your email to receive a reset link</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= e($error) ?></div>
        <?php endif ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><?= e($success) ?></div>
        <?php endif ?>

        <form method="POST">
            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" class="form-control" placeholder="example@domain.com" required>
            </div>
            <button type="submit" class="btn btn-primary" style="width: 100%;">Send Reset Link</button>
        </form>
        <p class="text-center mt-4" style="margin-bottom: 0;">
            Remember your password? <a href="<?= url('login.php') ?>" style="font-weight: 700;">Login here</a>
        </p>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>