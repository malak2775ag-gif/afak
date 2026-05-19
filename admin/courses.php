<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

session_start();
requireAdmin();

$pageTitle = 'Manage Courses';
$flash = getFlash();

function admin_course_slug(PDO $pdo, string $title, ?int $excludeId = null): string
{
    $base = preg_replace('/[^a-z0-9]+/', '-', strtolower(trim($title)));
    $base = trim((string) $base, '-');
    if ($base === '') {
        $base = 'course';
    }

    $n = 0;
    do {
        $slug = $n === 0 ? $base : $base . '-' . $n;
        if ($excludeId === null) {
            $stmt = $pdo->prepare("SELECT id FROM courses WHERE slug = ? LIMIT 1");
            $stmt->execute([$slug]);
        } else {
            $stmt = $pdo->prepare("SELECT id FROM courses WHERE slug = ? AND id != ? LIMIT 1");
            $stmt->execute([$slug, $excludeId]);
        }
        if (!$stmt->fetch()) {
            return $slug;
        }
        $n++;
    } while ($n < 200);

    return $base . '-' . time();
}

// approval and rejecttion method 
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $courseId = (int)($_POST['course_id'] ?? 0);
    $action = $_POST['action'] ?? '';
    if ($action === 'add_course') {
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $shortDescription = trim($_POST['short_description'] ?? '');
        $instructorId = (int) ($_POST['instructor_id'] ?? 0);
        $categoryId = $_POST['category_id'] !== '' ? (int) $_POST['category_id'] : null;
        $isFree = isset($_POST['is_free']) ? 1 : 0;
        $price = $isFree ? 0 : (float) ($_POST['price'] ?? 0);
        $duration = $_POST['duration_hours'] !== '' ? (int) $_POST['duration_hours'] : null;
        $level = $_POST['level'] ?? 'all';
        $learningStyle = $_POST['learning_style'] ?? 'mixed'; // Added learning_style
        $status = $_POST['status'] ?? 'draft';

        if ($title === '' || $description === '' || $instructorId <= 0) {
            flash('danger', 'Title, description, and instructor are required.');
        } else {
            $slug = admin_course_slug($pdo, $title);
            $stmt = $pdo->prepare(" 
                INSERT INTO courses
                    (title, slug, description, short_description, category_id, instructor_id, status, is_free, price, duration_hours, level, learning_style)
                VALUES
                    (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$title, $slug, $description, $shortDescription, $categoryId, $instructorId, $status, $isFree, $price, $duration, $level, $learningStyle]); // Added $learningStyle
            flash('success', 'Course created successfully.');
        }
        header('Location: ' . url('admin/courses.php')); // Redirect after processing
        exit;
    }

    if ($action === 'update_course' && $courseId) {
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $shortDescription = trim($_POST['short_description'] ?? '');
        $instructorId = (int) ($_POST['instructor_id'] ?? 0);
        $categoryId = $_POST['category_id'] !== '' ? (int) $_POST['category_id'] : null;
        $isFree = isset($_POST['is_free']) ? 1 : 0;
        $price = $isFree ? 0 : (float) ($_POST['price'] ?? 0);
        $duration = $_POST['duration_hours'] !== '' ? (int) $_POST['duration_hours'] : null;
        $level = $_POST['level'] ?? 'all';
        $learningStyle = $_POST['learning_style'] ?? 'mixed'; // Added learning_style

        if ($title === '' || $description === '' || $instructorId <= 0) {
            flash('danger', 'Title, description, and instructor are required.');
        } else {
            $slug = admin_course_slug($pdo, $title, $courseId);
            $stmt = $pdo->prepare(" 
                UPDATE courses
                SET title = ?, slug = ?, description = ?, short_description = ?, category_id = ?, instructor_id = ?, status = ?,
                    is_free = ?, price = ?, duration_hours = ?, level = ?, learning_style = ?
                WHERE id = ?
            ");
            $stmt->execute([$title, $slug, $description, $shortDescription, $categoryId, $instructorId, $status, $isFree, $price, $duration, $level, $learningStyle, $courseId]); // Added $learningStyle
            flash('success', 'Course updated.');
        }
        header('Location: ' . url('admin/courses.php'));
        exit;
    }

    if ($courseId) {
        if (isset($_POST['approve'])) {
            $pdo->prepare("UPDATE courses SET status = 'approved', approved_by = ?, approved_at = NOW(), rejection_reason = NULL WHERE id = ?")
                ->execute([$_SESSION['user_id'], $courseId]);
            $stmt = $pdo->prepare("SELECT instructor_id FROM courses WHERE id = ?");
            $stmt->execute([$courseId]);
            $inst = $stmt->fetch();
            if ($inst) {
                $pdo->prepare("INSERT INTO notifications (user_id, type, title, message, related_type, related_id) VALUES (?, 'content_approval', 'Course Approved', 'Your course has been approved.', 'course', ?)")
                    ->execute([$inst['instructor_id'], $courseId]);
            }
            flash('success', 'Course approved.');
        } elseif (isset($_POST['reject'])) {
            $reason = trim($_POST['rejection_reason'] ?? '');
            $pdo->prepare("UPDATE courses SET status = 'rejected', approved_by = ?, approved_at = NOW(), rejection_reason = ? WHERE id = ?")
                ->execute([$_SESSION['user_id'], $reason, $courseId]);
            $stmt = $pdo->prepare("SELECT instructor_id FROM courses WHERE id = ?");
            $stmt->execute([$courseId]);
            $inst = $stmt->fetch();
            if ($inst) {
                $pdo->prepare("INSERT INTO notifications (user_id, type, title, message, related_type, related_id) VALUES (?, 'content_rejection', 'Course Rejected', ?, 'course', ?)")
                    ->execute([$inst['instructor_id'], 'Your course was rejected. ' . $reason, $courseId]);
            }
            flash('success', 'Course rejected.');
        } elseif (isset($_POST['publish'])) {
            $pdo->prepare("UPDATE courses SET status = 'published' WHERE id = ? AND status = 'approved'")->execute([$courseId]);
            flash('success', 'Course published.');
        } elseif (isset($_POST['unpublish'])) {
            $pdo->prepare("UPDATE courses SET status = 'approved' WHERE id = ? AND status = 'published'")->execute([$courseId]);
            flash('success', 'Course unpublished.');
        } elseif (isset($_POST['delete'])) {
            $pdo->prepare("DELETE FROM courses WHERE id = ?")->execute([$courseId]);
            flash('success', 'Course deleted.');
        }
        header('Location: ' . url('admin/courses.php'));
        exit;
    }
}

