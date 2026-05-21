<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

session_start();
requireAdmin();

$userId = (int)($_GET['id'] ?? 0);
if (!$userId) {
    header('Location: ' . url('admin/users.php'));
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
    header('Location: ' . url('admin/users.php'));
    exit;
}

$pageTitle = 'Edit User';
$flash = getFlash();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $role = $_POST['role'] ?? 'student';
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $password = $_POST['password'] ?? '';

    if (empty($first_name) || empty($last_name)) {
        flash('danger', 'Name required.');
    } elseif (!in_array($role, ['student', 'instructor', 'admin'])) {
        flash('danger', 'Invalid role.');
    } else {
        if (!empty($password)) {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $pdo->prepare("UPDATE users SET first_name = ?, last_name = ?, role = ?, is_active = ?, password_hash = ? WHERE id = ?")
                ->execute([$first_name, $last_name, $role, $is_active, $hash, $userId]);
        } else {
            $pdo->prepare("UPDATE users SET first_name = ?, last_name = ?, role = ?, is_active = ? WHERE id = ?")
                ->execute([$first_name, $last_name, $role, $is_active, $userId]);
        }
        flash('success', 'User updated.');
        header('Location: ' . url('admin/users.php'));
        exit;
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<h2 class="section-title">Edit User</h2>

<?php if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'danger' ?>"><?= e($flash['message']) ?></div>
<?php endif ?>

<div class="form-card" style="max-width: 500px;">
    <form method="POST">
        <div class="form-group">
            <label>Username</label>
            <input type="text" class="form-control" value="<?= e($user['username']) ?>" disabled>
        </div>
        <div class="form-group">
            <label>Email</label>
            <input type="email" class="form-control" value="<?= e($user['email']) ?>" disabled>
        </div>
        <div class="form-group">
            <label>First Name</label>
            <input type="text" name="first_name" class="form-control" value="<?= e($user['first_name']) ?>" required>
        </div>
        <div class="form-group">
            <label>Last Name</label>
            <input type="text" name="last_name" class="form-control" value="<?= e($user['last_name']) ?>" required>
        </div>
        <div class="form-group">
            <label>Role</label>
            <select name="role" class="form-control">
                <option value="student" <?= $user['role'] === 'student' ? 'selected' : '' ?>>Student</option>
                <option value="instructor" <?= $user['role'] === 'instructor' ? 'selected' : '' ?>>Instructor</option>
                <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
            </select>
        </div>
        <div class="form-group">
            <label><input type="checkbox" name="is_active" value="1" <?= $user['is_active'] ? 'checked' : '' ?>> Active</label>
        </div>
        <div class="form-group">
            <label>New Password (leave blank to keep)</label>
            <input type="password" name="password" class="form-control">
        </div>
        <button type="submit" class="btn btn-primary">Update</button>
        <a href="<?= url('admin/users.php') ?>" class="btn btn-outline-light" style="color: var(--text);">Cancel</a>
    </form>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
