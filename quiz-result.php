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
    SELECT aa.*, a.title, a.passing_score, a.type as assessment_type, c.title as course_title, c.id as course_id,
           u.first_name, u.last_name, u.username
    FROM assessment_attempts aa
    JOIN assessments a ON aa.assessment_id = a.id
    JOIN courses c ON a.course_id = c.id
    JOIN enrollments e ON aa.enrollment_id = e.id
    JOIN users u ON e.student_id = u.id
    WHERE aa.id = ? AND e.student_id = ?
");
$stmt->execute([$attemptId, (int)$_SESSION['user_id']]);
$attempt = $stmt->fetch();

if (!$attempt) {
    header('Location: ' . url('dashboard.php'));
    exit;
}

$pageTitle = 'Quiz Result';

if ($attempt['assessment_type'] === 'rubric') {
    // جلب نتائج الـ Rubric
    $rubricResults = $pdo->prepare("
        SELECT rc.criterion_name, rc.description as criterion_desc, rc.max_score, rs.score, rs.feedback
        FROM rubric_criteria rc
        JOIN rubric_scores rs ON rc.id = rs.criterion_id
        WHERE rs.attempt_id = ?
        ORDER BY rc.sort_order ASC, rc.id ASC
    ");
    $rubricResults->execute([$attemptId]);
    $rubricEntries = $rubricResults->fetchAll();
    $hasSubjective = false;
} else {
    // جلب تفاصيل الكويز العادي
    $questions = $pdo->prepare("
        SELECT q.*, cm.title as material_title
        FROM questions q
        LEFT JOIN course_materials cm ON q.material_id = cm.id WHERE q.assessment_id = ? ORDER BY sort_order");
    $questions->execute([$attempt['assessment_id']]);
    $questions = $questions->fetchAll();

    $answers = [];
    $ansStmt = $pdo->prepare("SELECT * FROM attempt_answers WHERE attempt_id = ?");
    $ansStmt->execute([$attemptId]);
    foreach ($ansStmt->fetchAll() as $row) {
        $answers[$row['question_id']] = $row;
    }

    $hasSubjective = false;
    foreach ($questions as $q) {
        if (in_array($q['type'], ['essay', 'short_answer', 'file_upload'])) {
            $hasSubjective = true;
            break;
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="max-width: 800px; margin: 0 auto;">
    <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 1.5rem; padding: 1.5rem 0 1rem; border-bottom: 2px solid #f0f0f0; gap: 1rem; flex-wrap: wrap;">
        <div>
            <h2 style="margin: 0; font-size: 1.8rem; font-weight: 700; color: var(--text);"><?= e($attempt['title']) ?> Result</h2>
            <p class="text-muted" style="margin: 5px 0 0 0; font-size: 0.95rem;">
                Student: <strong><?= e($attempt['first_name'] . ' ' . $attempt['last_name']) ?></strong> • Submitted: <?= date('Y/m/d H:i', strtotime($attempt['submitted_at'])) ?>
            </p>
        </div>
        <div style="display: flex; align-items: center; gap: 1.5rem; text-align: right;">
            <div>
                <div style="font-size: 2.2rem; font-weight: 800; color: <?= $attempt['passed'] ? '#2ecc71' : '#f39c12' ?>; line-height: 1;">
                    <?= number_format($attempt['percent_score'], 1) ?>%
                </div>
                <div style="font-size: 0.8rem; font-weight: 700; text-transform: uppercase; color: #999; margin-top: 5px; letter-spacing: 0.5px;">
                    <?= $attempt['passed'] ? 'Passed' : 'Not Passed' ?>
                </div>
            </div>
        </div>
    </div>

    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2.5rem; gap: 15px;">
        <?php
        // Fetch general instructor summary feedback
        $summaryFeedback = $pdo->prepare("SELECT feedback_text, created_at FROM feedback WHERE attempt_id = ? ORDER BY created_at DESC");
        $summaryFeedback->execute([$attemptId]);
        $feedbacks = $summaryFeedback->fetchAll();
        ?>
        <div style="display: flex; gap: 10px;">
            <a href="<?= url('course-view.php?id=' . $attempt['course_id']) ?>" class="btn btn-secondary" style="padding: 6px 15px; font-size: 0.85rem; border-radius: 8px;">← Back to Course</a>
            <a href="<?= url('dashboard.php') ?>" class="btn btn-outline-light" style="padding: 6px 15px; font-size: 0.85rem; color: var(--text); border: 1px solid #ddd; border-radius: 8px;">Dashboard</a>
        </div>
        <?php if ($hasSubjective): ?>
            <div style="font-size: 0.8rem; background: #fff3cd; color: #856404; padding: 6px 15px; border-radius: 20px; border: 1px solid #ffeeba; font-weight: 600;">
                Subjective questions pending review
            </div>
        <?php endif; ?>
    </div>

    <?php if (!empty($feedbacks)): ?>
        <div style="margin-bottom: 2.5rem; padding: 20px; background: #eef2ff; border-radius: 12px; border-left: 5px solid var(--primary);">
            <h4 style="margin: 0 0 10px 0; color: var(--primary);"><i class="fas fa-comment-alt-dots"></i> Overall Instructor Feedback</h4>
            <?php foreach ($feedbacks as $fb): ?>
                <div style="margin-bottom: 10px; padding-bottom: 10px; border-bottom: 1px dashed rgba(0,0,0,0.1);">
                    <p style="margin: 0; line-height: 1.5; color: #333;"><?= nl2br(e($fb['feedback_text'])) ?></p>
                    <small class="text-muted"><?= date('M j, Y H:i', strtotime($fb['created_at'])) ?></small>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <h3 style="margin-bottom: 1.5rem; font-size: 1.2rem; font-weight: 700; color: #444; text-transform: uppercase; letter-spacing: 0.5px;">Detailed Review</h3>

    <?php if ($attempt['assessment_type'] === 'rubric'): ?>
        <?php foreach ($rubricEntries as $i => $entry): ?>
            <div class="ins-card" style="margin-bottom: 1rem; padding: 1.5rem; border-left: 4px solid var(--primary);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                    <h4 style="margin:0; font-size: 1.1rem;"><?= e($entry['criterion_name']) ?></h4>
                    <span style="font-weight: 700; color: var(--primary);"><?= (float)$entry['score'] ?> / <?= (float)$entry['max_score'] ?></span>
                </div>
                <?php if ($entry['criterion_desc']): ?><p class="text-muted" style="font-size: 0.9rem;"><?= e($entry['criterion_desc']) ?></p><?php endif; ?>
                <?php if ($entry['feedback']): ?>
                    <div style="margin-top: 10px; padding: 10px; background: #f8f9fa; border-radius: 6px; font-style: italic;">
                        <strong>Instructor Feedback:</strong> <?= nl2br(e($entry['feedback'])) ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <?php foreach ($questions as $i => $q): ?>
        <?php 
            $userAns = $answers[$q['id']] ?? null;
            $isCorrect = $userAns && $userAns['points_earned'] >= $q['points'];
        ?>
        <div class="ins-card" style="margin-bottom: 1rem; padding: 1rem; border-left: 4px solid <?= $isCorrect ? 'var(--teal)' : ($q['type'] === 'file_upload' ? '#3498db' : '#e74c3c') ?>;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                <strong style="font-size: 0.9rem;">Question <?= $i + 1 ?></strong>
                <span style="font-size: 0.8rem; font-weight: 600; padding: 2px 8px; border-radius: 4px; background: #eee; color: #555;">
                    <?= (float)($userAns['points_earned'] ?? 0) ?> / <?= (float)$q['points'] ?> pts
                </span>
            </div>
            <p style="font-size: 1rem; margin: 0 0 10px 0; line-height: 1.4;"><?= e($q['question_text']) ?></p>
            <?php if (!empty($q['material_title'])): ?>
                <p class="text-muted" style="font-size: 0.85rem; margin-top: -5px; margin-bottom: 10px;">
                    Related to lesson: <strong><?= e($q['material_title']) ?></strong>
                </p>
            <?php endif; ?>

            <div style="background: #fcfcfc; padding: 0.75rem; border: 1px solid #f0f0f0; border-radius: 6px;">
                <?php if ($q['type'] === 'file_upload'): ?>
                    <small class="text-muted d-block mb-1">Your Submission:</small>
                    <?php if ($userAns && $userAns['text_answer']): ?>
                        <a href="<?= url($userAns['text_answer']) ?>" target="_blank" class="btn btn-secondary" style="font-size: 0.8rem; padding: 5px 10px;">View Uploaded File</a>
                    <?php else: ?>
                        <span class="text-danger" style="font-size: 0.85rem;">No file submitted.</span>
                    <?php endif; ?>
                <?php elseif ($q['type'] === 'short_answer' || $q['type'] === 'essay'): ?>
                    <small class="text-muted d-block mb-1">Your Answer:</small>
                    <div style="padding: 8px; background: white; border: 1px solid #eee; border-radius: 4px; font-style: italic; font-size: 0.9rem;">
                        <?= $userAns && $userAns['text_answer'] ? nl2br(e($userAns['text_answer'])) : '<span class="text-muted">No answer.</span>' ?>
                    </div>
                <?php elseif ($q['type'] === 'true_false'): ?>
                    <div style="display: flex; gap: 0.75rem;">
                        <?php
                        $options = $pdo->prepare("SELECT * FROM question_options WHERE question_id = ? ORDER BY sort_order");
                        $options->execute([$q['id']]);
                        foreach ($options->fetchAll() as $opt):
                            $isUserSelection = ($userAns && $userAns['selected_option_id'] == $opt['id']);
                            $style = "padding: 6px; border-radius: 6px; border: 1px solid #eee; background: white; flex: 1; text-align: center; font-size: 0.85rem;";
                            $icon = "";
                            if ($opt['is_correct']) {
                                $style = "padding: 6px; border-radius: 6px; border: 1px solid var(--teal); background: #f0fdfa; color: var(--teal); flex: 1; text-align: center; font-size: 0.85rem;";
                                $icon = "";
                            } elseif ($isUserSelection && !$opt['is_correct']) {
                                $style = "padding: 6px; border-radius: 6px; border: 1px solid #e74c3c; background: #fef2f2; color: #e74c3c; text-decoration: line-through; flex: 1; text-align: center; font-size: 0.85rem;";
                                $icon = "";
                            }
                        ?>
                            <div style="<?= $style ?>">
                                <div style="font-weight: 600;"><?= $icon ?> <?= e($opt['option_text']) ?></div>
                                <?php if ($isUserSelection): ?>
                                    <small style="display: block; opacity: 0.8;">(Your Answer)</small>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <?php
                    $options = $pdo->prepare("SELECT * FROM question_options WHERE question_id = ? ORDER BY sort_order");
                    $options->execute([$q['id']]);
                    foreach ($options->fetchAll() as $opt):
                        $isUserSelection = ($userAns && $userAns['selected_option_id'] == $opt['id']);
                        $style = "";
                        $icon = "";
                        if ($opt['is_correct']) {
                            $style = "color: var(--teal); font-weight: 700; background: #f0fdfa; padding: 5px 10px; border-radius: 5px; border: 1px solid #ccfbf1;";
                            $icon = "";
                        } elseif ($isUserSelection && !$opt['is_correct']) {
                            $style = "color: #e74c3c; text-decoration: line-through; background: #fef2f2; padding: 5px 10px; border-radius: 5px; border: 1px solid #fee2e2;";
                            $icon = "";
                        }
                    ?>
                        <div style="margin-bottom: 5px; <?= $style ?> display: flex; align-items: center; gap: 8px; font-size: 0.9rem;">
                            <?= $icon ?> <?= $isUserSelection ? '<strong>' : '' ?><?= e($opt['option_text']) ?><?= $isUserSelection ? '</strong>' : '' ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>

                <?php if (!$isCorrect && !empty($q['feedback_text'])): ?>
                    <div style="margin-top: 10px; padding: 10px; background: #fff5f5; border: 1px solid #feb2b2; border-radius: 6px;">
                        <strong style="font-size: 0.85rem; color: #c53030;">&#x1F4A1; Review Suggestion:</strong>
                        <p style="font-size: 0.9rem; margin: 4px 0 0 0; color: #742a2a;"><?= e($q['feedback_text']) ?></p>
                    </div>
                <?php endif; ?>

                <?php if ($userAns && !empty($userAns['feedback'])): ?>
                    <div style="margin-top: 10px; padding-top: 8px; border-top: 1px dashed #ccc;">
                        <strong style="font-size: 0.8rem; color: var(--primary);">Instructor Feedback:</strong>
                        <p style="font-style: italic; font-size: 0.85rem; margin: 2px 0 0 0; color: #444;"><?= e($userAns['feedback']) ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