$instructors = $pdo->query("SELECT id, first_name, last_name, username FROM users WHERE role = 'instructor' AND is_active = 1 ORDER BY first_name, last_name")->fetchAll();
$categories = getHierarchicalCategories($pdo);
$editCourseId = (int) ($_GET['edit'] ?? 0);
$editCourse = null;
if ($editCourseId > 0) {
    $stmt = $pdo->prepare("SELECT * FROM courses WHERE id = ?");
    $stmt->execute([$editCourseId]);
    $editCourse = $stmt->fetch();
}

$statusFilter = $_GET['status'] ?? '';

$sql = "
    SELECT c.*, u.first_name, u.last_name, cat.name as category_name
    FROM courses c
    JOIN users u ON c.instructor_id = u.id
    LEFT JOIN categories cat ON c.category_id = cat.id
    WHERE 1=1
";
$params = [];
if ($statusFilter) {
    $sql .= " AND c.status = ?";
    $params[] = $statusFilter;
}
$sql .= " ORDER BY c.updated_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$courses = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<h2 class="section-title">Manage Courses</h2>

<?php if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'danger' ?>"><?= e($flash['message']) ?></div>
<?php endif ?>

<div class="form-card" style="margin-bottom: 1.5rem;">
    <h3 style="margin-top: 0;"><?= $editCourse ? 'Edit Course' : 'Add Course' ?></h3>
    <form method="POST">
        <input type="hidden" name="action" value="<?= $editCourse ? 'update_course' : 'add_course' ?>">
        <?php if ($editCourse): ?>
            <input type="hidden" name="course_id" value="<?= (int) $editCourse['id'] ?>">
        <?php endif ?>
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 0.75rem;">
            <div class="form-group" style="grid-column: 1 / -1;">
                <label>Title</label>
                <input type="text" name="title" class="form-control" required value="<?= e($editCourse['title'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Instructor</label>
                <select name="instructor_id" class="form-control" required>
                    <option value="">Select instructor</option>
                    <?php foreach ($instructors as $inst): ?>
                        <option value="<?= (int) $inst['id'] ?>" <?= (string) ($editCourse['instructor_id'] ?? '') === (string) $inst['id'] ? 'selected' : '' ?>>
                            <?= e($inst['first_name'] . ' ' . $inst['last_name'] . ' (' . $inst['username'] . ')') ?>
                        </option>
                    <?php endforeach ?>
                </select>
            </div>
            <div class="form-group">
                <label>Category</label>
                <select name="category_id" class="form-control">
                    <option value="">General</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= (int) $cat['id'] ?>" <?= (string) ($editCourse['category_id'] ?? '') === (string) $cat['id'] ? 'selected' : '' ?>>
                            <?= e($cat['display_name']) ?>
                        </option>
                    <?php endforeach ?>
                </select>
            </div>
            <?php if (!$editCourse): ?>
            <div class="form-group">
                <label>Initial Status</label>
                <select name="status" class="form-control">
                    <option value="draft">Draft</option>
                    <option value="approved">Approved</option>
                    <option value="published">Published</option>
                </select>
            </div>
            <?php endif ?>
            <div class="form-group">
                <label>Level</label>
                <select name="level" class="form-control">
                    <?php $lvl = $editCourse['level'] ?? 'all'; ?>
                    <option value="all" <?= $lvl === 'all' ? 'selected' : '' ?>>All levels</option>
                    <option value="beginner" <?= $lvl === 'beginner' ? 'selected' : '' ?>>Beginner</option>
                    <option value="intermediate" <?= $lvl === 'intermediate' ? 'selected' : '' ?>>Intermediate</option>
                    <option value="advanced" <?= $lvl === 'advanced' ? 'selected' : '' ?>>Advanced</option>
                </select>
            </div>
            <?php $ls = $editCourse['learning_style'] ?? 'mixed'; ?>
            <div class="form-group">
                <label>Primary Learning Style</label>
                <select name="learning_style" class="form-control">
                    <?php $ls = $editCourse['learning_style'] ?? 'mixed'; ?>
                    <option value="mixed" <?= $ls === 'mixed' ? 'selected' : '' ?>>Mixed (Default)</option>
                    <option value="visual" <?= $ls === 'visual' ? 'selected' : '' ?>>Visual (Video Heavy)</option>
                    <option value="verbal" <?= $ls === 'verbal' ? 'selected' : '' ?>>Verbal (Text Heavy)</option>
                </select>
            </div>
            <div class="form-group">
                <label>Duration (hours)</label>
                <input type="number" name="duration_hours" class="form-control" min="0" value="<?= e((string) ($editCourse['duration_hours'] ?? '')) ?>">
            </div>
            <div class="form-group">
                <label style="display:flex; align-items:center; gap: 0.5rem; margin-top: 2rem;">
                    <input type="checkbox" name="is_free" value="1" <?= !empty($editCourse['is_free']) ? 'checked' : '' ?>>
                    Free course
                </label>
            </div>
            <div class="form-group">
                <label>Price (USD)</label>
                <input type="number" name="price" class="form-control" step="0.01" min="0" value="<?= e((string) ($editCourse['price'] ?? '0')) ?>">
            </div>
            <div class="form-group" style="grid-column: 1 / -1;">
                <label>Short Description</label>
                <input type="text" name="short_description" class="form-control" value="<?= e($editCourse['short_description'] ?? '') ?>">
            </div>
            <div class="form-group" style="grid-column: 1 / -1;">
                <label>Description</label>
                <textarea name="description" class="form-control" rows="5" required><?= e($editCourse['description'] ?? '') ?></textarea>
            </div>
        </div>
        <button type="submit" class="btn btn-primary"><?= $editCourse ? 'Update Course' : 'Add Course' ?></button>
        <?php if ($editCourse): ?>
            <a href="<?= url('admin/courses.php') ?>" class="btn btn-secondary">Cancel</a>
        <?php endif ?>
    </form>
