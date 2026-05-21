<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

session_start();
requireInstructor();

$pageTitle = 'My Courses';
$userId = $_SESSION['user_id'];

$courses = $pdo->prepare("
    SELECT c.*, COUNT(e.id) as enrollments
    FROM courses c
    LEFT JOIN enrollments e ON e.course_id = c.id AND e.status = 'active'
    WHERE c.instructor_id = ?
    GROUP BY c.id
    ORDER BY c.updated_at DESC
");
$courses->execute([$userId]);
$courses = $courses->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="ins-wrap">
    <div class="ins-breadcrumb">
        <a href="<?= url('instructor/index.php') ?>">Instructor</a>
        <span> / </span>
        <span>My courses</span>
    </div>

    <div class="ins-page-head">
        <h1 class="ins-title">My courses</h1>
        <div class="ins-actions">
            <a href="<?= url('instructor/course-add.php') ?>" class="btn btn-primary">+ New course</a>
        </div>
    </div>

    <?php if (empty($courses)): ?>
        <div class="ins-card">
            <p class="ins-card-muted" style="margin:0;">You have no courses yet.</p>
            <a href="<?= url('instructor/course-add.php') ?>" class="btn btn-primary mt-2" style="display:inline-block;margin-top:1rem;">Create your first course</a>
        </div>
    <?php else: ?>
        <div class="ins-course-grid">
            <?php foreach ($courses as $c): ?>
                <?php
                $serverPath = __DIR__ . '/../' . ($c['thumbnail_url'] ?? '');
                if (!empty($c['thumbnail_url']) && file_exists($serverPath)) {
                    $img = url($c['thumbnail_url']);
                } else {
                    $img = url('assets/img/cover1.png');
                }
                $badge = 'ins-badge-' . preg_replace('/[^a-z0-9_]/', '', $c['status']);
                ?>
                <div class="ins-course-card" style="cursor:default;">
                    <div class="ins-course-card-img">
                        <img src="<?= e($img) ?>" alt="">
                    </div>
                    <div class="ins-course-card-body">
                        <span class="ins-badge <?= e($badge) ?>" style="margin-bottom:0.5rem;"><?= e(str_replace('_', ' ', $c['status'])) ?></span>
                        <h3><?= e($c['title']) ?></h3>
                        <div class="ins-course-card-meta"><?= (int) $c['enrollments'] ?> active learners</div>
                        <div class="ins-course-card-actions">
                            <a href="<?= url('instructor/students.php?course_id=' . (int) $c['id']) ?>" class="btn btn-secondary">Students</a>
                            <a href="<?= url('instructor/course-edit.php?id=' . (int) $c['id']) ?>" class="btn btn-primary">Edit</a>
                            <?php if ($c['status'] === 'published'): ?>
                                <a href="<?= url('course-detail.php?id=' . (int) $c['id']) ?>" class="btn btn-secondary">View</a>
                            <?php else: ?>
                                <span class="btn btn-secondary" style="opacity:0.6;cursor:not-allowed;" title="Visible after publish">View</span>
                            <?php endif ?>
                        </div>
                    </div>
                </div>
            <?php endforeach ?>
        </div>
    <?php endif ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
