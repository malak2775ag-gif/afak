<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

session_start();
requireStudent();

$pageTitle = 'My Certificates';
$pageStylesheets = [
    'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.2/css/all.min.css'
];

$certs = $pdo->prepare("
    SELECT cert.*, c.title as course_title
    FROM certificates cert
    JOIN courses c ON cert.course_id = c.id
    WHERE cert.student_id = ?
    ORDER BY cert.issued_at DESC, cert.created_at DESC
");
$certs->execute([$_SESSION['user_id']]);
$certs = $certs->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; gap: 1rem; flex-wrap: wrap; margin-bottom: 1rem;">
    <h2 class="section-title" style="margin-bottom: 0;">My Certificates</h2>
    <a href="<?= url('student-report.php') ?>" class="btn btn-secondary">Generate Report</a>
</div>

<?php if (empty($certs)): ?>
    <p class="text-muted">You don't have any certificates yet. Complete a course to receive one automatically.</p>
<?php else: ?>
    <div class="course-grid">
        <?php foreach ($certs as $c): ?>
            <div class="course-card">
                <div class="course-card-image">
                    <?php if ($c['status'] === 'approved'): ?>
                        <img src="<?= url('certificate-download.php?id=' . $c['id']) ?>" alt="Certificate Preview" style="width: 100%; height: 160px; object-fit: cover; border-bottom: 1px solid #eee;">
                    <?php else: ?>
                        <div style="display: flex; align-items: center; justify-content: center; background: #f8f9fa; height: 160px; font-size: 4rem; color: #f39c12;">
                            <i class="fas fa-file-certificate"></i>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="course-card-body">
                    <h3><?= e($c['course_title']) ?></h3>
                    <p class="course-meta">Code: <?= e($c['certificate_code']) ?></p>
                    <p>Status: <?= e($c['status']) ?></p>
                    <?php if ($c['status'] === 'approved'): ?>
                        <p class="text-muted">Issued: <?= date('M j, Y', strtotime($c['issued_at'] ?? $c['created_at'])) ?></p>
                        <div style="display: flex; gap: 0.5rem; margin-top: 1rem;">
                            <a href="<?= url('certificate-download.php?id=' . $c['id']) ?>" target="_blank" class="btn btn-secondary" style="flex: 1; text-align: center;">View</a>
                            <a href="<?= url('certificate-download.php?id=' . $c['id']) ?>" download="Certificate_<?= $c['certificate_code'] ?>.png" class="btn btn-primary" style="flex: 1; text-align: center;">Download</a>
                        </div>
                    <?php endif ?>
                </div>
            </div>
        <?php endforeach ?>
    </div>
<?php endif ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
