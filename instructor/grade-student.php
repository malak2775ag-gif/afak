<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

session_start();
if (!isLoggedIn() || $_SESSION['role'] !== 'instructor') {
    header('Location: ' . url('login.php'));
    exit;
}

$enrollmentId = (int)($_GET['enrollment_id'] ?? 0);
$stmt = $pdo->prepare("
    SELECT e.*, u.first_name, u.last_name, c.title as course_title, c.id as course_id
    FROM enrollments e
    JOIN users u ON e.student_id = u.id
    JOIN courses c ON e.course_id = c.id
    WHERE e.id = ? AND c.instructor_id = ?
");
$stmt->execute([$enrollmentId, $_SESSION['user_id']]);
$enrollment = $stmt->fetch();

if (!$enrollment) {
    header('Location: students.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['attempt_id'])) {
    $attemptId = (int)$_POST['attempt_id'];
    $feedback = trim($_POST['feedback_text'] ?? '');
    $manualScore = isset($_POST['manual_score']) && $_POST['manual_score'] !== '' ? (float)$_POST['manual_score'] : null;

    // Manually update the grade if entered
    if ($manualScore !== null) {
        // Fetch max score and passing score for accurate result calculation
        $stmt = $pdo->prepare("SELECT aa.max_score, a.passing_score FROM assessment_attempts aa JOIN assessments a ON aa.assessment_id = a.id WHERE aa.id = ?");
        $stmt->execute([$attemptId]);
        $meta = $stmt->fetch();

        if ($meta) {
            $passed = $manualScore >= (float)$meta['passing_score'] ? 1 : 0;
            $rawScore = ($manualScore / 100) * (float)$meta['max_score'];

            $pdo->prepare("UPDATE assessment_attempts SET percent_score = ?, score = ?, passed = ? WHERE id = ?")
                ->execute([$manualScore, $rawScore, $passed, $attemptId]);
        }
    }

    // Update question-specific feedback
    if (isset($_POST['q_feedback']) && is_array($_POST['q_feedback'])) {
        foreach ($_POST['q_feedback'] as $answerId => $qText) {
            $pdo->prepare("UPDATE attempt_answers SET feedback = ? WHERE id = ? AND attempt_id = ?")
                ->execute([trim($qText), (int)$answerId, $attemptId]);
        }
    }
    
    if (!empty($feedback)) {
        $pdo->prepare("INSERT INTO feedback (attempt_id, student_id, instructor_id, feedback_text) VALUES (?, ?, ?, ?)")
            ->execute([$attemptId, $enrollment['student_id'], $_SESSION['user_id'], $feedback]);
    }

    createNotification($pdo, (int)$enrollment['student_id'], 'grade', 'Grade Updated', 'Your grade for ' . $enrollment['course_title'] . ' has been updated.', 'attempt', $attemptId, url('quiz-result.php?attempt=' . $attemptId));
    flash('success', 'Grade and feedback updated successfully.');

    header('Location: ' . url('instructor/grade-student.php?enrollment_id=' . $enrollmentId));
    exit;
}

$attempts = $pdo->prepare("
    SELECT aa.*, a.title FROM assessment_attempts aa 
    JOIN assessments a ON aa.assessment_id = a.id 
    WHERE aa.enrollment_id = ? ORDER BY aa.submitted_at DESC
");
$attempts->execute([$enrollmentId]);
$attempts = $attempts->fetchAll();

$pageTitle = 'Review Submissions';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container" style="max-width: 900px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <h2>Review: <?= e($enrollment['first_name'] . ' ' . $enrollment['last_name']) ?></h2>
        <a href="students.php?course_id=<?= $enrollment['course_id'] ?>" class="btn btn-secondary">Back to Gradebook</a>
    </div>

    <?php if (empty($attempts)): ?>
        <div class="alert alert-info">No assessment attempts submitted yet.</div>
    <?php else: ?>
        <?php foreach ($attempts as $at): ?>
            <div class="form-card mb-4" style="max-width: 100%;">
                <form method="POST">
                    <input type="hidden" name="attempt_id" value="<?= $at['id'] ?>">
                <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border); padding-bottom: 0.5rem; margin-bottom: 1rem;">
                    <div>
                        <h3 style="margin:0;"><?= e($at['title']) ?></h3>
                        <small class="text-muted">Submitted: <?= date('M j, Y H:i', strtotime($at['submitted_at'])) ?></small>
                    </div>
                    <div style="text-align: right;">
                        <span style="font-size: 1.5rem; color: var(--primary); font-weight: bold;"><?= number_format($at['percent_score'], 1) ?>%</span>
                    </div>
                </div>

                <?php
                $answers = $pdo->prepare("SELECT aa.*, q.question_text, q.type FROM attempt_answers aa JOIN questions q ON aa.question_id = q.id WHERE aa.attempt_id = ?");
                $answers->execute([$at['id']]);
                $allAnswers = $answers->fetchAll();
                foreach ($allAnswers as $ans):
                ?>
                    <div style="margin-bottom: 1.5rem; padding: 15px; background: #fff; border: 1px solid var(--border); border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.02); position: relative;">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 20px; margin-bottom: 10px;">
                            <p style="margin:0; font-size: 1.05rem; flex: 1;"><strong>Q: <?= e($ans['question_text']) ?></strong></p>
                            <div style="width: 250px;">
                                <input type="text" name="q_feedback[<?= $ans['id'] ?>]" value="<?= e($ans['feedback'] ?? '') ?>" 
                                       class="form-control form-control-sm" placeholder="Feedback for this answer...">
                            </div>
                        </div>
                        
                        <?php if ($ans['type'] === 'multiple_choice' || $ans['type'] === 'true_false'): ?>
                            <div style="margin-top: 12px; display: flex; flex-direction: column; gap: 8px;">
                                <?php
                                $options = $pdo->prepare("SELECT * FROM question_options WHERE question_id = ? ORDER BY sort_order");
                                $options->execute([$ans['question_id']]);
                                foreach ($options->fetchAll() as $opt):
                                    $isCorrect = (bool)$opt['is_correct'];
                                    $isStudentSelection = ($ans['selected_option_id'] == $opt['id']);
                                    
                                    $itemStyle = "padding: 10px; border: 1px solid #eee; border-radius: 6px; font-size: 0.95rem;";
                                    if ($isCorrect) {
                                        $itemStyle = "padding: 10px; border: 1px solid #c3e6cb; background: #d4edda; color: #155724; border-radius: 6px; font-size: 0.95rem; font-weight: 600;";
                                    } elseif ($isStudentSelection && !$isCorrect) {
                                        $itemStyle = "padding: 10px; border: 1px solid #f5c6cb; background: #f8d7da; color: #721c24; border-radius: 6px; font-size: 0.95rem;";
                                    }
                                ?>
                                    <div style="<?= $itemStyle ?>">
                                        <?= $isCorrect ? '&#x2714; ' : '' ?><?= e($opt['option_text']) ?>
                                        <?php if ($isStudentSelection): ?>
                                            <span style="font-style: italic; font-size: 0.8rem; margin-left: 8px; opacity: 0.8;">(Student Selection)</span>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php elseif ($ans['type'] === 'file_upload'): ?>
                            <p class="text-muted" style="font-size: 0.85rem;">Student uploaded a file:</p>
                            <a href="<?= url($ans['text_answer']) ?>" target="_blank" class="btn btn-sm btn-outline-primary">View Submitted File</a>
                        <?php else: ?>
                            <p style="font-style: italic; color: #555;"><?= nl2br(e($ans['text_answer'] ?? 'Selected Option ID: ' . $ans['selected_option_id'])) ?></p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>

                <div style="margin-top: 1.5rem; padding-top: 1rem; border-top: 1px dashed var(--border);">
                    <h5>Instructor Feedback</h5>
                    <?php
                    $existing = $pdo->prepare("SELECT feedback_text, created_at FROM feedback WHERE attempt_id = ? ORDER BY created_at DESC");
                    $existing->execute([$at['id']]);
                    while ($fb = $existing->fetch()):
                    ?>
                        <div class="alert alert-info" style="font-size: 0.9rem;">
                            <strong><?= date('M j, Y', strtotime($fb['created_at'])) ?>:</strong> <?= e($fb['feedback_text']) ?>
                        </div>
                    <?php endwhile; ?>

                    <div style="background: #f8f9fa; padding: 20px; border-radius: 12px; border: 1px solid var(--border); margin-top: 1rem;">
                        <div style="margin-bottom: 15px; max-width: 200px;">
                            <label style="font-size: 0.9rem; font-weight: 700; display: block; margin-bottom: 6px; color: var(--primary);">Final Grade (%)</label>
                            <input type="number" name="manual_score" class="form-control" step="0.1" min="0" max="100" value="<?= (float)$at['percent_score'] ?>">
                        </div>
                        <label style="font-size: 0.9rem; font-weight: 700; display: block; margin-bottom: 6px; color: var(--primary);">General Summary Feedback</label>
                        <textarea name="feedback_text" class="form-control" rows="3" placeholder="Add any final thoughts or overall evaluation..."></textarea>
                        <button type="submit" class="btn btn-primary mt-3" style="padding: 10px 25px; font-weight: 600;">Save All Corrections & Feedback</button>
                    </div>
                </div>
                </form>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
