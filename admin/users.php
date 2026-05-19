<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

session_start();
requireAdmin();

$pageTitle = 'Manage Users';
$flash = getFlash();

// add user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add') {
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $first_name = trim($_POST['first_name'] ?? '');
        $last_name = trim($_POST['last_name'] ?? '');
        $password = $_POST['password'] ?? '';
        $role = $_POST['role'] ?? 'student';

        $errors = [];
        if (strlen($username) < 3) $errors[] = 'Username must be at least 3 characters.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email.';
        if (empty($first_name) || empty($last_name)) $errors[] = 'Name required.';
        if (strlen($password) < 6) $errors[] = 'Password must be at least 6 characters.';
        if (!in_array($role, ['student', 'instructor', 'admin'])) $errors[] = 'Invalid role.';

        if (empty($errors)) {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            if ($stmt->fetch()) {
                flash('danger', 'Username or email already exists.');
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $pdo->prepare("INSERT INTO users (username, email, password_hash, first_name, last_name, role) VALUES (?, ?, ?, ?, ?, ?)")
                    ->execute([$username, $email, $hash, $first_name, $last_name, $role]);
                flash('success', 'User added successfully.');
                header('Location: ' . url('admin/users.php'));
                exit;
            }
        }
    }
}

//  delete user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete'])) {
    $userId = (int)($_POST['user_id'] ?? 0);
    if ($userId && $userId !== $_SESSION['user_id']) {
        $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$userId]);
        flash('success', 'User deleted.');
        header('Location: ' . url('admin/users.php'));
        exit;
    }
}

$roleFilter = $_GET['role'] ?? '';
$search = trim($_GET['search'] ?? '');

$sql = "SELECT * FROM users WHERE 1=1";
$params = [];
if ($roleFilter) { $sql .= " AND role = ?"; $params[] = $roleFilter; }
if ($search) {
    $sql .= " AND (username LIKE ? OR email LIKE ? OR first_name LIKE ? OR last_name LIKE ?)";
    $term = "%$search%";
    $params = array_merge($params, [$term, $term, $term, $term]);
}
$sql .= " ORDER BY created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

$showAddForm = isset($_GET['action']) && $_GET['action'] === 'add';

require_once __DIR__ . '/../includes/header.php';
?>

<h2 class="section-title">Manage Users</h2>

<?php if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'danger' ?>"><?= e($flash['message']) ?></div>
<?php endif ?>

<form method="GET" style="display: flex; gap: 1rem; margin-bottom: 1.5rem; flex-wrap: wrap;">
    <input type="text" name="search" class="form-control" placeholder="Search..." value="<?= e($search) ?>" style="max-width: 200px;">
    <select name="role" class="form-control" style="max-width: 150px;">
        <option value="">All Roles</option>
        <option value="student" <?= $roleFilter === 'student' ? 'selected' : '' ?>>Student</option>
        <option value="instructor" <?= $roleFilter === 'instructor' ? 'selected' : '' ?>>Instructor</option>
        <option value="admin" <?= $roleFilter === 'admin' ? 'selected' : '' ?>>Admin</option>
    </select>
    <button type="submit" class="btn btn-primary">Filter</button>
    <a href="<?= url('admin/users.php?action=add') ?>" class="btn btn-primary">Add User</a>
</form>

<?php if ($showAddForm): ?>
<div class="form-card" style="max-width: 500px; margin-bottom: 2rem;">
    <h3>Add User</h3>
    <form method="POST">
        <input type="hidden" name="action" value="add">
        <div class="form-group">
            <label>Username</label>
            <input type="text" name="username" class="form-control" required>
        </div>
        <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" class="form-control" required>
        </div>
        <div class="form-group">
            <label>First Name</label>
            <input type="text" name="first_name" class="form-control" required>
        </div>
        <div class="form-group">
            <label>Last Name</label>
            <input type="text" name="last_name" class="form-control" required>
        </div>
        <div class="form-group">
            <label>Password</label>
            <input type="password" name="password" class="form-control" required>
        </div>
        <div class="form-group">
            <label>Role</label>
            <select name="role" class="form-control">
                <option value="student">Student</option>
                <option value="instructor">Instructor</option>
                <option value="admin">Admin</option>
            </select>
        </div>
        <button type="submit" class="btn btn-primary">Add User</button>
        <a href="<?= url('admin/users.php') ?>" class="btn btn-outline-light" style="color: var(--text);">Cancel</a>
    </form>
</div>
<?php endif ?>

<table style="width: 100%; background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 12px rgba(0,0,0,0.06);">
    <thead>
        <tr style="background: var(--light);">
            <th style="padding: 1rem; text-align: left;">User</th>
            <th style="padding: 1rem;">Role</th>
            <th style="padding: 1rem;">Status</th>
            <th style="padding: 1rem;">Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($users as $u): ?>
        <tr style="border-top: 1px solid var(--border);">
            <td style="padding: 1rem;">
                <strong><?= e($u['username']) ?></strong><br>
                <small><?= e($u['email']) ?></small><br>
                <?= e($u['first_name'] . ' ' . $u['last_name']) ?>
            </td>
            <td style="padding: 1rem;"><?= e($u['role']) ?></td>
            <td style="padding: 1rem;"><?= $u['is_active'] ? 'Active' : 'Inactive' ?></td>
            <td style="padding: 1rem;">
                <a href="<?= url('admin/user-edit.php?id=' . $u['id']) ?>" class="btn btn-primary" style="padding: 0.35rem 0.75rem;">Edit</a>
                <?php if ($u['id'] != $_SESSION['user_id']): ?>
                <form method="POST" style="display: inline;" onsubmit="return confirm('Delete this user?');">
                    <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                    <button type="submit" name="delete" class="btn" style="background: #dc3545; color: white; padding: 0.35rem 0.75rem;">Delete</button>
                </form>
                <?php endif ?>
            </td>
        </tr>
        <?php endforeach ?>
    </tbody>
</table>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
