<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

session_start();
requireInstructor();

$courseId = (int) ($_GET['id'] ?? 0);
if (!$courseId) {
    header('Location: ' . url('instructor/courses.php'));
    exit;
}

$stmt = $pdo->prepare('SELECT * FROM courses WHERE id = ? AND instructor_id = ?');
$stmt->execute([$courseId, $_SESSION['user_id']]);
$course = $stmt->fetch();

if (!$course) {
    header('Location: ' . url('instructor/courses.php'));
    exit;
}

$pageStylesheets = [
    'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.2/css/all.min.css'
];

$pageTitle = 'Edit: ' . $course['title'];
$flash = getFlash();

$editable = in_array($course['status'], ['draft', 'rejected', 'approved', 'published', 'pending_review'], true);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_course' && $editable) {
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $short_description = trim($_POST['short_description'] ?? '');
        $category_id = $_POST['category_id'] !== '' ? $_POST['category_id'] : null;
        $is_free = isset($_POST['is_free']) ? 1 : 0;
        $price = $is_free ? 0 : (isset($_POST['price']) && $_POST['price'] !== '' ? (float) $_POST['price'] : 0);
        $duration_hours = isset($_POST['duration_hours']) && $_POST['duration_hours'] !== '' ? (int) $_POST['duration_hours'] : null;
        $level = $_POST['level'] ?? 'all';
        $learning_style = $_POST['learning_style'] ?? 'mixed';
        $submit = $_POST['submit'] ?? 'save';

        $newStatus = $course['status'];
        if ($submit === 'submit_review' && in_array($course['status'], ['draft', 'rejected'], true)) {
            $newStatus = 'pending_review';
        }

        $slug = $course['slug'];
        if ($title !== '' && $title !== $course['title']) {
            $base = preg_replace('/[^a-z0-9]+/', '-', strtolower($title));
            $base = trim((string) $base, '-') ?: 'course';
            $n = 0;
            do {
                $trySlug = $n === 0 ? $base : $base . '-' . $n;
                $chk = $pdo->prepare('SELECT id FROM courses WHERE slug = ? AND id != ? LIMIT 1');
                $chk->execute([$trySlug, $courseId]);
                if (!$chk->fetch()) {
                    $slug = $trySlug;
                    break;
                }
                $n++;
            } while ($n < 50);
        }

        $thumbnail_url = $course['thumbnail_url'];
        if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] === UPLOAD_ERR_OK) {
            $fileType = $_FILES['thumbnail']['type'];
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            if (in_array($fileType, $allowedTypes, true)) {
                $fileExt = pathinfo($_FILES['thumbnail']['name'], PATHINFO_EXTENSION);
                $newFileName = uniqid('cover_', true) . '.' . $fileExt;
                $uploadDir = __DIR__ . '/../uploads/covers/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                if (move_uploaded_file($_FILES['thumbnail']['tmp_name'], $uploadDir . $newFileName)) {
                    $thumbnail_url = 'uploads/covers/' . $newFileName;
                }
            }
        }

        $pdo->prepare('UPDATE courses SET title = ?, slug = ?, description = ?, short_description = ?, category_id = ?, is_free = ?, price = ?, duration_hours = ?, level = ?, learning_style = ?, status = ?, rejection_reason = NULL, thumbnail_url = ? WHERE id = ?')
            ->execute([$title, $slug, $description, $short_description, $category_id, $is_free, $price, $duration_hours, $level, $learning_style, $newStatus, $thumbnail_url, $courseId]);
        flash('success', 'Course updated.');
        header('Location: ' . url('instructor/course-edit.php?id=' . $courseId));
        exit;
    }

    if ($action === 'add_unit' && $editable) {
        $title = trim($_POST['unit_title'] ?? '');
        if ($title !== '') {
            $maxOrder = $pdo->prepare('SELECT COALESCE(MAX(sort_order),0) FROM course_units WHERE course_id = ?');
            $maxOrder->execute([$courseId]);
            $next = (int) $maxOrder->fetchColumn() + 1;
            $pdo->prepare('INSERT INTO course_units (course_id, title, sort_order) VALUES (?, ?, ?)')->execute([$courseId, $title, $next]);
            flash('success', 'Module added.');
        }
        header('Location: ' . url('instructor/course-edit.php?id=' . $courseId));
        exit;
    }

    if ($action === 'add_material' && $editable) {
        $unitId = (int) $_POST['unit_id'];
        $title = trim($_POST['material_title'] ?? '');
        $type = $_POST['material_type'] ?? 'link';
        $content_url = trim($_POST['content_url'] ?? '');

        // Extract URL from H5P iframe code if provided
        if ($type === 'h5p' && preg_match('/src=["\']([^"\']+)["\']/', $content_url, $matches)) {
            $content_url = $matches[1];
        }

        // Handle File Upload for non-link types
        if ($type !== 'link' && $type !== 'h5p') {
            if (isset($_FILES['material_file']) && $_FILES['material_file']['name'] !== '') {
                if ($_FILES['material_file']['error'] !== UPLOAD_ERR_OK) {
                    $limit = ini_get('upload_max_filesize');
                    $postLimit = ini_get('post_max_size');
                    $uploadErrors = [
                        UPLOAD_ERR_INI_SIZE => "The file is too large. Your server limit is currently $limit (post_max_size is $postLimit).",
                        UPLOAD_ERR_FORM_SIZE => 'The file is too large.',
                        UPLOAD_ERR_PARTIAL => 'The file was only partially uploaded.',
                        UPLOAD_ERR_NO_FILE => 'No file was uploaded.',
                        UPLOAD_ERR_NO_TMP_DIR => 'Server missing a temporary folder.',
                        UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
                    ];
                    flash('danger', 'Upload failed: ' . ($uploadErrors[$_FILES['material_file']['error']] ?? 'Unknown error.'));
                    header('Location: ' . url('instructor/course-edit.php?id=' . $courseId));
                    exit;
                }
                
                $fileTmpPath = $_FILES['material_file']['tmp_name'];
                $fileName = $_FILES['material_file']['name'];
                $fileExt = pathinfo($fileName, PATHINFO_EXTENSION);
                $newFileName = uniqid('mat_', true) . '.' . $fileExt;
                $uploadDir = __DIR__ . '/../uploads/materials/';

                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

                if (move_uploaded_file($fileTmpPath, $uploadDir . $newFileName)) {
                    $content_url = 'uploads/materials/' . $newFileName;
                } else {
                    flash('danger', 'Failed to save the uploaded file.');
                    header('Location: ' . url('instructor/course-edit.php?id=' . $courseId));
                    exit;
                }
            } elseif (empty($content_url)) {
                flash('danger', 'Please select a file to upload or provide a link.');
                header('Location: ' . url('instructor/course-edit.php?id=' . $courseId));
                exit;
            }
        }

        if ($title !== '' && $content_url !== '') {
            $stmt = $pdo->prepare('SELECT course_id FROM course_units WHERE id = ?');
            $stmt->execute([$unitId]);
            $unit = $stmt->fetch();
            if ($unit && (int) $unit['course_id'] === $courseId) {
                $mo = $pdo->prepare('SELECT COALESCE(MAX(sort_order),0) FROM course_materials WHERE unit_id = ?');
                $mo->execute([$unitId]);
                $sort = (int) $mo->fetchColumn() + 1;
                $pdo->prepare('INSERT INTO course_materials (unit_id, title, type, content_url, sort_order) VALUES (?, ?, ?, ?, ?)')->execute([$unitId, $title, $type, $content_url, $sort]);
                flash('success', 'Lesson added.');
            }
        }
        header('Location: ' . url('instructor/course-edit.php?id=' . $courseId));
        exit;
    }

    if ($action === 'update_material' && $editable) {
        $matId = (int) $_POST['material_id'];
        $unitId = (int) $_POST['unit_id'];
        $title = trim($_POST['material_title'] ?? '');
        $type = $_POST['material_type'] ?? 'link';
        $content_url = trim($_POST['content_url'] ?? '');

        // Extract URL from H5P iframe code if provided
        if ($type === 'h5p' && preg_match('/src=["\']([^"\']+)["\']/', $content_url, $matches)) {
            $content_url = $matches[1];
        }

        // Security check: ensure material belongs to this course
        $stmt = $pdo->prepare("SELECT cm.* FROM course_materials cm JOIN course_units cu ON cm.unit_id = cu.id WHERE cm.id = ? AND cu.course_id = ?");
        $stmt->execute([$matId, $courseId]);
        $existing = $stmt->fetch();

        if ($existing && $title !== '') {
            // Handle File Upload
            if (isset($_FILES['material_file']) && $_FILES['material_file']['name'] !== '') {
                if ($_FILES['material_file']['error'] !== UPLOAD_ERR_OK) {
                    $limit = ini_get('upload_max_filesize');
                    $postLimit = ini_get('post_max_size');
                    $uploadErrors = [
                        UPLOAD_ERR_INI_SIZE => "The file is too large. Your server limit is currently $limit (post_max_size is $postLimit).",
                        UPLOAD_ERR_FORM_SIZE => 'The file is too large.',
                        UPLOAD_ERR_PARTIAL => 'The file was only partially uploaded.',
                        UPLOAD_ERR_NO_FILE => 'No file was uploaded.',
                        UPLOAD_ERR_NO_TMP_DIR => 'Server missing a temporary folder.',
                        UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
                        UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload.',
                    ];
                    flash('danger', 'Upload failed: ' . ($uploadErrors[$_FILES['material_file']['error']] ?? 'Unknown error.'));
                    header('Location: ' . url('instructor/course-edit.php?id=' . $courseId));
                    exit;
                }
                
                $fileTmpPath = $_FILES['material_file']['tmp_name'];
                $fileName = $_FILES['material_file']['name'];
                $fileExt = pathinfo($fileName, PATHINFO_EXTENSION);
                $newFileName = uniqid('mat_', true) . '.' . $fileExt;
                $uploadDir = __DIR__ . '/../uploads/materials/';

                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

                if (move_uploaded_file($fileTmpPath, $uploadDir . $newFileName)) {
                    $content_url = 'uploads/materials/' . $newFileName;
                } else {
                    flash('danger', 'Failed to save the uploaded file.');
                    header('Location: ' . url('instructor/course-edit.php?id=' . $courseId));
                    exit;
                }
            } elseif ($type !== 'link' && empty($content_url)) {
                // If not a link and no new file, keep current content
                $content_url = $existing['content_url'];
            }

            $pdo->prepare('UPDATE course_materials SET unit_id = ?, title = ?, type = ?, content_url = ? WHERE id = ?')
                ->execute([$unitId, $title, $type, $content_url, $matId]);
            
            flash('success', 'Lesson updated.');
        }
        header('Location: ' . url('instructor/course-edit.php?id=' . $courseId));
        exit;
    }

    if ($action === 'add_quiz' && $editable) {
        $unitId = $_POST['unit_id'] !== '' ? (int) $_POST['unit_id'] : null;
        $title = trim($_POST['quiz_title'] ?? '');
        $type = $unitId ? 'unit_quiz' : 'course_quiz';
        if ($title !== '') {
            $pdo->prepare('INSERT INTO assessments (course_id, unit_id, title, type, passing_score) VALUES (?, ?, ?, ?, 60)')->execute([$courseId, $unitId, $title, $type]);
            flash('success', 'Assessment created. Add questions next.');
        }
        header('Location: ' . url('instructor/course-edit.php?id=' . $courseId));
        exit;
    }
}

