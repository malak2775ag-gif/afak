<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

session_start();
requireInstructor();

$pageTitle = 'Create Course';
$flash = getFlash();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $slug = preg_replace('/[^a-z0-9]+/', '-', strtolower($title));
    $slug = trim($slug, '-');
    if ($slug === '') {
        $slug = 'course-' . bin2hex(random_bytes(3));
    }
    $description = trim($_POST['description'] ?? '');
    $short_description = trim($_POST['short_description'] ?? '');
    $category_id = trim($_POST['category_id'] ?? '') ?: null;
    $is_free = isset($_POST['is_free']) ? 1 : 0;
    $price = $is_free ? 0 : (isset($_POST['price']) ? (float) $_POST['price'] : 0);
    $duration_hours = isset($_POST['duration_hours']) ? (int) $_POST['duration_hours'] : null;
    $learning_style = $_POST['learning_style'] ?? 'mixed';
    $level = $_POST['level'] ?? 'all';
    $submit = $_POST['submit'] ?? 'draft';

    if (empty($title)) {
        $errors[] = 'Title required.';
    }
    if (empty($description)) {
        $errors[] = 'Description required.';
    }

    $thumbnail_url = null;
    if (isset($_FILES['cover']) && $_FILES['cover']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['cover']['tmp_name'];
        $fileName = $_FILES['cover']['name'];
        $fileType = $_FILES['cover']['type'];
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($fileType, $allowedTypes, true)) {
            $errors[] = 'Invalid cover image type. Allowed: JPG, PNG, GIF, WEBP.';
        } else {
            if ($_FILES['cover']['error'] !== UPLOAD_ERR_OK) {
                $limit = ini_get('upload_max_filesize');
                $postLimit = ini_get('post_max_size');
                $uploadErrors = [
                    UPLOAD_ERR_INI_SIZE => "The file is too large. Your server limit is currently $limit (post_max_size is $postLimit).",
                    UPLOAD_ERR_FORM_SIZE => 'The uploaded file exceeds the HTML form configured maximum size.',
                    UPLOAD_ERR_PARTIAL => 'The file was only partially uploaded.',
                    UPLOAD_ERR_NO_FILE => 'No file was uploaded.',
                    UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder for uploads.',
                    UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
                    UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload.',
                ];
                $errors[] = 'Cover image upload failed: ' . ($uploadErrors[$_FILES['cover']['error']] ?? 'Unknown error.');
                // Skip further processing of this file if there's an upload error
                $thumbnail_url = null; 
            } else {
            $fileExt = pathinfo($fileName, PATHINFO_EXTENSION);
            $newFileName = uniqid('cover_', true) . '.' . $fileExt;
            $uploadDir = __DIR__ . '/../uploads/covers/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            $destPath = $uploadDir . $newFileName;
            if (move_uploaded_file($fileTmpPath, $destPath)) {
                $thumbnail_url = 'uploads/covers/' . $newFileName;
            } else {
                $errors[] = 'Failed to save cover image.';
            }
        }
    }

    if (empty($errors)) {
        $baseSlug = $slug;
        $n = 0;
        do {
            $trySlug = $n === 0 ? $baseSlug : $baseSlug . '-' . $n;
            $chk = $pdo->prepare('SELECT id FROM courses WHERE slug = ? LIMIT 1');
            $chk->execute([$trySlug]);
            if (!$chk->fetch()) {
                $slug = $trySlug;
                break;
            }
            $n++;
        } while ($n < 50);
        if ($n >= 50) {
            $slug = $baseSlug . '-' . bin2hex(random_bytes(2));
        }

        $status = ($submit === 'submit_review') ? 'pending_review' : 'draft';
        $stmt = $pdo->prepare('
            INSERT INTO courses
            (title, slug, description, short_description, category_id, instructor_id, status, is_free, price, duration_hours, level, learning_style, thumbnail_url)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ');
        $stmt->execute([
            $title,
            $slug,
            $description,
            $short_description,
            $category_id,
            $_SESSION['user_id'],
            $status,
            $is_free,
            $price,
            $duration_hours,
            $level,
            $learning_style,
            $thumbnail_url,
        ]);

        $courseId = $pdo->lastInsertId();
        flash('success', 'Course created. ' . ($status === 'pending_review' ? 'Submitted for review.' : 'Saved as draft.'));
        header('Location: ' . url('instructor/course-edit.php?id=' . $courseId));
        exit;
    }
}

