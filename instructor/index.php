<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

session_start();
requireInstructor();

$pageTitle = 'Instructor Dashboard';
$userId = $_SESSION['user_id'];
$user = getCurrentUser();



// stats:


$stmt = $pdo->prepare("SELECT COUNT(*) FROM courses WHERE instructor_id = ?");
$stmt->execute([$userId]);
$stats['courses'] = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM enrollments e JOIN courses c ON e.course_id = c.id WHERE c.instructor_id = ?");
$stmt->execute([$userId]);
$stats['enrollments'] = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM certificates cert JOIN courses c ON cert.course_id = c.id WHERE c.instructor_id = ? AND cert.status = 'pending_admin'");
$stmt->execute([$userId]);
$stats['pending_certs'] = $stmt->fetchColumn();


// for courses with enrollment counts

$courses = $pdo->prepare("
    SELECT c.*, COUNT(e.id) as enrollments
    FROM courses c
    LEFT JOIN enrollments e ON c.id = e.course_id AND e.status = 'active'
    WHERE c.instructor_id = ?
    GROUP BY c.id
    ORDER BY c.updated_at DESC
");
$courses->execute([$userId]);
$courses = $courses->fetchAll();

// the notifications
$notifications = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 10");
$notifications->execute([$userId]);
$notifications = $notifications->fetchAll();

$flash = getFlash();

require_once __DIR__ . '/../includes/header.php';
?>

<?php if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'danger' ?>">
        <?= e($flash['message']) ?>
    </div>
<?php endif ?>

<?php $avatar = ($user && !empty($user['avatar_url'])) ? $user['avatar_url'] : 'assets/img/inst.jpg'; ?>
<div class="dashboard-header">
    <div class="user-welcome">
        <img src="<?= url($avatar) ?>" alt="Avatar" class="user-avatar-large">
        <h2 class="section-title">Instructor Dashboard</h2>
    </div>
    <a href="<?= url('profile.php') ?>" title="Profile Settings" class="settings-link">
        <img src="<?= url('assets/img/setting.jpg') ?>" alt="Settings" class="settings-icon">
    </a>
</div>

<div class="stats-row">
    <div class="stat-card">
        <div class="number"><?= $stats['courses'] ?></div>
        <div class="label">My Courses</div>
    </div>
    <div class="stat-card">
        <div class="number"><?= $stats['enrollments'] ?></div>
        <div class="label">Enrollments</div>
    </div>
    <div class="stat-card">
        <div class="number"><?= $stats['pending_certs'] ?></div>
        <div class="label">Certs Pending Admin</div>
    </div>
</div>

<div class="dashboard-grid">
    <aside class="sidebar">
        <h3 style="margin-top: 0;">Instructor Menu</h3>
        <nav class="sidebar-nav">
            <a href="<?= url('instructor/index.php') ?>" class="active">Dashboard</a>
            <a href="<?= url('instructor/courses.php') ?>">My Courses</a>
            <a href="<?= url('instructor/course-add.php') ?>">Create Course</a>
            <a href="<?= url('instructor/announcements.php') ?>">Announcements</a>
            <a href="<?= url('instructor/certificates.php') ?>">Certificates</a>
        </nav>
    </aside>

    <div>
        <h3>My Courses</h3>

        <?php if (empty($courses)): ?>
            <p class="text-muted">You have no courses yet. <a href="<?= url('instructor/course-add.php') ?>">Create one</a>.</p>
        <?php else: ?>
            <div class="course-grid">
                <?php foreach ($courses as $c): ?>
                    <?php
                        $thumb = $c['thumbnail_url'] ?? '';
                        // التحقق من الرابط سواء كان سحابياً أو محلياً
                        $coverImage = (!empty($thumb) && (strpos($thumb, 'http') === 0 || file_exists(__DIR__ . '/../' . $thumb))) 
                                      ? url($thumb) 
                                      : url('assets/img/cover1.png');
                    ?>
                <div class="course-card">
                        <div class="course-card-image">
                            <img src="<?= e($coverImage) ?>" alt="<?= e($c['title']) ?>" style="width:100%; height:150px; object-fit:cover; border-radius:8px;">
                        </div>
                        <div class="course-card-body">
                            <h3><?= e($c['title']) ?></h3>
                            <p class="course-meta"><?= ucfirst(e($c['status'])) ?> • <?= (int)$c['enrollments'] ?> enrolled</p>
                        <div style="margin-top: 10px; border-top: 1px solid #eee; padding-top: 10px; display: flex; justify-content: space-between;">
                            <a href="<?= url('instructor/course-edit.php?id=' . $c['id']) ?>" style="color: var(--primary); font-weight: 600; font-size: 0.9rem; text-decoration: none;">Manage Course</a>
                                <a href="<?= url('instructor/students.php?course_id=' . (int) $c['id']) ?>" style="color: var(--primary); font-weight: 600; font-size: 0.9rem; text-decoration: none;">View Student Book →</a>
                            </div>
                        </div>
                </div>
                <?php endforeach ?>
            </div>
        <?php endif ?>

        <?php if (!empty($notifications)): ?>
            <h3 class="mt-2">Notifications</h3>
            <?php foreach ($notifications as $n): ?>
                <div class="alert alert-info"><?= e($n['title']) ?>: <?= e($n['message']) ?></div>
            <?php endforeach ?>
        <?php endif ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>