$stmt = $pdo->prepare('SELECT * FROM course_units WHERE course_id = ? ORDER BY sort_order, id');
$stmt->execute([$courseId]);
$units = $stmt->fetchAll();

$contentByUnit = [];
foreach ($units as $u) {
    $m = $pdo->prepare('SELECT * FROM course_materials WHERE unit_id = ? ORDER BY sort_order, id');
    $m->execute([$u['id']]);
    $contentByUnit[(int) $u['id']]['materials'] = $m->fetchAll();

    $q = $pdo->prepare('SELECT * FROM assessments WHERE unit_id = ? ORDER BY sort_order, id');
    $q->execute([$u['id']]);
    $contentByUnit[(int) $u['id']]['quizzes'] = $q->fetchAll();
}

$assessments = $pdo->prepare('SELECT a.*, cu.title AS unit_title FROM assessments a LEFT JOIN course_units cu ON a.unit_id = cu.id WHERE a.course_id = ? ORDER BY a.sort_order, a.id');
$assessments->execute([$courseId]);
$assessments = $assessments->fetchAll();

$categories = $pdo->query('SELECT * FROM categories ORDER BY name')->fetchAll();

$statusClass = 'ins-badge-' . preg_replace('/[^a-z0-9_]/', '', $course['status']);

function ins_material_icon(string $type): string
{
    switch ($type) {
        case 'video':
            return '<i class="fas fa-video"></i>';

        case 'pdf':
            return '<i class="fas fa-file-pdf"></i>';

        case 'link':
            return '<i class="fas fa-link"></i>';
            
        case 'document':
            return '<i class="fas fa-file-word"></i>';
        case 'slide':
            return '<i class="fas fa-file-powerpoint"></i>';
        case 'h5p':
            return '<i class="fas fa-puzzle-piece"></i>'; // Generic icon for interactive content
        default:
            return '•';
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="ins-wrap">
    <div class="ins-breadcrumb">
        <a href="<?= url('instructor/index.php') ?>">Instructor</a>
        <span> / </span>

        <a href="<?= url('instructor/courses.php') ?>">My courses</a>
        <span> / </span>
        <span><?= e($course['title']) ?></span>
    </div>

    <div class="ins-page-head">
        <div>
            <h1 class="ins-title"><?= e($course['title']) ?></h1>
            <p class="text-muted" style="margin-top:0.35rem;">

                <span class="ins-badge <?= e($statusClass) ?>"><?= e(str_replace('_', ' ', $course['status'])) ?></span>
                <?php if (!empty($course['rejection_reason']) && $course['status'] === 'rejected'): ?>
                    — <span class="text-muted"><?= e($course['rejection_reason']) ?></span>
                <?php endif ?>

            </p>
        </div>

        <div class="ins-actions">
            <?php if ($course['status'] === 'published'): ?>

            <a href="<?= url('course-detail.php?id=' . $courseId) ?>" class="btn btn-secondary" target="_blank" rel="noopener">Public page</a>
            <?php endif ?>

            <a href="<?= url('instructor/courses.php') ?>" class="btn btn-secondary">All courses</a>
        </div>
    </div>

    <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'danger' ?>"><?= e($flash['message']) ?></div>
    <?php endif ?>

    <div class="ins-layout">
        <div class="ins-main-stack">
            <?php if ($editable): ?>
            <div class="ins-card">
                <h2>Course details</h2>
                <form method="POST" enctype="multipart/form-data">

                    <input type="hidden" name="action" value="update_course">

                    <div class="ins-form-grid">
                        <div class="form-group full-span">
                            <label>Title</label>
                            <input type="text" name="title" class="form-control" value="<?= e($course['title']) ?>" required>
                        </div>
                        <div class="form-group full-span">
                            <label>Short description</label>
                            <input type="text" name="short_description" class="form-control" value="<?= e($course['short_description'] ?? '') ?>">
                        </div>
                        <div class="form-group full-span">
                            <label>Description</label>
                            <textarea name="description" class="form-control" rows="5" required><?= e($course['description']) ?></textarea>
                        </div>
                        <div class="form-group">
                            <label>Category</label>
                            <select name="category_id" class="form-control">
                                <option value="">—</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= (int) $cat['id'] ?>" <?= (string) $course['category_id'] === (string) $cat['id'] ? 'selected' : '' ?>><?= e($cat['name']) ?></option>
                                <?php endforeach ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Level</label>
                            <select name="level" class="form-control">
                                <option value="all" <?= $course['level'] === 'all' ? 'selected' : '' ?>>All levels</option>
                                <option value="beginner" <?= $course['level'] === 'beginner' ? 'selected' : '' ?>>Beginner</option>
                                <option value="intermediate" <?= $course['level'] === 'intermediate' ? 'selected' : '' ?>>Intermediate</option>
                                <option value="advanced" <?= $course['level'] === 'advanced' ? 'selected' : '' ?>>Advanced</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Primary Learning Style</label>
                            <select name="learning_style" class="form-control">
                                <?php $ls = $course['learning_style'] ?? 'mixed'; ?>
                                <option value="mixed" <?= $ls === 'mixed' ? 'selected' : '' ?>>Mixed (Default)</option>
                                <option value="visual" <?= $ls === 'visual' ? 'selected' : '' ?>>Visual (Video Heavy)</option>
                                <option value="verbal" <?= $ls === 'verbal' ? 'selected' : '' ?>>Verbal (Text Heavy)</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Duration (hours)</label>
                            <input type="number" name="duration_hours" class="form-control" min="0" value="<?= e($course['duration_hours'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label class="ins-check">
                                <input type="checkbox" name="is_free" id="is_free_checkbox" value="1" <?= $course['is_free'] ? 'checked' : '' ?>>
                                Free course
                            </label>
                        </div>
                        <div class="form-group" id="price_group" style="<?= $course['is_free'] ? 'display:none;' : '' ?>">
                            <label>Price (OMR)</label>
                            <input type="number" name="price" class="form-control" step="0.01" min="0" value="<?= e($course['price'] ?? '0') ?>">
                        </div>
                        <div class="form-group full-span">
                            <label>Cover image</label>
                            <?php if (!empty($course['thumbnail_url'])): ?>
                                <p class="text-muted" style="margin:0 0 0.5rem;font-size:0.875rem;">Current file: <?= e(basename($course['thumbnail_url'])) ?></p>
                            <?php endif ?>
                            <input type="file" name="thumbnail" accept="image/jpeg,image/png,image/gif,image/webp" class="form-control">
                        </div>
                    </div>
                    <div class="ins-footer-actions">
                        <button type="submit" name="submit" value="save" class="btn btn-primary">Save changes</button>
                        <?php if (in_array($course['status'], ['draft', 'rejected'], true)): ?>
                            <button type="submit" name="submit" value="submit_review" class="btn btn-secondary">Submit for review</button>
                        <?php endif ?>
                    </div>
                </form>
            </div>
            <?php endif ?>

            <div class="ins-card">
                <h2>Curriculum</h2>
                <p class="ins-card-muted">Organize content into modules, then add lessons (video, PDF, or link). Learners see this structure in the course player.</p>

                <?php if (empty($units)): ?>
                    <p class="ins-empty">No modules yet. Add the first module below.</p>
                <?php else: ?>
                    <?php foreach ($units as $u): ?>
                        <div class="ins-unit" style="border: 1px solid var(--border); border-radius: 8px; margin-bottom: 1rem; overflow: hidden;">
                            <div class="ins-unit-head">
                                <h4><?= e($u['title']) ?></h4>
                                <span class="text-muted" style="font-size:0.8rem;"><?= count($contentByUnit[(int) $u['id']]['materials'] ?? []) ?> lessons, <?= count($contentByUnit[(int) $u['id']]['quizzes'] ?? []) ?> quizzes</span>
                            </div>
                            <div class="ins-unit-body" style="background: #fff; padding: 0.5rem;">
                                <?php if (empty($contentByUnit[(int) $u['id']]['materials']) && empty($contentByUnit[(int) $u['id']]['quizzes'])): ?>
                                    <p class="ins-empty">No content in this module.</p>
                                <?php else: ?>
                                    <ul class="ins-mat-list">
                                        <?php foreach ($contentByUnit[(int) $u['id']]['materials'] as $mat): ?>
                                            <li>
                                                <div style="display:flex; justify-content:space-between; align-items:center; width:100%;">
                                                    <div style="display:flex; align-items:center; gap:10px;">
                                                        <span class="ins-mat-ico"><?= ins_material_icon($mat['type']) ?></span>
                                                        <span>
                                                            <strong><?= e($mat['title']) ?></strong>
                                                            <span class="text-muted"> — <?= e($mat['type']) ?></span>
                                                            <?php if (!empty($mat['content_url'])): ?>
                                                                <br><small class="text-muted" style="word-break:break-all;"><?= e($mat['content_url']) ?></small>
                                                            <?php endif ?>
                                                        </span>
                                                    </div>
                                                    <?php if($editable): ?>
                                                        <button type="button" class="btn btn-secondary edit-mat-btn" 
                                                            style="padding: 4px 8px; font-size: 0.75rem;"
                                                            data-id="<?= $mat['id'] ?>" data-title="<?= e($mat['title']) ?>" 
                                                            data-type="<?= e($mat['type']) ?>" data-url="<?= e($mat['content_url']) ?>" 
                                                            data-unit="<?= $u['id'] ?>">Edit</button>
                                                    <?php endif; ?>
                                                </div>
                                            </li>
                                        <?php endforeach ?>
                                        <?php foreach ($contentByUnit[(int) $u['id']]['quizzes'] as $qz): ?>
                                            <li style="background: #fdfaf0; border-left: 3px solid #f39c12;">
                                                <div style="display:flex; justify-content:space-between; align-items:center; width:100%;">
                                                    <div style="display:flex; align-items:center; gap:10px;">
                                                        <span class="ins-mat-ico">📝</span>
                                                        <span>
                                                            <strong><?= e($qz['title']) ?></strong>
                                                            <span class="text-muted"> — Quiz</span>
                                                        </span>
                                                    </div>
                                                    <div>
                                                        <a href="<?= url('instructor/quiz-edit.php?id=' . (int) $qz['id']) ?>" class="btn btn-primary" style="padding: 4px 8px; font-size: 0.75rem;">Edit Questions</a>
                                                    </div>
                                                </div>
                                            </li>
                                        <?php endforeach ?>
                                    </ul>
                                <?php endif ?>
                            </div>
                        </div>
                    <?php endforeach ?>
                <?php endif ?>

                <?php if ($editable): ?>
                <form method="POST" class="ins-inline-form">
                    <input type="hidden" name="action" value="add_unit">
                    <input type="text" name="unit_title" class="form-control" placeholder="New module title" required>
                    <button type="submit" class="btn btn-primary">Add module</button>
                </form>
                <?php endif ?>
            </div>

            <?php if ($editable && !empty($units)): ?>
            <div class="ins-card" id="lesson-form-card">
                <h2>Add lesson</h2>
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" id="lesson-form-action" value="add_material">
                    <div class="ins-form-grid">
                        <div class="form-group">
                            <label>Module</label>
                            <select name="unit_id" class="form-control" required>
                                <?php foreach ($units as $u): ?>
                                    <option value="<?= (int) $u['id'] ?>"><?= e($u['title']) ?></option>
                                <?php endforeach ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Lesson title</label>
                            <input type="text" name="material_title" class="form-control" required placeholder="e.g. Welcome video">
                        </div>
                        <div class="form-group">
                            <label>Type</label>
                            <select name="material_type" id="material_type_select" class="form-control">
                                <option value="video">Video</option>
                                <option value="pdf">PDF</option>
                                <option value="link">Link</option>
                                <option value="document">Document</option>
                                <option value="slide">Slide deck</option>
                                <option value="h5p">H5P Interactive</option>
                            </select>
                        </div>
                        <div class="form-group full-span">
                            <div id="material_file_upload_group">
                                <label>Upload file</label>
                                <input type="file" name="material_file" class="form-control">
                                <small class="text-muted">Recommended for Video/PDF. Max: 20MB</small>
                            </div>
                            <div id="material_link_input_group" style="display:none;">
                                <label>URL or External Link</label>
                                <input type="text" name="content_url" class="form-control" placeholder="Paste URL or H5P embed code here...">
                            </div>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">Add lesson</button>
                </form>
            </div>
            <?php endif ?>

            <div class="ins-card">
                <h2>Course-wide Assessments</h2>
                <p class="ins-card-muted">Quizzes that apply to the entire course rather than a specific module.</p>

                <?php 
                $courseQuizzes = array_filter($assessments, fn($a) => is_null($a['unit_id']));
                if (empty($courseQuizzes)): ?>
                    <p class="ins-empty">No course-wide quizzes yet.</p>
                <?php else: ?>
                    <ul class="ins-mat-list">
                        <?php foreach ($courseQuizzes as $qz): ?>
                            <li style="background: #f0f7ff; border-left: 3px solid #3498db;">
                                <div style="display:flex; justify-content:space-between; align-items:center; width:100%;">
                                    <div style="display:flex; align-items:center; gap:10px;">
                                        <span class="ins-mat-ico">🎓</span>
                                        <span>
                                            <strong><?= e($qz['title']) ?></strong>
                                            <span class="text-muted"> — Final Assessment</span>
                                        </span>
                                    </div>
                                    <div>
                                        <a href="<?= url('instructor/quiz-edit.php?id=' . (int) $qz['id']) ?>" class="btn btn-primary" style="padding: 4px 8px; font-size: 0.75rem;">Edit Questions</a>
                                    </div>
                                </div>
                            </li>
                        <?php endforeach ?>
                    </ul>
                <?php endif ?>

                <?php if ($editable): ?>
                <form method="POST" class="ins-inline-form" style="margin-top:1rem;">
                    <input type="hidden" name="action" value="add_quiz">
                    <input type="text" name="quiz_title" class="form-control" placeholder="Quiz title" required>
                    <select name="unit_id" class="form-control" style="max-width:220px;">
                        <option value="">Course-level quiz</option>
                        <?php foreach ($units as $u): ?>
                            <option value="<?= (int) $u['id'] ?>"><?= e($u['title']) ?></option>
                        <?php endforeach ?>
                    </select>
                    <button type="submit" class="btn btn-primary">Create quiz</button>
                </form>
                <?php endif ?>
            </div>
        </div>

        <aside class="ins-side-stack">
            <div class="ins-card">
                <h3>Publish workflow</h3>
                <p class="ins-card-muted">
                    Draft → submit for admin review → approved → admin can publish for learners.
                </p>
            </div>
            <div class="ins-card">
                <h3>Quick links</h3>
                <p style="margin:0;">
                    <a href="<?= url('instructor/students.php?course_id=' . (int) $courseId) ?>">Students</a><br>
                    <a href="<?= url('instructor/announcements.php') ?>">Announcements</a><br>
                    <a href="<?= url('instructor/certificates.php') ?>">Certificates</a>
                </p>
            </div>
        </aside>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle Price based on Free checkbox
    const isFreeCheck = document.getElementById('is_free_checkbox');
    const priceGroup = document.getElementById('price_group');
    if (isFreeCheck && priceGroup) {
        isFreeCheck.addEventListener('change', function() {
            priceGroup.style.display = this.checked ? 'none' : 'block';
        });
    }

    // Toggle Lesson inputs based on Type
    const materialTypeSelect = document.getElementById('material_type_select');
    const fileUploadGroup = document.getElementById('material_file_upload_group');
    const linkInputGroup = document.getElementById('material_link_input_group');
    const fileInput = fileUploadGroup ? fileUploadGroup.querySelector('input[name="material_file"]') : null;
    const linkInput = linkInputGroup ? linkInputGroup.querySelector('input[name="content_url"]') : null;

    function toggleMaterialInputs() {
        if(!materialTypeSelect) return;
        const selectedType = materialTypeSelect.value;
        if (selectedType === 'link' || selectedType === 'h5p') {
            fileUploadGroup.style.display = 'none';
            linkInputGroup.style.display = 'block';
            fileInput.removeAttribute('required');
            linkInput.setAttribute('required', 'required');
        } else {
            fileUploadGroup.style.display = 'block';
            linkInputGroup.style.display = 'none';
            fileInput.setAttribute('required', 'required');
            linkInput.removeAttribute('required');

            // Update accept attribute based on type
            let acceptAttr = '';
            switch (selectedType) {
                case 'video':
                    acceptAttr = 'video/*';
                    break;
                case 'pdf':
                    acceptAttr = 'application/pdf';
                    break;
                case 'document':
                    acceptAttr = '.doc,.docx,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,.txt,.rtf';
                    break;
                case 'slide':
                    acceptAttr = '.ppt,.pptx,application/vnd.ms-powerpoint,application/vnd.openxmlformats-officedocument.presentationml.presentation,.odp';
                    break;
                default:
                    acceptAttr = ''; // Broad accept for other types, or restrict further
            }
            fileInput.setAttribute('accept', acceptAttr);
        }
    }

    materialTypeSelect.addEventListener('change', toggleMaterialInputs);
    toggleMaterialInputs(); // Call on load to set initial state

    // Edit Material logic
    const editBtns = document.querySelectorAll('.edit-mat-btn');
    const lessonCardTitle = document.querySelector('#lesson-form-card h2');
    const lessonActionInput = document.querySelector('#lesson-form-action');
    const lessonSubmitBtn = document.querySelector('#lesson-form-card button[type="submit"]');
    const unitSelect = document.querySelector('#lesson-form-card select[name="unit_id"]');
    const titleInput = document.querySelector('#lesson-form-card input[name="material_title"]');
    const urlInput = document.querySelector('#lesson-form-card input[name="content_url"]');
    const lessonForm = document.querySelector('#lesson-form-card form');

    editBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.dataset.id;
            const title = this.dataset.title;
            const type = this.dataset.type;
            const url = this.dataset.url;
            const unit = this.dataset.unit;

            // Switch to Edit Mode
            lessonCardTitle.innerText = "Edit lesson: " + title;
            lessonActionInput.value = "update_material";
            lessonSubmitBtn.innerText = "Update lesson";
            
            let idInput = document.getElementById('edit_material_id');
            if (!idInput) {
                idInput = document.createElement('input');
                idInput.type = 'hidden';
                idInput.name = 'material_id';
                idInput.id = 'edit_material_id';
                lessonForm.appendChild(idInput);
            }
            idInput.value = id;

            unitSelect.value = unit;
            titleInput.value = title;
            materialTypeSelect.value = type;
            urlInput.value = url;

            toggleMaterialInputs();
            document.getElementById('lesson-form-card').scrollIntoView({ behavior: 'smooth' });

            if (!document.getElementById('cancel-edit-btn')) {
                const cancelBtn = document.createElement('button');
                cancelBtn.type = 'button';
                cancelBtn.id = 'cancel-edit-btn';
                cancelBtn.className = 'btn btn-secondary';
                cancelBtn.style.marginLeft = '10px';
                cancelBtn.innerText = 'Cancel';
                cancelBtn.onclick = () => location.reload();
                lessonSubmitBtn.parentNode.appendChild(cancelBtn);
            }
        });
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
