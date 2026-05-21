<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

session_start();
requireInstructor();

$quizId = (int) ($_GET['id'] ?? 0);
if (!$quizId) {
    header('Location: ' . url('instructor/index.php'));
    exit;
}

$stmt = $pdo->prepare('SELECT a.*, c.instructor_id, c.title AS course_title FROM assessments a JOIN courses c ON a.course_id = c.id WHERE a.id = ?');
$stmt->execute([$quizId]);
$quiz = $stmt->fetch();

if (!$quiz || (int) $quiz['instructor_id'] !== (int) $_SESSION['user_id']) {
    header('Location: ' . url('instructor/index.php'));
    exit;
}

$pageTitle = 'Quiz: ' . $quiz['title'];
$flash = getFlash();

$materialsByUnitForCourse = [];
$courseUnits = $pdo->prepare('SELECT id, title FROM course_units WHERE course_id = ? ORDER BY sort_order, id');
$courseUnits->execute([$quiz['course_id']]);
$courseUnits = $courseUnits->fetchAll(PDO::FETCH_ASSOC);

foreach ($courseUnits as $unit) {
    $mats = $pdo->prepare('SELECT id, title FROM course_materials WHERE unit_id = ? ORDER BY sort_order, id');
    $mats->execute([$unit['id']]);
    $materialsByUnitForCourse[$unit['id']] = $mats->fetchAll(PDO::FETCH_ASSOC);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_quiz_meta'])) {
        $passing = isset($_POST['passing_score']) ? (float) $_POST['passing_score'] : 60;
        $passing = max(0, min(100, $passing));
        $maxAttempts = trim($_POST['max_attempts'] ?? '');
        $maxAttempts = $maxAttempts === '' ? null : max(1, (int) $maxAttempts);
        $timeLimit = trim($_POST['time_limit_minutes'] ?? '');
        $timeLimit = $timeLimit === '' ? null : max(1, (int) $timeLimit);
        $pdo->prepare('UPDATE assessments SET passing_score = ?, max_attempts = ?, time_limit_minutes = ? WHERE id = ?')
            ->execute([$passing, $maxAttempts, $timeLimit, $quizId]);
        flash('success', 'Quiz settings saved.');
        header('Location: ' . url('instructor/quiz-edit.php?id=' . $quizId));
        exit;
    }

    // Delete Question Logic
    if (isset($_POST['delete_question'])) {
        $qId = (int)$_POST['question_id'];
        // Verify question belongs to this quiz and instructor
        $check = $pdo->prepare("SELECT q.id FROM questions q JOIN assessments a ON q.assessment_id = a.id JOIN courses c ON a.course_id = c.id WHERE q.id = ? AND a.id = ? AND c.instructor_id = ?");
        $check->execute([$qId, $quizId, $_SESSION['user_id']]);
        if ($check->fetch()) {
            $pdo->prepare("DELETE FROM questions WHERE id = ?")->execute([$qId]);
            flash('success', 'Question deleted.');
        }
        header('Location: ' . url('instructor/quiz-edit.php?id=' . $quizId));
        exit;
    }

    // Update Question Logic
    if (isset($_POST['update_question'])) {
        $qId = (int)$_POST['question_id'];
        $text = trim($_POST['question_text'] ?? '');
        $type = $_POST['question_type'] ?? 'multiple_choice';
        $points = isset($_POST['points']) ? (float)$_POST['points'] : 1.0;
        $feedback = trim($_POST['feedback_text'] ?? '');
        $materialId = (int)($_POST['material_id'] ?? 0);
        $materialId = $materialId > 0 ? $materialId : null;

        // Verify ownership
        $check = $pdo->prepare("SELECT q.id FROM questions q JOIN assessments a ON q.assessment_id = a.id JOIN courses c ON a.course_id = c.id WHERE q.id = ? AND a.id = ? AND c.instructor_id = ?");
        $check->execute([$qId, $quizId, $_SESSION['user_id']]);
        
        if ($check->fetch() && $text !== '') {
            $pdo->prepare('UPDATE questions SET question_text = ?, type = ?, points = ?, feedback_text = ?, material_id = ? WHERE id = ?')
                ->execute([$text, $type, $points, $feedback, $materialId, $qId]);

            // Refresh options (delete and re-insert)
            $pdo->prepare("DELETE FROM question_options WHERE question_id = ?")->execute([$qId]);

            if ($type === 'true_false') {
                $trueCorrect = (!isset($_POST['tf_correct']) || $_POST['tf_correct'] === 'true') ? 1 : 0;
                $falseCorrect = $trueCorrect ? 0 : 1;
                $pdo->prepare('INSERT INTO question_options (question_id, option_text, is_correct, sort_order) VALUES (?, ?, ?, 0)')->execute([$qId, 'True', $trueCorrect]);
                $pdo->prepare('INSERT INTO question_options (question_id, option_text, is_correct, sort_order) VALUES (?, ?, ?, 1)')->execute([$qId, 'False', $falseCorrect]);
            } elseif ($type === 'short_answer' && !empty($_POST['short_answers'])) {
                $validAnswers = explode(',', $_POST['short_answers']);
                foreach ($validAnswers as $i => $ans) {
                    $ans = trim($ans);
                    if ($ans !== '') {
                        $pdo->prepare('INSERT INTO question_options (question_id, option_text, is_correct, sort_order) VALUES (?, ?, 1, ?)')->execute([$qId, $ans, $i]);
                    }
                }
            } elseif ($type === 'multiple_choice' && !empty($_POST['options'])) {
                $opts = is_array($_POST['options']) ? $_POST['options'] : [$_POST['options']];
                $correctIdx = (int) ($_POST['correct'] ?? 0);
                foreach ($opts as $i => $opt) {
                    $optValue = trim((string)$opt);
                    if ($optValue !== '') {
                        $isCorr = ($i === $correctIdx) ? 1 : 0;
                        $pdo->prepare('INSERT INTO question_options (question_id, option_text, is_correct, sort_order) VALUES (?, ?, ?, ?)')->execute([$qId, $optValue, $isCorr, $i]);
                    }
                }
            }
            flash('success', 'Question updated.');
        }
        header('Location: ' . url('instructor/quiz-edit.php?id=' . $quizId));
        exit;
    }

    if (isset($_POST['add_question'])) {
        $text = trim($_POST['question_text'] ?? '');
        $type = $_POST['question_type'] ?? 'multiple_choice';
        $points = isset($_POST['points']) ? (float)$_POST['points'] : 1.0;
        $feedback = trim($_POST['feedback_text'] ?? '');
        $materialId = (int)($_POST['material_id'] ?? 0);
        $materialId = $materialId > 0 ? $materialId : null;
        if ($text !== '') {
            $maxOrder = $pdo->prepare('SELECT COALESCE(MAX(sort_order),0) FROM questions WHERE assessment_id = ?');
            $maxOrder->execute([$quizId]);
            $maxOrder = (int) $maxOrder->fetchColumn() + 1;
            $pdo->prepare('INSERT INTO questions (assessment_id, question_text, type, points, feedback_text, sort_order, material_id) VALUES (?, ?, ?, ?, ?, ?, ?)')
                ->execute([$quizId, $text, $type, $points, $feedback, $maxOrder, $materialId]);
            $qId = (int) $pdo->lastInsertId();

            if ($type === 'true_false') {
                $trueCorrect = (!isset($_POST['tf_correct']) || $_POST['tf_correct'] === 'true') ? 1 : 0;
                $falseCorrect = $trueCorrect ? 0 : 1;
                $pdo->prepare('INSERT INTO question_options (question_id, option_text, is_correct, sort_order) VALUES (?, ?, ?, 0)')->execute([$qId, 'True', $trueCorrect]);
                $pdo->prepare('INSERT INTO question_options (question_id, option_text, is_correct, sort_order) VALUES (?, ?, ?, 1)')->execute([$qId, 'False', $falseCorrect]);
            } elseif ($type === 'short_answer' && !empty($_POST['short_answers'])) {
                $validAnswers = explode(',', $_POST['short_answers']);
                foreach ($validAnswers as $i => $ans) {
                    $ans = trim($ans);
                    if ($ans !== '') {
                        $pdo->prepare('INSERT INTO question_options (question_id, option_text, is_correct, sort_order) VALUES (?, ?, 1, ?)')->execute([$qId, $ans, $i]);
                    }
                }
            } elseif ($type === 'multiple_choice' && !empty($_POST['options'])) {
                $opts = is_array($_POST['options']) ? $_POST['options'] : [$_POST['options']];
                $correctIdx = (int) ($_POST['correct'] ?? 0);
                foreach ($opts as $i => $opt) {
                    $opt = is_array($opt) ? trim((string) ($opt[0] ?? '')) : trim((string) $opt);
                    if ($opt !== '') {
                        $isCorr = ($i === $correctIdx) ? 1 : 0;
                        $pdo->prepare('INSERT INTO question_options (question_id, option_text, is_correct, sort_order) VALUES (?, ?, ?, ?)')->execute([$qId, $opt, $isCorr, $i]);
                    }
                }
            }
            flash('success', 'Question added.');
        }
        header('Location: ' . url('instructor/quiz-edit.php?id=' . $quizId));
        exit;
    }
}

