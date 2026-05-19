<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/recommendations.php';

session_start();
requireStudent();

$userId = (int) $_SESSION['user_id'];
$pageTitle = 'Learning Style Survey';
$errors = [];

$profile = afak_get_learning_profile($pdo, $userId);

// If profile exists and not in edit mode, redirect to dashboard
if ($profile && ($_GET['edit'] ?? '') !== '1') {
    header('Location: ' . url('dashboard.php'));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Existing survey submission logic
    $major = trim((string) ($_POST['major'] ?? ''));
    $majorOther = trim((string) ($_POST['major_other'] ?? ''));
    if ($major === 'other' && $majorOther !== '') {
        $major = 'other:' . mb_substr($majorOther, 0, 40);
    }

    $data = [
        'major' => $major,
        'qualification_level' => (string) ($_POST['qualification_level'] ?? ''),
        'interested_field' => (string) ($_POST['interested_field'] ?? ''),
        'interested_level' => (string) ($_POST['interested_level'] ?? ''),
        'style_info_format' => (string) ($_POST['style_info_format'] ?? ''),
        'style_teaching' => (string) ($_POST['style_teaching'] ?? ''),
        'style_memory' => (string) ($_POST['style_memory'] ?? ''),
        'style_data' => (string) ($_POST['style_data'] ?? ''),
        'style_course_type' => (string) ($_POST['style_course_type'] ?? ''),
    ];

    $allowed = [
        'qualification_level' => ['foundation', 'diploma', 'advanced_diploma', 'bachelor', 'master', 'phd'],
        'interested_field' => ['it', 'business', 'eng', 'design', 'marketing'],
        'interested_level' => ['beginner', 'intermediate', 'advanced'],
        'style_info_format' => ['visual', 'verbal'],
        'style_teaching' => ['visual', 'verbal'],
        'style_memory' => ['visual', 'auditory'],
        'style_data' => ['charts', 'text'],
        'style_course_type' => ['concrete', 'abstract'],
    ];

    if ($data['major'] === '') {
        $errors[] = 'Please choose your major.';
    }
    foreach ($allowed as $field => $opts) {
        if (!in_array($data[$field], $opts, true)) {
            $errors[] = 'Please answer all survey questions.';
            break;
        }
    }

    if ($errors === []) {
        afak_save_learning_profile($pdo, $userId, $data);
        flash('success', 'Survey saved. Your recommendations are now personalized.');
        header('Location: ' . url('dashboard.php'));
        exit;
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="form-card" style="max-width: 900px; margin: 0 auto;"> 
    <h2 style="margin-bottom: 0.35rem;">Student Recommendation Survey</h2>
    <p class="text-muted" style="margin-top: 0;">
        Complete this once so AFAK can recommend courses based on your background, learning style, and performance.
    </p>

    <?php foreach ($errors as $err): ?>
        <div class="alert alert-danger"><?= e($err) ?></div>
    <?php endforeach ?>

    <form method="POST">
        <h3>Learning Background</h3>
        <div class="form-group">
            <label>What is your major?</label>
            <select name="major" class="form-control" required>
                <option value="">Select major</option>
                <?php $majorVal = $_POST['major'] ?? ''; ?>
                <option value="it" <?= $majorVal === 'it' ? 'selected' : '' ?>>IT</option>
                <option value="bsn" <?= $majorVal === 'bsn' ? 'selected' : '' ?>>BSN</option>
                <option value="eng" <?= $majorVal === 'eng' ? 'selected' : '' ?>>ENG</option>
                <option value="other" <?= $majorVal === 'other' ? 'selected' : '' ?>>Other</option>
            </select>
            <input type="text" name="major_other" class="form-control mt-1" placeholder="If other, specify..." value="<?= e($_POST['major_other'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label>What is your qualification/level of study?</label>
            <?php $qv = $_POST['qualification_level'] ?? ''; ?>
            <select name="qualification_level" class="form-control" required>
                <option value="">Select level</option>
                <option value="foundation" <?= $qv === 'foundation' ? 'selected' : '' ?>>Foundation</option>
                <option value="diploma" <?= $qv === 'diploma' ? 'selected' : '' ?>>Diploma</option>
                <option value="advanced_diploma" <?= $qv === 'advanced_diploma' ? 'selected' : '' ?>>Advanced Diploma</option>
                <option value="bachelor" <?= $qv === 'bachelor' ? 'selected' : '' ?>>Bachelor</option>
                <option value="master" <?= $qv === 'master' ? 'selected' : '' ?>>Master</option>
                <option value="phd" <?= $qv === 'phd' ? 'selected' : '' ?>>PhD</option>
            </select>
        </div>

        <div class="form-group">
            <label>Which field are you interested in?</label>
            <?php $ifv = $_POST['interested_field'] ?? ''; ?>
            <select name="interested_field" class="form-control" required>
                <option value="">Select field</option>
                <option value="it" <?= $ifv === 'it' ? 'selected' : '' ?>>IT</option>
                <option value="business" <?= $ifv === 'business' ? 'selected' : '' ?>>Business</option>
                <option value="eng" <?= $ifv === 'eng' ? 'selected' : '' ?>>ENG</option>
                <option value="design" <?= $ifv === 'design' ? 'selected' : '' ?>>Design</option>
                <option value="marketing" <?= $ifv === 'marketing' ? 'selected' : '' ?>>Marketing</option>
            </select>
        </div>

        <div class="form-group">
            <label>What is your current level in the interested field?</label>
            <?php $ilv = $_POST['interested_level'] ?? ''; ?>
            <select name="interested_level" class="form-control" required>
                <option value="">Select level</option>
                <option value="beginner" <?= $ilv === 'beginner' ? 'selected' : '' ?>>Beginner</option>
                <option value="intermediate" <?= $ilv === 'intermediate' ? 'selected' : '' ?>>Intermediate</option>
                <option value="advanced" <?= $ilv === 'advanced' ? 'selected' : '' ?>>Advanced</option>
            </select>
        </div>

        <h3>Learning Style</h3>
        <?php
        $questions = [
            'style_info_format' => ['In what format do you prefer to get new information?', 'visual' => 'Pictures, diagrams, graphs, or maps', 'verbal' => 'Written directions or verbal information'],
            'style_teaching' => ['How do you like teachers to deliver new information?', 'visual' => 'Use more diagrams on the board', 'verbal' => 'Spend more time explaining'],
            'style_memory' => ['What do you remember best?', 'visual' => 'What you see', 'auditory' => 'What you hear'],
            'style_data' => ['When someone is showing data, what do you prefer?', 'charts' => 'Charts or graphs', 'text' => 'Text summarizing the results'],
            'style_course_type' => ['What type of courses do you prefer?', 'concrete' => 'Concrete material (facts, data)', 'abstract' => 'Abstract material (concepts, theories)'],
        ];
        ?>
        <?php foreach ($questions as $name => $q): ?>
            <?php $curr = $_POST[$name] ?? ''; ?>
            <div class="form-group">
                <label><?= e($q[0]) ?></label>
                <select name="<?= e($name) ?>" class="form-control" required>
                    <option value="">Choose one</option>
                    <?php foreach ($q as $k => $v): if ($k === 0) continue; ?>
                        <option value="<?= e((string) $k) ?>" <?= $curr === (string) $k ? 'selected' : '' ?>><?= e($v) ?></option>
                    <?php endforeach ?>
                </select>
            </div>
        <?php endforeach ?>

        <button type="submit" class="btn btn-primary">Save Survey & Get Recommendations</button>
    </form>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
