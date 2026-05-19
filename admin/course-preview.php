<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

session_start();
requireAdmin();

$courseId = (int)($_GET['id'] ?? 0);
if (!$courseId) {
    header('Location: ' . url('admin/courses.php'));
    exit;
}

// Admins can see the course regardless of status
$stmt = $pdo->prepare("SELECT c.*, u.first_name, u.last_name FROM courses c JOIN users u ON c.instructor_id = u.id WHERE c.id = ?");
$stmt->execute([$courseId]);
$course = $stmt->fetch();

if (!$course) {
    die("Course not found.");
}

$pageTitle = "[Admin Preview] " . $course['title'];

// Fetch full curriculum
$units = $pdo->prepare("SELECT * FROM course_units WHERE course_id = ? ORDER BY sort_order ASC");
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

require_once __DIR__ . '/../includes/header.php';
?>

<div class="alert alert-info" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
    <span><strong>Review Mode:</strong> You are viewing the internal content of "<?= e($course['title']) ?>".</span>
    <a href="<?= url('admin/courses.php') ?>" class="btn btn-secondary" style="padding: 5px 15px;">Back to List</a>
</div>

<div style="display: grid; grid-template-columns: 300px 1fr; gap: 2rem;">
    <!-- Content Sidebar -->
    <aside style="background: white; padding: 1.5rem; border-radius: 12px; border: 1px solid var(--border); height: fit-content;">
        <h4 style="margin-top: 0;">Curriculum</h4>
        <ul style="list-style: none; padding: 0;">
            <?php foreach ($units as $u): ?>
                <li style="margin-bottom: 1rem;">
                    <div style="font-weight: 700; color: var(--primary); border-bottom: 1px solid #eee; padding-bottom: 5px; margin-bottom: 8px;">
                        <?= e($u['title']) ?>
                    </div>
                    <ul style="list-style: none; padding-left: 10px;">
                        <?php
                        $mats = $pdo->prepare("SELECT * FROM course_materials WHERE unit_id = ? ORDER BY sort_order");
                        $mats->execute([$u['id']]);
                        foreach ($mats->fetchAll() as $m):
                        ?>
                            <li style="margin-bottom: 5px;">
                                <a href="<?= url('admin/course-preview.php?id='.$courseId.'&unit='.$u['id'].'&material='.$m['id']) ?>" 
                                   style="text-decoration: none; font-size: 0.9rem; color: <?= $m['id'] == $materialId ? 'var(--teal)' : 'var(--text)' ?>; font-weight: <?= $m['id'] == $materialId ? '700' : '400' ?>;">
                                    <?= e($m['title']) ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </li>
            <?php endforeach; ?>
        </ul>

        <h4 style="margin-top: 2rem; border-top: 1px solid #eee; padding-top: 1rem;">Quizzes in Course</h4>
        <?php
        $quizzes = $pdo->prepare("SELECT * FROM assessments WHERE course_id = ? ORDER BY sort_order");
        $quizzes->execute([$courseId]);
        $allQuizzes = $quizzes->fetchAll();
        if (empty($allQuizzes)) echo '<p class="text-muted">No quizzes added.</p>';
        foreach ($allQuizzes as $qz):
        ?>
            <details style="margin-bottom: 10px; font-size: 0.9rem; background: #f8f9fa; padding: 8px; border-radius: 6px;">
                <summary style="cursor: pointer; font-weight: 600;"><?= e($qz['title']) ?></summary>
                <div style="margin-top: 10px; padding-left: 10px; border-left: 2px solid #ddd;">
                    <?php
                    $qs = $pdo->prepare("SELECT * FROM questions WHERE assessment_id = ? ORDER BY sort_order");
                    $qs->execute([$qz['id']]);
                    foreach ($qs->fetchAll() as $idx => $q):
                    ?>
                        <p style="margin-bottom: 5px;"><strong><?= $idx+1 ?>.</strong> <?= e($q['question_text']) ?></p>
                    <?php endforeach; ?>
                </div>
            </details>
        <?php endforeach; ?>
    </aside>

    <!-- Material Viewer -->
    <main style="background: white; padding: 2rem; border-radius: 12px; border: 1px solid var(--border);">
        <?php if ($currentMaterial): ?>
            <h2 style="margin-top: 0;"><?= e($currentMaterial['title']) ?></h2>
            <p class="text-muted"><?= ucfirst($currentMaterial['type']) ?></p>
            <hr style="border: 0; border-top: 1px solid #eee; margin: 1.5rem 0;">

            <div class="material-body">
                <?php 
                $url = $currentMaterial['content_url'];
                $type = $currentMaterial['type'];

                $isYouTube = preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/i', $url, $matches);
                $youtubeId = $isYouTube ? $matches[1] : null;
                
                $isVimeo = preg_match('/vimeo\.com\/(?:video\/)?([0-9]+)/i', $url, $vMatches);
                $vimeoId = $isVimeo ? $vMatches[1] : null;

                if ($type === 'video' || ($type === 'link' && ($youtubeId || $vimeoId))): 
                    if ($youtubeId): ?>
                        <iframe width="100%" height="450" src="https://www.youtube.com/embed/<?= $youtubeId ?>" frameborder="0" allowfullscreen style="border-radius: 8px;"></iframe>
                    <?php elseif ($vimeoId): ?>
                        <div class="video-container" style="position: relative; padding-bottom: 56.25%; height: 0; overflow: hidden; max-width: 100%; background: #000; border-radius: 12px;">
                            <iframe src="https://player.vimeo.com/video/<?= $vimeoId ?>" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%;" frameborder="0" allow="autoplay; fullscreen; picture-in-picture" allowfullscreen></iframe>
                        </div>
                    <?php else: ?>
                        <video controls style="width: 100%; border-radius: 8px; background: #000;">
                            <source src="<?= e(url($url)) ?>" type="video/mp4">
                        </video>
                    <?php endif; ?>
                <?php elseif ($type === 'pdf'): ?>
                    <iframe src="<?= e(url($url)) ?>" width="100%" height="700px" style="border: none;"></iframe>
                <?php elseif ($type === 'slide' || $type === 'document'): ?>
                    <?php $fullFileUrl = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://") . $_SERVER['HTTP_HOST'] . url($url); ?>
                    <iframe src="https://view.officeapps.live.com/op/embed.aspx?src=<?= urlencode($fullFileUrl) ?>" width="100%" height="600" frameborder="0"></iframe>
                <?php elseif ($type === 'link'): ?>
                    <p><a href="<?= e(url($url)) ?>" target="_blank" rel="noopener">Open resource →</a></p>
                <?php else: ?>
                    <div style="text-align: center; padding: 3rem; background: #fcfcfc; border: 2px dashed #eee; border-radius: 12px;">
                        <p>This is a <strong><?= e($type) ?></strong> resource.</p>
                        <a href="<?= e(url($url)) ?>" target="_blank" class="btn btn-primary">Open / Download Resource</a>
                    </div>
                <?php endif; ?>
            </div>

            <?php if (!empty($currentMaterial['description'])): ?>
                <div style="margin-top: 2rem; padding-top: 1rem; border-top: 1px solid #eee;">
                    <h4>Notes:</h4>
                    <p><?= nl2br(e($currentMaterial['description'])) ?></p>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div style="text-align: center; color: #999; padding: 5rem 0;">
                <p style="font-size: 3rem; margin-bottom: 1rem;"></p>
                <h3>Select a lesson from the curriculum to preview its content</h3>
            </div>
        <?php endif; ?>

        <!-- Approval Section -->
        <?php if ($course['status'] === 'pending_review'): ?>
            <div style="margin-top: 3rem; padding: 2rem; background: #fffaf0; border: 1px solid #ffeeba; border-radius: 12px; text-align: center;">
                <h3>Review Decisions</h3>
                <form method="POST" action="<?= url('admin/courses.php') ?>" style="display: flex; gap: 1rem; justify-content: center; margin-top: 1rem;">
                    <input type="hidden" name="course_id" value="<?= $courseId ?>">
                    <button type="submit" name="approve" class="btn" style="background: #28a745; color: white; padding: 0.75rem 2rem;">Approve Course</button>
                    <button type="button" class="btn" style="background: #dc3545; color: white; padding: 0.75rem 2rem;" onclick="toggleReject()">Reject Course</button>
                </form>
                
                <div id="reject-form" style="display: none; margin-top: 1.5rem;">
                    <form method="POST" action="<?= url('admin/courses.php') ?>">
                        <input type="hidden" name="course_id" value="<?= $courseId ?>">
                        <textarea name="rejection_reason" class="form-control" placeholder="Provide a reason for rejection..." required style="margin-bottom: 1rem;"></textarea>
                        <button type="submit" name="reject" class="btn" style="background: #dc3545; color: white;">Confirm Rejection</button>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </main>
