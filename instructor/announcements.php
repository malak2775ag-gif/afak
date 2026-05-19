<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

session_start();
requireInstructor();

$pageTitle = 'Announcements';
$userId = $_SESSION['user_id'];
$flash = getFlash();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add'])) {
        $courseId = (int)$_POST['course_id'];
        $title = trim($_POST['title'] ?? '');
        $content = trim($_POST['content'] ?? '');

        if ($title && $content) {
            $stmt = $pdo->prepare("SELECT id FROM courses WHERE id = ? AND instructor_id = ?");
            $stmt->execute([$courseId, $userId]);
            if ($stmt->fetch()) {
                $pdo->prepare("INSERT INTO announcements (course_id, created_by, title, content, status, published_at) VALUES (?, ?, ?, ?, 'approved', NOW())")
                    ->execute([$courseId, $userId, $title, $content]);
                flash('success', 'Announcement published successfully.');
            }
        }
    } elseif (isset($_POST['delete'])) {
        $annId = (int)$_POST['announcement_id'];
        $pdo->prepare("DELETE FROM announcements WHERE id = ? AND created_by = ?")->execute([$annId, $userId]);
        flash('success', 'Announcement deleted.');
    }
    header('Location: ' . url('instructor/announcements.php'));
    exit;
}

$myCourses = $pdo->prepare("SELECT id, title FROM courses WHERE instructor_id = ?");
$myCourses->execute([$userId]);
$myCourses = $myCourses->fetchAll();

$announcements = $pdo->prepare("
    SELECT a.*, c.title as course_title
    FROM announcements a
    JOIN courses c ON a.course_id = c.id
    WHERE c.instructor_id = ?
    ORDER BY a.created_at DESC
");
$announcements->execute([$userId]);
$announcements = $announcements->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<h2 class="section-title">Announcements</h2>

<?php if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'danger' ?>"><?= e($flash['message']) ?></div>
<?php endif ?>

<?php if (!empty($myCourses)): ?>
<div class="form-card" style="max-width: 500px; margin-bottom: 2rem;">
    <h3>Create Announcement</h3>
    <form method="POST">
        <input type="hidden" name="add" value="1">
        <div class="form-group">
            <label>Course</label>
            <select name="course_id" class="form-control" required>
                <?php foreach ($myCourses as $c): ?>
                    <option value="<?= $c['id'] ?>"><?= e($c['title']) ?></option>
                <?php endforeach ?>
            </select>
        </div>
        <div class="form-group">
            <label>Title</label>
            <input type="text" name="title" class="form-control" required>
        </div>
        <div class="form-group">
            <label>Content</label>
            <textarea name="content" class="form-control" rows="4" required></textarea>
        </div>
        <button type="submit" class="btn btn-primary">Post Announcement</button>
    </form>
</div>
<?php endif ?>

<h3>My Announcements</h3>
<table style="width: 100%; background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 12px rgba(0,0,0,0.06);">
    <thead>
        <tr style="background: var(--light);">
            <th style="padding: 1rem; text-align: left;">Title</th>
            <th style="padding: 1rem;">Course</th>
            <th style="padding: 1rem;">Status</th>
            <th style="padding: 1rem;">Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($announcements as $a): ?>
        <tr style="border-top: 1px solid var(--border);">
            <td style="padding: 1rem;"><?= e($a['title']) ?></td>
            <td style="padding: 1rem;"><?= e($a['course_title']) ?></td>
            <td style="padding: 1rem;"><?= e($a['status']) ?></td>
            <td style="padding: 1rem;">
                <form method="POST" style="display: inline;" onsubmit="return confirm('Delete this announcement?');">
                    <input type="hidden" name="announcement_id" value="<?= $a['id'] ?>">
                    <button type="submit" name="delete" class="btn" style="background: #dc3545; color: white; padding: 0.35rem 0.75rem; font-size: 0.8rem;">Delete</button>
                </form>
            </td>
        </tr>
        <?php endforeach ?>
    </tbody>
</table>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
