<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

session_start();
requireStudent();

$courseId = (int)($_GET['id'] ?? 0);
if (!$courseId) {
    header('Location: ' . url('dashboard.php'));
    exit;
}

$stmt = $pdo->prepare("SELECT e.id as enrollment_id, e.progress_percent, e.student_id FROM enrollments e WHERE e.student_id = ? AND e.course_id = ? AND e.status IN ('active', 'completed')");
$stmt->execute([$_SESSION['user_id'], $courseId]);
$enrollment = $stmt->fetch();

if (!$enrollment || $enrollment['progress_percent'] < 100) {
    flash('danger', 'Complete the course (100%) to request a certificate.');
    header('Location: ' . url('course-view.php?id=' . $courseId));
    exit;
}

// Check if certificate already exists
$stmt = $pdo->prepare("SELECT id, status FROM certificates WHERE enrollment_id = ?");
$stmt->execute([$enrollment['enrollment_id']]);
$existing = $stmt->fetch();
if ($existing) {
    flash('info', 'Certificate request already exists. Current status: ' . $existing['status']);
    header('Location: ' . url('my-certificates.php'));
    exit;
}

$pdo->beginTransaction();

$certCode = 'AFAK-' . strtoupper(bin2hex(random_bytes(4)));
$pdo->prepare("INSERT INTO certificates (enrollment_id, student_id, course_id, certificate_code, status) VALUES (?, ?, ?, ?, 'pending_instructor')")
    ->execute([$enrollment['enrollment_id'], $_SESSION['user_id'], $courseId, $certCode]);

// Notify course instructor that certificate is waiting for approval
$inst = $pdo->prepare("SELECT instructor_id, title FROM courses WHERE id = ?");
$inst->execute([$courseId]);
$course = $inst->fetch();

if ($course && !empty($course['instructor_id'])) {
    createNotification(
        $pdo,
        (int) $course['instructor_id'],
        'certificate',
        'Certificate Approval Needed',
        'A student completed "' . $course['title'] . '" and requested a certificate approval.',
        'course',
        (int) $courseId,
        url('instructor/certificates.php')
    );
}

$pdo->commit();

flash('success', 'Certificate requested successfully. It will be reviewed by your instructor, then admin.');
header('Location: ' . url('my-certificates.php'));
exit;
