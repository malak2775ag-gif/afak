<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

session_start();
requireStudent();

$pageTitle = 'Student Report';
$userId = (int) $_SESSION['user_id'];
$user = getCurrentUser() ?: [];

$stats = [
    'active_courses' => 0,
    'completed_courses' => 0,
    'certificates' => 0,
    'avg_quiz_score' => 0,
];

$stmt = $pdo->prepare("
    SELECT
        SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) AS active_courses,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) AS completed_courses
    FROM enrollments
    WHERE student_id = ?
");
$stmt->execute([$userId]);
$row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
$stats['active_courses'] = (int) ($row['active_courses'] ?? 0);
$stats['completed_courses'] = (int) ($row['completed_courses'] ?? 0);

$stmt = $pdo->prepare("SELECT COUNT(*) FROM certificates WHERE student_id = ? AND status = 'approved'");
$stmt->execute([$userId]);
$stats['certificates'] = (int) $stmt->fetchColumn();

$stmt = $pdo->prepare("
    SELECT AVG(aa.percent_score)
    FROM assessment_attempts aa
    JOIN enrollments e ON aa.enrollment_id = e.id
    WHERE e.student_id = ?
");
$stmt->execute([$userId]);
$stats['avg_quiz_score'] = round((float) ($stmt->fetchColumn() ?: 0), 1);

$stmt = $pdo->prepare("
    SELECT
        c.title,
        e.status,
        e.progress_percent,
        e.enrolled_at,
        e.completed_at
    FROM enrollments e
    JOIN courses c ON c.id = e.course_id
    WHERE e.student_id = ?
    ORDER BY e.enrolled_at DESC
");
$stmt->execute([$userId]);
$courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("
    SELECT
        a.title AS assessment_title,
        c.title AS course_title,
        aa.percent_score,
        aa.passed,
        aa.submitted_at
    FROM assessment_attempts aa
    JOIN enrollments e ON aa.enrollment_id = e.id
    JOIN assessments a ON a.id = aa.assessment_id
    JOIN courses c ON c.id = a.course_id
    WHERE e.student_id = ?
      AND aa.id IN (
          SELECT MAX(aa2.id)
          FROM assessment_attempts aa2
          JOIN enrollments e2 ON aa2.enrollment_id = e2.id
          WHERE e2.student_id = ?
          GROUP BY aa2.assessment_id, aa2.enrollment_id
      )
    ORDER BY aa.submitted_at DESC
");
$stmt->execute([$userId, $userId]);
$quizResults = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("
    SELECT c.title AS course_title, cert.certificate_code, cert.issued_at, cert.created_at
    FROM certificates cert
    JOIN courses c ON c.id = cert.course_id
    WHERE cert.student_id = ? AND cert.status = 'approved'
    ORDER BY cert.issued_at DESC, cert.created_at DESC
");
$stmt->execute([$userId]);
$certificates = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once __DIR__ . '/includes/header.php';
?>

<div class="form-card" style="max-width: 1100px; margin: 0 auto;">
    <div style="display: flex; justify-content: space-between; gap: 1rem; align-items: center; flex-wrap: wrap; margin-bottom: 1.5rem;">
        <div>
            <h2 style="margin-bottom: 0.35rem;">Student Progress Report</h2>
            <p class="text-muted" style="margin: 0;">
                <?= e(trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''))) ?> |
                <?= e($user['email'] ?? '') ?> |
                Generated on <?= date('M j, Y g:i A') ?>
            </p>
        </div>
        <button type="button" class="btn btn-primary" onclick="window.print()">Print / Save PDF</button>
    </div>

    <div class="stats-row" style="margin-bottom: 2rem;">
        <div class="stat-card">
            <div class="number"><?= $stats['active_courses'] ?></div>
            <div class="label">Active Courses</div>
        </div>
        <div class="stat-card">
            <div class="number"><?= $stats['completed_courses'] ?></div>
            <div class="label">Completed Courses</div>
        </div>
        <div class="stat-card">
            <div class="number"><?= $stats['certificates'] ?></div>
            <div class="label">Certificates</div>
        </div>
        <div class="stat-card">
            <div class="number"><?= number_format($stats['avg_quiz_score'], 1) ?>%</div>
            <div class="label">Average Quiz Score</div>
        </div>
    </div>

    <h3>Enrolled Courses</h3>
    <?php if (empty($courses)): ?>
        <p class="text-muted">No course activity yet.</p>
    <?php else: ?>
        <table style="width: 100%; border-collapse: collapse; margin-bottom: 2rem;">
            <thead>
                <tr style="background: var(--light);">
                    <th style="text-align: left; padding: 0.85rem;">Course</th>
                    <th style="text-align: left; padding: 0.85rem;">Status</th>
                    <th style="text-align: left; padding: 0.85rem;">Progress</th>
                    <th style="text-align: left; padding: 0.85rem;">Dates</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($courses as $course): ?>
                    <tr style="border-top: 1px solid var(--border);">
                        <td style="padding: 0.85rem;"><?= e($course['title']) ?></td>
                        <td style="padding: 0.85rem;"><?= e(ucfirst($course['status'])) ?></td>
                        <td style="padding: 0.85rem;"><?= number_format((float) $course['progress_percent'], 0) ?>%</td>
                        <td style="padding: 0.85rem;">
                            Enrolled: <?= date('M j, Y', strtotime($course['enrolled_at'])) ?><br>
                            <?php if (!empty($course['completed_at'])): ?>
                                Completed: <?= date('M j, Y', strtotime($course['completed_at'])) ?>
                            <?php else: ?>
                                <span class="text-muted">Not completed yet</span>
                            <?php endif ?>
                        </td>
                    </tr>
                <?php endforeach ?>
            </tbody>
        </table>
    <?php endif ?>

    <h3>Quiz Results</h3>
    <?php if (empty($quizResults)): ?>
        <p class="text-muted">No quiz submissions yet.</p>
    <?php else: ?>
        <table style="width: 100%; border-collapse: collapse; margin-bottom: 2rem;">
            <thead>
                <tr style="background: var(--light);">
                    <th style="text-align: left; padding: 0.85rem;">Assessment</th>
                    <th style="text-align: left; padding: 0.85rem;">Course</th>
                    <th style="text-align: left; padding: 0.85rem;">Score</th>
                    <th style="text-align: left; padding: 0.85rem;">Result</th>
                    <th style="text-align: left; padding: 0.85rem;">Submitted</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($quizResults as $result): ?>
                    <tr style="border-top: 1px solid var(--border);">
                        <td style="padding: 0.85rem;"><?= e($result['assessment_title']) ?></td>
                        <td style="padding: 0.85rem;"><?= e($result['course_title']) ?></td>
                        <td style="padding: 0.85rem;"><?= number_format((float) $result['percent_score'], 1) ?>%</td>
                        <td style="padding: 0.85rem;"><?= $result['passed'] ? 'Passed' : 'Needs improvement' ?></td>
                        <td style="padding: 0.85rem;"><?= date('M j, Y', strtotime($result['submitted_at'])) ?></td>
                    </tr>
                <?php endforeach ?>
            </tbody>
        </table>
    <?php endif ?>

    <h3>Issued Certificates</h3>
    <?php if (empty($certificates)): ?>
        <p class="text-muted">No certificates issued yet.</p>
    <?php else: ?>
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="background: var(--light);">
                    <th style="text-align: left; padding: 0.85rem;">Course</th>
                    <th style="text-align: left; padding: 0.85rem;">Certificate Code</th>
                    <th style="text-align: left; padding: 0.85rem;">Issued On</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($certificates as $certificate): ?>
                    <tr style="border-top: 1px solid var(--border);">
                        <td style="padding: 0.85rem;"><?= e($certificate['course_title']) ?></td>
                        <td style="padding: 0.85rem;"><?= e($certificate['certificate_code']) ?></td>
                        <td style="padding: 0.85rem;"><?= date('M j, Y', strtotime($certificate['issued_at'] ?? $certificate['created_at'])) ?></td>
                    </tr>
                <?php endforeach ?>
            </tbody>
        </table>
    <?php endif ?>
</div>

<style>
@media print {
    .site-header,
    .btn,
    .nav-toggle,
    footer {
        display: none !important;
    }

    .main-content {
        padding-top: 0;
    }

    .form-card {
        box-shadow: none !important;
        border: 0 !important;
        padding: 0 !important;
    }
}
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
