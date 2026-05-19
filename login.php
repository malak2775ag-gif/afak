<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/recommendations.php';

session_start();
redirectIfLoggedIn();

$pageTitle = 'Login';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Please enter username and password.';
    } else {
        $stmt = $pdo->prepare("SELECT id, username, first_name, last_name, role, password_hash, survey_skipped FROM users WHERE (username = ? OR email = ?) AND is_active = 1");
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['first_name'] = $user['first_name'];
            $_SESSION['username'] = $user['username'];

            $pdo->prepare("UPDATE users SET last_login_at = NOW() WHERE id = ?")->execute([$user['id']]);

            // Check if student needs survey
            if ($user['role'] === 'student' && afak_student_needs_survey($pdo, (int) $user['id'])) {
                header('Location: ' . url('survey.php'));
                exit;
            }

            $redirect = $_GET['redirect'] ?? '';
            if ($redirect) {
                header('Location: ' . (strpos($redirect, '/') === 0 ? $redirect : url($redirect)));
            } else {
                header('Location: ' . getDashboardUrl());
            }
            exit;
        }
        $error = 'Invalid username or password.';
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
            <h2 style="margin: 0; color: var(--afak-blue);">Welcome Back!</h2>
            
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= e($error) ?></div>
        <?php endif ?>

        <form method="POST">
            <div class="form-group">
                <label>Username or Email</label>
                <input type="text" name="username" class="form-control" value="<?= e($_POST['username'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" class="form-control" required>
                <div style="text-align: right; margin-top: 5px;">
                    <a href="<?= url('forgot-password.php') ?>" style="font-size: 0.85rem; color: #666;">Forgot password?</a>
                </div>
            </div>
            <button type="submit" class="btn btn-primary" style="width: 100%;">Login</button>
        </form>
        <p class="text-center mt-4" style="margin-bottom: 0;">
            Don't have an account? <a href="<?= url('register.php') ?>" style="font-weight: 700;">Register here</a>
        </p>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
