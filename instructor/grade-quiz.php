<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

session_start();
requireInstructor();

$attemptId = (int)($_GET['attempt'] ?? 0);
if (!$attemptId) {
    die('Invalid Attempt ID');
}

// Fetch attempt and verify instructor ownership
$stmt = $pdo->prepare("
    SELECT aa.*, a.title as quiz_title, a.passing_score, u.first_name, u.last_name, c.instructor_id
    FROM assessment_attempts aa
    JOIN assessments a ON aa.assessment_id = a.id
    JOIN enrollments e ON aa.enrollment_id = e.id
    JOIN users u ON e.student_id = u.id
    JOIN courses c ON a.course_id = c.id
    WHERE aa.id = ?
");
$stmt->execute([$attemptId]);
$attempt = $stmt->fetch();

if (!$attempt || $attempt['instructor_id'] != $_SESSION['user_id']) {
    die('Access Denied or Attempt not found.');
}

$pageTitle = 'Grade Quiz: ' . $attempt['quiz_title'];

// Handle Grading Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $grades = $_POST['grades'] ?? [];
    $feedbacks = $_POST['feedback'] ?? [];
    $totalEarned = 0;

    foreach ($grades as $questionId => $score) {
        $feedback = $feedbacks[$questionId] ?? '';
        $stmt = $pdo->prepare("UPDATE attempt_answers SET points_earned = ?, feedback = ? WHERE attempt_id = ? AND question_id = ?");
        $stmt->execute([$score, $feedback, $attemptId, $questionId]);
    }

    // Recalculate total score
    $stmt = $pdo->prepare("SELECT SUM(points_earned) FROM attempt_answers WHERE attempt_id = ?");
    $stmt->execute([$attemptId]);
    $newTotalScore = (float)$stmt->fetchColumn();
    
    $percentScore = $attempt['max_score'] > 0 ? round(($newTotalScore / $attempt['max_score']) * 100, 2) : 0;
    $passed = $percentScore >= (float)$attempt['passing_score'];

    $stmt = $pdo->prepare("UPDATE assessment_attempts SET score = ?, percent_score = ?, passed = ? WHERE id = ?");
    $stmt->execute([$newTotalScore, $percentScore, $passed ? 1 : 0, $attemptId]);

    // Notify Student
    createNotification($pdo, (int)$attempt['student_id'], 'grade', 'Quiz Graded', "Your attempt for '{$attempt['quiz_title']}' has been reviewed. Final score: {$percentScore}%", 'attempt', $attemptId, url('quiz-result.php?attempt=' . $attemptId));

    flash('success', 'Grades updated and student notified.');
    header('Location: ' . url('instructor/grade-quiz.php?attempt=' . $attemptId));
    exit;
}

// Get questions and answers
$stmt = $pdo->prepare("
    SELECT q.*, aa.text_answer, aa.selected_option_id, aa.points_earned, aa.feedback, aa.id as answer_record_id
    FROM questions q
    LEFT JOIN attempt_answers aa ON q.id = aa.question_id AND aa.attempt_id = ?
    WHERE q.assessment_id = ?
    ORDER BY q.sort_order
");
$stmt->execute([$attemptId, $attempt['assessment_id']]);
$questions = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container">
    <div class="dashboard-header">
        <h2>Grading: <?= e($attempt['quiz_title']) ?></h2>
        <p>Student: <strong><?= e($attempt['first_name'] . ' ' . $attempt['last_name']) ?></strong> | Current Score: <?= $attempt['percent_score'] ?>%</p>
    </div>

    <form method="POST">
        <?php foreach ($questions as $i => $q): ?>
            <div class="form-card mb-2" style="border-left: 5px solid <?= in_array($q['type'], ['essay', 'file_upload', 'short_answer']) ? '#f39c12' : '#2ecc71' ?>;">
                <h4>Question <?= $i+1 ?> (<?= ucfirst($q['type']) ?>)</h4>
                <p><?= e($q['question_text']) ?></p>
                
                <div class="student-answer" style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 15px;">
                    <strong>Student Response:</strong><br>
                    <?php if ($q['type'] === 'file_upload'): ?>
                        <?php if ($q['text_answer']): ?>
                            <a href="<?= url($q['text_answer']) ?>" target="_blank" class="btn btn-secondary btn-sm mt-1">View Uploaded File</a>
                        <?php else: ?> <span class="text-danger">No file.</span> <?php endif; ?>
                    <?php elseif (in_array($q['type'], ['multiple_choice', 'true_false'])): ?>
                        <span class="text-muted">(Auto-graded based on selection)</span>
                    <?php else: ?>
                        <div class="mt-1"><?= nl2br(e($q['text_answer'] ?? 'No answer provided.')) ?></div>
                    <?php endif; ?>
                </div>

                <div class="row" style="display: flex; gap: 20px; align-items: flex-end;">
                    <div class="form-group" style="flex: 1;">
                        <label>Points (Max: <?= $q['points'] ?>)</label>
                        <input type="number" step="0.5" max="<?= $q['points'] ?>" name="grades[<?= $q['id'] ?>]"
                               value="<?= (float)$q['points_earned'] ?>" class="form-control">
                    </div>
                    <div class="form-group" style="flex: 3;">
                        <label>Feedback for Student</label>
                        <input type="text" name="feedback[<?= $q['id'] ?>]" value="<?= e($q['feedback'] ?? '') ?>" class="form-control" placeholder="Well done! / Needs more detail...">
                    </div>
                </div>
            </div>
        <?php endforeach; ?>

        <div style="position: sticky; bottom: 20px; background: white; padding: 20px; border-top: 2px solid #eee; box-shadow: 0 -5px 15px rgba(0,0,0,0.05);">
            <button type="submit" class="btn btn-primary" style="width: 200px;">Save All Grades</button>
            <a href="<?= url('instructor/index.php') ?>" class="btn btn-outline-light">Cancel</a>
        </div>
    </form>
</div>

<style>
    .form-card { background: white; padding: 20px; border-radius: 12px; margin-bottom: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
    .mb-2 { margin-bottom: 1.5rem; }
    .row { display: flex; flex-wrap: wrap; }
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>