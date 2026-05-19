<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/recommendations.php';

session_start();
requireStudent();

$pageTitle = 'Dashboard';
$userId = $_SESSION['user_id'];

// Redirect to survey if not completed
if (afak_student_needs_survey($pdo, (int) $userId)) {
    header('Location: ' . url('survey.php'));
    exit;
}

$user = getCurrentUser();
$learningProfile = afak_get_learning_profile($pdo, (int) $userId);

// Stats
$enrolledCount = $pdo->prepare("SELECT COUNT(*) FROM enrollments WHERE student_id = ? AND status = 'active'");
$enrolledCount->execute([$userId]);
$enrolledCount = $enrolledCount->fetchColumn();

$completedCount = $pdo->prepare("SELECT COUNT(*) FROM enrollments WHERE student_id = ? AND status = 'completed'");
$completedCount->execute([$userId]);
$completedCount = $completedCount->fetchColumn();

$certificatesCount = $pdo->prepare("SELECT COUNT(*) FROM certificates WHERE student_id = ? AND status = 'approved'");
$certificatesCount->execute([$userId]);
$certificatesCount = $certificatesCount->fetchColumn();


$stmt = $pdo->prepare("
    SELECT e.id as enrollment_id, e.progress_percent, e.time_spent_seconds, e.status as enrollment_status, c.id, c.title, c.slug, c.thumbnail_url
    FROM enrollments e
    JOIN courses c ON e.course_id = c.id
    WHERE e.student_id = ?
    ORDER BY e.enrolled_at DESC
");
$stmt->execute([$userId]);
$enrollments = $stmt->fetchAll();

// notifications
$notifications = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
$notifications->execute([$userId]);
$notifications = $notifications->fetchAll();

// Fetch announcements for enrolled courses
$annStmt = $pdo->prepare("
    SELECT a.*, c.title as course_title, u.first_name, u.last_name
    FROM announcements a
    JOIN courses c ON a.course_id = c.id
    JOIN enrollments e ON e.course_id = c.id
    JOIN users u ON a.created_by = u.id
    WHERE e.student_id = ? AND a.status = 'approved'
    ORDER BY a.published_at DESC
    LIMIT 5
");
$annStmt->execute([$userId]);
$courseAnnouncements = $annStmt->fetchAll();

$recommendedCourses = afak_recommend_courses($pdo, (int) $userId, 6);
// Refine the top 3 recommendations using AI
if (!empty($recommendedCourses)) {
    $topThree = array_slice($recommendedCourses, 0, 3);
    $refined = afak_ai_refine_recommendations($topThree, $learningProfile ?: []);
    // Merge refined reasons back into the main list
    foreach($refined as $index => $course) {
        $recommendedCourses[$index] = $course;
    }
}

$flash = getFlash();

require_once __DIR__ . '/includes/header.php';
?>

<?php if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'danger' ?>"><?= e($flash['message']) ?></div>
<?php endif ?>

<?php $avatar = ($user && !empty($user['avatar_url'])) ? $user['avatar_url'] : 'assets/img/std.jpg'; ?>
<div class="dashboard-header">
    <div class="user-welcome">
        <img src="<?= url($avatar) ?>" alt="Avatar" class="user-avatar-large">
        <h2 class="section-title">Welcome back, <?= e($_SESSION['first_name']) ?>!</h2>
    </div>
    <a href="<?= url('profile.php') ?>" title="Profile Settings" class="settings-link">
        <img src="<?= url('assets/img/setting.jpg') ?>" alt="Settings" class="settings-icon">
    </a>
</div>

<div class="stats-row">
    <div class="stat-card">
        <div class="number"><?= $enrolledCount ?></div>
        <div class="label">Active Courses</div>
    </div>
    <div class="stat-card">
        <div class="number"><?= $completedCount ?></div>
        <div class="label">Completed</div>
    </div>
    <div class="stat-card">
        <div class="number"><?= $certificatesCount ?></div>
        <div class="label">Certificates</div>
    </div>
</div>

<div class="dashboard-grid">
    <aside class="sidebar">
        <h3 style="margin-top: 0;">Menu</h3>
        <nav class="sidebar-nav">
            <a href="<?= url('dashboard.php') ?>" class="active">Dashboard</a>
            <a href="<?= url('courses.php') ?>">Browse Courses</a>
            <a href="<?= url('my-certificates.php') ?>">My Certificates</a>
            <a href="<?= url('student-report.php') ?>">My Report</a>
            <a href="<?= url('profile.php') ?>">My Profile</a>
        </nav>
    </aside>

    <div>
        <div style="display: flex; justify-content: space-between; align-items: center; gap: 1rem; flex-wrap: wrap; margin-bottom: 1rem;">
            <h3 style="margin: 0;">My Courses</h3>
            <a href="<?= url('student-report.php') ?>" class="btn btn-secondary">Generate Report</a>
        </div>
        <?php if (empty($enrollments)): ?>
            <p class="text-muted">You haven't enrolled in any courses yet. <a href="<?= url('courses.php') ?>">Browse courses</a> to get started.</p>
        <?php else: ?>
            <div class="course-grid">
                <?php foreach ($enrollments as $e): ?>
                    <a href="<?= url('course-view.php?id=' . $e['id']) ?>" class="course-card" style="text-decoration: none; color: inherit;">
                        <div class="course-card-image">
                            <?php if (!empty($e['thumbnail_url']) && $e['thumbnail_url'] !== 'NULL'): ?>
                                <img src="<?= e(url($e['thumbnail_url'])) ?>" alt="<?= e($e['title']) ?>">
                            <?php else: ?>
                                <img src="<?= url('assets/img/cover1.png') ?>" alt="No Image">
                            <?php endif ?>
                        </div>
                        <div class="course-card-body">
                            <h3><?= e($e['title']) ?></h3>
                            <div class="progress-bar mt-1">
                                <div class="progress-bar-fill" style="width: <?= (float)$e['progress_percent'] ?>%"></div>
                            </div>
                            <p class="course-meta"><?= number_format($e['progress_percent'], 0) ?>% <?= $e['enrollment_status'] === 'completed' ? 'completed' : 'complete' ?></p>
                        </div>
                    </a>
                <?php endforeach ?>
            </div>
        <?php endif ?>

        <div style="display:flex;justify-content:space-between;align-items:center;gap:1rem;flex-wrap:wrap;margin-top:2rem;">
            <h3 style="margin:0;">Recommended For You</h3>
            <a href="<?= url('survey.php?edit=1') ?>" class="btn btn-secondary">Update Survey</a>
        </div>
        <?php if (empty($recommendedCourses)): ?>
            <p class="text-muted">You have to do learning style survey first.</p>
        <?php else: ?>
            <div class="course-grid">
                <?php foreach ($recommendedCourses as $course): ?>
                    <a href="<?= url('course-detail.php?id=' . $course['id']) ?>" class="course-card" style="text-decoration:none;color:inherit;">
                        <div class="course-card-image">
                            <?php if (!empty($course['thumbnail_url']) && $course['thumbnail_url'] !== 'NULL'): ?>
                                <img src="<?= e(url($course['thumbnail_url'])) ?>" alt="<?= e($course['title']) ?>">
                            <?php else: ?>
                                <img src="<?= url('assets/img/cover1.png') ?>" alt="No Image">
                            <?php endif ?>
                        </div>
                        <div class="course-card-body">
                            <h3><?= e($course['title']) ?></h3>
                            <p><?= e(mb_substr($course['short_description'] ?? $course['description'], 0, 90)) ?>...</p>
                            <?php if (!empty($course['recommendation_reason'])): ?>
                                <p class="text-muted" style="font-size:0.85rem;margin-top:0.5rem;">
                                    Why recommended: <?= e($course['recommendation_reason']) ?>
                                </p>
                            <?php endif ?>
                        </div>
                    </a>
                <?php endforeach ?>
            </div>
        <?php endif ?>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap: 2rem; margin-top: 3rem; border-top: 2px solid #eee; padding-top: 2rem;">
            
            <div>
                <?php if (!empty($courseAnnouncements)): ?>
                    <h3 style="margin-top: 0; display: flex; align-items: center; gap: 10px;"><i class="fas fa-bullhorn" style="color: #f39c12;"></i> Course Announcements</h3>
                    <?php foreach ($courseAnnouncements as $ann): ?>
                        <div class="alert alert-warning" style="background-color: #fffaf0; border-color: #ffeeba; color: #856404; margin-bottom: 1.5rem; border-radius: 12px; border-left: 5px solid #f39c12; box-shadow: 0 2px 8px rgba(0,0,0,0.05);">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 5px;">
                                <strong style="font-size: 1.05rem; color: #d35400;"><?= e($ann['title']) ?></strong>
                                <small style="color: #7f8c8d;"><?= date('M j', strtotime($ann['published_at'])) ?></small>
                            </div>
                            <div style="font-size: 0.75rem; margin-bottom: 10px; color: #7f8c8d; border-bottom: 1px solid rgba(0,0,0,0.05); padding-bottom: 5px;">
                                <strong><?= e($ann['course_title']) ?></strong> • <?= e($ann['first_name'] . ' ' . $ann['last_name']) ?>
                            </div>
                            <div style="font-size: 0.9rem; color: #2c3e50; line-height: 1.4;">
                                <?= nl2br(e($ann['content'])) ?>
                            </div>
                            <?php if (strpos($ann['content'], 'h5p') !== false): ?>
                                <div style="margin-top:12px; border-radius: 8px; overflow: hidden; border: 1px solid #ddd; background: #f0f0f0;">
                                    <iframe src="<?= e(trim($ann['content'])) ?>" width="100%" height="220" frameborder="0" allowfullscreen></iframe>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach ?>
                <?php else: ?>
                    <p class="text-muted">No course announcements yet.</p>
                <?php endif ?>
            </div>

            <!-- Personal notifications column -->
            <div>
                <?php if (!empty($notifications)): ?>
                    <h3 style="margin-top: 0; display: flex; align-items: center; gap: 10px;"><i class="fas fa-bell" style="color: #3498db;"></i> Recent Notifications</h3>
                    <?php foreach ($notifications as $n): ?>
                        <div class="alert alert-info" style="margin-bottom: 1rem; border-radius: 12px; padding: 1rem; background: #f0f7ff; border-color: #cce5ff;"><?= e($n['title']) ?>: <?= e($n['message']) ?></div>
                    <?php endforeach ?>
                <?php else: ?>
                    <h3 style="margin-top: 0;">Notifications</h3>
                    <p class="text-muted">No new notifications.</p>
                <?php endif ?>
            </div>

        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
