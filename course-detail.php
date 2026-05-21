<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

session_start();

$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    header('Location: ' . url('courses.php'));
    exit;
}

$isAdmin = (isset($_SESSION['role']) && $_SESSION['role'] === 'admin');

$sql = "
    SELECT c.*, u.first_name, u.last_name, cat.name as category_name
    FROM courses c
    JOIN users u ON c.instructor_id = u.id
    LEFT JOIN categories cat ON c.category_id = cat.id
    WHERE c.id = ?
";
if (!$isAdmin) {
    $sql .= " AND c.status = 'published'";
}
$stmt = $pdo->prepare($sql);
$stmt->execute([$id]);
$course = $stmt->fetch();

if (!$course) {
    header('Location: ' . url('courses.php'));
    exit;
}

$pageTitle = $course['title'];
$flash = getFlash();

// Check enrollment if logged in
$enrolled = false;
$enrollmentId = null;
$enrollmentStatus = null;
$enrollmentProgress = 0;
if (isLoggedIn() && $_SESSION['role'] === 'student') {
    $stmt = $pdo->prepare("SELECT id, progress_percent, status FROM enrollments WHERE student_id = ? AND course_id = ?");
    $stmt->execute([$_SESSION['user_id'], $id]);
    $enr = $stmt->fetch();
    if ($enr) {
        $enrolled = true;
        $enrollmentId = $enr['id'];
        $enrollmentStatus = $enr['status'];
        $enrollmentProgress = $enr['progress_percent'];
    }
}

// Paid course check
$canEnroll = $course['is_free'] || $enrolled;
$paymentStatus = null;
if (!$course['is_free'] && !$enrolled && isLoggedIn()) {
    $stmt = $pdo->prepare("SELECT status FROM payments WHERE student_id = ? AND course_id = ? ORDER BY id DESC LIMIT 1");
    $stmt->execute([$_SESSION['user_id'], $id]);
    $pRow = $stmt->fetch();
    if ($pRow) {
        $paymentStatus = $pRow['status'];
        if ($paymentStatus === 'completed') $canEnroll = true;
    }
}

// Fetch Curriculum (Units and Materials)
$stmtUnits = $pdo->prepare("SELECT * FROM course_units WHERE course_id = ? ORDER BY sort_order ASC");
$stmtUnits->execute([$id]);
$units = $stmtUnits->fetchAll();

$allMaterials = [];
$stmtMats = $pdo->prepare("SELECT * FROM course_materials WHERE unit_id IN (SELECT id FROM course_units WHERE course_id = ?) ORDER BY sort_order ASC");
$stmtMats->execute([$id]);
foreach ($stmtMats->fetchAll() as $m) { $allMaterials[$m['unit_id']][] = $m; }

require_once __DIR__ . '/includes/header.php';
?>

<?php if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'danger' ?>"><?= e($flash['message']) ?></div>
<?php endif ?>