</div>

<script>
function toggleReject() {
    const f = document.getElementById('reject-form');
    f.style.display = f.style.display === 'none' ? 'block' : 'none';
}
</script>

<style>
.course-sidebar {
    background: white;
    padding: 1.5rem;
    border-radius: 12px;
    border: 1px solid var(--border);
    max-height: calc(100vh - 150px);
    overflow-y: auto;
}
/* Re-using some student view styles for the preview */
.material-body iframe {
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    border-radius: 8px;
}
.unit-list {
    list-style: none;
    padding: 0;
    margin: 0;
}
.unit-list li {
    margin-bottom: 0.5rem;
    font-size: 0.95rem;
}
.unit-list li strong {
    display: block;
    margin-top: 1rem;
    color: var(--primary);
    border-bottom: 1px solid var(--border);
    padding-bottom: 0.25rem;
}
summary::-webkit-details-marker {
    color: var(--primary);
}
.unit-list li a {
    display: block;
    padding: 0.5rem 0.75rem;
    border-radius: 6px;
    text-decoration: none;
    color: var(--text);
    transition: all 0.2s;
}
.unit-list li a:hover {
    background: var(--light);
}
.unit-list li.active a {
    background: var(--primary);
    color: white;
}
.course-content {
    background: white;
    padding: 2rem;
    border-radius: 12px;
    border: 1px solid var(--border);
}
.video-container iframe {
    border: none;
}
.ins-badge {
    display: inline-block;
    padding: 0.25rem 0.5rem;
    font-size: 0.75rem;
    font-weight: 600;
    border-radius: 4px;
    margin-bottom: 1rem;
}
.ins-badge-published {
    background: #e6fffa;
    color: #2c7a7b;
}
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>