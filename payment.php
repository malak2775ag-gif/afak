<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

session_start();
requireStudent();

$courseId = (int)($_GET['id'] ?? 0);
if (!$courseId) {
    header('Location: ' . url('courses.php'));
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM courses WHERE id = ? AND status = 'published' AND is_free = 0");
$stmt->execute([$courseId]);
$course = $stmt->fetch();

if (!$course) {
    header('Location: ' . url('courses.php'));
    exit;
}

// Check if student has already paid or has a pending payment
$stmt = $pdo->prepare("SELECT status FROM payments WHERE student_id = ? AND course_id = ? ORDER BY id DESC LIMIT 1");
$stmt->execute([$_SESSION['user_id'], $courseId]);
$existingPayment = $stmt->fetch();

if ($existingPayment) {
    if ($existingPayment['status'] === 'completed') {
        header('Location: ' . url('course-detail.php?id=' . $courseId));
    } else {
        flash('info', 'Your payment is currently being reviewed. Please wait for administrator approval.');
        header('Location: ' . url('course-detail.php?id=' . $courseId));
    }
    exit;
}

$pageTitle = 'Payment';
$flash = getFlash();

// Handle payment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reference = trim($_POST['transaction_ref'] ?? '');
    $proof_path = null;
    
    if (empty($reference)) {
        flash('danger', 'Please enter your transaction reference number.');
    } elseif (!isset($_FILES['payment_proof']) || $_FILES['payment_proof']['error'] !== UPLOAD_ERR_OK) {
        flash('danger', 'Please upload a screenshot or proof of payment.');
    } else {
        // Handle File Upload
        $file = $_FILES['payment_proof'];
        $uploaded = afak_upload_file($file, 'payments');
        if ($uploaded) {
            $proof_path = $uploaded;
            
            // Store with 'pending' status. Note: Table should have 'proof_file' column.
            $pdo->prepare("INSERT INTO payments (student_id, course_id, amount, status, transaction_id, payment_method, proof_file) VALUES (?, ?, ?, 'pending', ?, 'Bank Transfer', ?)")
                ->execute([$_SESSION['user_id'], $courseId, $course['price'], $reference, $proof_path]);

            flash('success', 'Payment proof submitted. Reference: ' . e($reference) . '. An administrator will verify the transfer and unlock the course.');
            header('Location: ' . url('course-detail.php?id=' . $courseId));
            exit;
        } else {
            flash('danger', 'Failed to upload payment proof.');
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="form-card" style="max-width: 500px;">
    <h2 style="margin-bottom: 1.5rem;">Enroll in: <?= e($course['title']) ?></h2>
    <p style="font-size: 1.3rem; color: var(--primary); margin-bottom: 1.5rem;">Total Amount: <strong><?= number_format($course['price'], 3) ?> OMR</strong></p>

    <div style="background: #f8f9fa; padding: 1.25rem; border-radius: 10px; border-left: 5px solid var(--primary); margin-bottom: 1.5rem;">
        <h4 style="margin-top: 0; color: var(--primary);">Bank Transfer Details</h4>
        <p style="margin-bottom: 0.5rem; font-size: 0.95rem;">Please transfer the exact amount to the following account:</p>
        <div style="font-size: 0.9rem; line-height: 1.6;">
            <strong>Bank Name:</strong> Bank Muscat<br>
            <strong>Account Name:</strong> AFAK Learning Platform<br>
            <strong>Account Number:</strong> 0123-4567-8901-2345<br>
            <strong>Branch:</strong> Muscat HQ
        </div>
    </div>

    <form method="POST" enctype="multipart/form-data">
        <div class="form-group">
            <label>Transaction Reference / ID</label>
            <input type="text" name="transaction_ref" class="form-control" placeholder="Enter the reference number from your bank" required>
        </div>
        <div class="form-group">
            <label>Upload Payment Proof (Screenshot/PDF)</label>
            <input type="file" name="payment_proof" class="form-control" accept="image/*,.pdf" required>
            <small class="text-muted">Upload a clear image or PDF of your transfer confirmation.</small>
        </div>
        <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 0.5rem;">Submit Payment Confirmation</button>
    </form>
    <p class="mt-2"><a href="<?= url('course-detail.php?id=' . $courseId) ?>">Cancel</a></p>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
