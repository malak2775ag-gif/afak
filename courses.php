<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

session_start();

$pageTitle = 'All Courses';

$category = $_GET['category'] ?? '';
$search = trim($_GET['search'] ?? '');

$sql = "
    SELECT c.*, u.first_name, u.last_name, cat.name as category_name
    FROM courses c
    JOIN users u ON c.instructor_id = u.id
    LEFT JOIN categories cat ON c.category_id = cat.id
    WHERE c.status = 'published'
";
$params = [];

if ($category) {
    $sql .= " AND cat.slug = ?";
    $params[] = $category;
}
if ($search) {
    $sql .= " AND (c.title LIKE ? OR c.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$sql .= " ORDER BY c.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$courses = $stmt->fetchAll();

// Categories for filter
$categories = $pdo->query("SELECT id, name, slug FROM categories ORDER BY name")->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<h2 class="section-title">All Courses</h2>

<form method="GET" style="display: flex; gap: 1rem; margin-bottom: 1.5rem; flex-wrap: wrap;">
    <input type="text" name="search" class="form-control" placeholder="Search courses..." value="<?= e($search) ?>" style="max-width: 300px;">
    <select name="category" class="form-control" style="max-width: 200px;">
        <option value="">All Categories</option>
        <?php foreach ($categories as $cat): ?>
            <option value="<?= e($cat['slug']) ?>" <?= $category === $cat['slug'] ? 'selected' : '' ?>><?= e($cat['name']) ?></option>
        <?php endforeach ?>
    </select>
    <button type="submit" class="btn btn-primary">Search</button>
</form>

<?php if (empty($courses)): ?>
    <p class="text-muted">No courses found.</p>
<?php else: ?>
    <div class="course-grid">
        <?php foreach ($courses as $course): ?>
            <a href="<?= url('course-detail.php?id=' . $course['id']) ?>" class="course-card" style="text-decoration: none; color: inherit;">
                <div class="course-card-image">
                    <?php if (!empty($course['thumbnail_url']) && $course['thumbnail_url'] !== 'NULL'): ?>
                        <img src="<?= e(url($course['thumbnail_url'])) ?>" alt="<?= e($course['title']) ?>">
                    <?php else: ?>
                        <img src="<?= url('assets/img/cover1.png') ?>" alt="No Image">
                    <?php endif ?>
                </div>
                <div class="course-card-body">
                    <h3><?= e($course['title']) ?></h3>
                    <p><?= e(mb_substr($course['short_description'] ?? $course['description'], 0, 100)) ?>...</p>
                    <div class="course-meta">
                        <span><?= e($course['category_name'] ?? 'General') ?></span>
                        <span><?= e($course['first_name'] . ' ' . $course['last_name']) ?></span>
                        <?php if ($course['duration_hours']): ?>
                            <span><?= $course['duration_hours'] ?>h</span>
                        <?php endif ?>
                        <?php if (!$course['is_free']): ?>
                            <span><?= number_format($course['price'], 2) ?> OMR</span>
                        <?php else: ?>
                            <span>Free</span>
                        <?php endif ?>
                    </div>
                </div>
            </a>
        <?php endforeach ?>
    </div>
<?php endif ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
