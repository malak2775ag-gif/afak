<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

session_start();
requireStudent();

$attemptId = (int)($_GET['attempt'] ?? 0);
if (!$attemptId) {
    header('Location: ' . url('dashboard.php'));
    exit;
}


$stmt = $pdo->prepare("
    SELECT aa.*, a.title as quiz_title, u.first_name, u.last_name, u.username, c.title as course_title
    FROM assessment_attempts aa
    JOIN assessments a ON aa.assessment_id = a.id
    JOIN enrollments e ON aa.enrollment_id = e.id
    JOIN users u ON e.student_id = u.id
    JOIN courses c ON a.course_id = c.id
    WHERE aa.id = ? AND e.student_id = ?
");
$stmt->execute([$attemptId, $_SESSION['user_id']]);
$attempt = $stmt->fetch();

if (!$attempt) {
    header('Location: ' . url('dashboard.php'));
    exit;
}

$pageTitle = 'Quiz Submitted';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="max-width: 600px; margin: 40px auto;">
    <div class="form-card" style="text-align: center; padding: 2.5rem; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.1);">
        <div style="font-size: 4rem; color: #2ecc71; margin-bottom: 1rem;"></div>
        <h2 style="margin-bottom: 0.5rem;">The quiz was successfully delivered</h2>
        <p class="text-muted" style="margin-bottom: 2rem;">Your answers have been successfully recorded</p>

        <div style="background: #f8f9fa; border-radius: 12px; padding: 1.5rem; margin-bottom: 2rem; border: 1px solid #eee; text-align: left;">
            <div style="margin-bottom: 1rem; border-bottom: 1px solid #eee; padding-bottom: 0.5rem;">
                <small class="text-muted" style="display: block; font-size: 0.8rem;">Student name</small>
                <strong style="font-size: 1.1rem;"><?= e($attempt['first_name'] . ' ' . $attempt['last_name']) ?> (<?= e($attempt['username']) ?>)</strong>
            </div>
            <div style="margin-bottom: 1rem; border-bottom: 1px solid #eee; padding-bottom: 0.5rem;">
                <small class="text-muted" style="display: block; font-size: 0.8rem;"> Quiz address</small>
                <strong style="font-size: 1.1rem;"><?= e($attempt['quiz_title']) ?></strong>
            </div>
            <div style="margin-bottom: 1rem; border-bottom: 1px solid #eee; padding-bottom: 0.5rem;">
                <small class="text-muted" style="display: block; font-size: 0.8rem;"> last modified</small>
                <strong style="font-size: 1.1rem;"><?= date('H:i:s - Y/m/d', strtotime($attempt['submitted_at'])) ?></strong>
            </div>
            <div>
                <small class="text-muted" style="display: block; font-size: 0.8rem;">mark</small>
                <strong style="color: <?= $attempt['passed'] ? '#2ecc71' : '#f39c12' ?>; font-size: 1.5rem;">
                    <?= number_format($attempt['percent_score'], 1) ?>%
                </strong>
            </div>
        </div>

        <div style="display: flex; gap: 1rem;">
            <a href="<?= url('quiz-result.php?attempt=' . $attemptId) ?>" class="btn btn-primary" style="flex: 1; padding: 12px;">View</a>
            <a href="<?= url('dashboard.php') ?>" class="btn btn-secondary" style="flex: 1; padding: 12px;">Dashboard</a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
