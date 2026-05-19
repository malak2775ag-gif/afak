<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

session_start();
requireInstructor();

$pageTitle = 'Certificates';
$userId = $_SESSION['user_id'];

// Approve certificate (instructor approval)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['approve'])) {
    $certId = (int)$_POST['certificate_id'];
    $stmt = $pdo->prepare("SELECT cert.id FROM certificates cert JOIN courses c ON cert.course_id = c.id WHERE cert.id = ? AND c.instructor_id = ? AND cert.status = 'pending_instructor'");
    $stmt->execute([$certId, $userId]);
    if ($stmt->fetch()) {
        $pdo->prepare("UPDATE certificates SET status = 'pending_admin', instructor_approved_by = ?, instructor_approved_at = NOW() WHERE id = ?")
            ->execute([$userId, $certId]);
        flash('success', 'Certificate approved. Sent to admin for final approval.');
    }
    header('Location: ' . url('instructor/certificates.php'));
    exit;
}

$certs = $pdo->prepare("
    SELECT cert.*, u.first_name, u.last_name, c.title as course_title
    FROM certificates cert
    JOIN users u ON cert.student_id = u.id
    JOIN courses c ON cert.course_id = c.id
    WHERE c.instructor_id = ?
    ORDER BY cert.created_at DESC
");
$certs->execute([$userId]);
$certs = $certs->fetchAll();

$flash = getFlash();

require_once __DIR__ . '/../includes/header.php';
?>

<h2 class="section-title">Certificates</h2>

<?php if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'danger' ?>"><?= e($flash['message']) ?></div>
<?php endif ?>

<table style="width: 100%; background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 12px rgba(0,0,0,0.06);">
    <thead>
        <tr style="background: var(--light);">
            <th style="padding: 1rem; text-align: left;">Student</th>
            <th style="padding: 1rem;">Course</th>
            <th style="padding: 1rem;">Status</th>
            <th style="padding: 1rem;">Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($certs as $c): ?>
        <tr style="border-top: 1px solid var(--border);">
            <td style="padding: 1rem;"><?= e($c['first_name'] . ' ' . $c['last_name']) ?></td>
            <td style="padding: 1rem;"><?= e($c['course_title']) ?></td>
            <td style="padding: 1rem;"><?= e($c['status']) ?></td>
            <td style="padding: 1rem;">
                <?php if ($c['status'] === 'pending_instructor'): ?>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="certificate_id" value="<?= $c['id'] ?>">
                    <button type="submit" name="approve" class="btn btn-primary" style="padding: 0.35rem 0.75rem;">Approve</button>
                </form>
                <?php endif ?>
            </td>
        </tr>
        <?php endforeach ?>
    </tbody>
</table>

<?php if (empty($certs)): ?>
    <p class="text-muted">No certificates yet.</p>
<?php endif ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
