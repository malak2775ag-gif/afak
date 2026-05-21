<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

session_start();
requireInstructor();

$studentId = (int)($_GET['student'] ?? 0);
$courseId = (int)($_GET['course'] ?? 0);
$attemptId = (int)($_GET['attempt'] ?? 0);

if (!$studentId || !$courseId) {
    header('Location: ' . url('instructor/students.php'));
    exit;
}

$stmt = $pdo->prepare("SELECT c.instructor_id FROM courses c WHERE c.id = ?");
$stmt->execute([$courseId]);
$course = $stmt->fetch();
if (!$course || $course['instructor_id'] != $_SESSION['user_id']) {
    header('Location: ' . url('instructor/students.php'));
    exit;
}

$student = $pdo->prepare("SELECT * FROM users WHERE id = ? AND role = 'student'");
$student->execute([$studentId]);
$student = $student->fetch();
if (!$student) {
    header('Location: ' . url('instructor/students.php'));
    exit;
}

$pageTitle = 'Send Feedback';
$flash = getFlash();

// Get attempts for this student in this course
$attempts = $pdo->prepare("
    SELECT aa.*, a.title as assessment_title
    FROM assessment_attempts aa
    JOIN assessments a ON aa.assessment_id = a.id
    JOIN enrollments e ON aa.enrollment_id = e.id
    WHERE e.student_id = ? AND e.course_id = ?
    ORDER BY aa.submitted_at DESC
");
$attempts->execute([$studentId, $courseId]);
$attempts = $attempts->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $attemptId = (int)$_POST['attempt_id'];
    $feedbackText = trim($_POST['feedback_text'] ?? '');
    $focusAreas = trim($_POST['focus_areas'] ?? '');

    if ($feedbackText) {
        $stmt = $pdo->prepare("SELECT aa.id FROM assessment_attempts aa JOIN enrollments e ON aa.enrollment_id = e.id JOIN courses c ON e.course_id = c.id WHERE aa.id = ? AND e.student_id = ? AND c.instructor_id = ?");
        $stmt->execute([$attemptId, $studentId, $_SESSION['user_id']]);
        if ($stmt->fetch()) {
            $pdo->prepare("INSERT INTO feedback (attempt_id, student_id, instructor_id, feedback_text, focus_areas) VALUES (?, ?, ?, ?, ?)")
                ->execute([$attemptId, $studentId, $_SESSION['user_id'], $feedbackText, $focusAreas]);
            $pdo->prepare("INSERT INTO notifications (user_id, type, title, message, related_type, related_id) VALUES (?, 'feedback', 'New Feedback', ?, 'attempt', ?)")
                ->execute([$studentId, 'You have received feedback on your quiz.', $attemptId]);
            flash('success', 'Feedback sent.');
        }
    }
    header('Location: ' . url('instructor/feedback.php?student=' . $studentId . '&course=' . $courseId));
    exit;
}

require_once __DIR__ . '/../includes/header.php';
?>

<h2 class="section-title">Send Feedback to <?= e($student['first_name'] . ' ' . $student['last_name']) ?></h2>

<?php if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'danger' ?>"><?= e($flash['message']) ?></div>
<?php endif ?>

<?php if (empty($attempts)): ?>
    <p class="text-muted">No quiz attempts to give feedback on yet.</p>
<?php else: ?>
    <?php foreach ($attempts as $att): ?>
        <div style="background: white; padding: 1rem; border-radius: 8px; margin-bottom: 1rem;">
            <strong><?= e($att['assessment_title']) ?></strong> — Score: <?= number_format($att['percent_score'], 1) ?>% (<?= $att['passed'] ? 'Passed' : 'Failed' ?>)
            <form method="POST" style="margin-top: 1rem;">
                <input type="hidden" name="attempt_id" value="<?= $att['id'] ?>">
                <div class="form-group">
                    <label>Feedback</label>
                    <textarea name="feedback_text" class="form-control" rows="3" required></textarea>
                </div>
                <div class="form-group">
                    <label>Focus Areas (weak points to improve)</label>
                    <input type="text" name="focus_areas" class="form-control" placeholder="e.g. Variables, Functions">
                </div>
                <button type="submit" class="btn btn-primary">Send Feedback</button>
            </form>
        </div>
    <?php endforeach ?>
<?php endif ?>

<p><a href="<?= url('instructor/students.php') ?>">← Back to Students</a></p>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