</div>

<form method="GET" style="display: flex; gap: 1rem; margin-bottom: 1.5rem;">
    <select name="status" class="form-control" style="max-width: 200px;">
        <option value="">All Status</option>
        <option value="draft" <?= $statusFilter === 'draft' ? 'selected' : '' ?>>Draft</option>
        <option value="pending_review" <?= $statusFilter === 'pending_review' ? 'selected' : '' ?>>Pending Review</option>
        <option value="approved" <?= $statusFilter === 'approved' ? 'selected' : '' ?>>Approved</option>
        <option value="rejected" <?= $statusFilter === 'rejected' ? 'selected' : '' ?>>Rejected</option>
        <option value="published" <?= $statusFilter === 'published' ? 'selected' : '' ?>>Published</option>
    </select>
    <button type="submit" class="btn btn-primary">Filter</button>
</form>

<table style="width: 100%; background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 12px rgba(0,0,0,0.06);">
    <thead>
        <tr style="background: var(--light);">
            <th style="padding: 1rem; text-align: left;">Course</th>
            <th style="padding: 1rem;">Instructor</th>
            <th style="padding: 1rem;">Status</th>
            <th style="padding: 1rem;">Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($courses as $c): ?>
        <tr style="border-top: 1px solid var(--border);">
            <td style="padding: 1rem;">
                <strong><?= e($c['title']) ?></strong><br>
                <small><?= e($c['category_name'] ?? '-') ?></small>
            </td>
            <td style="padding: 1rem;"><?= e($c['first_name'] . ' ' . $c['last_name']) ?></td>
            <td style="padding: 1rem;"><?= e($c['status']) ?></td>
            <td style="padding: 1rem;">
                <a href="<?= url('course-detail.php?id=' . $c['id']) ?>" class="btn btn-primary" style="padding: 0.35rem 0.75rem;">View</a>
                <a href="<?= url('admin/courses.php?edit=' . $c['id']) ?>" class="btn btn-secondary" style="padding: 0.35rem 0.75rem;">Edit</a>
                <?php if ($c['status'] === 'pending_review'): ?>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="course_id" value="<?= $c['id'] ?>">
                        <button type="submit" name="approve" class="btn" style="background: #28a745; color: white; padding: 0.35rem 0.75rem;">Approve</button>
                        <button type="button" class="btn" style="background: #dc3545; color: white; padding: 0.35rem 0.75rem;" onclick="document.getElementById('reject-<?= $c['id'] ?>').style.display='inline-block'">Reject</button>
                    </form>
                    <span id="reject-<?= $c['id'] ?>" style="display:none;">
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="course_id" value="<?= $c['id'] ?>">
                            <input type="text" name="rejection_reason" placeholder="Reason" required style="padding: 0.35rem; border: 1px solid #ddd; border-radius: 4px;">
                            <button type="submit" name="reject" class="btn" style="background: #dc3545; color: white; padding: 0.35rem 0.75rem;">Confirm</button>
                        </form>
                    </span>
                <?php elseif ($c['status'] === 'approved'): ?>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="course_id" value="<?= $c['id'] ?>">
                        <button type="submit" name="publish" class="btn btn-primary" style="padding: 0.35rem 0.75rem;">Publish</button>
                    </form>
                <?php elseif ($c['status'] === 'published'): ?>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="course_id" value="<?= $c['id'] ?>">
                        <button type="submit" name="unpublish" class="btn" style="background: #6c757d; color: white; padding: 0.35rem 0.75rem;">Unpublish</button>
                    </form>
                <?php elseif ($c['status'] === 'rejected'): ?>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="course_id" value="<?= $c['id'] ?>">
                        <button type="submit" name="approve" class="btn" style="background: #28a745; color: white; padding: 0.35rem 0.75rem;">Re-Approve</button>
                    </form>
                <?php endif ?>
                <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to permanently delete this course? All related content, enrollments, and progress will be lost.');">
                    <input type="hidden" name="course_id" value="<?= $c['id'] ?>">
                    <button type="submit" name="delete" class="btn" style="background: #dc3545; color: white; padding: 0.35rem 0.75rem;">Delete</button>
                </form>
            </td>
        </tr>
        <?php endforeach ?>
    </tbody>
</table>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