<div class="course-detail-container" style="display: grid; grid-template-columns: 1fr 350px; gap: 2.5rem; align-items: start;">
    <div>
        <div class="course-hero-content" style="margin-bottom: 2rem;">
            <nav style="font-size: 0.9rem; margin-bottom: 1rem; color: var(--text-muted);">
                <a href="<?= url('courses.php') ?>" style="color: inherit;">Courses</a> / <?= e($course['category_name'] ?? 'General') ?>
            </nav>
            <h1 style="font-size: 2.5rem; margin-bottom: 1rem; line-height: 1.2;"><?= e($course['title']) ?></h1>
            <p style="font-size: 1.1rem; color: #555; margin-bottom: 1.5rem;"><?= e($course['short_description'] ?? '') ?></p>
            
            <div class="course-quick-meta" style="display: flex; gap: 1.5rem; flex-wrap: wrap; align-items: center;">
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <span style="background: var(--light); padding: 0.5rem; border-radius: 50%; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;"></span>
                    <div>
                        <small style="display: block; color: var(--text-muted); font-size: 0.75rem;">Instructor</small>
                        <strong><?= e($course['first_name'] . ' ' . $course['last_name']) ?></strong>
                    </div>
                </div>
                <?php if ($course['duration_hours']): ?>
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <span style="background: var(--light); padding: 0.5rem; border-radius: 50%; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;"></span>
                    <div>
                        <small style="display: block; color: var(--text-muted); font-size: 0.75rem;">Duration</small>
                        <strong><?= $course['duration_hours'] ?> hours</strong>
                    </div>
                </div>
                <?php endif ?>
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <span style="background: var(--light); padding: 0.5rem; border-radius: 50%; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;"></span>
                    <div>
                        <small style="display: block; color: var(--text-muted); font-size: 0.75rem;">Level</small>
                        <strong><?= ucfirst(e($course['level'] ?? 'All')) ?></strong>
                    </div>
                </div>
            </div>
        </div>

        <?php if (!empty($course['thumbnail_url']) && $course['thumbnail_url'] !== 'NULL'): ?>
            <img src="<?= e(url($course['thumbnail_url'])) ?>" alt="<?= e($course['title']) ?>" style="width: 100%; height: 350px; object-fit: cover; border-radius: 16px; margin-bottom: 2rem; box-shadow: 0 4px 20px rgba(0,0,0,0.1);">
        <?php else: ?>
            <img src="<?= url('assets/img/cover1.png') ?>" alt="No Image" style="width: 100%; height: 350px; object-fit: cover; border-radius: 16px; margin-bottom: 2rem; box-shadow: 0 4px 20px rgba(0,0,0,0.1);">
        <?php endif ?>

        <div style="background: white; padding: 2rem; border-radius: 16px; margin-bottom: 2rem; border: 1px solid var(--border);">
            <h3>About this course</h3>
            <div style="line-height: 1.7; color: #444;"><?= nl2br(e($course['description'])) ?></div>
        </div>

        <?php if (!empty($units)): ?>
            <div class="curriculum-preview" style="margin-bottom: 2rem;">
                <h3 style="margin-bottom: 1.5rem;">Course Content</h3>
                <?php foreach ($units as $u): ?>
                    <div style="border: 1px solid var(--border); border-radius: 12px; margin-bottom: 0.75rem; overflow: hidden; background: white;">
                        <div style="background: var(--light); padding: 1rem 1.5rem; font-weight: 600; display: flex; justify-content: space-between; align-items: center;">
                            <span><?= e($u['title']) ?></span>
                            <small class="text-muted"><?= count($allMaterials[$u['id']] ?? []) ?> lessons</small>
                        </div>
                        <?php if (isset($allMaterials[$u['id']])): ?>
                            <ul style="list-style: none; margin: 0; padding: 0;">
                                <?php foreach ($allMaterials[$u['id']] as $m): ?>
                                    <li style="padding: 0.75rem 1.5rem; border-top: 1px solid var(--border); display: flex; align-items: center; gap: 0.75rem; font-size: 0.95rem;">
                                        <span style="opacity: 0.6;"></span>
                                        <?= e($m['title']) ?>
                                    </li>
                                <?php endforeach ?>
                            </ul>
                        <?php endif ?>
                    </div>
                <?php endforeach ?>
            </div>
        <?php endif ?>
    </div>

    <div style="background: white; padding: 2rem; border-radius: 16px; box-shadow: 0 10px 30px rgba(0,0,0,0.08); position: sticky; top: 2rem; border: 1px solid var(--border);">
        <?php if ($isAdmin): ?>
            <div style="margin-bottom: 1.5rem; padding: 1.25rem; background: #e3f2fd; border: 1px solid #90caf9; border-radius: 12px; text-align: center;">
                <p style="margin-bottom: 0.8rem; font-weight: 700; color: #0d47a1; font-size: 0.9rem;">Administrator Review</p>
                <a href="<?= url('admin/course-preview.php?id=' . $id) ?>" class="btn btn-primary" style="width: 100%; display: block;">View Full Content</a>
            </div>
        <?php endif; ?>

        <?php if ($course['is_free']): ?>
            <p style="font-size: 2rem; font-weight: 800; color: var(--teal); margin-bottom: 1.5rem;">Free</p>
        <?php else: ?>
            <p style="font-size: 2rem; font-weight: 800; margin-bottom: 1.5rem;"><?= number_format($course['price'], 2) ?> <span style="font-size: 1rem; font-weight: 400; color: var(--text-muted);">OMR</span></p>
        <?php endif ?>

        <?php if ($enrolled): ?>
            <a href="<?= url('course-view.php?id=' . $id) ?>" class="btn btn-primary" style="width: 100%; display: block; text-align: center; margin-bottom: 0.75rem;">
                <?= $enrollmentStatus === 'completed' ? 'Revisit Course' : 'Continue Learning' ?>
            </a>
            <?php if ($enrollmentStatus === 'completed'): ?>
                <a href="<?= url('my-certificates.php') ?>" class="btn btn-secondary" style="width: 100%; display: block; text-align: center;">View Certificate</a>
            <?php endif ?>
        <?php elseif ($canEnroll && isLoggedIn()): ?>
            <form method="POST" action="<?= url('enroll.php') ?>">
                <input type="hidden" name="course_id" value="<?= $id ?>">
                <button type="submit" class="btn btn-primary" style="width: 100%;">Enroll Now</button>
            </form>
        <?php elseif (isLoggedIn() && $paymentStatus === 'pending'): ?>
            <div class="alert alert-warning" style="text-align: center; font-weight: 600;">Payment pending admin approval.</div>
        <?php elseif (isLoggedIn() && !$course['is_free']): ?>
            <a href="<?= url('payment.php?id=' . $id) ?>" class="btn btn-primary" style="width: 100%; display: block; text-align: center;">Pay Now</a>
        <?php else: ?>
            <a href="<?= url('login.php?redirect=' . urlencode('course-detail.php?id=' . $id)) ?>" class="btn btn-primary" style="width: 100%; display: block; text-align: center;">Login to Enroll</a>
        <?php endif ?>

        <div style="margin-top: 1.5rem; border-top: 1px solid var(--border); padding-top: 1.5rem;">
            <h4 style="font-size: 0.9rem; margin-bottom: 1rem;">This course includes:</h4>
            <ul style="list-style: none; padding: 0; font-size: 0.85rem; color: #555; line-height: 2;">
                <li>Full lifetime access</li>
                <li>Certificate of completion</li>
                <li>Quizzes & Assessments</li>
                <li>Access on mobile and TV</li>
            </ul>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