$questions = $pdo->prepare('SELECT q.*, cm.title as material_title FROM questions q LEFT JOIN course_materials cm ON q.material_id = cm.id WHERE assessment_id = ? ORDER BY sort_order, id');
$questions->execute([$quizId]);
$questions = $questions->fetchAll();
foreach ($questions as &$q) {
    $opts = $pdo->prepare('SELECT * FROM question_options WHERE question_id = ? ORDER BY sort_order, id');
    $opts->execute([$q['id']]);
    $q['options'] = $opts->fetchAll();
}
unset($q);

// Refresh quiz row after possible updates
$stmt = $pdo->prepare('SELECT a.*, c.title AS course_title FROM assessments a JOIN courses c ON a.course_id = c.id WHERE a.id = ?');
$stmt->execute([$quizId]);
$quiz = $stmt->fetch();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="ins-wrap">
    <div class="ins-breadcrumb">
        <a href="<?= url('instructor/index.php') ?>">Instructor</a>
        <span> / </span>
        <a href="<?= url('instructor/course-edit.php?id=' . (int) $quiz['course_id']) ?>"><?= e($quiz['course_title']) ?></a>
        <span> / </span>
        <span>Quiz</span>
    </div>

    <div class="ins-page-head">
        <div>
            <h1 class="ins-title"><?= e($quiz['title']) ?></h1>
            <p class="text-muted" style="margin-top:0.35rem;font-size:0.9rem;"><?= e($quiz['type']) ?> • Pass at <?= e((string) $quiz['passing_score']) ?>%</p>
        </div>
        <div class="ins-actions">
            <a href="<?= url('instructor/course-edit.php?id=' . (int) $quiz['course_id']) ?>" class="btn btn-secondary">← Back to course</a>
        </div>
    </div>

    <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'danger' ?>"><?= e($flash['message']) ?></div>
    <?php endif ?>

    <div class="ins-layout">
        <div class="ins-main-stack">
            <div class="ins-card">
                <h2>Quiz settings</h2>
                <form method="POST">
                    <input type="hidden" name="update_quiz_meta" value="1">
                    <div class="ins-form-grid">
                        <div class="form-group">
                            <label>Passing score (%)</label>
                            <input type="number" name="passing_score" class="form-control" min="0" max="100" step="0.01" value="<?= e((string) $quiz['passing_score']) ?>">
                        </div>
                        <div class="form-group">
                            <label>Max attempts</label>
                            <input type="number" name="max_attempts" class="form-control" min="1" placeholder="Unlimited if empty" value="<?= e($quiz['max_attempts'] !== null ? (string) $quiz['max_attempts'] : '') ?>">
                        </div>
                        <div class="form-group">
                            <label>Time limit (minutes)</label>
                            <input type="number" name="time_limit_minutes" class="form-control" min="1" placeholder="None if empty" value="<?= e($quiz['time_limit_minutes'] !== null ? (string) $quiz['time_limit_minutes'] : '') ?>">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">Save settings</button>
                </form>
            </div>

            <div class="ins-card">
                <h2>Questions</h2>
                <?php if (empty($questions)): ?>
                    <p class="ins-empty">No questions yet. Add the first one below.</p>
                <?php else: ?>
                    <?php foreach ($questions as $i => $q): ?>
                        <div class="ins-q-card">
                            <div style="float: right; display: flex; gap: 5px;">
                                <button type="button" class="btn btn-secondary btn-sm edit-q-btn" 
                                    data-id="<?= $q['id'] ?>" 
                                    data-text="<?= e($q['question_text']) ?>" 
                                    data-type="<?= e($q['type']) ?>" 
                                    data-points="<?= (float)$q['points'] ?>" 
                                    data-feedback="<?= e($q['feedback_text'] ?? '') ?>"
                                    data-material-id="<?= (int)$q['material_id'] ?>"
                                    data-options='<?= json_encode($q['options']) ?>'>Edit</button>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this question?');">
                                    <input type="hidden" name="question_id" value="<?= $q['id'] ?>">
                                    <button type="submit" name="delete_question" class="btn btn-danger btn-sm" style="background:#e74c3c;color:white;border:none;padding:2px 8px;border-radius:4px;cursor:pointer;">Delete</button>
                                </form>
                            </div>
                            <strong><?= $i + 1 ?>.</strong> <?= e($q['question_text']) ?>
                            <div class="text-muted" style="font-size:0.8rem;margin-top:0.25rem;"><?= e(str_replace('_', ' ', $q['type'])) ?> • <?= (float)$q['points'] ?> pts</div>
                            <?php if (!empty($q['feedback_text'])): ?>
                                <div style="font-size: 0.8rem; color: var(--primary); margin-top: 5px;"><strong>Auto-Feedback:</strong> <?= e($q['feedback_text']) ?></div>
                            <?php endif; ?>
                            <?php if (!empty($q['material_title'])): ?>
                                <div style="font-size: 0.8rem; color: #6c757d; margin-top: 5px;"><strong>Related Lesson:</strong> <?= e($q['material_title']) ?></div>
                            <?php endif; ?>
                            <?php if (!empty($q['options'])): ?>
                                <?php if ($q['type'] === 'short_answer'): ?>
                                    <div style="font-size: 0.85rem; margin-top: 5px;"><strong>Correct Keywords:</strong> <?= e(implode(', ', array_column($q['options'], 'option_text'))) ?></div>
                                <?php else: ?>
                                    <ul class="ins-opt-list">
                                        <?php foreach ($q['options'] as $o): ?>
                                            <li class="<?= $o['is_correct'] ? 'ins-opt-correct' : '' ?>"><?= $o['is_correct'] ? '&#x2713; ' : '' ?><?= e($o['option_text']) ?></li>
                                        <?php endforeach ?>
                                    </ul>
                                <?php endif; ?>
                            <?php endif ?>
                        </div>
                    <?php endforeach ?>
                <?php endif ?>
            </div>

            <div class="ins-card" id="q-form-card">
                <h2 id="q-form-title">Add question</h2>
                <form method="POST" id="q-form">
                    <input type="hidden" name="add_question" id="q-form-action" value="1">
                    <div class="form-group">
                        <label>Question text</label>
                        <textarea name="question_text" class="form-control" rows="3" required></textarea>
                    </div>
                    <div class="form-group">
                        <label>Automated Feedback (Shown if student answers wrong)</label>
                        <textarea name="feedback_text" class="form-control" rows="2" placeholder="e.g. You seem weak in variables, please review Unit 1."></textarea>
                    </div>
                    <div class="ins-form-grid" style="margin-bottom: 1rem;">
                        <div class="form-group">
                            <label>Question type</label>
                            <select name="question_type" id="question_type" class="form-control">
                                <option value="multiple_choice">Multiple choice</option>
                                <option value="true_false">True / False</option>
                                <option value="short_answer">Short Answer</option>
                                <option value="essay">Essay / Long Answer</option>
                                <option value="file_upload">Assignment (File Upload)</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Points / Weight</label>
                            <input type="number" name="points" class="form-control" value="1" min="0" step="0.5">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Associated Lesson (Optional)</label>
                        <select name="material_id" id="question_material_id" class="form-control">
                            <option value=""> Select a Lesson </option>
                            <?php foreach ($courseUnits as $unit): ?>
                                <optgroup label="<?= e($unit['title']) ?>">
                                    <?php foreach ($materialsByUnitForCourse[$unit['id']] ?? [] as $material): ?>
                                        <option value="<?= (int)$material['id'] ?>"><?= e($material['title']) ?></option>
                                    <?php endforeach; ?>
                                </optgroup>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted">Link this question to a specific lesson for student reference.</small>
                    </div>

                    <div id="block-mc">
                        <div class="form-group">
                            <label>Answer choices</label>
                            <input type="text" name="options[]" class="form-control" placeholder="Option A">
                            <input type="text" name="options[]" class="form-control mt-1" placeholder="Option B">
                            <input type="text" name="options[]" class="form-control mt-1" placeholder="Option C (optional)">
                            <input type="text" name="options[]" class="form-control mt-1" placeholder="Option D (optional)">
                        </div>
                        <div class="form-group">
                            <label>Correct answer</label>
                            <select name="correct" class="form-control" style="max-width:120px;">
                                <option value="0">First option</option>
                                <option value="1">Second</option>
                                <option value="2">Third</option>
                                <option value="3">Fourth</option>
                            </select>
                        </div>
                    </div>

                    <div id="block-sa" style="display:none;">
                        <div class="form-group">
                            <label>Acceptable Correct Answers</label>
                            <input type="text" name="short_answers" class="form-control" placeholder="e.g. CPU, Central Processing Unit, processor">
                            <small class="text-muted">Separate multiple valid answers with commas. The system will auto-grade matches (case-insensitive).</small>
                        </div>
                    </div>

                    <div id="block-tf" style="display:none;">
                        <div class="form-group">
                            <label>Correct answer</label>
                            <select name="tf_correct" class="form-control" style="max-width:160px;">
                                <option value="true">True</option>
                                <option value="false">False</option>
                            </select>
                        </div>
                    </div>

                    <button type="submit" id="q-form-submit" class="btn btn-primary">Add question</button>
                </form>
            </div>
        </div>

        <!--

        <aside class="ins-side-stack">
            <div class="ins-card">
                <h2>Tips</h2>
                <p class="ins-card-muted" style="margin:0;">
                    Keep questions short. For video lessons, align each quiz with one module so learners get quick checks along the way.
                </p>
            </div>
        </aside>
            -->
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const qTypeSelect = document.getElementById('question_type');
    const mc = document.getElementById('block-mc');
    const tf = document.getElementById('block-tf');
    const sa = document.getElementById('block-sa');

    function toggleBlocks() {
        mc.style.display = 'none';
        tf.style.display = 'none';
        sa.style.display = 'none';

        if (qTypeSelect.value === 'true_false') {
            tf.style.display = 'block';
        } else if (qTypeSelect.value === 'short_answer') {
            sa.style.display = 'block';
        } else if (qTypeSelect.value === 'file_upload' || qTypeSelect.value === 'essay') {
            // No extra inputs needed
        } else {
            mc.style.display = 'block';
        }
    }

    qTypeSelect.addEventListener('change', toggleBlocks);

    // Edit Question Logic
    const editBtns = document.querySelectorAll('.edit-q-btn');
    const formCard = document.getElementById('q-form-card');
    const formTitle = document.getElementById('q-form-title');
    const formAction = document.getElementById('q-form-action');
    const formSubmit = document.getElementById('q-form-submit');
    const qForm = document.getElementById('q-form');

    editBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const data = this.dataset;
            const options = JSON.parse(data.options);

            // Switch to Edit Mode
            formTitle.innerText = "Edit Question";
            formAction.name = "update_question";
            formSubmit.innerText = "Update Question";

            let idInput = document.getElementById('edit_q_id');
            if (!idInput) {
                idInput = document.createElement('input');
                idInput.type = 'hidden';
                idInput.name = 'question_id';
                idInput.id = 'edit_q_id';
                qForm.appendChild(idInput);
            }
            idInput.value = data.id;

            qForm.querySelector('[name="question_text"]').value = data.text;
            qForm.querySelector('[name="points"]').value = data.points;
            qForm.querySelector('[name="feedback_text"]').value = data.feedback;
            qForm.querySelector('[name="material_id"]').value = data.materialId;
            qTypeSelect.value = data.type;
            toggleBlocks();

            // Fill options based on type
            if (data.type === 'multiple_choice') {
                const mcInputs = mc.querySelectorAll('input[name="options[]"]');
                const mcCorrect = mc.querySelector('select[name="correct"]');
                mcInputs.forEach(inp => inp.value = "");
                options.forEach((opt, idx) => {
                    if (mcInputs[idx]) mcInputs[idx].value = opt.option_text;
                    if (opt.is_correct) mcCorrect.value = idx;
                });
            } else if (data.type === 'short_answer') {
                const saInput = sa.querySelector('input[name="short_answers"]');
                saInput.value = options.map(o => o.option_text).join(', ');
            } else if (data.type === 'true_false') {
                const tfCorrect = tf.querySelector('select[name="tf_correct"]');
                const correctOpt = options.find(o => o.is_correct);
                if (correctOpt) tfCorrect.value = correctOpt.option_text.toLowerCase();
            }

            formCard.scrollIntoView({ behavior: 'smooth' });

            if (!document.getElementById('cancel-edit-q')) {
                const cancel = document.createElement('button');
                cancel.type = 'button';
                cancel.id = 'cancel-edit-q';
                cancel.className = 'btn btn-secondary';
                cancel.style.marginLeft = '10px';
                cancel.innerText = 'Cancel';
                cancel.onclick = () => location.reload();
                formSubmit.parentNode.appendChild(cancel);
            }
        });
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
