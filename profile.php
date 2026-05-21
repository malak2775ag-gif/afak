<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

session_start();
requireLogin();

$pageTitle = 'My Profile';
$user = getCurrentUser();
$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $bio = trim($_POST['bio'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';

    if (empty($first_name)) $errors[] = 'First name is required.';
    if (empty($last_name)) $errors[] = 'Last name is required.';

    if (!empty($password)) {
        if (strlen($password) < 6) $errors[] = 'Password must be at least 6 characters.';
        if ($password !== $password_confirm) $errors[] = 'Passwords do not match.';
    }

    // avatar upload
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['avatar']['tmp_name'];
        $fileName = $_FILES['avatar']['name'];
        $fileType = $_FILES['avatar']['type'];
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        
        if (in_array($fileType, $allowedTypes)) {
            $fileExt = pathinfo($fileName, PATHINFO_EXTENSION);
            $newFileName = 'avatar_' . $_SESSION['user_id'] . '_' . time() . '.' . $fileExt;
            $uploadDir = __DIR__ . '/uploads/avatars/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            
            if (move_uploaded_file($fileTmpPath, $uploadDir . $newFileName)) {
                $avatarUrl = 'uploads/avatars/' . $newFileName;
                $pdo->prepare("UPDATE users SET avatar_url = ? WHERE id = ?")->execute([$avatarUrl, $_SESSION['user_id']]);
                $user['avatar_url'] = $avatarUrl; // Update for current view
            } else {
                $errors[] = 'Failed to upload avatar.';
            }
        } else {
            $errors[] = 'Invalid image type. Allowed: JPG, PNG, GIF, WEBP.';
        }
    }

    if (empty($errors)) {
        if (!empty($password)) {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $pdo->prepare("UPDATE users SET first_name = ?, last_name = ?, bio = ?, password_hash = ? WHERE id = ?")
                ->execute([$first_name, $last_name, $bio, $hash, $_SESSION['user_id']]);
        } else {
            $pdo->prepare("UPDATE users SET first_name = ?, last_name = ?, bio = ? WHERE id = ?")
                ->execute([$first_name, $last_name, $bio, $_SESSION['user_id']]);
        }
        $_SESSION['first_name'] = $first_name;
        $success = 'Profile updated successfully.';
        $user = getCurrentUser();
    }
}

if (!$user) $user = [];

require_once __DIR__ . '/includes/header.php';
?>

<div class="form-card" style="max-width: 500px;">
    <h2>My Profile</h2>
    <?php foreach ($errors as $e): ?>
        <div class="alert alert-danger"><?= e($e) ?></div>
    <?php endforeach ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><?= e($success) ?></div>
    <?php endif ?>

    <form method="POST" enctype="multipart/form-data">
        <div class="text-center mb-3">
            <?php
            $defaultAvatar = 'assets/img/std.jpg';
            if (isset($user['role'])) {
                if ($user['role'] === 'instructor') {
                    $defaultAvatar = 'assets/img/inst.jpg';
                } elseif ($user['role'] === 'admin') {
                    $defaultAvatar = 'assets/img/admin.png';
                }
            }
            $displayAvatar = !empty($user['avatar_url']) ? $user['avatar_url'] : $defaultAvatar;
            ?>
            <img src="<?= url($displayAvatar) ?>" alt="Profile" style="width: 100px; height: 100px; border-radius: 50%; object-fit: cover; border: 3px solid #eee; margin-bottom: 10px;">
            <br>
            <label class="std_avatar" style="cursor: pointer; background-color: green; color: white; padding: 5px 10px; border-radius: 5px;">
                Change Avatar <input type="file" name="avatar" accept="image/*" style="display: none;">
            </label>
        </div>

        <div class="form-group">
            <label>Username</label>
            <input type="text" class="form-control" value="<?= e($user['username'] ?? '') ?>" disabled>
        </div>
        <div class="form-group">
            <label>Email</label>
            <input type="email" class="form-control" value="<?= e($user['email'] ?? '') ?>" disabled>
        </div>
        <div class="form-group">
            <label>First Name</label>
            <input type="text" name="first_name" class="form-control" value="<?= e($user['first_name'] ?? $_POST['first_name'] ?? '') ?>" required>
        </div>
        <div class="form-group">
            <label>Last Name</label>
            <input type="text" name="last_name" class="form-control" value="<?= e($user['last_name'] ?? $_POST['last_name'] ?? '') ?>" required>
        </div>
        <div class="form-group">
            <label>Bio</label>
            <textarea name="bio" class="form-control" rows="3"><?= e($user['bio'] ?? $_POST['bio'] ?? '') ?></textarea>
        </div>
        <div class="form-group">
            <label>New Password (leave blank to keep)</label>
            <input type="password" name="password" class="form-control">
        </div>
        <div class="form-group">
            <label>Confirm New Password</label>
            <input type="password" name="password_confirm" class="form-control">
        </div>
        <button type="submit" class="btn btn-primary" style="width: 100%;">Update Profile</button>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const avatarInput = document.querySelector('input[name="avatar"]');
    const avatarPreview = document.querySelector('img[alt="Profile"]');
    if (avatarInput && avatarPreview) {
        avatarInput.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                avatarPreview.src = URL.createObjectURL(file);
            }
        });
    }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
