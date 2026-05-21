<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

session_start();
requireStudent(); // requireStudent() implicitly calls requireLogin()

$isAdmin = (($_SESSION['role'] ?? '') === 'admin');
$isInstructor = (($_SESSION['role'] ?? '') === 'instructor');

$courseId = (int)($_GET['id'] ?? 0);
if (!$courseId) {
    header('Location: ' . url('dashboard.php'));
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM courses WHERE id = ? AND status = 'published'");
$stmt->execute([$courseId]);
$course = $stmt->fetch();

if (!$course) {
    header('Location: ' . url('courses.php'));
    exit;
}

$stmt = $pdo->prepare("SELECT id, progress_percent, status FROM enrollments WHERE student_id = ? AND course_id = ? AND status IN ('active', 'completed')");
$stmt->execute([$_SESSION['user_id'], $courseId]);
$enrollment = $stmt->fetch();

// If the user is not enrolled AND is neither an admin nor an instructor, redirect them.
if (!$enrollment && !$isAdmin && !$isInstructor) { 
    header('Location: ' . url('course-detail.php?id=' . $courseId));
    exit;
}
// Allow admins and instructors to preview the course without an enrollment record
if (!$enrollment) {
    $enrollment = ['id' => 0, 'progress_percent' => 0, 'status' => 'preview'];
}

$pageTitle = $course['title'];
$enrollmentId = $enrollment['id'];
$enrollmentId = (int)$enrollment['id'];

// Units with thematerials
$units = $pdo->prepare("SELECT * FROM course_units WHERE course_id = ? ORDER BY sort_order, id");
$units->execute([$courseId]);
$units = $units->fetchAll();

$unitId = (int)($_GET['unit'] ?? 0);
$materialId = (int)($_GET['material'] ?? 0);

// get the first unit and material if none selected
if (!$unitId && !empty($units)) {
    foreach ($units as $u) {
        $mats = $pdo->prepare("SELECT id FROM course_materials WHERE unit_id = ? ORDER BY sort_order LIMIT 1");
        $mats->execute([$u['id']]);
        $first = $mats->fetch();
        if ($first) {
            $unitId = $u['id'];
            $materialId = $first['id'];
            break;
        }
    }
}
if ($unitId) {
    $materialsStmt = $pdo->prepare("SELECT * FROM course_materials WHERE unit_id = ? ORDER BY sort_order");
    $materialsStmt->execute([$unitId]);
    $materials = $materialsStmt->fetchAll();
    if (!$materialId && !empty($materials)) $materialId = $materials[0]['id'];
} else {
    $materials = [];
}

$currentMaterial = null;
if ($materialId) {
    $stmt = $pdo->prepare("SELECT * FROM course_materials WHERE id = ?");
    $stmt->execute([$materialId]);
    $currentMaterial = $stmt->fetch();
}


if ($currentMaterial && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['complete'])) {
    // Only students with a valid enrollment can mark materials as complete
    if ($enrollmentId <= 0) {
        header('Location: ' . url('course-view.php?id=' . $courseId . '&unit=' . $unitId . '&material=' . $materialId));
        exit;
    }

    $stmt = $pdo->prepare("
        INSERT INTO material_progress (enrollment_id, material_id, is_completed, last_accessed_at, completed_at)
        VALUES (?, ?, 1, NOW(), NOW())
        ON DUPLICATE KEY UPDATE is_completed = 1, completed_at = NOW(), last_accessed_at = NOW()
    ");
    $stmt->execute([$enrollmentId, $materialId]);
    // recalc enrollment progress
    $totalMaterials = $pdo->prepare("SELECT COUNT(*) FROM course_materials cm JOIN course_units cu ON cm.unit_id = cu.id WHERE cu.course_id = ?");
    $totalMaterials->execute([$courseId]);
    $totalMaterials = $totalMaterials->fetchColumn();
    $completedMaterials = $pdo->prepare("
        SELECT COUNT(*) FROM material_progress mp
        JOIN course_materials cm ON mp.material_id = cm.id
        JOIN course_units cu ON cm.unit_id = cu.id
        WHERE mp.enrollment_id = ? AND mp.is_completed = 1 AND cu.course_id = ?
    ");
    $completedMaterials->execute([$enrollmentId, $courseId]);
    $completedMaterials = $completedMaterials->fetchColumn();
    $progress = $totalMaterials > 0 ? round(($completedMaterials / $totalMaterials) * 100, 2) : 0;
    if ($progress >= 100) {
        $pdo->prepare("UPDATE enrollments SET progress_percent = ?, status = 'completed', completed_at = NOW() WHERE id = ?")->execute([$progress, $enrollmentId]);
        if ($enrollment['status'] !== 'completed') {
            createNotification(
                $pdo,
                (int) $_SESSION['user_id'],
                'grade',
                'Course Completed',
                'You completed ' . $course['title'] . '. You can now request your certificate for instructor/admin approval.',
                'course',
                $courseId,
                url('certificate-request.php?id=' . $courseId)
            );
        }
    } else {
        $pdo->prepare("UPDATE enrollments SET progress_percent = ? WHERE id = ?")->execute([$progress, $enrollmentId]);
    }
    header('Location: ' . url('course-view.php?id=' . $courseId . '&unit=' . $unitId . '&material=' . $materialId));
    exit;
}

$completedIds = [];
$stmt = $pdo->prepare("SELECT material_id FROM material_progress WHERE enrollment_id = ? AND is_completed = 1");
$stmt->execute([$enrollmentId]);
while ($row = $stmt->fetch()) $completedIds[] = $row['material_id'];

require_once __DIR__ . '/includes/header.php';
?>

<div style="display: flex; gap: 2rem; flex-wrap: wrap;">
    <aside class="course-sidebar" style="flex-shrink: 0;">
        <h3 style="margin-top: 0;"><?= e($course['title']) ?></h3>
        <div class="progress-bar mb-2">
            <div class="progress-bar-fill" style="width: <?= (float)$enrollment['progress_percent'] ?>%"></div>
        </div>
        <p class="text-muted"><?= number_format($enrollment['progress_percent'], 0) ?>% complete</p>
        <?php if ($enrollment['progress_percent'] >= 100): ?>
            <?php
            $hasCert = $pdo->prepare("SELECT id FROM certificates WHERE enrollment_id = ?");
            $hasCert->execute([$enrollmentId]);
            $hasCert = $hasCert->fetch();
            ?>
            <?php if (!$hasCert): ?>
                <a href="<?= url('certificate-request.php?id=' . $courseId) ?>" class="btn btn-primary" style="width: 100%; margin-top: 0.5rem;">Request Certificate</a>
            <?php else: ?>
                <a href="<?= url('my-certificates.php') ?>" class="btn btn-outline-light" style="width: 100%; margin-top: 0.5rem; color: var(--text);">View Certificate</a>
            <?php endif ?>
        <?php endif ?>

        <h4 style="margin: 1rem 0 0.5rem;">Content</h4>
        <ul class="unit-list">
            <?php foreach ($units as $u): ?>
                <?php
                $uId = (int)$u['id'];
                $unitMats = $pdo->prepare("SELECT * FROM course_materials WHERE unit_id = ? ORDER BY sort_order");
                $unitMats->execute([$uId]);
                $unitMats = $unitMats->fetchAll();

                $unitQuizzes = $pdo->prepare("SELECT * FROM assessments WHERE unit_id = ? ORDER BY sort_order");
                $unitQuizzes->execute([$uId]);
                $unitQuizzes = $unitQuizzes->fetchAll();
                ?>
                <li><strong><?= e($u['title']) ?></strong></li>
                <?php foreach ($unitMats as $m): ?>
                    <li class="<?= $m['id'] == $materialId ? 'active' : '' ?> <?= in_array($m['id'], $completedIds) ? 'completed' : '' ?>">
                        <a href="<?= url('course-view.php?id=' . $courseId . '&unit=' . $u['id'] . '&material=' . $m['id']) ?>">
                            <?= e($m['title']) ?> (<?= $m['type'] ?>)
                        </a>
                    </li>
                <?php endforeach ?>
                <?php foreach ($unitQuizzes as $qz): ?>
                    <?php
                    $attemptCheck = $pdo->prepare("SELECT id, passed FROM assessment_attempts WHERE enrollment_id = ? AND assessment_id = ? ORDER BY submitted_at DESC LIMIT 1");
                    $attemptCheck->execute([$enrollmentId, $qz['id']]);
                    $lastAttempt = $attemptCheck->fetch();
                    ?>
                    <li style="display: flex; justify-content: space-between; align-items: center;">
                        <a href="<?= url('quiz.php?id=' . $qz['id']) ?>" style="font-weight: 600; color: var(--primary);">
                             📝 <?= e($qz['title']) ?>
                        </a>
                        <?php if ($lastAttempt): ?>
                            <a href="<?= url('quiz-submission-summary.php?attempt=' . $lastAttempt['id']) ?>" class="ins-badge <?= $lastAttempt['passed'] ? 'ins-badge-published' : 'ins-badge-rejected' ?>" style="font-size: 0.65rem; padding: 1px 5px; text-decoration: none;">
                                <?= $lastAttempt['passed'] ? 'Passed' : 'View' ?>
                            </a>
                        <?php endif; ?>
                    </li>
                <?php endforeach ?>
            <?php endforeach ?>

            <?php
            $courseQuizzes = $pdo->prepare("SELECT * FROM assessments WHERE course_id = ? AND unit_id IS NULL ORDER BY sort_order");
            $courseQuizzes->execute([$courseId]);
            $courseQuizzes = $courseQuizzes->fetchAll();
            if (!empty($courseQuizzes)): ?>
                <li style="margin-top: 15px;"><strong>Final Assessments</strong></li>
                <?php foreach ($courseQuizzes as $qz): ?>
                    <li style="margin-bottom: 5px;">
                        <a href="<?= url('quiz.php?id=' . $qz['id']) ?>" style="text-decoration: none; color: var(--primary); font-weight: 600;">
                            🎓 <?= e($qz['title']) ?>
                        </a>
                    </li>
                <?php endforeach ?>
            <?php endif ?>
        </ul>

        <a href="<?= url('course-detail.php?id=' . $courseId) ?>" class="btn btn-outline-light" style="margin-top: 1rem; color: var(--text); border-color: var(--border);">← Back to course</a>
    </aside>

    <div class="course-content" style="flex: 1; min-width: 0;">
        <?php if ($currentMaterial): ?>
            <h2><?= e($currentMaterial['title']) ?></h2>
            <p class="text-muted"><?= ucfirst($currentMaterial['type']) ?></p>

            <?php 
            $url = $currentMaterial['content_url'];
            $isYouTube = preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/i', $url, $matches);
            $youtubeId = $isYouTube ? $matches[1] : null;
            
            $isVimeo = preg_match('/vimeo\.com\/(?:video\/)?([0-9]+)/i', $url, $vMatches);
            $vimeoId = $isVimeo ? $vMatches[1] : null;
            ?>

            <?php if ($currentMaterial['type'] === 'video' || ($currentMaterial['type'] === 'link' && ($isYouTube || $isVimeo))): ?>
                <?php if ($isYouTube): ?>
                    <div class="video-container" style="position: relative; padding-bottom: 56.25%; height: 0; overflow: hidden; max-width: 100%; background: #000; border-radius: 12px;">
                        <iframe src="https://www.youtube.com/embed/<?= $youtubeId ?>" 
                                style="position: absolute; top: 0; left: 0; width: 100%; height: 100%;" 
                                frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                    </div>
                <?php elseif ($isVimeo): ?>
                    <div class="video-container" style="position: relative; padding-bottom: 56.25%; height: 0; overflow: hidden; max-width: 100%; background: #000; border-radius: 12px;">
                        <iframe src="https://player.vimeo.com/video/<?= $vimeoId ?>" 
                                style="position: absolute; top: 0; left: 0; width: 100%; height: 100%;" 
                                frameborder="0" allow="autoplay; fullscreen; picture-in-picture" allowfullscreen></iframe>
                    </div>
                <?php else: ?>
                    <!-- Fallback for direct MP4 files -->
                    <video controls width="100%" style="max-width: 800px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
                        <source src="<?= e(url($url)) ?>" type="video/mp4">
                        Your browser does not support video.
                    </video>
                <?php endif; ?>
            <?php elseif ($currentMaterial['type'] === 'pdf'): ?>
                <iframe src="<?= e(url($currentMaterial['content_url'])) ?>" width="100%" height="600" style="border: none;"></iframe>
                <p class="mt-1"><a href="<?= e(url($currentMaterial['content_url'])) ?>" download class="btn btn-secondary" style="font-size: 0.8rem; padding: 5px 10px;">Download PDF</a></p>
            <?php elseif ($currentMaterial['type'] === 'slide' || $currentMaterial['type'] === 'document'): ?>
                <?php
                
                $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
                $fullFileUrl = $protocol . $_SERVER['HTTP_HOST'] . url($currentMaterial['content_url']);
                $isLocalhost = (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false || strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false);
                ?>
                <div class="document-viewer-wrap" style="border: 1px solid var(--border); border-radius: 12px; overflow: hidden; background: #fdfdfd;">
                    <?php if ($isLocalhost): ?>
                        <div style="padding: 2rem; text-align: center; background: #fff3cd; border-bottom: 1px solid #ffeeba; color: #856404;">
                            <p style="margin: 0; font-weight: 600;">Preview not available on localhost.</p>
                            <p style="margin: 0.5rem 0 0; font-size: 0.9rem;">The online viewer requires a public internet connection to access the file. Please download it instead.</p>
                        </div>
                    <?php else: ?>
                        <iframe src="https://view.officeapps.live.com/op/embed.aspx?src=<?= urlencode($fullFileUrl) ?>" width="100%" height="600" frameborder="0"></iframe>
                    <?php endif; ?>
                    
                    <div style="padding: 2rem; text-align: center; border-top: 1px solid var(--border); background: white;">
                        <p style="margin-bottom: 0.5rem;"><strong>Interactive Preview</strong></p>
                        <p class="text-muted" style="font-size: 0.85rem; max-width: 500px; margin: 0 auto 1.5rem;">
                            Note: The preview above requires a public internet connection. If you are on <code>localhost</code> or the file isn't loading, use the button below.
                        </p>
                        <a href="<?= e(url($currentMaterial['content_url'])) ?>" download class="btn btn-primary" style="display: inline-flex; align-items: center; gap: 8px;">
                             Download <?= $currentMaterial['type'] === 'slide' ? 'PowerPoint Presentation' : 'Word Document' ?>
                        </a>
                    </div>
                </div>
            <?php elseif ($currentMaterial['type'] === 'link'): ?>
                <p><a href="<?= e(url($currentMaterial['content_url'])) ?>" target="_blank" rel="noopener">Open resource →</a></p>
            <?php elseif ($currentMaterial['type'] === 'h5p'): ?>
                <div class="h5p-container" style="width: 100%; min-height: 600px; border-radius: 12px; overflow: hidden; border: 1px solid var(--border);">
                    <iframe src="<?= e($currentMaterial['content_url']) ?>" width="100%" height="600" frameborder="0" allowfullscreen="allowfullscreen" allow="geolocation *; microphone *; camera *; midi *; encrypted-media *"></iframe>
                    <script src="https://h5p.org/sites/all/modules/h5p/library/js/h5p-resizer.js" charset="UTF-8"></script>
                </div>
            <?php else: ?>
                <p><a href="<?= e(url($currentMaterial['content_url'])) ?>" target="_blank" class="btn btn-primary">Download / View <?= ucfirst($currentMaterial['type']) ?></a></p>
            <?php endif ?>

            <form method="POST" class="mt-2">
                <button type="submit" name="complete" value="1" class="btn btn-primary">Mark as Complete</button>
            </form>
        <?php else: ?>
            <p>Select a material from the sidebar to start learning.</p>
        <?php endif ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
