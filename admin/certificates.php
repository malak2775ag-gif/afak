<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

session_start();
requireAdmin();

$pageTitle = 'Certificates';
$flash = getFlash();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $certId = (int)$_POST['certificate_id'];
    if (isset($_POST['approve'])) {
        $pdo->prepare("UPDATE certificates SET status = 'approved', admin_approved_by = ?, admin_approved_at = NOW(), issued_at = NOW() WHERE id = ? AND status = 'pending_admin'")
            ->execute([$_SESSION['user_id'], $certId]);
        $stmt = $pdo->prepare("SELECT student_id FROM certificates WHERE id = ?");
        $stmt->execute([$certId]);
        $cert = $stmt->fetch();
        if ($cert) {
            $pdo->prepare("INSERT INTO notifications (user_id, type, title, message, related_type, related_id) VALUES (?, 'certificate', 'Certificate Ready', 'Your certificate has been approved and is ready to download.', 'certificate', ?)")
                ->execute([$cert['student_id'], $certId]);
        }
        flash('success', 'Certificate approved.');
    } elseif (isset($_POST['reject'])) {
        $pdo->prepare("UPDATE certificates SET status = 'rejected', admin_approved_by = ? WHERE id = ? AND status = 'pending_admin'")
            ->execute([$_SESSION['user_id'], $certId]);
        flash('success', 'Certificate rejected.');
    }
    header('Location: ' . url('admin/certificates.php'));
    exit;
}

$status = $_GET['status'] ?? 'pending_admin';
$certs = $pdo->prepare("
    SELECT cert.*, u.first_name, u.last_name, c.title as course_title
    FROM certificates cert
    JOIN users u ON cert.student_id = u.id
    JOIN courses c ON cert.course_id = c.id
    WHERE cert.status = ?
    ORDER BY cert.created_at DESC
");
$certs->execute([$status]);
$certs = $certs->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<h2 class="section-title">Certificates</h2>

<?php if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'danger' ?>"><?= e($flash['message']) ?></div>
<?php endif ?>

<form method="GET" style="margin-bottom: 1.5rem;">
    <select name="status" class="form-control" style="max-width: 200px; display: inline-block;">
        <option value="pending_admin" <?= $status === 'pending_admin' ? 'selected' : '' ?>>Pending Approval</option>
        <option value="approved" <?= $status === 'approved' ? 'selected' : '' ?>>Approved</option>
        <option value="rejected" <?= $status === 'rejected' ? 'selected' : '' ?>>Rejected</option>
    </select>
    <button type="submit" class="btn btn-primary">Filter</button>
</form>

<table style="width: 100%; background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 12px rgba(0,0,0,0.06);">
    <thead>
        <tr style="background: var(--light);">
            <th style="padding: 1rem; text-align: left;">Student</th>
            <th style="padding: 1rem;">Course</th>
            <th style="padding: 1rem;">Code</th>
            <th style="padding: 1rem;">Date</th>
            <th style="padding: 1rem;">Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($certs as $c): ?>
        <tr style="border-top: 1px solid var(--border);">
            <td style="padding: 1rem;"><?= e($c['first_name'] . ' ' . $c['last_name']) ?></td>
            <td style="padding: 1rem;"><?= e($c['course_title']) ?></td>
            <td style="padding: 1rem;"><?= e($c['certificate_code']) ?></td>
            <td style="padding: 1rem;"><?= date('Y-m-d', strtotime($c['created_at'])) ?></td>
            <td style="padding: 1rem;">
                <?php if ($c['status'] === 'pending_admin'): ?>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="certificate_id" value="<?= $c['id'] ?>">
                    <button type="submit" name="approve" class="btn btn-primary" style="padding: 0.35rem 0.75rem;">Approve</button>
                    <button type="submit" name="reject" class="btn" style="background: #dc3545; color: white; padding: 0.35rem 0.75rem;">Reject</button>
                </form>
                <?php endif ?>
            </td>
        </tr>
        <?php endforeach ?>
    </tbody>
</table>

<?php if (empty($certs)): ?>
    <p class="text-muted">No certificates found.</p>
<?php endif ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
