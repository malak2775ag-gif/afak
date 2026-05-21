<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

session_start();
requireAdmin();

$pageTitle = 'Payments';
$flash = getFlash();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify'])) {
    $paymentId = (int)$_POST['payment_id'];
    $pdo->prepare("UPDATE payments SET status = 'completed', verified_by = ?, verified_at = NOW() WHERE id = ? AND status = 'pending'")
        ->execute([$_SESSION['user_id'], $paymentId]);
    $stmt = $pdo->prepare("SELECT student_id, course_id FROM payments WHERE id = ?");
    $stmt->execute([$paymentId]);
    $pay = $stmt->fetch();
    if ($pay) {
        $stmt = $pdo->prepare("SELECT id FROM enrollments WHERE student_id = ? AND course_id = ?");
        $stmt->execute([$pay['student_id'], $pay['course_id']]);
        if (!$stmt->fetch()) {
            $pdo->prepare("INSERT INTO enrollments (student_id, course_id) VALUES (?, ?)")->execute([$pay['student_id'], $pay['course_id']]);
        }
        $pdo->prepare("INSERT INTO notifications (user_id, type, title, message) VALUES (?, 'payment', 'Payment Verified', 'Your payment has been verified. You can now access the course.')")
            ->execute([$pay['student_id']]);
    }
    flash('success', 'Payment verified. Student enrolled.');
    header('Location: ' . url('admin/payments.php'));
    exit;
}

$status = $_GET['status'] ?? 'pending';
$payments = $pdo->prepare("
    SELECT p.*, u.first_name, u.last_name, u.email, c.title as course_title
    FROM payments p
    JOIN users u ON p.student_id = u.id
    JOIN courses c ON p.course_id = c.id
    WHERE p.status = ?
    ORDER BY p.created_at DESC
");
$payments->execute([$status]);
$payments = $payments->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<h2 class="section-title">Payments</h2>

<?php if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'danger' ?>"><?= e($flash['message']) ?></div>
<?php endif ?>

<form method="GET" style="margin-bottom: 1.5rem;">
    <select name="status" class="form-control" style="max-width: 200px; display: inline-block;">
        <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>Pending</option>
        <option value="completed" <?= $status === 'completed' ? 'selected' : '' ?>>Completed</option>
        <option value="failed" <?= $status === 'failed' ? 'selected' : '' ?>>Failed</option>
    </select>
    <button type="submit" class="btn btn-primary">Filter</button>
</form>

<table style="width: 100%; background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 12px rgba(0,0,0,0.06);">
    <thead>
        <tr style="background: var(--light);">
            <th style="padding: 1rem; text-align: left;">Student</th>
            <th style="padding: 1rem;">Course</th>
            <th style="padding: 1rem;">Amount</th>
            <th style="padding: 1rem;">Reference</th>
            <th style="padding: 1rem;">Proof</th>
            <th style="padding: 1rem;">Status</th>
            <th style="padding: 1rem;">Date</th>
            <th style="padding: 1rem;">Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($payments as $p): ?>
        <tr style="border-top: 1px solid var(--border);">
            <td style="padding: 1rem;"><?= e($p['first_name'] . ' ' . $p['last_name']) ?><br><small><?= e($p['email']) ?></small></td>
            <td style="padding: 1rem;"><?= e($p['course_title']) ?></td>
            <td style="padding: 1rem;"><?= number_format($p['amount'], 3) ?> OMR</td>
            <td style="padding: 1rem;"><code><?= e($p['transaction_id']) ?></code></td>
            <td style="padding: 1rem;">
                
                <?php if (!empty($p['proof_file'])): ?>
                    <a href="<?= url($p['proof_file']) ?> " target="_blank" class="btn btn-secondary" style="padding: 0.35rem 0.75rem; font-size: 0.10 rem;color: #087eb0; font-weight: bold;">View Proof</a>
                <?php else: ?>
                    <span style="color: #dc3545; font-weight: bold;">No file</span>
                <?php endif; ?>
            </td>
            <td style="padding: 1rem;"><?= e($p['status']) ?></td>
            <td style="padding: 1rem;"><?= date('Y-m-d H:i', strtotime($p['created_at'])) ?></td>
            <td style="padding: 1rem;">
                <?php if ($p['status'] === 'pending'): ?>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="payment_id" value="<?= $p['id'] ?>">
                    <button type="submit" name="verify" class="btn btn-primary" style="padding: 0.35rem 0.75rem;">Verify & Enroll</button>
                </form>
                <?php endif ?>
            </td>
        </tr>
        <?php endforeach ?>
    </tbody>
</table>

<?php if (empty($payments)): ?>
    <p class="text-muted">No payments found.</p>
<?php endif ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