$categories = $pdo->query('SELECT * FROM categories ORDER BY name')->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="ins-wrap">
    <div class="ins-breadcrumb">
        <a href="<?= url('instructor/index.php') ?>">Instructor</a>
        <span> / </span>
        <a href="<?= url('instructor/courses.php') ?>">My courses</a>
        <span> / </span>
        <span>New course</span>
    </div>

    <div class="ins-page-head">
        <h1 class="ins-title">Create a new course</h1>
        <div class="ins-actions">
            <a href="<?= url('instructor/courses.php') ?>" class="btn btn-secondary">Cancel</a>
        </div>
    </div>

    <?php foreach ($errors as $e): ?>
        <div class="alert alert-danger"><?= e($e) ?></div>
    <?php endforeach ?>

    <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'danger' ?>"><?= e($flash['message']) ?></div>
    <?php endif ?>

    <div class="ins-steps">
        <div class="ins-step"><strong>1</strong> Basics &amp; pricing</div>
        <div class="ins-step"><strong>2</strong> Build curriculum</div>
        <div class="ins-step"><strong>3</strong> Add quizzes</div>
    </div>

    <div class="ins-card">
        <h2>Course information</h2>
        <form method="POST" enctype="multipart/form-data">
            <div class="ins-form-grid">
                <div class="form-group full-span">
                    <label>Course title</label>
                    <input type="text" name="title" class="form-control" value="<?= e($_POST['title'] ?? '') ?>" required placeholder="e.g. Introduction to Data Literacy">
                </div>
                <div class="form-group full-span">
                    <label>Subtitle / short summary</label>
                    <input type="text" name="short_description" class="form-control" value="<?= e($_POST['short_description'] ?? '') ?>" placeholder="One line for catalog cards">
                </div>
                <div class="form-group full-span">
                    <label>Full description</label>
                    <textarea name="description" class="form-control" rows="6" required placeholder="What learners will achieve, prerequisites, format..."><?= e($_POST['description'] ?? '') ?></textarea>
                </div>
                <div class="form-group">
                    <label>Category</label>
                    <select name="category_id" class="form-control">
                        <option value="">— Select —</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= (int) $cat['id'] ?>" <?= (isset($_POST['category_id']) && $_POST['category_id'] == $cat['id']) ? 'selected' : '' ?>><?= e($cat['name']) ?></option>
                        <?php endforeach ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Level</label>
                    <select name="level" class="form-control">
                        <option value="all">All levels</option>
                        <option value="beginner" <?= ($_POST['level'] ?? '') === 'beginner' ? 'selected' : '' ?>>Beginner</option>
                        <option value="intermediate" <?= ($_POST['level'] ?? '') === 'intermediate' ? 'selected' : '' ?>>Intermediate</option>
                        <option value="advanced" <?= ($_POST['level'] ?? '') === 'advanced' ? 'selected' : '' ?>>Advanced</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Primary Learning Style</label>
                    <select name="learning_style" class="form-control">
                        <option value="mixed">Mixed (Default)</option>
                        <option value="visual">Visual (Video Heavy)</option>
                        <option value="verbal">Verbal (Text Heavy)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Estimated duration (hours)</label>
                    <input type="number" name="duration_hours" class="form-control" min="0" value="<?= e($_POST['duration_hours'] ?? '') ?>" placeholder="Optional">
                </div>
                <div class="form-group">
                    <label class="ins-check">
                        <input type="checkbox" name="is_free" id="is_free_checkbox" value="1" <?= ($_SERVER['REQUEST_METHOD'] !== 'POST' || isset($_POST['is_free'])) ? 'checked' : '' ?>>
                        This course is free
                    </label>
                </div>
                <div class="form-group" id="price_group" style="<?= ($_SERVER['REQUEST_METHOD'] !== 'POST' || isset($_POST['is_free'])) ? 'display:none;' : '' ?>">
                    <label>Price (OMR)</label>
                    <input type="number" name="price" class="form-control" step="0.01" min="0" value="<?= e($_POST['price'] ?? '0') ?>">
                </div>
                <div class="form-group full-span">
                    <label>Cover image</label>
                    <input type="file" name="cover" accept="image/jpeg,image/png,image/gif,image/webp" class="form-control">
                    <small class="text-muted">JPG, PNG, GIF, or WebP — shown on your dashboard and can be used on the catalog.</small>
                </div>
            </div>

            <div class="ins-footer-actions">
                <button type="submit" name="submit" value="draft" class="btn btn-secondary">Save as draft</button>
                <button type="submit" name="submit" value="submit_review" class="btn btn-primary">Submit for review</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const isFreeCheck = document.getElementById('is_free_checkbox');
    const priceGroup = document.getElementById('price_group');
    if (isFreeCheck && priceGroup) {
        isFreeCheck.addEventListener('change', function() {
            priceGroup.style.display = this.checked ? 'none' : 'block';
        });
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
