<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

session_start();
requireStudent();

$assessmentId = (int)($_GET['id'] ?? 0);
if (!$assessmentId) {
    header('Location: ' . url('dashboard.php'));
    exit;
}

$stmt = $pdo->prepare("
    SELECT a.*, c.title as course_title, c.id as course_id
    FROM assessments a
    JOIN courses c ON a.course_id = c.id
    WHERE a.id = ?
");
$stmt->execute([$assessmentId]);
$assessment = $stmt->fetch();

if (!$assessment) {
    header('Location: ' . url('dashboard.php'));
    exit;
}

// Check enrollment
$stmt = $pdo->prepare("SELECT id FROM enrollments WHERE student_id = ? AND course_id = ? AND status IN ('active', 'completed')");
$stmt->execute([$_SESSION['user_id'], $assessment['course_id']]);
$enrollment = $stmt->fetch();

if (!$enrollment) {
    header('Location: ' . url('course-detail.php?id=' . $assessment['course_id']));
    exit;
}

$pageTitle = $assessment['title'];

// التحقق من حد المحاولات
$maxAttempts = $assessment['max_attempts'] !== null ? (int)$assessment['max_attempts'] : null;
$stmtCount = $pdo->prepare("SELECT COUNT(*) FROM assessment_attempts WHERE enrollment_id = ? AND assessment_id = ?");
$stmtCount->execute([$enrollment['id'], $assessmentId]);
$attemptCount = (int)$stmtCount->fetchColumn();

$isBlocked = ($maxAttempts !== null && $attemptCount >= $maxAttempts);

// Submit quiz
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($isBlocked) {
        flash('danger', 'You have reached the maximum number of attempts allowed for this quiz.');
        header('Location: ' . url('quiz.php?id=' . $assessmentId));
        exit;
    }
    $questions = $pdo->prepare("SELECT id, type, points FROM questions WHERE assessment_id = ? ORDER BY sort_order");
    $questions->execute([$assessmentId]);
    $questions = $questions->fetchAll();

    $totalScore = 0;
    $maxScore = 0;
    $submittedAnswers = [];
    $requiresManualGrading = false;

    foreach ($questions as $q) {
        $maxScore += (float) $q['points'];
        $answer = null;

        // Handle File Upload type
        if ($q['type'] === 'file_upload' && isset($_FILES['q_' . $q['id']])) {
            $file = $_FILES['q_' . $q['id']];
            if ($file['error'] === UPLOAD_ERR_OK) {
                $uploaded = afak_upload_file($file, 'submissions');
                if ($uploaded) {
                    $answer = $uploaded;
                }
            }
        } else {
            $answer = $_POST['q_' . $q['id']] ?? null;
        }

        $submittedAnswers[$q['id']] = $answer;

        if ($q['type'] === 'short_answer') {
            $stmt = $pdo->prepare("SELECT option_text FROM question_options WHERE question_id = ? AND is_correct = 1");
            $stmt->execute([$q['id']]);
            $validKeywords = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            $isCorrectSA = false;
            if (!empty($validKeywords)) {
                foreach ($validKeywords as $vk) {
                    $cleanVk = trim($vk);
                    $cleanAns = trim($answer);
                    if (strcasecmp($cleanAns, $cleanVk) === 0 || preg_match('/\b' . preg_quote($cleanVk, '/') . '\b/i', $cleanAns)) {
                        $isCorrectSA = true;
                        break;
                    }
                }
            }

            if ($isCorrectSA) {
                $totalScore += (float) $q['points'];
            } else {
                $requiresManualGrading = true;
            }
        } elseif ($q['type'] === 'essay' || $q['type'] === 'file_upload') {
            $requiresManualGrading = true;
        } elseif ($q['type'] === 'multiple_choice' || $q['type'] === 'true_false') {
            $stmt = $pdo->prepare("SELECT is_correct FROM question_options WHERE id = ? AND question_id = ?");
            $stmt->execute([$answer, $q['id']]);
            $opt = $stmt->fetch();
            if ($opt && $opt['is_correct']) $totalScore += (float) $q['points'];
        }
    }

    $percentScore = $maxScore > 0 ? round(($totalScore / $maxScore) * 100, 2) : 0;
    $passed = $percentScore >= (float) $assessment['passing_score'];

    $stmt = $pdo->prepare("
        INSERT INTO assessment_attempts (enrollment_id, assessment_id, attempt_number, score, max_score, percent_score, passed, submitted_at)
        VALUES (?, ?, 1, ?, ?, ?, ?, NOW())
    ");
    $stmt->execute([$enrollment['id'], $assessmentId, $totalScore, $maxScore, $percentScore, $passed ? 1 : 0]);

    $attemptId = $pdo->lastInsertId();

    // Save answers
    foreach ($questions as $q) {
        $ansValue = $submittedAnswers[$q['id']] ?? null;

        if ($ansValue !== null) {
            $points = 0;
            if ($q['type'] === 'short_answer') {
                $stmt = $pdo->prepare("SELECT option_text FROM question_options WHERE question_id = ? AND is_correct = 1");
                $stmt->execute([$q['id']]);
                $validKeywords = $stmt->fetchAll(PDO::FETCH_COLUMN);
                foreach ($validKeywords as $vk) {
                    $cleanVk = trim($vk);
                    $cleanAns = trim($ansValue);
                    if (strcasecmp($cleanAns, $cleanVk) === 0 || preg_match('/\b' . preg_quote($cleanVk, '/') . '\b/i', $cleanAns)) {
                        $points = (float) $q['points'];
                        break;
                    }
                }
            } elseif ($q['type'] === 'multiple_choice' || $q['type'] === 'true_false') {
                $stmt = $pdo->prepare("SELECT is_correct FROM question_options WHERE id = ? AND question_id = ?");
                $stmt->execute([$ansValue, $q['id']]);
                $opt = $stmt->fetch();
                if ($opt && $opt['is_correct']) $points = (float) $q['points'];
            }
            $pdo->prepare("INSERT INTO attempt_answers (attempt_id, question_id, selected_option_id, text_answer, points_earned) VALUES (?, ?, ?, ?, ?)")
                ->execute([$attemptId, $q['id'], is_numeric($ansValue) ? (int)$ansValue : null, !is_numeric($ansValue) ? $ansValue : null, $points]);
        }
    }

    // Notification
    $msg = "You scored {$percentScore}% on {$assessment['title']}.";
    if ($requiresManualGrading) {
        $msg .= " (Some questions require instructor review, your score might update later)";
    } else {
        $msg .= ($passed ? ' Passed!' : ' Did not pass.');
    }

    $pdo->prepare("INSERT INTO notifications (user_id, type, title, message, related_type, related_id) VALUES (?, 'grade', ?, ?, 'attempt', ?)")
        ->execute([$_SESSION['user_id'], 'Quiz submitted', $msg, $attemptId]);

    // Notify Instructor if manual grading is needed
    $instructorId = $pdo->query("SELECT instructor_id FROM courses WHERE id = " . (int)$assessment['course_id'])->fetchColumn();
    if ($requiresManualGrading && $instructorId) {
        createNotification($pdo, (int)$instructorId, 'grade', 'New Quiz Pending Review', "A student submitted '{$assessment['title']}' which needs manual grading.", 'attempt', $attemptId, url('instructor/grade-quiz.php?attempt=' . $attemptId));
    }

    header('Location: ' . url('quiz-submission-summary.php?attempt=' . $attemptId));
    exit;
}

// Get questions with options
$questions = $pdo->prepare("SELECT * FROM questions WHERE assessment_id = ? ORDER BY sort_order");
$questions->execute([$assessmentId]);
$questions = $questions->fetchAll();

foreach ($questions as &$q) {
    $opts = $pdo->prepare("SELECT * FROM question_options WHERE question_id = ? ORDER BY sort_order");
    $opts->execute([$q['id']]);
    $q['options'] = $opts->fetchAll();
}
unset($q); // Break the reference to prevent Question 3/4 from clashing

require_once __DIR__ . '/includes/header.php';

// Check for previous attempts to show a notice
$prevAttempt = $pdo->prepare("SELECT id, percent_score, passed FROM assessment_attempts WHERE enrollment_id = ? AND assessment_id = ? ORDER BY submitted_at DESC LIMIT 1");
$prevAttempt->execute([$enrollment['id'], $assessmentId]);
$lastResult = $prevAttempt->fetch();
?>

<?php if ($maxAttempts !== null): ?>
    <div class="alert alert-info">
        <strong>Attempt Limit:</strong> You have used <strong><?= $attemptCount ?></strong> out of <strong><?= $maxAttempts ?></strong> attempts.
    </div>
<?php endif; ?>

<?php if ($isBlocked): ?>
    <div class="alert alert-danger" style="padding: 2rem; text-align: center; border-radius: 15px;">
        <h3 style="margin-bottom: 1rem;">Attempt Limit Reached</h3>
        <p>Sorry, you have exhausted all available attempts for this quiz and cannot submit it again.</p>
        <div style="margin-top: 1.5rem; display: flex; gap: 10px; justify-content: center;">
            <?php if ($lastResult): ?>
                <a href="<?= url('quiz-result.php?attempt=' . $lastResult['id']) ?>" class="btn btn-primary">View Last Result</a>
            <?php endif; ?>
            <a href="<?= url('course-view.php?id=' . $assessment['course_id']) ?>" class="btn btn-secondary">Back to Course</a>
        </div>
    </div>
<?php else: ?>

<?php if ($lastResult): ?>
    <div class="alert alert-info" style="display: flex; justify-content: space-between; align-items: center;">
        <span>You have already submitted this quiz (Last score: <strong><?= number_format($lastResult['percent_score'], 1) ?>%</strong>).</span>
        <a href="<?= url('quiz-result.php?attempt=' . $lastResult['id']) ?>" class="btn btn-secondary" style="padding: 5px 15px; font-size: 0.85rem;">View Detailed Results</a>
    </div>
<?php endif; ?>

<h2><?= e($assessment['title']) ?></h2>
<p class="text-muted"><?= e($assessment['course_title']) ?> • Passing score: <?= $assessment['passing_score'] ?>%</p>

<form method="POST" enctype="multipart/form-data">
    <?php foreach ($questions as $i => $q): ?>
        <div class="quiz-question">
            <h4><?= ($i + 1) ?>. <?= e($q['question_text']) ?></h4>
            <div class="quiz-options">
                <?php if ($q['type'] === 'file_upload'): ?>
                    <input type="file" name="q_<?= $q['id'] ?>" class="form-control" required>
                    <small class="text-muted">Upload your work (PDF, DOCX, or ZIP recommended).</small>
                <?php elseif ($q['type'] === 'short_answer'): ?>
                    <input type="text" name="q_<?= $q['id'] ?>" class="form-control" placeholder="Type your answer here..." required>
                <?php elseif ($q['type'] === 'essay'): ?>
                    <textarea name="q_<?= $q['id'] ?>" class="form-control" rows="5" placeholder="Write your response here..." required></textarea>
                <?php elseif ($q['type'] === 'true_false'): ?>
                    <div style="display: flex; gap: 1rem; margin-top: 0.5rem;">
                        <?php foreach ($q['options'] as $opt): ?>
                            <label for="q_<?= $i ?>_opt_<?= $opt['id'] ?>" style="display: flex; align-items: center; gap: 0.75rem; cursor: pointer; padding: 12px 20px; border: 1px solid var(--border); border-radius: 10px; background: #fff; flex: 1; justify-content: center; transition: all 0.2s; border: 2px solid #eee;">
                                <input type="radio" id="q_<?= $i ?>_opt_<?= $opt['id'] ?>" name="q_<?= $q['id'] ?>" value="<?= $opt['id'] ?>" required style="width: 1.2rem; height: 1.2rem;">
                                <span style="font-weight: 600; font-size: 1.1rem;"><?= e($opt['option_text']) ?></span>
                            </label>
                        <?php endforeach ?>
                    </div>
                <?php elseif ($q['type'] === 'multiple_choice'): ?>
                    <?php foreach ($q['options'] as $opt): ?>
                        <div style="margin-bottom: 0.75rem;"> 
                            <label for="q_<?= $i ?>_opt_<?= $opt['id'] ?>" style="display: flex; align-items: center; gap: 0.75rem; cursor: pointer; padding: 10px 15px; border: 1px solid #eee; border-radius: 8px; background: #fff; transition: background 0.2s;">
                                <input type="radio" id="q_<?= $i ?>_opt_<?= $opt['id'] ?>" name="q_<?= $q['id'] ?>" value="<?= $opt['id'] ?>" required style="width: 1.1rem; height: 1.1rem; accent-color: var(--primary);">
                                <span style="font-size: 1rem; color: var(--text);"><?= e($opt['option_text']) ?></span>
                            </label>
                        </div>
                    <?php endforeach ?>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach ?>
    <button type="submit" class="btn btn-primary" style="width: 100%;">Submit Quiz</button>
</form>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
