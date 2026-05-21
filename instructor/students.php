<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

session_start();
// Assuming requireInstructor exists or checking role manually
if (!isLoggedIn() || $_SESSION['role'] !== 'instructor') {
    header('Location: ' . url('login.php'));
    exit;
}

$instructorId = $_SESSION['user_id'];

// Get all courses owned by this instructor
$stmt = $pdo->prepare("SELECT id, title FROM courses WHERE instructor_id = ? ORDER BY title ASC");
$stmt->execute([$instructorId]);
$courses = $stmt->fetchAll();

// If no course_id provided, default to the first course if available
$courseId = (int)($_GET['course_id'] ?? (!empty($courses) ? $courses[0]['id'] : 0));
$students = [];
$courseTitle = '';

if ($courseId) {
    foreach ($courses as $c) {
        if ($c['id'] == $courseId) {
            $courseTitle = $c['title'];
            break;
        }
    }
    // Verify instructor ownership and fetch student list
    $stmt = $pdo->prepare("
        SELECT u.id as student_id, u.first_name, u.last_name, u.email, 
               e.progress_percent, e.status, e.id as enrollment_id
        FROM enrollments e
        JOIN users u ON e.student_id = u.id
        JOIN courses c ON e.course_id = c.id
        WHERE e.course_id = ? AND c.instructor_id = ?
        ORDER BY u.last_name, u.first_name
    ");
    $stmt->execute([$courseId, $instructorId]);
    $students = $stmt->fetchAll();

    // Get total assessments for this course to calculate completion ratio
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM assessments WHERE course_id = ?");
    $stmt->execute([$courseId]);
    $totalAssessments = $stmt->fetchColumn();
}

$pageTitle = 'Student Gradebook';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container">
    <h2 class="section-title">Student Book <?= $courseTitle ? '- ' . e($courseTitle) : '' ?></h2>

    <?php if ($courseId && !empty($students)): $courseIdEnc = (int)$courseId; ?>
        <div class="form-card" style="max-width: 100%;">
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="border-bottom: 2px solid var(--border);">
                        <th style="padding: 1rem; text-align: left;">Student</th>
                        <th style="padding: 1rem; text-align: left;">Course Progress</th>
                        <th style="padding: 1rem; text-align: left;">Submissions</th>
                        <th style="padding: 1rem; text-align: left;">Avg. Grade</th>
                        <th style="padding: 1rem; text-align: right;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($students as $s): ?>
                        <tr style="border-bottom: 1px solid var(--border);">
                            <td style="padding: 1rem;">
                                <strong><?= e($s['first_name'] . ' ' . $s['last_name']) ?></strong><br>
                                <small class="text-muted"><?= e($s['email']) ?></small>
                            </td>
                            <td style="padding: 1rem;">
                                <div class="progress-bar" style="width: 100px; height: 8px;"><div class="progress-bar-fill" style="width: <?= (float)$s['progress_percent'] ?>%"></div></div>
                                <small><?= number_format($s['progress_percent'], 0) ?>%</small>
                            </td>
                            <td style="padding: 1rem;">
                                <?php
                                $sub = $pdo->prepare("SELECT COUNT(DISTINCT assessment_id) FROM assessment_attempts WHERE enrollment_id = ?");
                                $sub->execute([$s['enrollment_id']]);
                                $count = $sub->fetchColumn();
                                echo "<span class='badge'>" . $count . " / " . $totalAssessments . "</span>";
                                ?>
                            </td>
                            <td style="padding: 1rem; font-weight: bold;">
                                <?php
                                $score = $pdo->prepare("SELECT AVG(percent_score) FROM assessment_attempts WHERE enrollment_id = ?");
                                $score->execute([$s['enrollment_id']]);
                                $avg = $score->fetchColumn();
                                echo $avg !== null ? number_format($avg, 1) . '%' : '--';
                                ?>
                            </td>
                            <td style="padding: 1rem; text-align: right;">
                                <a href="grade-student.php?enrollment_id=<?= $s['enrollment_id'] ?>" class="btn btn-primary btn-sm">Review Work</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php elseif ($courseId && empty($students)): ?>
        <p class="text-muted">No students are currently enrolled in this course.</p>
    <?php else: ?>
        <div class="alert alert-info">Please select a course from your <a href="<?= url('instructor/index.php') ?>">Dashboard</a> to view the Student Book.</div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>