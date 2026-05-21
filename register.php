<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/recommendations.php';

session_start();
redirectIfLoggedIn();

$pageTitle = 'Register';
$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';
    $role = $_POST['role'] ?? 'student';
    // student or instructor
    if ($role !== 'instructor') {
        $role = 'student';
    }

    if (strlen($username) < 3) $errors[] = 'Username must be at least 3 characters.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email address.';
    if (empty($first_name)) $errors[] = 'First name is required.';
    if (empty($last_name)) $errors[] = 'Last name is required.';
    if (strlen($password) < 6) $errors[] = 'Password must be at least 6 characters.';
    if ($password !== $password_confirm) $errors[] = 'Passwords do not match.';

    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        if ($stmt->fetch()) {
            $errors[] = 'Username or email already exists.';
        }
    }

    if (empty($errors)) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        // instructors must be approved by admin before login (is_active = 0)
        $isActive = $role === 'instructor' ? 0 : 1;

        $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, first_name, last_name, role, is_active) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$username, $email, $hash, $first_name, $last_name, $role, $isActive]);
        $userId = $pdo->lastInsertId();

        if ($role === 'student') {
            
            $_SESSION['user_id'] = $userId;
            $_SESSION['role'] = 'student';
            $_SESSION['first_name'] = $first_name;
            $_SESSION['username'] = $username;

            header('Location: ' . url('survey.php'));
            exit;
        } else {
            // instructors wait for admin approval
            $success = 'Your instructor account has been created and is pending admin approval. You will be able to log in once it is activated.';
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<style>
    .register-wrapper {
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 80vh;
        padding: 3rem 0;
    }
    .register-card {
        margin: 0 !important;
        width: 100%;
        max-width: 550px;
    }
</style>

<div class="register-wrapper">
    <div class="form-card register-card">
        <div class="text-center mb-4">
            <img src="<?= url('assets/img/AFAKLOGO.jpg') ?>" alt="AFAK Logo" style="width: 100px; height: 100px; border-radius: 50%; object-fit: cover; margin: 0 auto 1.5rem auto; display: block;">
            <h2 style="margin: 0; color: var(--afak-blue);">Create Account</h2>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success"><?= e($success) ?></div>
        <?php endif ?>
        <?php foreach ($errors as $err): ?>
            <div class="alert alert-danger"><?= e($err) ?></div>
        <?php endforeach ?>

        <form method="POST">
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" class="form-control" value="<?= e($_POST['username'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" class="form-control" value="<?= e($_POST['email'] ?? '') ?>" required>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <div class="form-group">
                    <label>First Name</label>
                    <input type="text" name="first_name" class="form-control" value="<?= e($_POST['first_name'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label>Last Name</label>
                    <input type="text" name="last_name" class="form-control" value="<?= e($_POST['last_name'] ?? '') ?>" required>
                </div>
            </div>
            <div class="form-group">
                <label>Register as</label>
                <select name="role" class="form-control">
                    <option value="student" <?= (($_POST['role'] ?? 'student') === 'student') ? 'selected' : '' ?>>Student</option>
                    <option value="instructor" <?= (($_POST['role'] ?? '') === 'instructor') ? 'selected' : '' ?>>Instructor</option>
                </select>
                <small class="text-muted">Instructors require admin approval after registration.</small>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Confirm Password</label>
                <input type="password" name="password_confirm" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary" style="width: 100%; padding: 12px; font-weight: 600;">Register</button>
        </form>
        <p class="text-center mt-4" style="margin-bottom: 0;">
            Already have an account? <a href="<?= url('login.php') ?>" style="font-weight: 700;">Login here</a>
        </p>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
